<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HPP rata-rata bergerak stok produk jadi (T11) — nilai "botol telanjang"
 * (bibit + absolute + botol + sticker). Box/kartu/tester/Lapis 2 TIDAK termasuk;
 * itu ditambahkan segar saat botol dijual lagi (repack).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_produks', function (Blueprint $table) {
            $table->decimal('hpp_t11', 15, 2)->default(0)->after('stok_t11');
        });
    }

    public function down(): void
    {
        Schema::table('master_produks', function (Blueprint $table) {
            $table->dropColumn('hpp_t11');
        });
    }
};
