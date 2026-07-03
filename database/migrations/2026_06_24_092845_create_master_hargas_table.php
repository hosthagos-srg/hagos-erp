<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_hargas', function (Blueprint $table) {
            $table->string('harga_id')->primary();
            $table->string('sku_id')->nullable();
            $table->string('channel')->nullable();
            $table->string('harga_jual')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_hargas');
    }
};
