<?php

namespace Database\Seeders;

use App\Models\MasterKategori;
use Illuminate\Database\Seeder;

/**
 * Kategori beban yang sebelumnya tak punya wadah:
 * - "Ongkir Ditanggung Penjual": ongkir yang ditalangi penjual (sering pada order non-marketplace)
 *   selama ini tidak terkurangi dari laba kecuali dicatat manual.
 * Catatan: biaya iklan/ads marketplace sudah punya kategori "Iklan".
 */
class BebanTambahanSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Ongkir Ditanggung Penjual', 'Komisi Affiliate'] as $nilai) {
            MasterKategori::firstOrCreate(
                ['tipe_kategori' => 'Kategori Pengeluaran', 'nilai' => $nilai],
            );
        }
    }
}
