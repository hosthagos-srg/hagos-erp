<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_bibits', function (Blueprint $table) {
            $table->string('bibit_id')->primary();
            $table->string('sku_aroma')->nullable();
            $table->string('nama_bibit')->nullable();
            $table->string('merek_bibit')->nullable();
            $table->string('nama_asli')->nullable();
            $table->string('harga_per_ml')->nullable();
            $table->string('stok_ml')->nullable();
            $table->string('threshold_ml')->nullable();
            $table->string('status')->nullable();
            $table->string('masuk_ml')->nullable();
            $table->string('jual_ml')->nullable();
            $table->string('tester_ml')->nullable();
            $table->string('stok_awal')->nullable();
            $table->string('nilai_masuk')->nullable();
            $table->string('harga_awal')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_bibits');
    }
};
