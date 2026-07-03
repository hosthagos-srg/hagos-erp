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
        Schema::create('piutang_pribadi', function (Blueprint $table) {
            $table->id();
            $table->string('nama');                              // peminjam (kerabat/keluarga)
            $table->string('hubungan')->nullable();              // mis. Keluarga / Kerabat / Teman
            $table->decimal('jumlah_pinjaman', 15, 2);
            $table->date('tgl_pinjam');
            $table->string('akun_sumber');                       // akun kas asal uang
            $table->string('catatan')->nullable();
            $table->string('status')->default('aktif');          // aktif | lunas
            $table->timestamps();
        });

        Schema::create('piutang_pribadi_bayar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('piutang_pribadi_id')->constrained('piutang_pribadi')->cascadeOnDelete();
            $table->decimal('jumlah', 15, 2);
            $table->date('tgl_bayar');
            $table->string('akun_masuk');                        // akun kas tujuan pengembalian
            $table->string('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('piutang_pribadi_bayar');
        Schema::dropIfExists('piutang_pribadi');
    }
};
