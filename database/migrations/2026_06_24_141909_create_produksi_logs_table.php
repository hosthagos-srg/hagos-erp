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
        Schema::create('produksi_logs', function (Blueprint $table) {
            $table->id();
            $table->date('tgl_racik');
            $table->string('diracik_oleh');
            $table->string('tipe'); // 'Tester' atau 'Absolute'
            $table->json('detail_text'); // Simpan rincian produksi
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produksi_logs');
    }
};
