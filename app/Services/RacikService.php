<?php

namespace App\Services;

use App\Models\PenjualanDetail;
use App\Models\PenjualanHeader;
use App\Models\MasterProduk;
use App\Models\MasterResep;
use App\Models\MasterBibit;
use App\Models\MasterKomponen;

/**
 * Logika racik 1 baris pesanan (potong stok + hitung HPP). Sumber tunggal —
 * dipakai RacikController (gudang) dan auto-racik pesanan non-marketplace.
 */
class RacikService
{
    public function __construct(private HppService $hpp) {}

    /**
     * Racik satu detail: pakai stok T11 bila ada, racik sisanya, hitung HPP & margin,
     * potong stok bibit + botol tester. TIDAK mengubah status header (diserahkan ke pemanggil).
     */
    public function racik(PenjualanDetail $detail, PenjualanHeader $header): void
    {
        // Guard idempoten: baris yang sudah diracik (HPP terisi) TIDAK diproses ulang.
        // Mencegah stok terpotong dobel bila 2 admin/klik ganda memproses detail yang sama.
        if (!is_null($detail->hpp_satuan)) return;

        $skuId = $detail->sku_id;
        $qty = (int) $detail->qty;
        $channel = $header->channel;

        $produk = MasterProduk::where('sku_id', $skuId)->first();
        $resep = MasterResep::where('sku_id', $skuId)->first();

        // Pakai stok produk jadi (T11) lebih dulu, racik baru hanya sisanya
        $stokT11 = $produk ? (int) $produk->stok_t11 : 0;
        $hppT11Bare = $produk ? (float) $produk->hpp_t11 : 0; // nilai botol telanjang T11
        $qtyRacikBaru = $qty;
        if ($stokT11 > 0) {
            if ($stokT11 >= $qty) {
                $qtyRacikBaru = 0;
                $produk->stok_t11 -= $qty;
            } else {
                $qtyRacikBaru = $qty - $stokT11;
                $produk->stok_t11 = 0;
            }
            $produk->save();
        }
        $qtyT11Used = $qty - $qtyRacikBaru;
        if ($qtyT11Used > 0) {
            \App\Models\StokJadiLog::catat($skuId, 'keluar', $qtyT11Used, 'penjualan', $hppT11Bare, $header->internal_id, $header->diracik_oleh);
        }

        $ekstraTester = $header->ekstra_tester ?? 0;
        $isShipped = is_null($header->metode_pengiriman) ? null : ($header->metode_pengiriman === 'Dikirim');

        // Komposisi bibit custom per-detail (mix pilihan pelanggan), bila ada.
        $blend = $this->parseBlend($detail->resep_blend);

        $bd = $this->hpp->breakdown($skuId, $channel, $qty, $ekstraTester, $isShipped, $blend);

        // HPP total:
        //  - Racik baru: Lapis 1 penuh (botol telanjang baru + packaging).
        //  - Dari T11: HPP botol telanjang tersimpan + packaging (box+kartu) karena dikemas ulang.
        //  - Tester & Lapis 2: untuk seluruh pesanan (semua pcs dikirim tetap dipacking & dapat tester).
        $totalHpp = ($qtyRacikBaru * $bd['lapis1_per_unit'])
                  + ($qtyT11Used * ($hppT11Bare + $bd['packaging_per_unit']))
                  + $bd['tester']['total'] + $bd['lapis2']['total'];
        $avgHpp = $qty > 0 ? $totalHpp / $qty : 0;

        $detail->hpp_satuan = round($avgHpp, 2);
        // Margin: normalnya SEMENTARA (harga kotor) & difinalkan saat settlement. TAPI bila pesanan
        // sudah CAIR sebelum diracik (net_settlement terisi), settlement tak akan jalan lagi — jadi
        // hitung margin FINAL berbasis net di sini (alokasi net proporsional per subtotal baris).
        $netSettle = (float) ($header->net_settlement ?? 0);
        if ($netSettle > 0) {
            $all = PenjualanDetail::where('internal_id', $header->internal_id)->get();
            $sumSub = 0.0;
            foreach ($all as $x) $sumSub += ((float) $x->harga_satuan) * max(1, (int) $x->qty);
            $lineSub = ((float) $detail->harga_satuan) * $qty;
            $share = $sumSub > 0 ? ($lineSub / $sumSub) : (1 / max(1, $all->count()));
            $netPerUnit = ($netSettle * $share) / max(1, $qty);
            $detail->margin_satuan = round($netPerUnit - $avgHpp, 2);
        } else {
            $detail->margin_satuan = round((float) $detail->harga_satuan - $avgHpp, 2);
        }

        // Potong stok bibit (M1) untuk qty racik baru — dukung mix (multi-bibit) & custom blend.
        if ($qtyRacikBaru > 0) {
            foreach ($this->hpp->resolveBibitComponents($skuId, $blend) as $c) {
                if (empty($c['bibit_id']) || $c['ml'] <= 0) continue;
                $bibit = MasterBibit::where('bibit_id', $c['bibit_id'])->first();
                if ($bibit) {
                    $bibit->stok_ml = (float) $bibit->stok_ml - ((float) $c['ml'] * $qtyRacikBaru);
                    $bibit->save();
                }
            }
        }

        // Potong stok botol tester jadi
        $jmlTesterTotal = $bd['tester']['jml'];
        if ($jmlTesterTotal > 0) {
            $this->potongKomponen('KMP-TSTR', $jmlTesterTotal);
        }

        // Potong stok komponen ber-stok (selaras komposisi HPP):
        //  - bare (botol, absolute): hanya untuk qty yang DIRACIK BARU (T11 sudah punya botol telanjang)
        //  - packaging (box): untuk SEMUA qty terjual (ditambah segar saat dikemas/repack)
        $usage = $bd['komponen_usage'] ?? ['bare' => [], 'packaging' => []];
        foreach ($usage['bare'] as $u) {
            $this->potongKomponen($u['id'], $u['qty'] * $qtyRacikBaru);
        }
        foreach ($usage['packaging'] as $u) {
            $this->potongKomponen($u['id'], $u['qty'] * $qty);
        }

        $detail->save();

        // Catat ke log produksi (traceability) → otomatis masuk Log Aktivitas & dashboard.
        $this->catatLogRacik($detail, $header, $produk, 'Racik Pesanan');
    }

