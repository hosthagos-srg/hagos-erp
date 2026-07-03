<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('website_sku_maps', function (Blueprint $table) {
            $table->id();
            $table->string('website_ref')->unique();   // identifier produk dari website (UUID/SKU/id di payload pesanan)
            $table->string('sku_id');                   // sku_id di ERP (Master Produk)
            $table->string('nama_website')->nullable(); // nama produk di website (referensi)
            $table->timestamps();

            $table->index('sku_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_sku_maps');
    }
};
