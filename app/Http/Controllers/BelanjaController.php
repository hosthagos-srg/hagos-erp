<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\BelanjaHeader;
use App\Models\BelanjaDetail;
use App\Models\MasterBibit;
use App\Models\MasterKomponen;
use App\Models\MasterAkunKas;

class BelanjaController extends Controller
{
    public function index(Request $request)
    {
        $query = BelanjaHeader::withCount('details')->with('details')
            ->orderBy('tgl_belanja', 'desc')->orderBy('created_at', 'desc');
        if ($request->filled('jenis')) {
            $query->where('jenis', $request->jenis);
        }
        if ($request->filled('status')) {
            $query->where('status_belanja', $request->status);
        }

        // Hitung jumlah per status (mengikuti filter jenis, tanpa filter status)
        $statusCountQuery = BelanjaHeader::query();
        if ($request->filled('jenis')) {
            $statusCountQuery->where('jenis', $request->jenis);
        }
        $statusCounts = $statusCountQuery->selectRaw('status_belanja, COUNT(*) as n')
            ->groupBy('status_belanja')->pluck('n', 'status_belanja');

        // Nama item untuk tampilan detail
        $namaBibit = MasterBibit::pluck('nama_bibit', 'bibit_id');
        $namaKomponen = MasterKomponen::pluck('nama_komponen', 'komponen_id');
        // Satuan asli komponen (absolute=ml, botol/box=pcs, dll) untuk label qty riwayat
        $satuanKomponen = MasterKomponen::pluck('satuan', 'komponen_id');

        $belanjas = $query->paginate(30)->withQueryString();
        return view('belanja.index', compact('belanjas', 'statusCounts', 'namaBibit', 'namaKomponen', 'satuanKomponen'));
    }

