<?php

namespace Database\Seeders;

use App\Models\MasterBibit;
use App\Models\MasterProduk;
use App\Models\MasterResep;
use App\Models\MasterHarga;
use App\Models\MasterKomponen;
use App\Models\MasterAkunKas;
use App\Models\MasterKategori;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Urutan seed sesuai dependensi Foreign Key
        $this->seedCsv('HAGOS_Master_Data (3) - M1_Bibit (1).csv', MasterBibit::class, 'bibit_id');
        $this->seedCsv('HAGOS_Master_Data (3) - M5_Komponen.csv', MasterKomponen::class, 'komponen_id');
        $this->seedCsv('HAGOS_Master_Data (3) - M2_Produk.csv', MasterProduk::class, 'sku_id');
        $this->seedCsv('HAGOS_Master_Data (3) - M3_Resep.csv', MasterResep::class, 'resep_id');
        $this->seedCsv('HAGOS_Master_Data (3) - M4_Harga.csv', MasterHarga::class, 'harga_id');
        $this->seedCsv('HAGOS_Master_Data (3) - M6_AkunKas.csv', MasterAkunKas::class, 'akun_id');
        $this->seedCsv('HAGOS_Master_Data (3) - M7_Kategori.csv', MasterKategori::class, null);
    }

    private function seedCsv($filename, $modelClass, $primaryKey)
    {
        $path = base_path("Seed Data/" . $filename);
        if (!file_exists($path)) {
            $this->command->error("File tidak ditemukan: " . $path);
            return;
        }

        $file = fopen($path, 'r');
        
        // Skip 3 baris pertama
        fgetcsv($file, 0, ',');
        fgetcsv($file, 0, ',');
        fgetcsv($file, 0, ',');

        // Baca header di baris ke-4
        $header = fgetcsv($file, 0, ',');
        
        // Bersihkan header dari BOM (Byte Order Mark) atau spasi
        $header = array_map(function($col) {
            $col = preg_replace('/^\xEF\xBB\xBF/', '', $col);
            return trim($col);
        }, $header);

        if (!$header || count($header) < 2) {
            $this->command->error("Gagal membaca header untuk " . $filename . " (Cek apakah delimiter menggunakan koma)");
            return;
        }

        $count = 0;
        while (($row = fgetcsv($file, 0, ',')) !== false) {
            // Abaikan baris kosong
            if (empty(array_filter($row))) {
                continue;
            }

            // Gabungkan header dan row
            $rowData = [];
            foreach ($header as $index => $colName) {
                if (empty($colName)) continue; // Abaikan kolom tanpa header
                
                $val = isset($row[$index]) ? trim($row[$index]) : null;
                // Jika kosong, ubah jadi null agar tidak insert string kosong untuk integer
                $rowData[$colName] = $val === '' ? null : $val;
            }
            
            if ($primaryKey) {
                if (empty($rowData[$primaryKey])) {
                    continue; // Skip kalau PK kosong
                }
                $modelClass::updateOrCreate(
                    [$primaryKey => $rowData[$primaryKey]],
                    $rowData
                );
            } else {
                $modelClass::create($rowData);
            }
            
            $count++;
        }
        
        fclose($file);
        $this->command->info("Berhasil import {$count} baris ke " . class_basename($modelClass));
    }
}
