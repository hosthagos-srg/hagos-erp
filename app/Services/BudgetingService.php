<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\AnggaranMingguan;
use App\Models\MutasiKas;

/**
 * Perhitungan anggaran mingguan (amplop ketat, reset tiap awal bulan).
 * Sumber tunggal logika budgeting — dipakai BudgetingController & NotifikasiService.
 */
class BudgetingService
{
    /**
     * Pecah 1 bulan menjadi minggu Senin–Minggu, di-clip ke batas bulan.
     * (Minggu 1 bisa parsial di awal, minggu terakhir bisa parsial di akhir.)
     */
    public function weeksOfMonth(Carbon $monthStart): array
    {
        $monthEnd = $monthStart->copy()->endOfMonth();
        $weeks = [];
        $cursor = $monthStart->copy();
        $no = 1;
        while ($cursor->lte($monthEnd)) {
            $daysToSun = 7 - $cursor->dayOfWeekIso;          // Senin=1..Minggu=7
            $weekEnd = $cursor->copy()->addDays($daysToSun); // Minggu di pekan ini
            if ($weekEnd->gt($monthEnd)) $weekEnd = $monthEnd->copy();
            $weeks[] = ['no' => $no++, 'start' => $cursor->copy(), 'end' => $weekEnd->copy()];
            $cursor = $weekEnd->copy()->addDay()->startOfDay(); // Senin berikutnya
        }
        return $weeks;
    }

    /**
     * Hitung breakdown anggaran untuk 1 bulan.
     * Return: monthStart, weeks, activeIdx, isCurrentMonth, cards[], spendCat,
     *         tanpaAnggaran, sumActive*, countOver.
     */
    public function monthBreakdown(Carbon $monthStart): array
    {
        $monthStart = $monthStart->copy()->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $isCurrentMonth = $monthStart->isSameMonth(Carbon::today());

        $weeks = $this->weeksOfMonth($monthStart);

        // Minggu aktif = minggu yg memuat hari ini (hanya bila melihat bulan berjalan)
        $activeIdx = null;
        if ($isCurrentMonth) {
            $today = Carbon::today();
            foreach ($weeks as $wi => $w) {
                if ($today->betweenIncluded($w['start'], $w['end'])) { $activeIdx = $wi; break; }
            }
        }

        // Ambil semua pengeluaran bulan ini sekali, kelompokkan per kategori × minggu
        $expenses = MutasiKas::where('tipe', 'keluar')->where('kategori', 'pengeluaran')
            ->whereDate('tanggal', '>=', $monthStart->toDateString())
            ->whereDate('tanggal', '<=', $monthEnd->toDateString())
            ->get();

        $spend = [];       // [kategori][weekIdx] => total
        $spendCat = [];    // [kategori] => total bulan
        foreach ($expenses as $e) {
            $cat = trim(explode('·', $e->keterangan)[0]);
            $d = Carbon::parse($e->tanggal)->startOfDay();
            $spendCat[$cat] = ($spendCat[$cat] ?? 0) + (float) $e->jumlah;
            foreach ($weeks as $wi => $w) {
                if ($d->betweenIncluded($w['start'], $w['end'])) {
                    $spend[$cat][$wi] = ($spend[$cat][$wi] ?? 0) + (float) $e->jumlah;
                    break;
                }
            }
        }

        $budgets = AnggaranMingguan::where('aktif', true)->orderBy('kategori')->get();

        $cards = [];
        $sumActiveTersedia = 0; $sumActiveTerpakai = 0; $sumActiveSisa = 0; $countOver = 0;

        foreach ($budgets as $bg) {
            $jatah = (float) $bg->jumlah_mingguan;
            $carry = 0.0; // reset awal bulan
            $rows = [];
            foreach ($weeks as $wi => $w) {
                $tersedia = $jatah + $carry;
                $terpakai = (float) ($spend[$bg->kategori][$wi] ?? 0);
                $sisa = $tersedia - $terpakai;
                $rows[] = [
                    'no'       => $w['no'],
                    'start'    => $w['start'],
                    'end'      => $w['end'],
                    'jatah'    => $jatah,
                    'carryIn'  => $carry,
                    'tersedia' => $tersedia,
                    'terpakai' => $terpakai,
                    'sisa'     => $sisa,
                    'isActive' => ($wi === $activeIdx),
                    'over'     => $sisa < 0,
                ];
                $carry = $sisa; // amplop ketat: sisa negatif ikut minggu depan
            }

            $focusIdx = $activeIdx ?? (count($rows) - 1);
            $focus = $rows[$focusIdx] ?? null;
            if ($focus) {
                $sumActiveTersedia += $focus['tersedia'];
                $sumActiveTerpakai += $focus['terpakai'];
                $sumActiveSisa     += $focus['sisa'];
                if ($focus['over']) $countOver++;
            }

            $cards[] = [
                'id'       => $bg->id,
                'kategori' => $bg->kategori,
                'jatah'    => $jatah,
                'catatan'  => $bg->catatan,
                'weeks'    => $rows,
                'focus'    => $focus,
            ];
        }

        $budgetedCats = $budgets->pluck('kategori')->all();
        $tanpaAnggaran = collect($spendCat)
            ->reject(fn($v, $k) => in_array($k, $budgetedCats) || $k === '')
            ->sortDesc();

        return compact(
            'monthStart', 'weeks', 'activeIdx', 'isCurrentMonth', 'cards',
            'spendCat', 'tanpaAnggaran',
            'sumActiveTersedia', 'sumActiveTerpakai', 'sumActiveSisa', 'countOver'
        );
    }

    /**
     * Kategori yang OVER budget pada minggu berjalan (untuk notifikasi lonceng).
     * Return array of ['kategori','tersedia','terpakai','sisa','no'].
     */
    public function overBudgetCurrentWeek(): array
    {
        $bd = $this->monthBreakdown(Carbon::today()->startOfMonth());
        if ($bd['activeIdx'] === null) return [];

        $over = [];
        foreach ($bd['cards'] as $c) {
            $f = $c['focus'];
            if ($f && $f['sisa'] < 0) {
                $over[] = [
                    'kategori' => $c['kategori'],
                    'tersedia' => $f['tersedia'],
                    'terpakai' => $f['terpakai'],
                    'sisa'     => $f['sisa'],
                    'no'       => $f['no'],
                ];
            }
        }
        return $over;
    }
}
