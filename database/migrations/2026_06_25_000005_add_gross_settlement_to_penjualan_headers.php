<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Omset riil penjual dari file settlement (Subtotal Pesanan Shopee / Total Pendapatan TikTok).
 * Dipakai sbg basis Omset utk pesanan marketplace yang sudah cair, agar:
 *   Omset (gross_settlement) − Potongan = Net Settlement (rekonsiliasi persis, tanpa angka palsu).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penjualan_headers', function (Blueprint $table) {
            $table->decimal('gross_settlement', 15, 2)->nullable()->after('net_settlement');
        });
    }

    public function down(): void
    {
        Schema::table('penjualan_headers', function (Blueprint $table) {
            $table->dropColumn('gross_settlement');
        });
    }
};
