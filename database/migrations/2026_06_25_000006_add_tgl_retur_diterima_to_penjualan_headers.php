<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tanggal barang retur diterima kembali di Hagos. NULL = retur ditandai tapi barang belum sampai.
 * Saat dikonfirmasi diterima, barang baru masuk Stok Jadi (T11).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penjualan_headers', function (Blueprint $table) {
            $table->date('tgl_retur_diterima')->nullable()->after('alasan_batal');
        });
    }

    public function down(): void
    {
        Schema::table('penjualan_headers', function (Blueprint $table) {
            $table->dropColumn('tgl_retur_diterima');
        });
    }
};
