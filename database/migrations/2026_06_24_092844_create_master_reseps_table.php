<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_reseps', function (Blueprint $table) {
            $table->string('resep_id')->primary();
            $table->string('sku_id')->nullable();
            $table->string('bibit_id')->nullable();
            $table->string('konsentrasi')->nullable();
            $table->string('ml_bibit_utama')->nullable();
            $table->string('ml_absolute')->nullable();
            $table->string('jml_tester')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_reseps');
    }
};