    public function create()
    {
        $bibits = MasterBibit::orderBy('nama_bibit')->get(['bibit_id', 'nama_bibit', 'harga_per_ml']);
        $komponens = MasterKomponen::orderBy('nama_komponen')->get(['komponen_id', 'nama_komponen', 'harga_satuan', 'satuan']);
        $akuns = MasterAkunKas::orderBy('nama_akun')->pluck('nama_akun');
        return view('belanja.create', compact('bibits', 'komponens', 'akuns'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'jenis'           => 'required|in:bibit,komponen',
            'tgl_belanja'     => 'required|date',
            'supplier_toko'   => 'nullable|string',
            'platform_beli'   => 'nullable|string',
            'status_belanja'  => 'required|in:Dipesan,Dikirim,Diterima,Dibatalkan',
            'no_resi'         => 'nullable|string',
            'kurir'           => 'nullable|string',
            'voucher_nominal' => 'nullable|numeric|min:0',
            'ongkir_net'      => 'nullable|numeric',
            'biaya_layanan'   => 'nullable|numeric',
            'akun_bayar'      => 'nullable|string',
            'belanja_id'      => 'nullable|string',
            'items'                    => 'required|array|min:1',
            'items.*.item_id'          => 'required|string',
            'items.*.qty'              => 'required|numeric|min:0.01',
            'items.*.harga_total_item' => 'required|numeric|min:0',
        ]);

        try {
            $belanjaId = DB::transaction(function () use ($request) {
                $jenis = $request->jenis;

                // Subtotal & total bayar
                $subtotal = collect($request->items)->sum(fn($i) => (float) $i['harga_total_item']);
                $voucher  = (float) ($request->voucher_nominal ?? 0);
                $ongkir   = (float) ($request->ongkir_net ?? 0);
                $layanan  = (float) ($request->biaya_layanan ?? 0);
                $totalBayar = $subtotal - $voucher + $ongkir + $layanan;

                // Faktor alokasi (voucher+ongkir+biaya layanan dibagi proporsional per nilai item)
                $faktor = $subtotal > 0 ? $totalBayar / $subtotal : 1;

                // belanja_id: pakai input (mis. no pesanan Shopee), atau auto
                $belanjaId = $request->belanja_id;
                if (!$belanjaId) {
                    $prefix = $jenis === 'bibit' ? 'BLB' : 'KMB';
                    $n = BelanjaHeader::where('jenis', $jenis)->count() + 1;
                    $belanjaId = $prefix . '-' . str_pad($n, 4, '0', STR_PAD_LEFT);
                }

                $header = BelanjaHeader::create([
                    'belanja_id'      => $belanjaId,
                    'jenis'           => $jenis,
                    'tgl_belanja'     => $request->tgl_belanja,
                    'supplier_toko'   => $request->supplier_toko,
                    'platform_beli'   => $request->platform_beli,
                    'status_belanja'  => $request->status_belanja,
                    'no_resi'         => $request->no_resi,
                    'kurir'           => $request->kurir,
                    'subtotal_kotor'  => $subtotal,
                    'voucher_nominal' => $voucher,
                    'ongkir_net'      => $ongkir,
                    'biaya_layanan'   => $layanan,
                    'total_bayar'     => $totalBayar,
                    'akun_bayar'      => $request->akun_bayar,
                ]);

                $tgl = \Illuminate\Support\Carbon::parse($request->tgl_belanja)->format('Ymd');
                $seqByItem = []; // item_id => nomor urut terakhir untuk tanggal ini
                foreach ($request->items as $item) {
                    $itemId = $item['item_id'];
                    // Mulai dari jumlah batch yang sudah ada untuk item+tanggal ini (lanjut, bukan reset ke 01)
                    if (!isset($seqByItem[$itemId])) {
                        $seqByItem[$itemId] = BelanjaDetail::where('item_id', $itemId)
                            ->where('batch_id', 'like', $itemId . '-' . $tgl . '-%')
                            ->count();
                    }
                    $seqByItem[$itemId]++;

                    $qty = (float) $item['qty'];
                    $hargaTotal = (float) $item['harga_total_item'];
                    $netValue = $hargaTotal * $faktor;            // setelah alokasi
                    $netPerUnit = $qty > 0 ? $netValue / $qty : 0;

                    BelanjaDetail::create([
                        'batch_id'           => $itemId . '-' . $tgl . '-' . str_pad($seqByItem[$itemId], 2, '0', STR_PAD_LEFT),
                        'belanja_id'         => $belanjaId,
                        'item_id'            => $item['item_id'],
                        'qty'                => $qty,
                        'harga_total_item'   => $hargaTotal,
                        'harga_net_per_unit' => round($netPerUnit, 4),
                        'stok_sisa'          => 0,
                    ]);
                }

                // Uang KELUAR saat belanja dibuat (sudah bayar pas pesan), kecuali langsung Dibatalkan
                if ($request->akun_bayar && $header->status_belanja !== 'Dibatalkan' && $totalBayar > 0) {
                    \App\Models\MutasiKas::catat($request->akun_bayar, 'keluar', $totalBayar, 'belanja_' . $jenis, $belanjaId, 'Belanja ' . $jenis . ' ' . $belanjaId);
                }

                // Jika sudah Diterima saat input -> langsung naikkan stok + weighted average
                if ($header->status_belanja === 'Diterima') {
                    $this->terapkanStok($header->fresh('details'));
                }

                return $belanjaId;
            });

            return redirect()->route('belanja.index')->with('success', "Belanja $belanjaId tersimpan.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan belanja: ' . $e->getMessage())->withInput();
        }
    }

    /** Ubah status; 'Dikirim' simpan no resi (untuk tracking); 'Diterima' -> stok naik + weighted average. */
    public function updateStatus(Request $request, $belanja_id)
    {
        $request->validate([
            'status_belanja' => 'required|in:Dipesan,Dikirim,Diterima,Dibatalkan',
            'no_resi'        => 'nullable|string',
            'kurir'          => 'nullable|string',
        ]);
        $header = BelanjaHeader::with('details')->findOrFail($belanja_id);
        $status = $request->status_belanja;

        if ($header->stok_diterapkan) {
            return redirect()->back()->with('error', 'Belanja ini sudah Diterima (stok masuk) — status tidak bisa diubah lagi.');
        }

        try {
            DB::transaction(function () use ($request, $header, $status) {
                $header->status_belanja = $status;
                if ($request->filled('no_resi')) $header->no_resi = $request->no_resi;
                if ($request->filled('kurir'))   $header->kurir = $request->kurir;
                $header->save();

                if ($status === 'Diterima') {
                    $this->terapkanStok($header);
                }

                // Dibatalkan -> uang KEMBALI ke akun (sekali), jika sebelumnya sudah dipotong
                if ($status === 'Dibatalkan') {
                    $adaKeluar = \App\Models\MutasiKas::where('ref_id', $header->belanja_id)->where('tipe', 'keluar')->exists();
                    $sudahRefund = \App\Models\MutasiKas::where('ref_id', $header->belanja_id)->where('kategori', 'batal_belanja')->exists();
                    if ($adaKeluar && !$sudahRefund && $header->akun_bayar) {
                        \App\Models\MutasiKas::catat($header->akun_bayar, 'masuk', (float) $header->total_bayar, 'batal_belanja', $header->belanja_id, 'Refund batal belanja ' . $header->belanja_id);
                    }
                }
            });
            $msg = match ($status) {
                'Dikirim'    => 'Belanja ditandai DIKIRIM' . ($request->filled('no_resi') ? ' (resi: ' . $request->no_resi . ')' : '') . '.',
                'Diterima'   => 'Belanja DITERIMA. Stok & harga rata-rata diperbarui.',
                'Dibatalkan' => 'Belanja dibatalkan.',
                default      => "Status diubah menjadi $status.",
            };
            return redirect()->back()->with('success', $msg);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    /** Naikkan stok + weighted-average HPP untuk semua item (sekali, anti dobel). */
    private function terapkanStok(BelanjaHeader $header): void
    {
        if ($header->stok_diterapkan) return;

        foreach ($header->details as $d) {
            if ($header->jenis === 'bibit') {
                $bibit = MasterBibit::where('bibit_id', $d->item_id)->first();
                if (!$bibit) continue;
                $oldStok = (float) $bibit->stok_ml;
                $oldHarga = (float) $bibit->harga_per_ml;
                $newStok = $oldStok + (float) $d->qty;
                $bibit->harga_per_ml = $newStok > 0
                    ? round((($oldStok * $oldHarga) + ((float) $d->qty * (float) $d->harga_net_per_unit)) / $newStok, 2)
                    : (float) $d->harga_net_per_unit;
                $bibit->stok_ml = $newStok;
                $bibit->save();
            } else {
                $komp = MasterKomponen::where('komponen_id', $d->item_id)->first();
                if (!$komp) continue;
                $oldStok = (float) $komp->stok;
                $oldHarga = (float) $komp->harga_satuan;
                $newStok = $oldStok + (float) $d->qty;
                $komp->harga_satuan = $newStok > 0
                    ? round((($oldStok * $oldHarga) + ((float) $d->qty * (float) $d->harga_net_per_unit)) / $newStok, 2)
                    : (float) $d->harga_net_per_unit;
                $komp->stok = $newStok;
                $komp->save();
            }
            $d->stok_sisa = (float) $d->qty;
            $d->save();
        }

        $header->stok_diterapkan = true;
        $header->status_belanja = 'Diterima';
        $header->save();
    }
}
