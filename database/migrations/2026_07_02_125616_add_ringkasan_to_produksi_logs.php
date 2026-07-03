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
        Schema::table('produksi_logs', function (Blueprint $table) {
            $table->string('ringkasan')->nullable()->after('tipe');
        });
    }

    public function down(): void
    {
        Schema::table('produksi_logs', function (Blueprint $table) {
            $table->dropColumn('ringkasan');
        });
    }
};
