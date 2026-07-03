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
        Schema::table('penjualan_headers', function (Blueprint $table) {
            $table->string('no_resi')->nullable()->after('external_order_id');
            $table->index('no_resi');
        });
    }

    public function down(): void
    {
        Schema::table('penjualan_headers', function (Blueprint $table) {
            $table->dropIndex(['no_resi']);
            $table->dropColumn('no_resi');
        });
    }
};
