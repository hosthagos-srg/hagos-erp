<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * T12 — Koreksi Stok (Stock Adjustment).
 * Setiap kali stok sistem disesuaikan ke stok fisik (opname / tumpah / timbangan),
 * selisihnya dicatat sebagai DATA (indikator akurasi), bukan kebocoran diam-diam.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('koreksi_stoks', function (Blueprint $table) {
            $table->uuid('koreksi_id')->primary();
            $table->date('tanggal');
            $table->string('item_type');           // 'bibit' | 'komponen'
            $table->string('item_id');             // bibit_id / komponen_id
            $table->string('nama_item')->nullable();
            $table->decimal('stok_sistem', 15, 2)->default(0);
            $table->decimal('stok_fisik', 15, 2)->default(0);
            $table->decimal('selisih', 15, 2)->default(0); // fisik - sistem
            $table->string('alasan')->nullable();  // Opname / Tumpah / Timbangan / Lainnya
            $table->string('dicatat_oleh')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('koreksi_stoks');
    }
};
