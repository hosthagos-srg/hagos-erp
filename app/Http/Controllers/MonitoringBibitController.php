<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\MasterBibit;

/**
 * Monitoring Harga Bibit. Bibit = ~70% HPP, jadi tujuannya bukan sekadar lihat harga:
 *  1) Deteksi DRIFT — harga master (dipakai HPP) vs harga beli TERAKHIR. Kalau master lebih
 *     rendah dari harga beli → HPP kemurahan → margin diam-diam bocor. Ini yang paling penting.
 *  2) Riwayat & tren per bibit — semua pembelian (harga/ml, supplier, tanggal) + naik/turun.
 */
class MonitoringBibitController extends Controller
{
    public function index()
    {
        $bibits = MasterBibit::orderBy('nama_bibit')->get();

        // Riwayat pembelian bibit (item_id BIB%) terbaru → terlama.
        $belanja = DB::table('belanja_details as d')
            ->join('belanja_headers as h', 'h.belanja_id', '=', 'd.belanja_id')
            ->where('d.item_id', 'like', 'BIB%')
            ->orderByDesc('h.tgl_belanja')->orderByDesc('d.created_at')
            ->get(['d.item_id', 'd.qty', 'd.harga_net_per_unit', 'h.tgl_belanja', 'h.supplier_toko']);
        $histByBibit = $belanja->groupBy('item_id');

        $rank = ['under' => 0, 'over' => 1, 'sync' => 2, 'no_data' => 3];
        $rows = $bibits->map(function ($b) use ($histByBibit, $rank) {
            $hist   = $histByBibit->get($b->bibit_id, collect());
            $last   = $hist->first(); // terbaru (sudah desc)
            $master = (float) $b->harga_per_ml;
            $beli   = $last ? (float) $last->harga_net_per_unit : null;
            $drift  = ($beli && $beli > 0) ? ($master - $beli) / $beli * 100 : null;

            if ($beli === null)          $status = 'no_data';
            elseif (abs($drift) < 2)     $status = 'sync';   // selisih < 2% → dianggap sinkron
            elseif ($master < $beli)     $status = 'under';  // HPP di bawah harga beli → margin bocor
            else                         $status = 'over';   // HPP di atas harga beli → HPP kemahalan

            // Riwayat + tren (bandingkan tiap pembelian ke yang lebih lama)
            $listAsc = $hist->sortBy('tgl_belanja')->values();
            $riwayat = [];
            $prev = null;
            foreach ($listAsc as $x) {
                $h = (float) $x->harga_net_per_unit;
                $delta = $prev !== null && $prev > 0 ? ($h - $prev) / $prev * 100 : null;
                $riwayat[] = ['tgl' => $x->tgl_belanja, 'harga' => $h, 'supplier' => $x->supplier_toko, 'qty' => (float) $x->qty, 'delta' => $delta];
                $prev = $h;
            }
            $riwayat = array_reverse($riwayat); // tampil terbaru dulu

            return (object) [
                'bibit_id'  => $b->bibit_id,
                'nama'      => $b->nama_bibit,
                'merek'     => $b->merek_bibit,
                'master'    => $master,
                'stok'      => (float) $b->stok_ml,
                'nilai'     => (float) $b->stok_ml * $master,
                'beli'      => $beli,
                'beli_tgl'  => $last->tgl_belanja ?? null,
                'supplier'  => $last->supplier_toko ?? null,
                'drift'     => $drift,
                'status'    => $status,
                'rank'      => $rank[$status],
                'n_beli'    => $hist->count(),
                'riwayat'   => $riwayat,
            ];
        })
        ->sortBy([['rank', 'asc'], ['nilai', 'desc']])
        ->values();

        $jmlUnder = $rows->where('status', 'under')->count();
        $jmlOver  = $rows->where('status', 'over')->count();
        $jmlSync  = $rows->where('status', 'sync')->count();
        $nilaiUnder = (float) $rows->where('status', 'under')->sum('nilai');
        $nilaiTotal = (float) $rows->sum('nilai');

        return view('laporan.monitoring_bibit', compact('rows', 'jmlUnder', 'jmlOver', 'jmlSync', 'nilaiUnder', 'nilaiTotal'));
    }
}
