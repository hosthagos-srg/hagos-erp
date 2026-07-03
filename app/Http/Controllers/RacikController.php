<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PenjualanHeader;
use App\Models\PenjualanDetail;
use App\Models\MasterProduk;
use App\Models\MasterResep;
use App\Models\MasterBibit;
use App\Models\MasterKomponen;
use App\Services\HppService;
use App\Services\RacikService;
use Illuminate\Support\Facades\DB;

class RacikController extends Controller
{
    public function __construct(private HppService $hpp, private RacikService $racikService) {}

    public function index()
    {
        // Ambil semua detail yang headernya status = 'Menunggu'
        // Paginasi 50/halaman: form tidak melebihi PHP max_input_vars (cegah POST terpotong saat racik massal).
        $antrean = PenjualanDetail::select('penjualan_details.*', 'penjualan_headers.channel', 'penjualan_headers.tgl_pesanan', 'penjualan_headers.status_pesanan', 'penjualan_headers.nama_pembeli', 'penjualan_headers.external_order_id')
            ->join('penjualan_headers', 'penjualan_details.internal_id', '=', 'penjualan_headers.internal_id')
            ->where('penjualan_headers.status_pesanan', 'Menunggu')
            ->orderBy('penjualan_headers.tgl_pesanan')
            ->orderBy('penjualan_details.created_at')
            ->paginate(50);

        $produks = MasterProduk::all();
        $admins = \App\Models\MasterKategori::where('tipe_kategori', 'Admin')->orderBy('nilai')->pluck('nilai');

        return view('racik.index', compact('antrean', 'produks', 'admins'));
    }

    /** Riwayat peracikan: pesanan yang sudah diracik (tgl_racik terisi). */
    public function riwayat(Request $request)
    {
        $query = PenjualanDetail::select(
                'penjualan_details.*',
                'penjualan_headers.channel', 'penjualan_headers.tgl_pesanan', 'penjualan_headers.tgl_racik',
                'penjualan_headers.diracik_oleh', 'penjualan_headers.status_pesanan',
                'penjualan_headers.nama_pembeli', 'penjualan_headers.external_order_id', 'penjualan_headers.internal_id as h_internal',
                'master_produks.nama_produk', 'master_produks.ukuran_ml'
            )
            ->join('penjualan_headers', 'penjualan_details.internal_id', '=', 'penjualan_headers.internal_id')
            ->leftJoin('master_produks', 'penjualan_details.sku_id', '=', 'master_produks.sku_id')
            ->whereNotNull('penjualan_headers.tgl_racik')
            ->orderBy('penjualan_headers.tgl_racik', 'desc')
            ->orderBy('penjualan_details.created_at', 'desc');

        if ($request->filled('dari'))         $query->whereDate('penjualan_headers.tgl_racik', '>=', $request->dari);
        if ($request->filled('sampai'))       $query->whereDate('penjualan_headers.tgl_racik', '<=', $request->sampai);
        if ($request->filled('diracik_oleh')) $query->where('penjualan_headers.diracik_oleh', $request->diracik_oleh);

        $riwayat = $query->paginate(50)->withQueryString();

        // Ringkasan (ikut filter) — total pcs diracik & per peracik
        $sumQ = PenjualanDetail::join('penjualan_headers', 'penjualan_details.internal_id', '=', 'penjualan_headers.internal_id')
            ->whereNotNull('penjualan_headers.tgl_racik');
        if ($request->filled('dari'))         $sumQ->whereDate('penjualan_headers.tgl_racik', '>=', $request->dari);
        if ($request->filled('sampai'))       $sumQ->whereDate('penjualan_headers.tgl_racik', '<=', $request->sampai);
        if ($request->filled('diracik_oleh')) $sumQ->where('penjualan_headers.diracik_oleh', $request->diracik_oleh);
        $totalPcs = (int) (clone $sumQ)->sum('penjualan_details.qty');
        $perPeracik = (clone $sumQ)->selectRaw('penjualan_headers.diracik_oleh as oleh, SUM(penjualan_details.qty) as pcs')
            ->groupBy('oleh')->orderByDesc('pcs')->get();

        $admins = \App\Models\MasterKategori::where('tipe_kategori', 'Admin')->orderBy('nilai')->pluck('nilai');

        return view('racik.riwayat', compact('riwayat', 'totalPcs', 'perPeracik', 'admins'));
    }

    /**
     * Parser angka untuk INPUT dari form (bisa "122,5" atau "122.5"). Kolom DB sudah numerik.
     */
    private function parseNumber($str) {
        if ($str === null || $str === '') return 0;
        $str = (string) $str;
        $str = preg_replace('/[^0-9.,\-]/', '', $str);
        $lastDot = strrpos($str, '.');
        $lastComma = strrpos($str, ',');
        if ($lastDot !== false && $lastComma !== false) {
            if ($lastDot > $lastComma) { $str = str_replace(',', '', $str); }
            else { $str = str_replace('.', '', $str); $str = str_replace(',', '.', $str); }
        } elseif ($lastComma !== false) {
            $str = str_replace(',', '.', $str);
        }
        return (float) $str;
    }

