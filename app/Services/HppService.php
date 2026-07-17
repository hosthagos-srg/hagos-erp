<?php

namespace App\Services;

use App\Models\MasterProduk;
use App\Models\MasterResep;
use App\Models\MasterResepBibit;
use App\Models\MasterBibit;
use App\Models\MasterKomponen;

/**
 * Sumber tunggal perhitungan HPP (spec Bab 7). Dipakai oleh RacikController (saat racik)
 * dan tampilan detail pesanan, agar formula tidak terduplikasi & tidak melenceng.
 */
class HppService
{
    /** Cache harga komponen per request */
    private array $komponen = [];

    private function k(string $id, float $default = 0): float
    {
        if (!array_key_exists($id, $this->komponen)) {
            $val = MasterKomponen::where('komponen_id', $id)->value('harga_satuan');
            $this->komponen[$id] = is_null($val) ? null : (float) $val;
        }
        $v = $this->komponen[$id];
        return ($v === null) ? $default : $v;
    }

    /** Biaya 1 botol tester jadi (pakai KMP-TSTR; bila belum di-opname, hitung dari spec 7.1). */
    public function hppTester(): float
    {
        $tstr = $this->k('KMP-TSTR', 0);
        if ($tstr > 0) return $tstr;

        $avg = (float) MasterBibit::where('status', 'Aktif')->avg('harga_per_ml');
        if ($avg <= 0) $avg = (float) MasterBibit::avg('harga_per_ml');
        return $this->k('KMP-BTL3', 1158) + (1.5 * $avg) + (1.5 * $this->k('KMP-ABSC', 51.5)) + $this->k('KMP-STKT', 15);
    }

    /**
     * Default apakah pesanan channel ini DIKIRIM (kena Lapis 2). Bisa di-override per pesanan.
     */
    public function defaultDikirim(string $channel): bool
    {
        $c = strtolower($channel);
        if (str_contains($c, 'marketplace')) return true; // marketplace selalu dikirim
        if (str_contains($c, 'wa')) return true;          // WA default dikirim
        return false;                                     // offline / reseller / refill: ambil langsung
    }

    /**
     * Klasifikasi channel -> tipe HPP (komposisi Lapis 1) & default pengiriman.
     */
    public function klasifikasiChannel(string $channel): array
    {
        $isRSB = stripos($channel, 'Reseller B') !== false;
        $isRFL = stripos($channel, 'Refill') !== false;
        $isOffNoBox = stripos($channel, 'Offline (Tanpa Box + Tester)') !== false;
        $isMarketplace = stripos($channel, 'Marketplace') !== false;

        if ($isRFL) $tipe = 'Refill (RFL)';
        elseif ($isRSB) $tipe = 'Reseller B (RSB)';
        elseif ($isOffNoBox) $tipe = 'Offline Tanpa Box';
        else $tipe = 'Reguler';

        $dikirimDefault = $this->defaultDikirim($channel);

        return [
            'tipe' => $tipe,
            'is_rsb' => $isRSB,
            'is_rfl' => $isRFL,
            'is_offline_nobox' => $isOffNoBox,
            'is_pickup' => !$dikirimDefault,        // default; bisa di-override per pesanan
            'is_shipped' => $dikirimDefault,        // default; bisa di-override per pesanan
            'is_marketplace' => $isMarketplace,
            'butuh_settlement' => $isMarketplace,   // hanya marketplace yg nunggu settlement
        ];
    }

    /**
     * Nilai "botol telanjang" 1 botol jadi (bibit + absolute + botol + sticker).
     * Channel-independent (pakai 'Offline' sebagai basis perhitungan).
     */
    public function bareBottle(string $skuId): float
    {
        return $this->breakdown($skuId, 'Offline', 1, 0, false)['bare_per_unit'];
    }