    /** Tulis 1 baris log produksi untuk peracikan pesanan (dipakai racik & go-live). */
    private function catatLogRacik(PenjualanDetail $detail, PenjualanHeader $header, ?MasterProduk $produk, string $tipe): void
    {
        $order = $header->external_order_id ?: ('INV-' . strtoupper(substr($header->internal_id, 0, 8)));
        $nama = $produk->nama_produk ?? $detail->sku_id;
        \App\Models\ProduksiLog::create([
            'tgl_racik'    => now()->toDateString(),
            'diracik_oleh' => $header->diracik_oleh ?: (auth()->user()->name ?? 'Sistem'),
            'tipe'         => $tipe,
            'ringkasan'    => $tipe . ': ' . $nama . ' (' . $detail->sku_id . ') × ' . (int) $detail->qty . ' — pesanan ' . $order,
            'detail_text'  => [
                'internal_id' => $header->internal_id,
                'order'       => $order,
                'sku_id'      => $detail->sku_id,
                'qty'         => (int) $detail->qty,
                'hpp_satuan'  => $detail->hpp_satuan,
            ],
        ]);
    }

    /**
     * GO-LIVE: tandai pesanan yang SUDAH diracik & dikirim di dunia nyata (sebelum sistem mulai).
     * Hitung HPP penuh (semua qty dianggap racik baru) & margin sementara, TAPI TIDAK memotong stok
     * apa pun — karena bahannya sudah terpakai sebelum stok awal dihitung. Status diatur pemanggil.
     */
    public function tandaiDikirim(PenjualanDetail $detail, PenjualanHeader $header): void
    {
        // Guard idempoten: jangan hitung ulang bila HPP sudah terisi (sudah diproses).
        if (!is_null($detail->hpp_satuan)) return;

        $skuId = $detail->sku_id;
        $qty = (int) $detail->qty;
        $channel = $header->channel;
        $ekstraTester = $header->ekstra_tester ?? 0;
        $isShipped = is_null($header->metode_pengiriman) ? null : ($header->metode_pengiriman === 'Dikirim');

        $bd = $this->hpp->breakdown($skuId, $channel, $qty, $ekstraTester, $isShipped, $this->parseBlend($detail->resep_blend));

        // HPP penuh "racik baru" untuk semua qty (Lapis 1 + tester + Lapis 2). Tanpa T11, tanpa potong stok.
        $totalHpp = ($qty * $bd['lapis1_per_unit']) + $bd['tester']['total'] + $bd['lapis2']['total'];
        $avgHpp = $qty > 0 ? $totalHpp / $qty : 0;

        $detail->hpp_satuan = round($avgHpp, 2);
        $detail->margin_satuan = round((float) $detail->harga_satuan - $avgHpp, 2);
        $detail->save();

        $produk = MasterProduk::where('sku_id', $skuId)->first();
        $this->catatLogRacik($detail, $header, $produk, 'Racik Pesanan (Go-Live)');
    }

    /** Parse kolom resep_blend (JSON [{bibit_id, ml}, ...]) → array ternormalisasi atau null. */
    private function parseBlend($raw): ?array
    {
        if (empty($raw)) return null;
        $data = is_array($raw) ? $raw : json_decode($raw, true);
        if (!is_array($data) || count($data) === 0) return null;
        $out = [];
        foreach ($data as $c) {
            if (!empty($c['bibit_id'])) {
                $out[] = ['bibit_id' => $c['bibit_id'], 'ml' => (float) ($c['ml'] ?? 0)];
            }
        }
        return count($out) ? $out : null;
    }

    /** Kurangi stok 1 komponen (hanya yang track_stok='Ya'). Aman jika komponen tak ada. */
    private function potongKomponen(string $komponenId, float $jumlah): void
    {
        if ($jumlah <= 0) return;
        $komp = MasterKomponen::where('komponen_id', $komponenId)->first();
        if (!$komp) return;
        if (isset($komp->track_stok) && strtolower((string) $komp->track_stok) === 'tidak') return; // tidak dilacak
        $komp->stok = (float) $komp->stok - $jumlah;
        $komp->save();
    }

