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
        Schema::create('anggaran_mingguan', function (Blueprint $table) {
            $table->id();
            $table->string('kategori')->unique();               // kategori pengeluaran (mis. "Konsumsi Tim")
            $table->decimal('jumlah_mingguan', 15, 2)->default(0); // jatah per minggu
            $table->boolean('aktif')->default(true);
            $table->string('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anggaran_mingguan');
    }
};
