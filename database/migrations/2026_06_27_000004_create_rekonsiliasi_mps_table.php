<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekonsiliasi_mps', function (Blueprint $table) {
            $table->id();
            $table->string('channel');                       // mis. "TikTok Shop", "Shopee"
            $table->string('periode');                       // Y-m
            $table->decimal('net_sistem', 15, 2)->default(0); // total net_settlement versi sistem (snapshot)
            $table->decimal('saldo_riil', 15, 2)->default(0); // dana riil yang benar-benar diterima (input owner)
            $table->decimal('selisih', 15, 2)->default(0);    // net_sistem - saldo_riil (positif = ada potongan siluman)
            $table->boolean('dibebankan')->default(false);    // apakah selisih sudah dicatat sbg beban
            $table->string('mutasi_ref')->nullable();         // ref mutasi_kas bila dibebankan
            $table->text('catatan')->nullable();
            $table->string('dicatat_oleh')->nullable();
            $table->timestamps();

            $table->unique(['channel', 'periode']);           // 1 rekonsiliasi per channel per bulan
            $table->index('periode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekonsiliasi_mps');
    }
};