    /** Tambah stok 1 komponen (kebalikan potongKomponen) — untuk pembatalan/hapus racik. */
    private function tambahKomponen(string $komponenId, float $jumlah): void
    {
        if ($jumlah <= 0) return;
        $komp = MasterKomponen::where('komponen_id', $komponenId)->first();
        if (!$komp) return;
        if (isset($komp->track_stok) && strtolower((string) $komp->track_stok) === 'tidak') return;
        $komp->stok = (float) $komp->stok + $jumlah;
        $komp->save();
    }

    /**
     * KEBALIKAN racik(): kembalikan semua stok yang terpotong saat meracik pesanan ini
     * (bibit + komponen bare/packaging + tester + botol jadi T11). Dipakai saat HAPUS pesanan
     * agar "seperti tidak pernah diinput". Hanya baris yang sudah diracik (hpp terisi) yang dibalik.
     * qty dari T11 direkonstruksi dari StokJadiLog (tipe 'keluar', sumber 'penjualan').
     */
    public function kembalikanStokRacik(PenjualanHeader $header): void
    {
        $details = PenjualanDetail::where('internal_id', $header->internal_id)->get();
        $isShipped = is_null($header->metode_pengiriman) ? null : ($header->metode_pengiriman === 'Dikirim');

        foreach ($details as $detail) {
            if (is_null($detail->hpp_satuan)) continue; // belum diracik → tak ada yang dipotong
            $qty = (int) $detail->qty;

            // Berapa unit diambil dari stok jadi (T11) saat racik
            $qtyT11 = (int) \App\Models\StokJadiLog::where('ref_id', $header->internal_id)
                ->where('sku_id', $detail->sku_id)->where('tipe', 'keluar')->where('sumber', 'penjualan')->sum('qty');
            $qtyRacikBaru = max(0, $qty - $qtyT11);

            $blend = $this->parseBlend($detail->resep_blend);
            // breakdown TANPA ekstra tester (ekstra dikembalikan sekali per-pesanan di bawah)
            $bd = $this->hpp->breakdown($detail->sku_id, $header->channel, $qty, 0, $isShipped, $blend);

            // 1) Bibit (hanya untuk qty racik baru)
            if ($qtyRacikBaru > 0) {
                foreach ($this->hpp->resolveBibitComponents($detail->sku_id, $blend) as $c) {
                    if (empty($c['bibit_id']) || $c['ml'] <= 0) continue;
                    $b = MasterBibit::where('bibit_id', $c['bibit_id'])->first();
                    if ($b) { $b->stok_ml = (float) $b->stok_ml + ((float) $c['ml'] * $qtyRacikBaru); $b->save(); }
                }
            }

            // 2) Komponen: bare (racik baru) & packaging (semua qty) — selaras dgn potongan di racik()
            $usage = $bd['komponen_usage'] ?? ['bare' => [], 'packaging' => []];
            foreach ($usage['bare'] as $u)      $this->tambahKomponen($u['id'], $u['qty'] * $qtyRacikBaru);
            foreach ($usage['packaging'] as $u) $this->tambahKomponen($u['id'], $u['qty'] * $qty);

            // 3) Tester bawaan (tanpa ekstra)
            if (($bd['tester']['jml'] ?? 0) > 0) $this->tambahKomponen('KMP-TSTR', $bd['tester']['jml']);
            // (Stok Jadi T11 ditangani terpusat di langkah 6 — lihat bawah.)
        }

        // 5) Ekstra tester bersifat per-PESANAN → kembalikan sekali
        $ekstra = (int) ($header->ekstra_tester ?? 0);
        if ($ekstra > 0) $this->tambahKomponen('KMP-TSTR', $ekstra);

        // 6) Rekonsiliasi Stok Jadi (T11): balikkan SEMUA efek stok_jadi_logs pesanan ini.
        //    'keluar' (racik ambil dari T11) menaikkan balik; 'masuk' (botol balik ke T11 saat
        //    Batal/Retur diterima) menurunkan balik; 'rusak' tak menyentuh T11 → diabaikan.
        //    Tanpa ini, urutan Batal→T11 lalu Hapus meninggalkan stok T11 hantu.
        $netT11 = [];
        foreach (\App\Models\StokJadiLog::where('ref_id', $header->internal_id)->get() as $log) {
            $q = (float) $log->qty;
            if ($log->tipe === 'keluar')    $netT11[$log->sku_id] = ($netT11[$log->sku_id] ?? 0) + $q;
            elseif ($log->tipe === 'masuk') $netT11[$log->sku_id] = ($netT11[$log->sku_id] ?? 0) - $q;
        }
        foreach ($netT11 as $skuId => $q) {
            if ($q == 0) continue;
            $produk = MasterProduk::where('sku_id', $skuId)->first();
            if ($produk) { $produk->stok_t11 = (int) $produk->stok_t11 + $q; $produk->save(); }
        }
    }
}
