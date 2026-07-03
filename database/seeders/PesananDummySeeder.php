<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\PenjualanHeader;
use App\Models\PenjualanDetail;
use App\Models\MasterHarga;
use App\Services\HppService;
use App\Services\RacikService;

/**
 * Data uji pesanan untuk SEMUA channel — playground untuk menguji fitur tanpa input manual berulang.
 *
 * Jalankan:  php artisan db:seed --class=PesananDummySeeder
 *
 * Sifat:
 *  - IDEMPOTEN: hanya menghapus pesanan bertanda catatan='SEED' (hasil seeder), TIDAK menyentuh data input asli.
 *  - Menaikkan stok bibit/komponen seperlunya (GREATEST) agar racik selalu berhasil — tidak menurunkan stok yang sudah tinggi.
 *  - Meniru workflow riil: non-marketplace diracik LANGSUNG; marketplace ada yang antre & ada yang sudah selesai racik (menunggu settlement).
 */
class PesananDummySeeder extends Seeder
{
    public function run(): void
    {
        $hpp = app(HppService::class);
        $racik = app(RacikService::class);

        // 1. Bersihkan HANYA pesanan hasil seeder sebelumnya (tanda catatan='SEED')
        $oldIds = PenjualanHeader::where('catatan', 'SEED')->pluck('internal_id');
        if ($oldIds->isNotEmpty()) {
            PenjualanDetail::whereIn('internal_id', $oldIds)->delete();
            PenjualanHeader::whereIn('internal_id', $oldIds)->delete();
        }

        // 2. Pastikan stok cukup untuk racik (tidak menurunkan yang sudah tinggi)
        DB::statement("UPDATE master_bibits SET stok_ml = GREATEST(COALESCE(stok_ml,0), 3000) WHERE status = 'Aktif'");
        DB::statement("UPDATE master_komponens SET stok = GREATEST(COALESCE(stok,0), 3000)");

        // 3. Skenario per channel
        // [channel, sku_id, qty, metode(null=default), racikLangsung, ekstraTester, statusBayar, marketplace, externalId]
        $skenario = [
            // --- Non-marketplace: diracik LANGSUNG saat input (status Selesai Racik) ---
            ['Offline',            'HGS001-50-REG', 1, null,            true, 0, 'Lunas',   false, null],
            ['Offline',            'HGS003-30-REG', 2, null,            true, 0, 'Lunas',   false, null],
            ['WA',                 'HGS002-50-REG', 1, null,            true, 0, 'Lunas',   false, null],
            ['Reseller A',         'HGS004-50-REG', 1, 'Ambil Langsung',true, 0, 'Piutang', false, null],
            ['Reseller A',         'HGS005-50-REG', 1, 'Dikirim',       true, 0, 'Lunas',   false, null],
            ['Refill',             'HGS006-50-REG', 1, null,            true, 0, 'Lunas',   false, null],
            ['Reseller B',         'HGS001-30-REG', 1, null,            true, 0, 'Lunas',   false, null],

            // --- Marketplace: ANTRE racik (status Menunggu, belum cair) ---
            ['Marketplace TikTok', 'HGS002-30-REG', 1, null,            false, 0, null, true, $this->fakeTikTokId()],
            ['Marketplace TikTok', 'HGS007-50-REG', 2, null,            false, 1, null, true, $this->fakeTikTokId()], // + bonus tester
            ['Marketplace Shopee', 'HGS008-30-REG', 1, null,            false, 0, null, true, $this->fakeShopeeId()],

            // --- Marketplace: sudah SELESAI RACIK, menunggu settlement ---
            ['Marketplace TikTok', 'HGS009-50-REG', 1, null,            true, 0, null, true, $this->fakeTikTokId()],
            ['Marketplace Shopee', 'HGS010-50-REG', 1, null,            true, 0, null, true, $this->fakeShopeeId()],
        ];

        $dibuat = 0;
        $lewat = [];
        foreach ($skenario as [$channel, $skuId, $qty, $metode, $racikLangsung, $ekstra, $statusBayar, $isMp, $extId]) {
            $harga = MasterHarga::where('sku_id', $skuId)->where('channel', $channel)->value('harga_jual');
            if ($harga === null) {
                $lewat[] = "$channel / $skuId (harga belum diset)";
                // tetap buat dengan harga 0 agar alur tetap bisa diuji, tapi catat
                $harga = 0;
            }
            $hargaJual = (float) $harga;
            $subtotal = $hargaJual * $qty;
            $metodeFinal = $metode ?: ($hpp->defaultDikirim($channel) ? 'Dikirim' : 'Ambil Langsung');

            $header = PenjualanHeader::create([
                'channel'           => $channel,
                'metode_pengiriman' => $metodeFinal,
                'tgl_pesanan'       => now()->subDays(rand(0, 6))->toDateString(),
                'status_pesanan'    => 'Menunggu',
                'status_pembayaran' => $isMp ? 'Belum Cair' : ($statusBayar ?? 'Lunas'),
                'akun_masuk'        => $isMp ? null : 'Kas Tunai',
                'gmv_kotor'         => $subtotal,
                'nama_pembeli'      => 'SEED ' . $channel,
                'external_order_id' => $extId,
                'ekstra_tester'     => $ekstra,
                'catatan'           => 'SEED',
            ]);

            $detail = PenjualanDetail::create([
                'internal_id'  => $header->internal_id,
                'sku_id'       => $skuId,
                'qty'          => $qty,
                'harga_satuan' => $hargaJual,
                'subtotal'     => $subtotal,
                'hpp_satuan'   => null,
                'margin_satuan'=> null,
            ]);

            if ($racikLangsung) {
                $racik->racik($detail, $header);
                $header->status_pesanan = 'Selesai Racik';
                $header->tgl_racik = now()->toDateString();
                $header->diracik_oleh = 'Seeder';
                $header->save();
            }
            $dibuat++;
        }

        $this->command->info("PesananDummySeeder: $dibuat pesanan dibuat (tanda catatan='SEED').");
        if ($lewat) {
            $this->command->warn("Harga belum diset (dibuat dgn harga 0): " . implode('; ', $lewat));
        }
    }

    private function fakeTikTokId(): string
    {
        return (string) random_int(580000000000000000, 589999999999999999); // 18 digit angka
    }

    private function fakeShopeeId(): string
    {
        $chars = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ0123456789'), 0, 8));
        return '2606' . substr((string) random_int(10, 99), 0, 2) . $chars; // alfanumerik ~14 char
    }
}
