<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Konversi kolom angka yang sebelumnya disimpan sebagai VARCHAR berformat lokal
 * (mis. "1.800" = 1800, "51,5" = 51.5, "122.5" = 122.5) menjadi tipe numerik DECIMAL/INT.
 *
 * Langkah:
 *   1. Normalisasi setiap nilai string ke literal numerik kanonik (titik = desimal, tanpa pemisah ribuan).
 *   2. ALTER tipe kolom ke DECIMAL/INT.
 *
 * Asumsi normalisasi (aman untuk dataset HAGOS): harga = rupiah bulat (ribuan pakai titik/koma),
 * nilai ml maksimal 1-2 angka desimal. Tidak ada angka dengan 3 angka desimal asli
 * (karena grup 3 digit setelah pemisah dianggap pemisah ribuan).
 */
return new class extends Migration
{
    /**
     * Ubah string berformat lokal -> literal numerik kanonik (string), atau null jika kosong.
     */
    private function normalize($raw): ?string
    {
        if ($raw === null) return null;
        $s = trim((string) $raw);
        if ($s === '') return null;

        // Buang semua kecuali digit, titik, koma, minus
        $s = preg_replace('/[^0-9.,\-]/', '', $s);
        if ($s === '' || $s === '-') return null;

        $neg = strpos($s, '-') !== false;
        $s = str_replace('-', '', $s);

        $lastDot = strrpos($s, '.');
        $lastComma = strrpos($s, ',');

        if ($lastDot !== false && $lastComma !== false) {
            if ($lastDot > $lastComma) {
                // titik = desimal, koma = ribuan  -> "1,234.56"
                $s = str_replace(',', '', $s);
            } else {
                // koma = desimal, titik = ribuan  -> "1.234,56"
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            }
        } elseif ($lastComma !== false) {
            $parts = explode(',', $s);
            if (strlen(end($parts)) === 3) {
                $s = str_replace(',', '', $s);      // ribuan: "1,800" -> 1800
            } else {
                $s = str_replace(',', '.', $s);     // desimal: "51,5" -> 51.5
            }
        } elseif ($lastDot !== false) {
            $parts = explode('.', $s);
            if (strlen(end($parts)) === 3) {
                $s = str_replace('.', '', $s);      // ribuan: "1.800" -> 1800
            }
            // selain itu biarkan sebagai desimal: "122.5"
        }

        if ($s === '' || $s === '.') return null;
        return ($neg ? '-' : '') . $s;
    }

    private array $map = [
        'master_bibits'   => ['pk' => 'bibit_id',   'cols' => ['harga_per_ml', 'stok_ml', 'threshold_ml', 'masuk_ml', 'jual_ml', 'tester_ml', 'stok_awal', 'nilai_masuk', 'harga_awal']],
        'master_komponens'=> ['pk' => 'komponen_id','cols' => ['harga_satuan']],
        'master_reseps'   => ['pk' => 'resep_id',   'cols' => ['ml_bibit_utama', 'ml_absolute', 'jml_tester']],
        'master_hargas'   => ['pk' => 'harga_id',   'cols' => ['harga_jual']],
        'master_produks'  => ['pk' => 'sku_id',     'cols' => ['hpp_botol', 'ukuran_ml']],
    ];

    public function up(): void
    {
        // 1. Normalisasi data lama
        foreach ($this->map as $table => $info) {
            foreach (DB::table($table)->get() as $row) {
                $upd = [];
                foreach ($info['cols'] as $c) {
                    $upd[$c] = $this->normalize($row->$c ?? null);
                }
                DB::table($table)->where($info['pk'], $row->{$info['pk']})->update($upd);
            }
        }

        // 2. Ubah tipe kolom
        DB::statement("ALTER TABLE master_bibits
            MODIFY harga_per_ml DECIMAL(15,2) NULL,
            MODIFY stok_ml      DECIMAL(15,2) NULL,
            MODIFY threshold_ml DECIMAL(15,2) NULL,
            MODIFY masuk_ml     DECIMAL(15,2) NULL,
            MODIFY jual_ml      DECIMAL(15,2) NULL,
            MODIFY tester_ml    DECIMAL(15,2) NULL,
            MODIFY stok_awal    DECIMAL(15,2) NULL,
            MODIFY nilai_masuk  DECIMAL(18,2) NULL,
            MODIFY harga_awal   DECIMAL(15,2) NULL");

        DB::statement("ALTER TABLE master_komponens MODIFY harga_satuan DECIMAL(15,2) NULL DEFAULT 0");
        DB::statement("ALTER TABLE master_reseps
            MODIFY ml_bibit_utama DECIMAL(10,2) NULL,
            MODIFY ml_absolute    DECIMAL(10,2) NULL,
            MODIFY jml_tester     DECIMAL(8,2)  NULL");
        DB::statement("ALTER TABLE master_hargas MODIFY harga_jual DECIMAL(15,2) NULL");
        DB::statement("ALTER TABLE master_produks
            MODIFY hpp_botol DECIMAL(15,2) NULL,
            MODIFY ukuran_ml INT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE master_bibits
            MODIFY harga_per_ml VARCHAR(255) NULL,
            MODIFY stok_ml      VARCHAR(255) NULL,
            MODIFY threshold_ml VARCHAR(255) NULL,
            MODIFY masuk_ml     VARCHAR(255) NULL,
            MODIFY jual_ml      VARCHAR(255) NULL,
            MODIFY tester_ml    VARCHAR(255) NULL,
            MODIFY stok_awal    VARCHAR(255) NULL,
            MODIFY nilai_masuk  VARCHAR(255) NULL,
            MODIFY harga_awal   VARCHAR(255) NULL");
        DB::statement("ALTER TABLE master_komponens MODIFY harga_satuan VARCHAR(255) NULL");
        DB::statement("ALTER TABLE master_reseps
            MODIFY ml_bibit_utama VARCHAR(255) NULL,
            MODIFY ml_absolute    VARCHAR(255) NULL,
            MODIFY jml_tester     VARCHAR(255) NULL");
        DB::statement("ALTER TABLE master_hargas MODIFY harga_jual VARCHAR(255) NULL");
        DB::statement("ALTER TABLE master_produks
            MODIFY hpp_botol VARCHAR(255) NULL,
            MODIFY ukuran_ml VARCHAR(255) NULL");
    }
};
