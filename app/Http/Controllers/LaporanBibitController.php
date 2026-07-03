<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Laporan Bibit Terpakai (bulanan). Basis: pesanan yang SUDAH diproses (bukan Menunggu/Batal),
 * per tgl_pesanan. Bibit per unit dari resep (ml_bibit_utama), sudah memperhitungkan ukuran.
 */
class LaporanBibitController extends Controller
{
    public function index(Request $request)
    {
        Carbon::setLocale('id');
        $bulan = $request->input('bulan', now()->format('Y-m'));
        $periode = Carbon::createFromFormat('Y-m', $bulan);
        $awal = $periode->copy()->startOfMonth()->toDateString();
        $akhir = $periode->copy()->endOfMonth()->toDateString();
        $sort = $request->input('sort', 'ml'); // ml | qty | nilai | nama

        $rows = DB::table('penjualan_details as d')
            ->join('penjualan_headers as h', 'h.internal_id', '=', 'd.internal_id')
            ->join('master_produks as p', 'p.sku_id', '=', 'd.sku_id')
            ->join('master_reseps as r', 'r.sku_id', '=', 'd.sku_id')
            ->join('master_bibits as b', 'b.bibit_id', '=', 'r.bibit_id')
            ->whereNotIn('h.status_pesanan', ['Menunggu', 'Batal'])
            ->whereBetween('h.tgl_pesanan', [$awal, $akhir])
            ->groupBy('b.bibit_id', 'b.nama_bibit', 'b.harga_per_ml')
            ->selectRaw('b.bibit_id, b.nama_bibit, b.harga_per_ml,
                SUM(d.qty) as total_qty,
                SUM(r.ml_bibit_utama * d.qty) as total_ml,
                SUM(CASE WHEN p.ukuran_ml = 30 THEN d.qty ELSE 0 END) as qty_30,
                SUM(CASE WHEN p.ukuran_ml = 50 THEN d.qty ELSE 0 END) as qty_50')
            ->get()
            ->map(function ($r) {
                $r->total_ml = (float) $r->total_ml;
                $r->total_qty = (int) $r->total_qty;
                $r->nilai = $r->total_ml * (float) $r->harga_per_ml;
                return $r;
            });

        $rows = (match ($sort) {
            'qty'   => $rows->sortByDesc('total_qty'),
            'nilai' => $rows->sortByDesc('nilai'),
            'nama'  => $rows->sortBy('nama_bibit'),
            default => $rows->sortByDesc('total_ml'),
        })->values();

        // ── Ringkasan (kartu atas) ──
        $totalMl     = (float) $rows->sum('total_ml');
        $totalQty    = (int) $rows->sum('total_qty');
        $qty30       = (int) $rows->sum('qty_30');
        $qty50       = (int) $rows->sum('qty_50');
        $nilaiBibit  = (float) $rows->sum('nilai');
        $jumlahAroma = $rows->count();
        $aromaTop    = $rows->sortByDesc('total_ml')->first();

        $bulanTersedia = DB::table('penjualan_headers')
            ->whereNotIn('status_pesanan', ['Menunggu', 'Batal'])
            ->selectRaw("DATE_FORMAT(tgl_pesanan, '%Y-%m') as bln")
            ->groupBy('bln')->orderByDesc('bln')->pluck('bln');

        return view('laporan.bibit_terpakai', compact(
            'rows', 'bulan', 'sort', 'totalMl', 'totalQty', 'qty30', 'qty50',
            'nilaiBibit', 'jumlahAroma', 'aromaTop', 'bulanTersedia', 'periode'
        ));
    }
}
