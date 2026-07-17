<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WebsiteOrderService;

/**
 * Tarik pesanan (sudah dibayar) dari API website -> buat pesanan ERP (antrean racik).
 * Filter dilakukan di sisi ERP berdasar payment_status='paid' (bukan param query,
 * karena field `status` di API adalah status pengiriman, mis. on_delivery).
 */
class TarikPesananWebsite extends Command
{
    protected $signature = 'website:tarik {--dry : Login + tampilkan yang AKAN diimpor, tanpa menyimpan} {--no-detail : Lewati pengambilan detail per pesanan}';
    protected $description = 'Tarik pesanan paid dari API website hagosperfume.com ke ERP';

    public function handle(WebsiteOrderService $svc): int
    {
        $this->info('Login ke website...');
        try { $svc->login(); $this->info('OK login, token diterima.'); }
        catch (\Throwable $e) { $this->error($e->getMessage()); return self::FAILURE; }

        $this->info('Mengambil daftar pesanan...');
        try { $list = $svc->getAllOrders(); }
        catch (\Throwable $e) { $this->error($e->getMessage()); return self::FAILURE; }

        $this->line('Total pesanan diterima: ' . count($list));

        $dumpedDetail = false;
        $stat = ['created' => 0, 'dup' => 0, 'skip_unpaid' => 0, 'skip_no_id' => 0, 'skip_unmatched' => 0, 'error' => 0];
        $unmatchedList = [];

        foreach ($list as $o) {
            $m = $svc->mapToErpOrder($o);

            // Hanya proses yang sudah dibayar
            if (!$m['paid']) { $stat['skip_unpaid']++; continue; }

            // Lengkapi dari detail (buyer/alamat/ongkir/resi)
            if (!$this->option('no-detail') && !empty($m['website_id'])) {
                try {
                    $detail = $svc->getOrderDetail((string) $m['website_id']);
                    if ($this->option('dry') && !$dumpedDetail && !empty($detail)) {
                        $this->line("\n=== CONTOH JSON DETAIL (order {$m['external_order_id']}) ===");
                        $this->line(json_encode($detail, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                        $this->line("=== akhir contoh detail ===\n");
                        $dumpedDetail = true;
                    }
                    $m = $svc->mergeDetail($m, $detail);
                } catch (\Throwable $e) {
                    $this->warn("  Detail {$m['external_order_id']} gagal diambil: " . $e->getMessage());
                }
            }

            if (!empty($m['unmatched'])) $unmatchedList[$m['external_order_id']] = $m['unmatched'];

            if ($this->option('dry')) {
                $items = implode(', ', array_map(fn($i) => ($i['sku_id'] ?? '?') . '×' . $i['qty'] . ($i['matched'] ? '' : '⚠'), $m['items']));
                $sub = array_sum(array_map(fn($i) => $i['harga_satuan'] * $i['qty'], array_filter($m['items'], fn($i) => $i['matched'])));
                $this->line(sprintf('  [%s] %s | %s | Rp%s | %s',
                    empty($m['unmatched']) ? 'OK ' : 'REVIEW', $m['external_order_id'],
                    $m['buyer']['nama'] ?: '(tanpa nama)', number_format($sub, 0, ',', '.'), $items));
                continue;
            }

            try { $stat[$svc->simpanKeErp($m)]++; }
            catch (\Throwable $e) { $stat['error']++; $this->warn("  Gagal simpan {$m['external_order_id']}: " . $e->getMessage()); }
        }

        $this->line('');
        $this->info($this->option('dry') ? '=== RINGKASAN (DRY-RUN, tidak menyimpan) ===' : '=== HASIL IMPOR ===');
        foreach ($stat as $k => $v) if ($v > 0) $this->line("  $k : $v");
        if (!empty($unmatchedList)) {
            $this->warn('  Pesanan dgn SKU tak dikenal (dilewati sampai SKU dilengkapi):');
            foreach ($unmatchedList as $ord => $u) $this->line("    - $ord: " . implode('; ', $u));
        }
        if ($this->option('dry')) $this->comment('Jalankan tanpa --dry untuk benar-benar mengimpor.');

        return self::SUCCESS;
    }
}
