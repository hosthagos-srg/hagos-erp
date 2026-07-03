<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PenjualanHeader;
use App\Models\PenjualanDetail;

/**
 * Sembuhkan pesanan yang "nyangkut": status masih 'Menunggu' padahal SEMUA
 * barisnya sudah diracik (hpp_satuan terisi). Bisa terjadi bila peracikan
 * dilakukan bertahap/terputus. Tandai 'Selesai Racik' agar masuk alur normal.
 * Aman dijalankan berulang (idempoten).
 */
class HealStatusRacik extends Command
{
    protected $signature = 'racik:heal-status {--dry : Hanya tampilkan, tanpa mengubah}';
    protected $description = 'Tandai Selesai Racik untuk pesanan Menunggu yang semua barisnya sudah diracik';

    public function handle(): int
    {
        $headers = PenjualanHeader::where('status_pesanan', 'Menunggu')->get();
        $fixed = 0;

        foreach ($headers as $h) {
            $total = PenjualanDetail::where('internal_id', $h->internal_id)->count();
            if ($total === 0) continue; // tak ada baris → lewati
            $pending = PenjualanDetail::where('internal_id', $h->internal_id)
                ->whereNull('hpp_satuan')->count();
            if ($pending > 0) continue; // masih ada yang belum diracik → biarkan

            $order = $h->external_order_id ?: ('INV-' . strtoupper(substr($h->internal_id, 0, 8)));
            $this->line("  [{$h->channel}] {$order} — {$total} baris semua sudah diracik → Selesai Racik");

            if (!$this->option('dry')) {
                $h->status_pesanan = 'Selesai Racik';
                if (empty($h->tgl_racik)) $h->tgl_racik = now()->toDateString();
                $h->save();
            }
            $fixed++;
        }

        if ($fixed === 0) {
            $this->info('Tidak ada pesanan nyangkut. Semua status sudah benar.');
        } else {
            $this->info(($this->option('dry') ? 'DRY-RUN: ' : 'Selesai: ') . "{$fixed} pesanan " . ($this->option('dry') ? 'akan diperbaiki.' : 'diperbaiki.'));
        }
        return self::SUCCESS;
    }
}
