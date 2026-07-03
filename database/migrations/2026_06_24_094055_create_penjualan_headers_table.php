<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penjualan_headers', function (Blueprint $table) {
            $table->uuid('internal_id')->primary();
            $table->string('external_order_id')->nullable();
            $table->string('channel')->nullable();
            $table->date('tgl_pesanan')->nullable();
            $table->date('tgl_racik')->nullable();
            $table->string('diracik_oleh')->nullable();
            $table->string('status_pesanan')->nullable();
            $table->string('status_pembayaran')->nullable();
            $table->decimal('gmv_kotor', 15, 2)->nullable();
            $table->decimal('diskon_manual', 15, 2)->nullable();
            $table->decimal('net_settlement', 15, 2)->nullable();
            $table->date('tgl_cair_saldo')->nullable();
            $table->date('tgl_cair_bank')->nullable();
            $table->string('akun_masuk')->nullable();
            $table->string('nama_pembeli')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penjualan_headers');
    }
};
