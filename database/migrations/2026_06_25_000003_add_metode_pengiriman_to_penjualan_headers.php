<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Metode pemenuhan pesanan: 'Dikirim' (kena Lapis 2 fulfillment) atau 'Ambil Langsung' (tidak).
 * Memungkinkan akurasi per-pesanan, mis. Reseller A bisa ambil langsung ATAU dikirim.
 * Null = ikut default channel (lihat HppService::defaultDikirim).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penjualan_headers', function (Blueprint $table) {
            $table->string('metode_pengiriman')->nullable()->after('channel');
        });
    }

    public function down(): void
    {
        Schema::table('penjualan_headers', function (Blueprint $table) {
            $table->dropColumn('metode_pengiriman');
        });
    }
};
