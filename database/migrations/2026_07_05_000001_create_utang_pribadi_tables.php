<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Utang Pribadi — Hagos MEMINJAM uang tunai dari orang (kebalikan Piutang Pribadi).
        Schema::create('utang_pribadi', function (Blueprint $table) {
            $table->id();
            $table->string('nama');                   // pemberi pinjaman (orang yang menghutangi)
            $table->string('hubungan')->nullable();   // mis. Keluarga / Teman / Investor
            $table->decimal('jumlah_pinjaman', 15, 2); // jumlah utang
            $table->date('tgl_pinjam');
            $table->string('akun_tujuan');            // akun kas TEMPAT UANG MASUK saat meminjam
            $table->string('catatan')->nullable();
            $table->string('status')->default('aktif'); // aktif | lunas
            $table->timestamps();
        });

        Schema::create('utang_pribadi_bayar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('utang_pribadi_id')->constrained('utang_pribadi')->cascadeOnDelete();
            $table->decimal('jumlah', 15, 2);
            $table->date('tgl_bayar');
            $table->string('akun_sumber');            // akun kas ASAL UANG KELUAR saat membayar
            $table->string('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utang_pribadi_bayar');
        Schema::dropIfExists('utang_pribadi');
    }
};
