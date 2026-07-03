<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utang_cicilan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sumber_dana_id')->constrained('sumber_dana');
            $table->string('deskripsi');
            $table->decimal('total_utang', 15, 2);
            $table->decimal('cicilan_per_bulan', 15, 2);
            $table->integer('total_bulan');
            $table->date('bulan_mulai'); // bulan pertama cicilan (hari=1)
            $table->enum('status', ['aktif', 'lunas'])->default('aktif');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('cicilan_pembayaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('utang_cicilan_id')->constrained('utang_cicilan')->cascadeOnDelete();
            $table->date('periode'); // tanggal jatuh tempo bulan ini
            $table->decimal('jumlah_tagihan', 15, 2);
            $table->decimal('jumlah_bayar', 15, 2)->default(0);
            $table->date('tgl_bayar')->nullable();
            $table->enum('status', ['belum', 'lunas'])->default('belum');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cicilan_pembayaran');
        Schema::dropIfExists('utang_cicilan');
    }
};
