<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\PenjualanHeader;
use App\Models\PenjualanDetail;
use App\Models\MasterHarga;

/**
 * Pesanan uji untuk GUDANG RACIK — semua status 'Menunggu' (masuk antrean racik),
 * belum diracik (hpp_satuan null). Untuk menguji checklist, select-all, bulk diracik-oleh.
 *
 * Jalankan:  php artisan db:seed --class=RacikTestSeeder
 * IDEMPOTEN: hanya hapus pesanan bertanda catatan='SEED-RACIK'.
 */
class RacikTestSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Bersihkan seed sebelumnya
        $old = PenjualanHeader::where('catatan', 'SEED-RACIK')->pluck('internal_id');
        if ($old->isNotEmpty()) {
            PenjualanDetail::whereIn('internal_id', $old)->delete();
            PenjualanHeader::whereIn('internal_id', $old)->delete();
        }

        // 2. Pastikan stok cukup utk racik
        DB::statement("UPDATE master_bibits SET stok_ml = GREATEST(COALESCE(stok_ml,0), 3000) WHERE status = 'Aktif'");
        DB::statement("UPDATE master_komponens SET stok = GREATEST(COALESCE(stok,0), 3000)");

        // 3. Pesanan (1 header bisa banyak detail). [channel, externalId, [ [sku,qty], ... ] ]
        $orders = [
            ['Marketplace TikTok', $this->tiktok(), [['HGS001-30-REG', 2]]],
            ['Marketplace TikTok', $this->tiktok(), [['HGS002-50-REG', 1]]],
            ['Marketplace Shopee', $this->shopee(), [['HGS003-30-REG', 3]]],
            ['Marketplace Shopee', $this->shopee(), [['HGS004-50-REG', 1]]],
            ['Marketplace TikTok', $this->tiktok(), [['HGS001-50-REG', 1]]], // aroma HGS001 lagi → uji stok kurang
            ['Marketplace TikTok', $this->tiktok(), [['HGS005-30-REG', 1], ['HGS006-50-REG', 1]]], // multi-SKU
            ['Marketplace Shopee', $this->shopee(), [['HGS007-30-REG', 2]]],
        ];

        $nHeader = 0; $nDetail = 0; $lewat = [];
        foreach ($orders as [$channel, $extId, $items]) {
            $gmv = 0;
            $rows = [];
            foreach ($items as [$sku, $qty]) {
                $harga = (float) (MasterHarga::where('sku_id', $sku)->where('channel', $channel)->value('harga_jual') ?? 0);
                if ($harga == 0) $lewat[] = "$channel / $sku";
                $sub = $harga * $qty;
                $gmv += $sub;
                $rows[] = [$sku, $qty, $harga, $sub];
            }

            $header = PenjualanHeader::create([
                'channel'           => $channel,
                'metode_pengiriman' => 'Dikirim',
                'tgl_pesanan'       => now()->subDays(rand(0, 5))->toDateString(),
                'status_pesanan'    => 'Menunggu',
                'status_pembayaran' => 'Belum Cair',
                'gmv_kotor'         => $gmv,
                'nama_pembeli'      => 'SEED ' . $channel,
                'external_order_id' => $extId,
                'ekstra_tester'     => 0,
                'catatan'           => 'SEED-RACIK',
            ]);
            $nHeader++;

            foreach ($rows as [$sku, $qty, $harga, $sub]) {
                PenjualanDetail::create([
                    'internal_id'  => $header->internal_id,
                    'sku_id'       => $sku,
                    'qty'          => $qty,
                    'harga_satuan' => $harga,
                    'subtotal'     => $sub,
                    'hpp_satuan'   => null, // belum diracik → muncul di antrean
                    'margin_satuan'=> null,
                ]);
                $nDetail++;
            }
        }

        $this->command->info("RacikTestSeeder: $nHeader pesanan / $nDetail item status 'Menunggu' dibuat (catatan='SEED-RACIK').");
        if ($lewat) $this->command->warn("Harga 0 (belum diset): " . implode('; ', array_unique($lewat)));
    }

    private function tiktok(): string { return (string) random_int(580000000000000000, 589999999999999999); }
    private function shopee(): string { return '2606' . strtoupper(substr(str_shuffle('ABCDEFGHJKLMNP0123456789'), 0, 8)); }
}
