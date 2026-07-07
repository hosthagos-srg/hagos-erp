<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('belanja_details', function (Blueprint $table) {
            // Penerimaan PER ITEM: Menunggu → Diterima (masuk stok) / Retur (proses) → Retur Selesai (refund masuk)
            $table->string('status_terima', 20)->default('Menunggu')->after('stok_sisa');
            $table->date('tgl_terima')->nullable()->after('status_terima');
            $table->string('catatan_terima', 255)->nullable()->after('tgl_terima'); // alasan retur, dll
            $table->string('resi_retur', 100)->nullable()->after('catatan_terima');  // resi kirim balik ke supplier
            $table->date('tgl_refund')->nullable()->after('resi_retur');
            $table->decimal('nilai_refund', 15, 2)->nullable()->after('tgl_refund');
        });

        // Backfill data lama: belanja yang stoknya sudah diterapkan → semua itemnya Diterima.
        DB::statement("
            UPDATE belanja_details d
            JOIN belanja_headers h ON h.belanja_id = d.belanja_id
            SET d.status_terima = 'Diterima',
                d.tgl_terima = h.tgl_belanja
            WHERE h.stok_diterapkan = 1
        ");
    }

    public function down(): void
    {
        Schema::table('belanja_details', function (Blueprint $table) {
            $table->dropColumn(['status_terima', 'tgl_terima', 'catatan_terima', 'resi_retur', 'tgl_refund', 'nilai_refund']);
        });
    }
};
