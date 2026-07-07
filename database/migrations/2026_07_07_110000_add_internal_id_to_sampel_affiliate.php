<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sampel_affiliate', function (Blueprint $table) {
            // Tautan ke pesanan "Gratis" yang masuk antrean racik (potong stok saat diracik).
            $table->string('internal_id')->nullable()->after('sku_id');
        });
    }

    public function down(): void
    {
        Schema::table('sampel_affiliate', function (Blueprint $table) {
            $table->dropColumn('internal_id');
        });
    }
};
