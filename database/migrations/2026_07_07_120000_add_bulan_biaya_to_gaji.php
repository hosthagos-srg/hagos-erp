<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gaji', function (Blueprint $table) {
            // Bulan biaya (accrual) — menentukan gaji masuk laba bulan mana.
            // Terpisah dari tanggal_bayar (tanggal transfer asli = untuk kas/bukti bank).
            $table->date('bulan_biaya')->nullable()->after('periode');
        });

        // Backfill: gaji lama → bulan biaya = bulan dari tanggal_bayar (jaga P&L lama tetap sama).
        DB::statement("UPDATE gaji SET bulan_biaya = DATE_FORMAT(tanggal_bayar, '%Y-%m-01') WHERE bulan_biaya IS NULL");
    }

    public function down(): void
    {
        Schema::table('gaji', function (Blueprint $table) {
            $table->dropColumn('bulan_biaya');
        });
    }
};
