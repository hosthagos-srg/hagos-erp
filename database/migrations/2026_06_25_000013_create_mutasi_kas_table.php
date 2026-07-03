<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Buku besar mutasi kas (semua uang masuk/keluar per akun).
 * Saldo akun = saldo_awal + Σ(masuk) − Σ(keluar).
 * Sumber: belanja, pengeluaran, withdrawal, transfer, penjualan, dll.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mutasi_kas', function (Blueprint $table) {
            $table->uuid('mutasi_id')->primary();
            $table->date('tanggal');
            $table->string('akun');                 // = master_akun_kas.nama_akun
            $table->string('tipe');                 // 'masuk' | 'keluar'
            $table->decimal('jumlah', 18, 2);       // selalu positif
            $table->string('kategori');             // belanja_bibit/belanja_komponen/batal_belanja/pengeluaran/withdrawal/transfer/penjualan/...
            $table->string('ref_id')->nullable();   // mis. belanja_id
            $table->string('keterangan')->nullable();
            $table->string('dicatat_oleh')->nullable();
            $table->timestamps();

            $table->index(['akun', 'tipe']);
            $table->index('ref_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mutasi_kas');
    }
};
