<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cicilan_pembayaran', function (Blueprint $table) {
            $table->decimal('biaya_tambahan', 15, 2)->default(0)->after('jumlah_bayar');
            $table->string('keterangan_biaya')->nullable()->after('biaya_tambahan');
        });
    }

    public function down(): void
    {
        Schema::table('cicilan_pembayaran', function (Blueprint $table) {
            $table->dropColumn(['biaya_tambahan', 'keterangan_biaya']);
        });
    }
};
