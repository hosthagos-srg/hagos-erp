<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('master_komponens', function (Blueprint $table) {
            $table->double('stok')->default(0)->after('satuan');
        });

        Schema::table('penjualan_headers', function (Blueprint $table) {
            $table->integer('ekstra_tester')->default(0)->after('status_pesanan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_komponens', function (Blueprint $table) {
            $table->dropColumn('stok');
        });

        Schema::table('penjualan_headers', function (Blueprint $table) {
            $table->dropColumn('ekstra_tester');
        });
    }
};
