<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('karyawan', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('posisi')->nullable();
            $table->decimal('gaji_pokok', 15, 2)->default(0);
            $table->string('no_hp')->nullable();
            $table->date('tgl_masuk')->nullable();
            $table->string('status')->default('Aktif'); // Aktif | Nonaktif
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('kasbon', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karyawan_id')->constrained('karyawan')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('tipe'); // 'kasbon' (ambil/utang naik) | 'bayar' (lunasi/utang turun)
            $table->decimal('jumlah', 15, 2);
            $table->string('metode')->nullable();  // bayar: 'Tunai' | 'Potong Gaji'
            $table->string('akun')->nullable();     // akun kas terkait
            $table->unsignedBigInteger('gaji_id')->nullable(); // jika bayar via potong gaji
            $table->string('keterangan')->nullable();
            $table->string('dicatat_oleh')->nullable();
            $table->timestamps();
        });

        Schema::create('gaji', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karyawan_id')->constrained('karyawan')->cascadeOnDelete();
            $table->string('periode'); // mis. "Juni 2026"
            $table->date('tanggal_bayar');
            $table->decimal('gaji_pokok', 15, 2)->default(0);
            $table->decimal('tunjangan', 15, 2)->default(0);
            $table->decimal('potongan_kasbon', 15, 2)->default(0);
            $table->decimal('potongan_lain', 15, 2)->default(0);
            $table->decimal('gaji_bersih', 15, 2)->default(0);
            $table->string('akun')->nullable();
            $table->string('dicatat_oleh')->nullable();
            $table->string('catatan')->nullable();
            $table->timestamps();
        });

        // Impor nama admin yang ada sebagai karyawan awal
        $admins = DB::table('master_kategoris')->where('tipe_kategori', 'Admin')->pluck('nilai');
        foreach ($admins as $nama) {
            DB::table('karyawan')->insert([
                'nama' => $nama, 'posisi' => 'Admin/Operasional', 'status' => 'Aktif',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gaji');
        Schema::dropIfExists('kasbon');
        Schema::dropIfExists('karyawan');
    }
};