    /**
     * Rincian HPP lengkap untuk 1 baris pesanan (qty pcs, kondisi racik baru semua).
     * $isShipped: null = ikut default channel; true/false = override (Dikirim/Ambil Langsung).
     */
    public function breakdown(string $skuId, string $channel, int $qty = 1, float $ekstraTester = 0, ?bool $isShipped = null, ?array $blendOverride = null): array
    {
        $produk = MasterProduk::where('sku_id', $skuId)->first();
        $resep  = MasterResep::where('sku_id', $skuId)->first();
        $components = $this->resolveBibitComponents($skuId, $blendOverride);

        return $this->computeBreakdown(
            $components,
            $resep ? (float) $resep->ml_absolute : 0,
            $resep ? (float) $resep->jml_tester : 0,
            $produk ? (int) $produk->ukuran_ml : 0,
            $channel, $qty, $ekstraTester, $isShipped
        );
    }

    /**
     * Tentukan komposisi bibit 1 botol untuk sebuah SKU/pesanan. Urutan prioritas:
     *   1. $blendOverride (custom per-detail: [['bibit_id','ml'], ...])
     *   2. Resep MIX di master_resep_bibit (>1 bibit)
     *   3. Fallback: 1 bibit tunggal dari master_reseps (resep lama)
     * Return: [['bibit_id','label','harga_ml','ml'], ...]
     */
    public function resolveBibitComponents(string $skuId, ?array $blendOverride = null): array
    {
        if (is_array($blendOverride) && count($blendOverride) > 0) {
            return $this->buildComponents($blendOverride);
        }

        $resep = MasterResep::where('sku_id', $skuId)->first();
        if ($resep) {
            $rows = MasterResepBibit::where('resep_id', $resep->resep_id)->get();
            if ($rows->isNotEmpty()) {
                return $this->buildComponents($rows->map(fn($r) => ['bibit_id' => $r->bibit_id, 'ml' => (float) $r->ml])->all());
            }
            if ($resep->bibit_id) {
                return $this->buildComponents([['bibit_id' => $resep->bibit_id, 'ml' => (float) $resep->ml_bibit_utama]]);
            }
        }
        return [];
    }

    /** Lengkapi tiap komponen dgn nama & harga_per_ml dari master bibit. */
    private function buildComponents(array $list): array
    {
        $out = [];
        foreach ($list as $c) {
            $bibitId = $c['bibit_id'] ?? null;
            $bibit = $bibitId ? MasterBibit::where('bibit_id', $bibitId)->first() : null;
            $out[] = [
                'bibit_id' => $bibitId,
                'label'    => $c['label'] ?? ($bibit->nama_bibit ?? ($bibitId ?: '-')),
                'harga_ml' => array_key_exists('harga_ml', $c) ? (float) $c['harga_ml'] : ($bibit ? (float) $bibit->harga_per_ml : 0),
                'ml'       => (float) ($c['ml'] ?? 0),
            ];
        }
        return $out;
    }

    /**
     * Preview HPP dari nilai resep MENTAH (sebelum produk disimpan), mis. di wizard tambah produk.
     * Pakai formula yang sama dgn breakdown() agar konsisten.
     */
    public function previewHpp(float $hargaPerMl, float $mlBibit, float $mlAbs, float $jmlTester, int $ukuran, string $channel, string $namaBibit = '-', int $qty = 1, float $ekstraTester = 0, ?bool $isShipped = null): array
    {
        $components = [['bibit_id' => null, 'label' => $namaBibit, 'harga_ml' => $hargaPerMl, 'ml' => $mlBibit]];
        return $this->computeBreakdown($components, $mlAbs, $jmlTester, $ukuran, $channel, $qty, $ekstraTester, $isShipped);
    }

    /**
     * Preview HPP untuk racikan MULTI-BIBIT (mix) dari daftar bibit mentah.
     * $components: [['bibit_id'=>, 'ml'=>], ...] — harga_ml diambil dari master bibit.
     */
    public function previewHppBlend(array $components, float $mlAbs, float $jmlTester, int $ukuran, string $channel, int $qty = 1, float $ekstraTester = 0, ?bool $isShipped = null): array
    {
        return $this->computeBreakdown($this->buildComponents($components), $mlAbs, $jmlTester, $ukuran, $channel, $qty, $ekstraTester, $isShipped);
    }

