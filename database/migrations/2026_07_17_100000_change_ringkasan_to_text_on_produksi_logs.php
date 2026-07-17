<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom `ringkasan` semula varchar(255). Saat produksi tester massal (mis. 150 botol
 * dari 30 aroma), teks ringkasan yang mendaftar semua aroma melebihi 255 karakter →
 * SQLSTATE[22001] "Data too long for column 'ringkasan'". Ubah ke TEXT (aman, memperbesar
 * saja, tak ada data yang hilang).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produksi_logs', function (Blueprint $table) {
            $table->text('ringkasan')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('produksi_logs', function (Blueprint $table) {
            $table->string('ringkasan', 255)->nullable()->change();
        });
    }
};
