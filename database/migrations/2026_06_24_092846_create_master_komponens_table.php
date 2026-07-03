<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_komponens', function (Blueprint $table) {
            $table->string('komponen_id')->primary();
            $table->string('nama_komponen')->nullable();
            $table->string('harga_satuan')->nullable();
            $table->string('satuan')->nullable();
            $table->string('track_stok')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_komponens');
    }
};
