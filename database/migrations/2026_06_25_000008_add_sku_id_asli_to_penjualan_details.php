<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SKU/aroma asal sebelum tukar aroma (bibit kosong saat racik).
 * NULL = tidak ada tukar aroma. flag_swap=1 menandai pesanan yang aromanya diganti.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penjualan_details', function (Blueprint $table) {
            $table->string('sku_id_asli')->nullable()->after('sku_id');
        });
    }

    public function down(): void
    {
        Schema::table('penjualan_details', function (Blueprint $table) {
            $table->dropColumn('sku_id_asli');
        });
    }
};
