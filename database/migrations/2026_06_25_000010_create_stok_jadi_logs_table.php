<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Buku besar pergerakan Stok Produk Jadi (T11): dari mana masuk & keluar.
 * Sumber masuk: batal, retur, salah_racik, produksi, opname.
 * Sumber keluar: penjualan (dipakai memenuhi pesanan).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stok_jadi_logs', function (Blueprint $table) {
            $table->uuid('log_id')->primary();
            $table->date('tanggal');
            $table->string('sku_id');
            $table->string('tipe');                 // 'masuk' | 'keluar'
            $table->integer('qty');
            $table->string('sumber');               // batal/retur/salah_racik/produksi/opname/penjualan
            $table->decimal('hpp_per_unit', 15, 2)->nullable();
            $table->string('ref_id')->nullable();   // internal_id pesanan bila relevan
            $table->string('dicatat_oleh')->nullable();
            $table->string('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stok_jadi_logs');
    }
};
