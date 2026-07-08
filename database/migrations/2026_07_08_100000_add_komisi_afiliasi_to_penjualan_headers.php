<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penjualan_headers', function (Blueprint $table) {
            // Penanda afiliasi: komisi afiliasi (dari settlement TikTok, kolom "Komisi Afiliasi").
            // > 0 = pesanan afiliasi. Terpisah dari net_settlement/margin/HPP (tidak memengaruhinya).
            $table->decimal('komisi_afiliasi', 15, 2)->nullable()->after('potongan_detail');
        });
    }

    public function down(): void
    {
        Schema::table('penjualan_headers', function (Blueprint $table) {
            $table->dropColumn('komisi_afiliasi');
        });
    }
};
