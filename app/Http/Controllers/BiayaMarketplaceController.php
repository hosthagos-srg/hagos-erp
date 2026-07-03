<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BiayaMarketplaceController extends Controller
{
    public function index(Request $request)
    {
        // Periode berdasarkan tgl_cair_saldo (kapan settlement cair)
        $bulan = $request->input('bulan'); // 'Y-m' atau kosong = semua
        $bulanTersedia = DB::table('penjualan_headers')
            ->whereNotNull('tgl_cair_saldo')->where('channel', 'like', 'Marketplace%')
            ->selectRaw("DATE_FORMAT(tgl_cair_saldo, '%Y-%m') as b")->groupBy('b')->orderBy('b', 'desc')->pluck('b');

        // Semua order marketplace yang settlement-nya sudah cair (net_settlement terisi),
        // termasuk yg statusnya Batal tapi tetap dibayar — fee-nya riil.
        $q = DB::table('penjualan_headers')
            ->where('channel', 'like', 'Marketplace%')
            ->whereNotNull('net_settlement');
        if ($bulan) {
            $p = Carbon::createFromFormat('Y-m', $bulan);
            $q->whereBetween('tgl_cair_saldo', [$p->copy()->startOfMonth()->toDateString(), $p->copy()->endOfMonth()->toDateString()]);
        }
        $orders = $q->get(['channel', 'gross_settlement', 'net_settlement', 'gmv_kotor', 'potongan_detail']);

        $totalGross = 0; $totalNet = 0; $jml = $orders->count();
        $perChannel = [];        // channel => [gross, net, potongan, jml]
        $perFee = [];            // nama biaya => total
        $itemizedSum = 0;        // total fee yg terinci

        foreach ($orders as $o) {
            $gross = (float) ($o->gross_settlement ?: $o->gmv_kotor);
            $net = (float) $o->net_settlement;
            $potongan = $gross - $net;
            $totalGross += $gross; $totalNet += $net;

            $ch = $o->channel;
            if (!isset($perChannel[$ch])) $perChannel[$ch] = ['channel' => $ch, 'gross' => 0, 'net' => 0, 'potongan' => 0, 'jml' => 0];
            $perChannel[$ch]['gross'] += $gross;
            $perChannel[$ch]['net'] += $net;
            $perChannel[$ch]['potongan'] += $potongan;
            $perChannel[$ch]['jml']++;

            $detail = is_array($o->potongan_detail) ? $o->potongan_detail : json_decode((string) $o->potongan_detail, true);
            if (is_array($detail)) {
                foreach ($detail as $nama => $val) {
                    $abs = abs((float) $val);
                    $perFee[$nama] = ($perFee[$nama] ?? 0) + $abs;
                    $itemizedSum += $abs;
                }
            }
        }

        $totalPotongan = $totalGross - $totalNet;
        $takeRate = $totalGross > 0 ? ($totalPotongan / $totalGross) * 100 : 0;

        // Potongan tak terinci (order tanpa rincian valid)
        $takTerinci = max(0, $totalPotongan - $itemizedSum);
        if ($takTerinci > 1) $perFee['(Potongan tak terinci)'] = $takTerinci;

        arsort($perFee);
        $perChannel = collect($perChannel)->sortByDesc('potongan')->values();

        return view('biaya_marketplace.index', compact(
            'bulan', 'bulanTersedia', 'orders', 'jml',
            'totalGross', 'totalNet', 'totalPotongan', 'takeRate',
            'perChannel', 'perFee'
        ));
    }
}
