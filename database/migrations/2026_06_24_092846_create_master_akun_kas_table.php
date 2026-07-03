<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_akun_kas', function (Blueprint $table) {
            $table->string('akun_id')->primary();
            $table->string('nama_akun')->nullable();
            $table->string('tipe')->nullable();
            $table->string('saldo_awal')->nullable();
            $table->text('fungsi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_akun_kas');
    }
};
