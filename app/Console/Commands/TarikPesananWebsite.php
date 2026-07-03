<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WebsiteOrderService;

class TarikPesananWebsite extends Command
{
    protected $signature = 'website:tarik {--dry : Hanya login + tampilkan bentuk response, tanpa menyimpan}';
    protected $description = 'Tarik pesanan (sudah dibayar) dari API website hagosperfume.com ke ERP';

    public function handle(WebsiteOrderService $svc): int
    {
        $this->info('Login ke website...');
        try {
            $svc->login();
            $this->info('OK Login berhasil, token diterima.');
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info('Mengambil pesanan (status=paid)...');
        try {
            $data = $svc->getOrders(['status' => 'paid']);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        if ($this->option('dry')) {
            // Mode dry: tampilkan STRUKTUR response supaya kita tahu cara memetakannya.
            $this->line('');
            $this->info('=== STRUKTUR RESPONSE (dry-run) ===');
            $this->line('Tipe root: ' . gettype($data));
            $this->line('Key root: ' . (is_array($data) ? implode(', ', array_keys($data)) : '-'));

            $list = $data;
            foreach (['data', 'orders', 'items', 'result'] as $k) {
                if (isset($data[$k]) && is_array($data[$k])) { $list = $data[$k]; break; }
            }
            $first = is_array($list) ? ($list[0] ?? null) : null;
            $this->line('Jumlah pesanan: ' . (is_array($list) ? count($list) : 'n/a'));
            $this->line('');
            $this->info('Contoh 1 pesanan (JSON):');
            $this->line(json_encode($first, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $this->line('');
            $this->comment('Salin JSON di atas & kirim ke asisten untuk membangun mapper + auto-import.');
            return self::SUCCESS;
        }

        $this->warn('Import otomatis belum aktif. Jalankan dengan --dry dulu untuk menangkap struktur response.');
        return self::SUCCESS;
    }
}
