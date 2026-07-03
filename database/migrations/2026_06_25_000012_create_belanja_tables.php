<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Belanja Bibit (T2) & Komponen (T3) — struktur sama (header-detail).
 * Stok naik + weighted-average HPP hanya saat status 'Diterima'.
 * Alokasi voucher+ongkir+biaya layanan proporsional -> harga_net_per_unit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('belanja_headers', function (Blueprint $table) {
            $table->string('belanja_id')->primary();
            $table->string('jenis');                 // 'bibit' | 'komponen'
            $table->date('tgl_belanja');
            $table->string('supplier_toko')->nullable();
            $table->string('platform_beli')->nullable();
            $table->string('status_belanja')->default('Dipesan'); // Dipesan/Dikirim/Diterima/Dibatalkan
            $table->string('no_resi')->nullable();
            $table->string('kurir')->nullable();
            $table->decimal('subtotal_kotor', 18, 2)->default(0);
            $table->decimal('voucher_nominal', 18, 2)->default(0);
            $table->decimal('ongkir_net', 18, 2)->default(0);
            $table->decimal('biaya_layanan', 18, 2)->default(0);
            $table->decimal('total_bayar', 18, 2)->default(0);
            $table->string('akun_bayar')->nullable();
            $table->boolean('stok_diterapkan')->default(false); // sudah dinaikkan ke stok? (anti dobel)
            $table->timestamps();
        });

        Schema::create('belanja_details', function (Blueprint $table) {
            $table->string('batch_id')->primary();   // bibit: bibit-tgl-urut; komponen: uuid pendek
            $table->string('belanja_id');
            $table->string('item_id');                // bibit_id / komponen_id
            $table->decimal('qty', 15, 2);            // ml (bibit) / pcs (komponen)
            $table->decimal('harga_total_item', 18, 2);
            $table->decimal('harga_net_per_unit', 15, 4)->default(0); // setelah alokasi
            $table->decimal('stok_sisa', 15, 2)->default(0);          // traceability batch
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('belanja_details');
        Schema::dropIfExists('belanja_headers');
    }
};
