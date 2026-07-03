<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Komposisi bibit untuk resep MIX (>1 bibit dalam 1 botol).
     * Resep single-bibit lama tetap di master_reseps (tabel ini kosong untuk mereka).
     */
    public function up(): void
    {
        Schema::create('master_resep_bibit', function (Blueprint $table) {
            $table->id();
            $table->string('resep_id');            // FK ke master_reseps.resep_id
            $table->string('sku_id')->nullable();  // denormal utk query cepat
            $table->string('bibit_id');
            $table->decimal('ml', 10, 4)->default(0); // ml bibit ini dalam 1 botol
            $table->timestamps();

            $table->index('resep_id');
            $table->index('sku_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_resep_bibit');
    }
};
