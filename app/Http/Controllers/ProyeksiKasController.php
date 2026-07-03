<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\MasterAkunKas;
use App\Models\MutasiKas;
use App\Models\Karyawan;

class ProyeksiKasController extends Controller
{
    public function index(Request $request)
    {
        $horizon = (int) $request->input('minggu', 8);   // jumlah minggu ke depan
        $horizon = max(2, min(26, $horizon));
        $lag = (int) $request->input('lag', 14);          // perkiraan hari settlement cair
        $includeGaji = $request->boolean('gaji', true);

        $today = Carbon::today();
        $weekStart = $today->copy()->startOfWeek();

        // Saldo kas awal (semua akun, kecuali tipe Piutang)
        $masuk = MutasiKas::where('tipe', 'masuk')->selectRaw('akun, SUM(jumlah) t')->groupBy('akun')->pluck('t', 'akun');
        $keluar = MutasiKas::where('tipe', 'keluar')->selectRaw('akun, SUM(jumlah) t')->groupBy('akun')->pluck('t', 'akun');
        $saldoAwal = MasterAkunKas::where('tipe', '!=', 'Piutang')->get()->sum(function ($a) use ($masuk, $keluar) {
            $awal = (float) str_replace(['.', ','], ['', '.'], (string) $a->saldo_awal);
            return $awal + (float) ($masuk[$a->nama_akun] ?? 0) - (float) ($keluar[$a->nama_akun] ?? 0);
        });

        // Take rate marketplace historis (utk perkiraan net settlement)
        $cair = DB::table('penjualan_headers')->whereNotNull('net_settlement')->where('channel', 'like', 'Marketplace%')
            ->selectRaw('SUM(COALESCE(gross_settlement, gmv_kotor)) g, SUM(net_settlement) n')->first();
        $takeRate = ($cair && $cair->g > 0) ? max(0, ($cair->g - $cair->n) / $cair->g) : 0.15;

        // Inisialisasi minggu
        $weeks = [];
        for ($i = 0; $i < $horizon; $i++) {
            $ws = $weekStart->copy()->addWeeks($i);
            $weeks[$i] = [
                'label' => $ws->translatedFormat('d M') . '–' . $ws->copy()->endOfWeek()->translatedFormat('d M'),
                'start' => $ws->toDateString(),
                'settlement' => 0, 'piutang' => 0, 'cicilan' => 0, 'gaji' => 0,
            ];
        }
        $endHorizon = $weekStart->copy()->addWeeks($horizon)->subDay();

        $weekIdx = function (Carbon $date) use ($weekStart, $horizon) {
            if ($date->lt($weekStart)) return 0; // sudah lewat → minggu ini (imminent)
            $i = (int) floor($weekStart->diffInDays($date) / 7);
            return ($i >= 0 && $i < $horizon) ? $i : null;
        };

        // ── Inflow: settlement marketplace belum cair (perkiraan cair = tgl_pesanan + lag) ──
        DB::table('penjualan_headers')
            ->where('channel', 'like', 'Marketplace%')->where('status_pembayaran', '!=', 'Cair')
            ->where('status_pesanan', '!=', 'Batal')
            ->get(['tgl_pesanan', 'gmv_kotor'])
            ->each(function ($r) use (&$weeks, $weekIdx, $lag, $takeRate) {
                $cairDate = Carbon::parse($r->tgl_pesanan)->addDays($lag);
                $idx = $weekIdx($cairDate);
                if ($idx !== null) $weeks[$idx]['settlement'] += (float) $r->gmv_kotor * (1 - $takeRate);
            });

        // ── Inflow: piutang reseller (jatuh tempo = tgl + 7) ──
        DB::table('penjualan_headers')
            ->where('status_pesanan', '!=', 'Batal')->where('status_pembayaran', 'Piutang')
            ->get(['tgl_pesanan', 'gmv_kotor', 'diskon_manual'])
            ->each(function ($r) use (&$weeks, $weekIdx) {
                $due = Carbon::parse($r->tgl_pesanan)->addDays(7);
                $idx = $weekIdx($due);
                if ($idx !== null) $weeks[$idx]['piutang'] += (float) $r->gmv_kotor - (float) ($r->diskon_manual ?? 0);
            });

        // ── Outflow: cicilan jatuh tempo (belum bayar) ──
        DB::table('cicilan_pembayaran')->where('status', 'belum')
            ->whereBetween('periode', [$weekStart->toDateString(), $endHorizon->toDateString()])
            ->get(['periode', 'jumlah_tagihan'])
            ->each(function ($r) use (&$weeks, $weekIdx) {
                $idx = $weekIdx(Carbon::parse($r->periode));
                if ($idx !== null) $weeks[$idx]['cicilan'] += (float) $r->jumlah_tagihan;
            });

        // ── Outflow: gaji rutin (perkiraan = Σ gaji pokok aktif, dibayar tiap tgl 1) ──
        $gajiBulanan = 0;
        if ($includeGaji) {
            $gajiBulanan = (float) Karyawan::where('status', 'Aktif')->sum('gaji_pokok');
            if ($gajiBulanan > 0) {
                $cursor = $weekStart->copy()->startOfMonth();
                while ($cursor->lte($endHorizon)) {
                    if ($cursor->gte($weekStart)) {
                        $idx = $weekIdx($cursor);
                        if ($idx !== null) $weeks[$idx]['gaji'] += $gajiBulanan;
                    }
                    $cursor->addMonth();
                }
            }
        }

        // ── Hitung running balance ──
        $saldo = $saldoAwal;
        $minSaldo = $saldoAwal; $mingguKritis = null;
        foreach ($weeks as $i => &$w) {
            $w['inflow'] = $w['settlement'] + $w['piutang'];
            $w['outflow'] = $w['cicilan'] + $w['gaji'];
            $w['net'] = $w['inflow'] - $w['outflow'];
            $saldo += $w['net'];
            $w['saldo'] = $saldo;
            if ($saldo < $minSaldo) { $minSaldo = $saldo; }
            if ($saldo < 0 && $mingguKritis === null) $mingguKritis = $w['label'];
        }
        unset($w);

        $totalIn = array_sum(array_column($weeks, 'inflow'));
        $totalOut = array_sum(array_column($weeks, 'outflow'));

        return view('proyeksi_kas.index', compact(
            'weeks', 'saldoAwal', 'horizon', 'lag', 'includeGaji', 'takeRate',
            'gajiBulanan', 'totalIn', 'totalOut', 'minSaldo', 'mingguKritis'
        ));
    }
}
