<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_komponens', function (Blueprint $table) {
            $table->double('threshold')->default(0)->after('stok');
        });
    }

    public function down(): void
    {
        Schema::table('master_komponens', function (Blueprint $table) {
            $table->dropColumn('threshold');
        });
    }
};
