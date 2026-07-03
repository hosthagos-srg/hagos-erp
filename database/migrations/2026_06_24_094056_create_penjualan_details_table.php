<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penjualan_details', function (Blueprint $table) {
            $table->uuid('detail_id')->primary();
            $table->uuid('internal_id');
            $table->string('sku_id');
            $table->integer('qty')->default(1);
            $table->decimal('harga_satuan', 15, 2)->nullable();
            $table->decimal('subtotal', 15, 2)->nullable();
            $table->decimal('hpp_satuan', 15, 2)->nullable();
            $table->string('batch_bibit')->nullable();
            $table->string('resep_blend')->nullable();
            $table->decimal('margin_satuan', 15, 2)->nullable();
            $table->boolean('flag_custom')->default(false);
            $table->boolean('flag_swap')->default(false);
            $table->timestamps();

            // Setup foreign keys (optional but recommended structurally)
            // Assuming sku_id exists in master_produks and internal_id in penjualan_headers
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penjualan_details');
    }
};
