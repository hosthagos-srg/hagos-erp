<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PiutangController extends Controller
{
    public function index(Request $request)
    {
        $tipe = $request->input('tipe'); // reseller | marketplace | (kosong=semua)
        $today = Carbon::today();

        $items = collect();

        // 1. Piutang reseller (status Piutang) — jatuh tempo 7 hari
        if ($tipe !== 'marketplace') {
            DB::table('penjualan_headers')
                ->where('status_pesanan', '!=', 'Batal')
                ->where('status_pembayaran', 'Piutang')
                ->get(['internal_id', 'channel', 'nama_pembeli', 'tgl_pesanan', 'gmv_kotor', 'diskon_manual', 'external_order_id'])
                ->each(function ($r) use ($items, $today) {
                    $nilai = (float) $r->gmv_kotor - (float) ($r->diskon_manual ?? 0);
                    $umur = (int) $today->diffInDays(Carbon::parse($r->tgl_pesanan));
                    $items->push((object) [
                        'tipe' => 'Reseller', 'grup' => ($r->nama_pembeli ?: 'Reseller') . ' · ' . $r->channel,
                        'label' => $r->external_order_id ?: ('INV-' . strtoupper(substr($r->internal_id, 0, 8))),
                        'pembeli' => $r->nama_pembeli ?: '-', 'channel' => $r->channel,
                        'internal_id' => $r->internal_id, 'tgl' => $r->tgl_pesanan,
                        'nilai' => $nilai, 'umur' => $umur,
                        'jatuh_tempo' => $umur > 7, 'tempo_label' => 7,
                    ]);
                });
        }

        // 2. Settlement marketplace belum cair — jatuh tempo 12 hari
        if ($tipe !== 'reseller') {
            DB::table('penjualan_headers')
                ->where('status_pesanan', '!=', 'Batal')
                ->where('channel', 'like', 'Marketplace%')
                ->where('status_pembayaran', '!=', 'Cair')
                ->get(['internal_id', 'channel', 'tgl_pesanan', 'gmv_kotor', 'external_order_id', 'jumlah_dicek'])
                ->each(function ($r) use ($items, $today) {
                    $umur = (int) $today->diffInDays(Carbon::parse($r->tgl_pesanan));
                    $items->push((object) [
                        'tipe' => 'Marketplace', 'grup' => $r->channel,
                        'label' => $r->external_order_id ?: ('INV-' . strtoupper(substr($r->internal_id, 0, 8))),
                        'pembeli' => '-', 'channel' => $r->channel,
                        'internal_id' => $r->internal_id, 'tgl' => $r->tgl_pesanan,
                        'nilai' => (float) $r->gmv_kotor, 'umur' => $umur,
                        'jatuh_tempo' => $umur > 12, 'tempo_label' => 12,
                    ]);
                });
        }

        // Bucket umur
        $bucket = function ($umur) {
            if ($umur <= 7) return '0-7';
            if ($umur <= 14) return '8-14';
            if ($umur <= 30) return '15-30';
            return '>30';
        };
        $buckets = ['0-7', '8-14', '15-30', '>30'];

        // Matriks aging per grup (penghutang)
        $matrix = [];
        foreach ($items as $it) {
            $g = $it->grup;
            if (!isset($matrix[$g])) {
                $matrix[$g] = ['grup' => $g, 'tipe' => $it->tipe, '0-7' => 0, '8-14' => 0, '15-30' => 0, '>30' => 0, 'total' => 0, 'umur_max' => 0];
            }
            $matrix[$g][$bucket($it->umur)] += $it->nilai;
            $matrix[$g]['total'] += $it->nilai;
            $matrix[$g]['umur_max'] = max($matrix[$g]['umur_max'], $it->umur);
        }
        $matrix = collect($matrix)->sortByDesc('total')->values();

        // Ringkasan
        $totalPiutang = $items->sum('nilai');
        $totalReseller = $items->where('tipe', 'Reseller')->sum('nilai');
        $totalMP = $items->where('tipe', 'Marketplace')->sum('nilai');
        $lewatTempo = $items->where('jatuh_tempo', true);
        $totalLewat = $lewatTempo->sum('nilai');
        $jmlLewat = $lewatTempo->count();

        // Total per bucket (footer matriks)
        $bucketTotal = ['0-7' => 0, '8-14' => 0, '15-30' => 0, '>30' => 0];
        foreach ($items as $it) $bucketTotal[$bucket($it->umur)] += $it->nilai;

        // Detail (tertua di atas)
        $detail = $items->sortByDesc('umur')->values();

        return view('piutang.index', compact(
            'matrix', 'buckets', 'bucketTotal', 'detail', 'tipe',
            'totalPiutang', 'totalReseller', 'totalMP', 'totalLewat', 'jmlLewat'
        ));
    }
}
