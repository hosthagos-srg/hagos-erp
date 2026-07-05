<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\MasterBibit;
use App\Models\MasterKomponen;
use App\Models\MasterKategori;
use App\Models\KoreksiStok;

class StokController extends Controller
{
    public function index()
    {
        // Default urut per NAMA (abjad) — lebih mudah dibaca saat opname.
        $bibits = MasterBibit::orderBy('nama_bibit')->get();
        // Sembunyikan komponen yang TIDAK dilacak stoknya (gaji packing, kartu ucapan, shrink,
        // sticker tester/utama, dll) — bukan barang inventori, tak perlu tampil di daftar stok.
        $komponens = MasterKomponen::orderBy('nama_komponen')
            ->whereRaw("LOWER(COALESCE(track_stok, 'ya')) <> 'tidak'")
            ->get();

        // Nilai inventory
        $nilaiBibit = $bibits->sum(fn($b) => (float) $b->stok_ml * (float) $b->harga_per_ml);
        $nilaiKomponen = $komponens->sum(fn($k) => (float) $k->stok * (float) $k->harga_satuan);

        $bibitWarning = $bibits->filter(fn($b) => (float) $b->stok_ml <= (float) $b->threshold_ml)->count();

        // Stok tester jadi (botol tester siap pakai) = komponen KMP-TSTR
        $testerJadi = $komponens->firstWhere('komponen_id', 'KMP-TSTR');
        $stokTesterJadi = $testerJadi ? (float) $testerJadi->stok : 0;

        // Komponen perlu beli (threshold > 0 & stok <= threshold)
        $komponenWarning = $komponens->filter(fn($k) => (float) $k->threshold > 0 && (float) $k->stok <= (float) $k->threshold)->count();

        $admins = MasterKategori::where('tipe_kategori', 'Admin')->orderBy('nilai')->pluck('nilai');
        $alasanList = ['Opname', 'Tumpah / Susut', 'Timbangan', 'Lainnya'];

        // Riwayat koreksi terbaru
        $riwayat = KoreksiStok::orderBy('tanggal', 'desc')->orderBy('created_at', 'desc')->limit(30)->get();

        // Log aktivitas bibit: 200 racik TERAKHIR, 50 per halaman. Bibit terpakai tiap racik
        // diturunkan dari resep (dukung mix via resep_blend per-detail & master_resep_bibit).
        $logIds = \App\Models\ProduksiLog::orderByDesc('id')->limit(200)->pluck('id');
        $racikLog = \App\Models\ProduksiLog::whereIn('id', $logIds)
            ->orderByDesc('tgl_racik')->orderByDesc('id')
            ->paginate(50, ['*'], 'rlog')->appends(['tab' => 'terpakai']);

        $hppSvc = app(\App\Services\HppService::class);
        $racikLog->getCollection()->transform(function ($log) use ($hppSvc) {
            $dt = is_array($log->detail_text) ? $log->detail_text : (json_decode($log->detail_text ?? '', true) ?: []);
            $tipe = (string) ($log->tipe ?? '');
            $out = collect();

            if ($tipe === 'Tester') {
                // detail_text = [{bibit_id, nama_bibit, qty}, ...]; tiap botol tester pakai 1,5 ml bibit.
                foreach ($dt as $e) {
                    if (!is_array($e) || empty($e['bibit_id'])) continue;
                    $qb = (int) ($e['qty'] ?? 0);
                    if ($qb <= 0) continue;
                    $out->push(['label' => $e['nama_bibit'] ?? $e['bibit_id'], 'ml' => 1.5 * $qb]);
                }
            } elseif (str_starts_with($tipe, 'Racik Pesanan')) {
                // Produk: bibit dari resep (dukung mix via resep_blend per-detail).
                $sku = $dt['sku_id'] ?? null;
                $qty = max(1, (int) ($dt['qty'] ?? 0));
                $blend = null;
                if ($sku && !empty($dt['internal_id'])) {
                    $rb = DB::table('penjualan_details')->where('internal_id', $dt['internal_id'])
                        ->where('sku_id', $sku)->value('resep_blend');
                    $blend = $rb ? json_decode($rb, true) : null;
                }
                $comps = $sku ? $hppSvc->resolveBibitComponents($sku, is_array($blend) ? $blend : null) : [];
                $out = collect($comps)
                    ->filter(fn($c) => !empty($c['bibit_id']) && (float) $c['ml'] > 0)
                    ->map(fn($c) => ['label' => $c['label'], 'ml' => (float) $c['ml'] * $qty]);
            }
            // 'Absolute' → tidak memakai bibit (out tetap kosong → tampil "—").
            $log->bibit_pakai = $out->values();
            return $log;
        });

        return view('stok.index', compact(
            'bibits', 'komponens', 'nilaiBibit', 'nilaiKomponen',
            'bibitWarning', 'komponenWarning', 'stokTesterJadi', 'admins', 'alasanList', 'riwayat',
            'racikLog'
        ));
    }

