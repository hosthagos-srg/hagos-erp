<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sumber_dana', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->unsignedTinyInteger('jatuh_tempo_tgl'); // 1-31
            $table->timestamps();
        });

        // Seed default
        DB::table('sumber_dana')->insert([
            ['nama' => 'Kartu Kredit BRI', 'jatuh_tempo_tgl' => 25, 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'Spaylater', 'jatuh_tempo_tgl' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'Kartu Kredit BNI', 'jatuh_tempo_tgl' => 3, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('sumber_dana');
    }
};
