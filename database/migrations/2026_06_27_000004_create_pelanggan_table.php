<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pelanggan', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();   // dicocokkan dgn penjualan_headers.nama_pembeli
            $table->string('tipe')->nullable(); // Reseller A | Reseller B | WA | Offline | Lainnya
            $table->string('no_hp')->nullable();
            $table->string('kota')->nullable();
            $table->text('alamat')->nullable();
            $table->text('catatan')->nullable();
            $table->string('status')->default('Aktif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelanggan');
    }
};
