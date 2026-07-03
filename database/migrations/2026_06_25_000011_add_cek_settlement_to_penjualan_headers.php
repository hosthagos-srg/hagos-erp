<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pelacakan pengecekan manual pesanan marketplace yang belum cair (tracking settlement/COD).
 * tgl_dicek = kapan terakhir dicek; jumlah_dicek = sudah berapa kali dicek.
 * Notif: belum cair & umur >12 hari (belum dicek), atau sudah dicek & >3 hari sejak cek terakhir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penjualan_headers', function (Blueprint $table) {
            $table->date('tgl_dicek')->nullable()->after('tgl_cair_bank');
            $table->integer('jumlah_dicek')->default(0)->after('tgl_dicek');
        });
    }

    public function down(): void
    {
        Schema::table('penjualan_headers', function (Blueprint $table) {
            $table->dropColumn(['tgl_dicek', 'jumlah_dicek']);
        });
    }
};