    public function process(Request $request)
    {
        $actions = $request->input('actions', []); // [detail_id => ['aksi' => 'racik', 'sku_id' => '...', 'diracik_oleh' => '...']]

        try {
            DB::transaction(function () use ($actions) {
                foreach ($actions as $detailId => $data) {
                    $aksi = $data['aksi'];
                    if ($aksi === 'skip') continue; // Diabaikan dari bulk action

                    // lockForUpdate: kunci baris agar 2 admin memproses detail yg sama tidak balapan
                    // (transaksi kedua menunggu, lalu guard HPP di RacikService melewati yg sudah diracik).
                    $detail = PenjualanDetail::lockForUpdate()->findOrFail($detailId);
                    $header = PenjualanHeader::where('internal_id', $detail->internal_id)->first();
                    
                    if ($aksi === 'batal') {
                        $header->status_pesanan = 'Batal';
                        $header->save();
                        continue;
                    }

                    // Tukar aroma (mis. bibit kosong): ganti SKU, catat aroma asal, harga TETAP (dari pesanan asli)
                    $skuId = $data['sku_id'] ?? $detail->sku_id;
                    if ($skuId !== $detail->sku_id) {
                        if (is_null($detail->sku_id_asli)) {
                            $detail->sku_id_asli = $detail->sku_id; // catat aroma asal (sekali)
                        }
                        $detail->sku_id = $skuId;
                        $detail->flag_swap = 1;
                        // harga_satuan TIDAK diubah -> tetap harga yang dibayar pembeli; HPP ikut aroma baru saat racik
                    }

                    $channel = $header->channel;
                    $qty = $detail->qty;

                    // Set peracik lebih dulu (bila dipilih) agar tercatat benar di log racik.
                    if (!empty($data['diracik_oleh'])) {
                        $header->diracik_oleh = $data['diracik_oleh'];
                    }

                    if ($aksi === 't11') {
                        // Penuhi dari stok produk jadi (T11). Pastikan cukup, lalu pakai service
                        // (RacikService otomatis pakai T11 dulu; karena stok cukup, semua dari T11 → HPP = botol telanjang + packaging + tester/L2).
                        $produk = MasterProduk::where('sku_id', $skuId)->first();
                        if (!$produk || $produk->stok_t11 < $qty) {
                            throw new \Exception("Stok T11 tidak mencukupi untuk SKU: " . ($produk->nama_produk ?? $skuId));
                        }
                        $this->racikService->racik($detail, $header);
                    } elseif ($aksi === 'racik') {
                        // Racik 1 detail (potong stok + hitung HPP) lewat service bersama
                        $this->racikService->racik($detail, $header);
                    } elseif ($aksi === 'kirim_golive') {
                        // GO-LIVE: pesanan sudah diracik & dikirim sebelum sistem. Hitung HPP, TANPA potong stok.
                        $this->racikService->tandaiDikirim($detail, $header);
                    }

                    // Simpan perubahan detail (mis. swap aroma); racik sudah save di service
                    $detail->save();

                    // Tandai header 'Selesai Racik' HANYA jika semua detailnya sudah diracik
                    // (hpp_satuan terisi). Mendukung pesanan multi-SKU.
                    if ($aksi !== 'batal') {
                        $pending = PenjualanDetail::where('internal_id', $header->internal_id)
                            ->whereNull('hpp_satuan')->count();
                        if ($pending === 0) {
                            $header->status_pesanan = 'Selesai Racik';
                            $header->tgl_racik = now()->toDateString();
                            if (!empty($data['diracik_oleh'])) {
                                $header->diracik_oleh = $data['diracik_oleh'];
                            }
                            $header->save();
                        }
                    }
                }
            });

            return redirect()->route('racik.index')->with('success', 'Pesanan terpilih berhasil diproses!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memproses racikan: ' . $e->getMessage());
        }
    }