    public function koreksi(Request $request)
    {
        $request->validate([
            'item_type'  => 'required|in:bibit,komponen',
            'item_id'    => 'required|string',
            'stok_fisik' => 'required|numeric|min:0',
            'alasan'     => 'required|string|max:100',
            'dicatat_oleh' => 'nullable|string|max:100',
        ]);

        $type = $request->item_type;
        $stokFisik = (float) $request->stok_fisik;

        DB::transaction(function () use ($request, $type, $stokFisik) {
            if ($type === 'bibit') {
                $item = MasterBibit::where('bibit_id', $request->item_id)->firstOrFail();
                $stokSistem = (float) $item->stok_ml;
                $nama = $item->nama_bibit;
            } else {
                $item = MasterKomponen::where('komponen_id', $request->item_id)->firstOrFail();
                $stokSistem = (float) $item->stok;
                $nama = $item->nama_komponen;
            }

            KoreksiStok::create([
                'tanggal'     => now()->toDateString(),
                'item_type'   => $type,
                'item_id'     => $request->item_id,
                'nama_item'   => $nama,
                'stok_sistem' => $stokSistem,
                'stok_fisik'  => $stokFisik,
                'selisih'     => $stokFisik - $stokSistem,
                'alasan'      => $request->alasan,
                'dicatat_oleh' => $request->dicatat_oleh,
            ]);

            if ($type === 'bibit') {
                $item->stok_ml = $stokFisik;
            } else {
                $item->stok = $stokFisik;
            }
            $item->save();
        });

        return redirect()->route('stok.index', ['tab' => $type === 'komponen' ? 'komponen' : 'bibit'])
            ->with('success', "Stok berhasil dikoreksi ke {$stokFisik}.");
    }

    /** Edit detail bibit (threshold, nama, merek, nama asli, harga/ml, status). Stok TIDAK diubah di sini. */
    public function updateBibit(Request $request, string $bibit)
    {
        $item = MasterBibit::where('bibit_id', $bibit)->firstOrFail();
        $request->validate([
            'nama_bibit'   => 'required|string|max:255',
            'merek_bibit'  => 'nullable|string|max:255',
            'nama_asli'    => 'nullable|string|max:255',
            'harga_per_ml' => 'required|numeric|min:0',
            'threshold_ml' => 'nullable|numeric|min:0',
            'status'       => 'nullable|in:Aktif,Nonaktif',
        ]);

        $item->update($request->only('nama_bibit', 'merek_bibit', 'nama_asli', 'harga_per_ml', 'threshold_ml', 'status'));

        return redirect()->route('stok.index')->with('success', "Detail bibit {$item->nama_bibit} diperbarui.");
    }

    /** Edit detail komponen (threshold, nama, harga, satuan). */
    public function updateKomponen(Request $request, string $komponen)
    {
        $item = MasterKomponen::where('komponen_id', $komponen)->firstOrFail();
        $request->validate([
            'nama_komponen' => 'required|string|max:255',
            'harga_satuan'  => 'required|numeric|min:0',
            'satuan'        => 'nullable|string|max:50',
            'threshold'     => 'nullable|numeric|min:0',
        ]);

        $item->update($request->only('nama_komponen', 'harga_satuan', 'satuan', 'threshold'));

        return redirect()->route('stok.index', ['tab' => 'komponen'])->with('success', "Detail komponen {$item->nama_komponen} diperbarui.");
    }
}
