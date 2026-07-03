<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penanda pesanan Batal yang barangnya masih dikembalikan (COD ditolak, alamat tak ketemu,
 * paket anomali, aroma tidak cocok). True = menunggu barang balik; saat diterima
 * (tgl_retur_diterima terisi) barang baru masuk T11.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penjualan_headers', function (Blueprint $table) {
            $table->boolean('perlu_barang_balik')->default(false)->after('alasan_batal');
        });
    }

    public function down(): void
    {
        Schema::table('penjualan_headers', function (Blueprint $table) {
            $table->dropColumn('perlu_barang_balik');
        });
    }
};
