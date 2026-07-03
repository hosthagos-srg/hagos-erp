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
        Schema::table('master_produks', function (Blueprint $table) {
            $table->integer('stok_t11')->default(0)->after('hpp_botol');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_produks', function (Blueprint $table) {
            $table->dropColumn('stok_t11');
        });
    }
};
