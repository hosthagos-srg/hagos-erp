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

        // Bibit terpakai BULAN INI (agregat: pesanan diproses × ml resep utama). Sama basis dgn
        // laporan Bibit Terpakai. Catatan: pakai resep bibit-tunggal; komposisi mix belum termasuk.
        $awalBln = now()->startOfMonth()->toDateString();
        $akhirBln = now()->endOfMonth()->toDateString();
        $bibitTerpakai = DB::table('penjualan_details as d')
            ->join('penjualan_headers as h', 'h.internal_id', '=', 'd.internal_id')
            ->join('master_reseps as r', 'r.sku_id', '=', 'd.sku_id')
            ->join('master_bibits as b', 'b.bibit_id', '=', 'r.bibit_id')
            ->whereNotIn('h.status_pesanan', ['Menunggu', 'Batal'])
            ->whereBetween('h.tgl_pesanan', [$awalBln, $akhirBln])
            ->groupBy('b.bibit_id', 'b.nama_bibit', 'b.harga_per_ml')
            ->selectRaw('b.nama_bibit, b.harga_per_ml, SUM(d.qty) as total_qty, SUM(r.ml_bibit_utama * d.qty) as total_ml')
            ->orderByDesc(DB::raw('SUM(r.ml_bibit_utama * d.qty)'))
            ->get()
            ->map(function ($r) { $r->nilai = (float) $r->total_ml * (float) $r->harga_per_ml; return $r; });
        $bulanID = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $bulanLabel = $bulanID[(int) now()->month] . ' ' . now()->year;

        return view('stok.index', compact(
            'bibits', 'komponens', 'nilaiBibit', 'nilaiKomponen',
            'bibitWarning', 'komponenWarning', 'stokTesterJadi', 'admins', 'alasanList', 'riwayat',
            'bibitTerpakai', 'bulanLabel'
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
