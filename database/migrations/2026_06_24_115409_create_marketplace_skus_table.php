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
        Schema::create('marketplace_skus', function (Blueprint $table) {
            $table->id();
            $table->string('platform'); // TikTok or Shopee
            $table->string('marketplace_nama');
            $table->string('marketplace_variasi')->nullable();
            $table->string('sku_id')->nullable(); // Foreign to MasterProduk, nullable if unmatched
            $table->timestamps();
            
            // Unique constraint to prevent duplicate unmatched rows
            $table->unique(['platform', 'marketplace_nama', 'marketplace_variasi'], 'mkt_sku_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_skus');
    }
};
