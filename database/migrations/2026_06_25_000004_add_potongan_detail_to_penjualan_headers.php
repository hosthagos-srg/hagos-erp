<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rincian potongan/biaya platform per pesanan (dari file settlement), disimpan sbg JSON
 * { "Nama Biaya": jumlah, ... } untuk ditampilkan di detail pesanan (spec 7.2 — Opsi 3).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penjualan_headers', function (Blueprint $table) {
            $table->json('potongan_detail')->nullable()->after('net_settlement');
        });
    }

    public function down(): void
    {
        Schema::table('penjualan_headers', function (Blueprint $table) {
            $table->dropColumn('potongan_detail');
        });
    }
};