    /**
     * Inti perhitungan HPP — dipakai breakdown() (dari DB) & previewHpp()/previewHppBlend() (nilai mentah).
     * $bibitComponents: [['label','harga_ml','ml'], ...] — mendukung 1 bibit (biasa) atau banyak (mix).
     */
    private function computeBreakdown(array $bibitComponents, float $mlAbs, float $jmlTester, int $ukuran, string $channel, int $qty = 1, float $ekstraTester = 0, ?bool $isShipped = null): array
    {
        $qty = max(1, $qty);
        $cls = $this->klasifikasiChannel($channel);

        $hargaBotol = $this->k('KMP-BTL' . $ukuran, 0);
        $hargaAbsC  = $this->k('KMP-ABSC', 51.5);
        $hargaBox   = $this->k('KMP-BOX', 2550);
        // Konsumabel (sticker & kartu) DIKELUARKAN dari HPP — kini dicatat sbg Pengeluaran nyata
        // (kategori "Bahan Packing/Konsumabel"). Set 0 di sini biar tak dobel. Harga master tetap utuh.
        $hargaStk   = 0; // dulu KMP-STKU (141)
        $hargaKartu = 0; // dulu KMP-KARTU (70)

        // === Lapis 1 (per unit) ===
        // Bibit bisa >1 (mix): tiap bibit jadi 1 baris tersendiri, biayanya dijumlahkan.
        $l1 = [];
        $bibitCost = 0;
        $i = 0;
        foreach ($bibitComponents as $c) {
            $ml  = (float) ($c['ml'] ?? 0);
            $hml = (float) ($c['harga_ml'] ?? 0);
            $tot = $ml * $hml;
            $bibitCost += $tot;
            $l1['bibit_' . $i] = ['label' => $c['label'] ?? '-', 'ml' => $ml, 'harga_ml' => $hml, 'total' => round($tot, 2)];
            $i++;
        }
        if ($i === 0) { // tak ada bibit terdaftar → baris kosong agar struktur konsisten
            $l1['bibit_0'] = ['label' => '-', 'ml' => 0, 'harga_ml' => 0, 'total' => 0];
        }

        $absCost = $mlAbs * $hargaAbsC;
        $l1['absolute'] = ['label' => 'Absolute campuran', 'ml' => $mlAbs, 'harga_ml' => $hargaAbsC, 'total' => round($absCost, 2)];

        // Komponen kemasan sesuai tipe channel
        $pakaiBotol = !$cls['is_rfl'];                 // RFL bawa botol sendiri
        $pakaiKemasanReg = !($cls['is_rsb'] || $cls['is_rfl']); // sticker+kartu hanya REG/offline
        $pakaiBox = $cls['tipe'] === 'Reguler';        // box hanya Reguler (marketplace/WA/offline-with-box)

        if ($pakaiBotol)     $l1['botol']   = ['label' => "Botol {$ukuran}ml", 'total' => $hargaBotol];
        if ($pakaiBox)       $l1['box']     = ['label' => 'Box karton', 'total' => $hargaBox];
        if ($pakaiKemasanReg) {
            $l1['sticker'] = ['label' => 'Sticker utama', 'total' => $hargaStk];
            $l1['kartu']   = ['label' => 'Kartu ucapan', 'total' => $hargaKartu];
        }

        $l1PerUnit = 0;
        foreach ($l1 as $row) $l1PerUnit += $row['total'];

        // "Botol telanjang" = bibit(total) + absolute + botol + sticker (nilai yang disimpan di T11).
        // "Packaging" = box + kartu (ditambahkan segar saat dijual/repack).
        $barePerUnit = round($bibitCost, 2) + ($l1['absolute']['total'] ?? 0)
                     + ($l1['botol']['total'] ?? 0) + ($l1['sticker']['total'] ?? 0);
        $packagingPerUnit = ($l1['box']['total'] ?? 0) + ($l1['kartu']['total'] ?? 0);

        // Kuantitas komponen BER-STOK yang dipotong (selaras dgn komposisi HPP di atas):
        //  - 'bare'      → dipotong per unit yang DIRACIK BARU (botol telanjang). T11 sudah punya ini.
        //  - 'packaging' → dipotong per unit TERJUAL (box ditambah segar saat dikemas/repack).
        // Sticker/kartu/pack/shrink di-set track_stok='Tidak' → tidak dilacak stoknya (sengaja, dikecualikan).
        $komponenUsage = ['bare' => [], 'packaging' => []];
        if ($pakaiBotol && $ukuran > 0) $komponenUsage['bare'][] = ['id' => 'KMP-BTL' . $ukuran, 'qty' => 1];
        if ($mlAbs > 0)                 $komponenUsage['bare'][] = ['id' => 'KMP-ABSC', 'qty' => $mlAbs];
        if ($pakaiBox)                  $komponenUsage['packaging'][] = ['id' => 'KMP-BOX', 'qty' => 1];

        // === Tester (Lapis 1, per pesanan) ===
        // RSB & RFL tidak dapat tester bawaan; hanya ekstra tester (bonus) yg dihitung.
        $hargaTester = $this->hppTester();
        $testerBawaan = ($cls['is_rsb'] || $cls['is_rfl'] || $cls['is_offline_nobox']) ? 0 : $jmlTester;
        $jmlTesterTotal = ($testerBawaan * $qty) + $ekstraTester;
        $testerCost = $jmlTesterTotal * $hargaTester;

        // === Lapis 2 (Fulfillment) — hanya pesanan dikirim ===
        // Pakai override per pesanan bila ada; jika tidak, ikut default channel.
        $shipped = is_null($isShipped) ? $cls['is_shipped'] : $isShipped;
        $l2 = ['gaji' => 0, 'shrink' => 0, 'bahan_packing' => 0, 'total' => 0];
        if ($shipped) {
            // Semua biaya fulfillment (gaji packing + shrink + bahan packing) DIKELUARKAN dari HPP —
            // kini dicatat sebagai Pengeluaran nyata saat uang benar-benar keluar (cash-basis),
            // supaya saldo cashflow sinkron & tak dobel. Harga master komponen tetap utuh.
            // Gaji packing: tukang packing dibayar per botol Rp3.000 → catat di Pengeluaran "Gaji Packing".
            $gaji = 0;   // dulu KMP-GAJI (3000)
            $shrink = 0; // dulu KMP-SHRINK (168)
            $bahan = 0;  // dulu KMP-PACK (1641)
            $l2 = [
                'gaji' => $gaji, 'shrink' => $shrink, 'bahan_packing' => $bahan,
                'total' => ($qty * ($gaji + $shrink)) + $bahan,
            ];
        }

        $hppTotal = ($qty * $l1PerUnit) + $testerCost + $l2['total'];

        return [
            'channel' => $channel,
            'tipe' => $cls['tipe'],
            'is_shipped' => $shipped,
            'is_marketplace' => $cls['is_marketplace'],
            'butuh_settlement' => $cls['butuh_settlement'],
            'qty' => $qty,
            'lapis1' => $l1,
            'lapis1_per_unit' => round($l1PerUnit, 2),
            'bare_per_unit' => round($barePerUnit, 2),
            'packaging_per_unit' => round($packagingPerUnit, 2),
            'komponen_usage' => $komponenUsage,
            'tester' => ['jml' => $jmlTesterTotal, 'harga' => round($hargaTester, 2), 'total' => round($testerCost, 2)],
            'lapis2' => $l2,
            'hpp_total' => round($hppTotal, 2),
            'hpp_per_unit' => round($hppTotal / $qty, 2),
        ];
    }
}
