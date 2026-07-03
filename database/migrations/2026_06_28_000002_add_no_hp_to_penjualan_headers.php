<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penjualan_headers', function (Blueprint $table) {
            $table->string('no_hp_pembeli')->nullable()->after('nama_pembeli');
        });
    }

    public function down(): void
    {
        Schema::table('penjualan_headers', function (Blueprint $table) {
            $table->dropColumn('no_hp_pembeli');
        });
    }
};
