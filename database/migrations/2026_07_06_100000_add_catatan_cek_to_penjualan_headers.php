<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penjualan_headers', function (Blueprint $table) {
            // Catatan hasil pengecekan monitoring (mis. "paket stuck 3-6 Jul, masih menuju pembeli").
            $table->text('catatan_cek')->nullable()->after('jumlah_dicek');
        });
    }

    public function down(): void
    {
        Schema::table('penjualan_headers', function (Blueprint $table) {
            $table->dropColumn('catatan_cek');
        });
    }
};
