<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\MasterBibit;
use App\Models\CicilanPembayaran;
use App\Models\PenjualanHeader;
use App\Services\BudgetingService;

/**
 * Mengumpulkan semua notifikasi sistem (cicilan jatuh tempo, settlement perlu cek,
 * stok bibit perlu beli) untuk lonceng di sidebar.
 */
class NotifikasiService
{
    public function get(): array
    {
        $groups = [];

        // ── 1. Cicilan jatuh tempo (H-3 / terlambat, belum dibayar) ──
        $today = Carbon::today();
        $batas = $today->copy()->addDays(3);
        $cicilan = CicilanPembayaran::with('utangCicilan.sumberDana')
            ->where('status', 'belum')
            ->whereDate('periode', '<=', $batas)
            ->orderBy('periode')
            ->get();

        if ($cicilan->isNotEmpty()) {
            $groups[] = [
                'key'   => 'cicilan',
                'icon'  => '💳',
                'label' => 'Cicilan Jatuh Tempo',
                'color' => 'red',
                'url'   => route('utang.index'),
                'count' => $cicilan->count(),
                'items' => $cicilan->map(function ($c) use ($today) {
                    $periode = Carbon::parse($c->periode);
                    $selisih = $today->diffInDays($periode, false);
                    $label = $selisih < 0 ? 'Terlambat ' . abs($selisih) . 'h' : ($selisih === 0 ? 'Hari ini' : 'H-' . $selisih);
                    $nama = $c->utangCicilan->sumberDana->nama ?? '';
                    return [
                        'text'   => "[{$label}] {$nama} — Rp " . number_format($c->jumlah_tagihan, 0, ',', '.'),
                        'urgent' => $selisih <= 0,
                    ];
                })->toArray(),
            ];
        }

        // ── 2. Settlement marketplace perlu dicek ──
        $settlement = PenjualanHeader::where('channel', 'like', 'Marketplace%')
            ->where('status_pembayaran', '!=', 'Cair')
            ->where('status_pesanan', '!=', 'Batal')
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereNull('tgl_dicek')->whereDate('tgl_pesanan', '<=', now()->subDays(12));
                })->orWhere(function ($q2) {
                    $q2->whereNotNull('tgl_dicek')->whereDate('tgl_dicek', '<=', now()->subDays(3));
                });
            })
            ->orderBy('tgl_pesanan')
            ->get();

        if ($settlement->isNotEmpty()) {
            $groups[] = [
                'key'   => 'settlement',
                'icon'  => '📦',
                'label' => 'Pesanan Perlu Dicek',
                'color' => 'amber',
                'url'   => route('penjualan.index'),
                'count' => $settlement->count(),
                'items' => $settlement->map(function ($p) {
                    $umur = Carbon::parse($p->tgl_pesanan)->diffInDays(now());
                    $cek = $p->jumlah_dicek > 0 ? " · dicek {$p->jumlah_dicek}×" : '';
                    return [
                        'text'   => ($p->external_order_id ?? $p->internal_id) . " · {$p->channel} · {$umur} hari{$cek}",
                        'urgent' => false,
                    ];
                })->toArray(),
            ];
        }

        // ── 3. Stok bibit di bawah threshold ──
        $stok = MasterBibit::whereColumn('stok_ml', '<=', 'threshold_ml')
            ->orderBy('nama_bibit')->get();

        if ($stok->isNotEmpty()) {
            $groups[] = [
                'key'   => 'stok',
                'icon'  => '🌿',
                'label' => 'Stok Bibit Perlu Beli',
                'color' => 'orange',
                'url'   => route('stok.index'),
                'count' => $stok->count(),
                'items' => $stok->map(fn($b) => [
                    'text'   => ($b->sku_aroma ?? $b->bibit_id) . ' · ' . $b->nama_bibit . ' — sisa ' . rtrim(rtrim(number_format($b->stok_ml, 2, ',', '.'), '0'), ',') . 'ml',
                    'urgent' => true,
                ])->toArray(),
            ];
        }

        // ── 4. Stok komponen di bawah threshold (yang dipantau) ──
        $komponen = \App\Models\MasterKomponen::where('threshold', '>', 0)
            ->whereColumn('stok', '<=', 'threshold')
            ->orderBy('nama_komponen')->get();

        if ($komponen->isNotEmpty()) {
            $groups[] = [
                'key'   => 'komponen',
                'icon'  => '📦',
                'label' => 'Stok Komponen Perlu Beli',
                'color' => 'orange',
                'url'   => route('stok.index'),
                'count' => $komponen->count(),
                'items' => $komponen->map(fn($k) => [
                    'text'   => $k->komponen_id . ' · ' . $k->nama_komponen . ' — sisa ' . rtrim(rtrim(number_format($k->stok, 2, ',', '.'), '0'), ',') . ' ' . $k->satuan,
                    'urgent' => true,
                ])->toArray(),
            ];
        }

        // ── 5. Budget over (minggu berjalan) ──
        $over = app(BudgetingService::class)->overBudgetCurrentWeek();
        if (!empty($over)) {
            $groups[] = [
                'key'   => 'budget',
                'icon'  => '🎯',
                'label' => 'Budget Over',
                'color' => 'red',
                'url'   => route('budgeting.index'),
                'count' => count($over),
                'items' => array_map(fn($o) => [
                    'text'   => $o['kategori'] . ' — lewat Rp ' . number_format(abs($o['sisa']), 0, ',', '.')
                              . ' (pakai Rp ' . number_format($o['terpakai'], 0, ',', '.')
                              . ' / jatah Rp ' . number_format($o['tersedia'], 0, ',', '.') . ')',
                    'urgent' => true,
                ], $over),
            ];
        }

        $total = collect($groups)->sum('count');

        return ['total' => $total, 'groups' => $groups];
    }
}
