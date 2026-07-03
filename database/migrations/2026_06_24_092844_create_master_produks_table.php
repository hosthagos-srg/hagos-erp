<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_produks', function (Blueprint $table) {
            $table->string('sku_id')->primary();
            $table->string('variant_uuid')->nullable();
            $table->string('sku_aroma')->nullable();
            $table->string('bibit_id')->nullable();
            $table->string('nama_produk')->nullable();
            $table->string('kategori')->nullable();
            $table->string('ukuran_ml')->nullable();
            $table->string('bentuk')->nullable();
            $table->string('status')->nullable();
            $table->string('hpp_botol')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_produks');
    }
};