    public function checkStock(Request $request)
    {
        $actions = $request->input('actions', []);
        $usage = [];        // item_id => [type, item_id, nama_bibit, stok_sistem, total_butuh]
        $orderNeeds = [];   // detail_id => [item_id => qty]  (urut FIFO sesuai form)

        foreach ($actions as $detailId => $data) {
            // Hanya 'racik' yang memotong stok bibit/botol baru (go-live/t11/batal/skip tidak).
            if (($data['aksi'] ?? '') !== 'racik') continue;

            $detail = \App\Models\PenjualanDetail::find($detailId);
            if (!$detail) continue;

            $skuId = $data['sku_id'] ?? $detail->sku_id;
            $qty = (int) $detail->qty;

            $produk = \App\Models\MasterProduk::where('sku_id', $skuId)->first();
            $resep = \App\Models\MasterResep::where('sku_id', $skuId)->first();

            $stokT11 = $produk ? (int) $produk->stok_t11 : 0;
            $qtyRacikBaru = $stokT11 >= $qty ? 0 : $qty - $stokT11;
            if ($qtyRacikBaru <= 0) { $orderNeeds[$detailId] = []; continue; } // semua dari T11

            $needs = [];
            // ── Bibit (dukung mix multi-bibit & custom blend per-detail) ──
            $blend = $detail->resep_blend ? json_decode($detail->resep_blend, true) : null;
            foreach ($this->hpp->resolveBibitComponents($skuId, is_array($blend) ? $blend : null) as $c) {
                if (empty($c['bibit_id']) || $c['ml'] <= 0) continue;
                $need = (float) $c['ml'] * $qtyRacikBaru;
                if (!isset($usage[$c['bibit_id']])) {
                    $b = \App\Models\MasterBibit::where('bibit_id', $c['bibit_id'])->first();
                    $usage[$c['bibit_id']] = ['type' => 'bibit', 'item_id' => $c['bibit_id'], 'nama_bibit' => $c['label'], 'stok_sistem' => $b ? (float) $b->stok_ml : 0, 'total_butuh' => 0];
                }
                $usage[$c['bibit_id']]['total_butuh'] += $need;
                $needs[$c['bibit_id']] = ($needs[$c['bibit_id']] ?? 0) + $need;
            }

            // ── Botol (sesuai ukuran produk: KMP-BTL{ukuran}) — 1 botol per unit racik baru ──
            if ($produk && (int) $produk->ukuran_ml > 0) {
                $botolId = 'KMP-BTL' . (int) $produk->ukuran_ml;
                if (!isset($usage[$botolId])) {
                    $k = \App\Models\MasterKomponen::where('komponen_id', $botolId)->first();
                    if ($k) {
                        $usage[$botolId] = ['type' => 'komponen', 'item_id' => $botolId, 'nama_bibit' => $k->nama_komponen, 'stok_sistem' => (float) $k->stok, 'total_butuh' => 0];
                    }
                }
                if (isset($usage[$botolId])) {
                    $usage[$botolId]['total_butuh'] += $qtyRacikBaru;
                    $needs[$botolId] = ($needs[$botolId] ?? 0) + $qtyRacikBaru;
                }
            }
            $orderNeeds[$detailId] = $needs;
        }

        $deficits = [];
        foreach ($usage as $u) {
            if ($u['total_butuh'] > $u['stok_sistem']) $deficits[] = $u;
        }

        if (empty($deficits)) return response()->json(['status' => 'ok']);

        // Greedy FIFO: tentukan pesanan mana yang BISA diproses dgn stok sekarang & mana yg diblokir
        // (untuk fitur "Proses yang stoknya cukup, lewati yang kurang").
        $avail = [];
        foreach ($usage as $u) $avail[$u['item_id']] = $u['stok_sistem'];
        $blocked = [];
        foreach ($orderNeeds as $detailId => $needs) {
            $ok = true;
            foreach ($needs as $itemId => $need) {
                if (($avail[$itemId] ?? PHP_INT_MAX) < $need) { $ok = false; break; }
            }
            if ($ok) { foreach ($needs as $itemId => $need) $avail[$itemId] -= $need; }
            else { $blocked[] = (string) $detailId; }
        }
        $feasible = count($orderNeeds) - count($blocked);

        return response()->json(['status' => 'deficit', 'deficits' => $deficits, 'blocked' => $blocked, 'feasible_count' => $feasible]);
    }

    public function adjustStock(Request $request)
    {
        $adjustments = $request->input('adjustments', []);
        
        \Illuminate\Support\Facades\DB::transaction(function() use ($adjustments) {
            foreach ($adjustments as $adj) {
                $type = $adj['type'] ?? 'bibit';
                $itemId = $adj['item_id'] ?? ($adj['bibit_id'] ?? null);
                if (!$itemId) continue;
                $stokFisik = $this->parseNumber($adj['real_stock'] ?? 0);

                if ($type === 'komponen') {
                    $item = \App\Models\MasterKomponen::where('komponen_id', $itemId)->first();
                    if (!$item) continue;
                    $stokSistem = (float) $item->stok;
                    $nama = $item->nama_komponen;
                } else {
                    $item = \App\Models\MasterBibit::where('bibit_id', $itemId)->first();
                    if (!$item) continue;
                    $stokSistem = (float) $item->stok_ml;
                    $nama = $item->nama_bibit;
                }

                // T12 — catat koreksi (selisih jadi data, bukan kebocoran diam-diam)
                \App\Models\KoreksiStok::create([
                    'tanggal' => now()->toDateString(),
                    'item_type' => $type,
                    'item_id' => $itemId,
                    'nama_item' => $nama,
                    'stok_sistem' => $stokSistem,
                    'stok_fisik' => $stokFisik,
                    'selisih' => $stokFisik - $stokSistem,
                    'alasan' => $adj['alasan'] ?? 'Penyesuaian saat racik',
                    'dicatat_oleh' => $adj['dicatat_oleh'] ?? null,
                ]);

                if ($type === 'komponen') { $item->stok = $stokFisik; }
                else { $item->stok_ml = $stokFisik; }
                $item->save();
            }
        });

        return response()->json(['status' => 'ok']);
    }
}
