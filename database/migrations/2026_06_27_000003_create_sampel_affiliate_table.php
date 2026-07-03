<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sampel_affiliate', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('platform');          // TikTok | Shopee | Lainnya
            $table->string('nama_affiliate');
            $table->string('sku_id');
            $table->integer('qty');
            $table->decimal('hpp_satuan', 15, 2)->default(0);
            $table->decimal('total_hpp', 15, 2)->default(0);
            $table->string('catatan')->nullable();
            $table->string('dicatat_oleh')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sampel_affiliate');
    }
};
