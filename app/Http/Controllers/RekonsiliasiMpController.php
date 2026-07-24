<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RekonsiliasiMp;
use App\Models\MutasiKas;
use App\Models\MasterAkunKas;

class RekonsiliasiMpController extends Controller
{
    public function index(Request $request)
    {
        $bulan = $request->input('bulan', now()->format('Y-m'));
        [$awal, $akhir] = $this->rentang($bulan);

        // Net settlement versi sistem, per channel marketplace (order cair di periode)
        $netPerChannel = DB::table('penjualan_headers')
            ->where('status_pesanan', '!=', 'Batal')
            ->where('status_pembayaran', 'Cair')
            ->whereNotNull('tgl_cair_saldo')
            ->whereBetween('tgl_cair_saldo', [$awal, $akhir])
            ->selectRaw('channel, COUNT(*) as jml, SUM(net_settlement) as net')
            ->groupBy('channel')
            ->orderByDesc('net')
            ->get();

        // Bulan yang punya data pencairan
        $bulanTersedia = DB::table('penjualan_headers')
            ->whereNotNull('tgl_cair_saldo')
            ->selectRaw("DATE_FORMAT(tgl_cair_saldo, '%Y-%m') as bulan")
            ->groupBy('bulan')->orderByDesc('bulan')->pluck('bulan');

        $rekons = RekonsiliasiMp::where('periode', $bulan)->orderBy('channel')->get()->keyBy('channel');
        $akuns = MasterAkunKas::whereNotIn('tipe', ['Piutang'])->orderBy('akun_id')->pluck('nama_akun');

        return view('rekonsiliasi.index', compact('bulan', 'netPerChannel', 'bulanTersedia', 'rekons', 'akuns'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'channel'      => 'required|string',
            'periode'      => 'required|string',
            'saldo_riil'   => 'required|numeric|min:0',
            'catatan'      => 'nullable|string',
            'dicatat_oleh' => 'nullable|string',
            'bebankan'     => 'nullable|boolean',
            'akun'         => 'nullable|string',
        ]);

        [$awal, $akhir] = $this->rentang($data['periode']);

        $netSistem = (float) DB::table('penjualan_headers')
            ->where('status_pesanan', '!=', 'Batal')
            ->where('status_pembayaran', 'Cair')
            ->whereNotNull('tgl_cair_saldo')
            ->whereBetween('tgl_cair_saldo', [$awal, $akhir])
            ->where('channel', $data['channel'])
            ->sum('net_settlement');

        $selisih = round($netSistem - (float) $data['saldo_riil'], 2);

        $rek = RekonsiliasiMp::updateOrCreate(
            ['channel' => $data['channel'], 'periode' => $data['periode']],
            [
                'net_sistem'   => $netSistem,
                'saldo_riil'   => $data['saldo_riil'],
                'selisih'      => $selisih,
                'catatan'      => $data['catatan'] ?? null,
                'dicatat_oleh' => $data['dicatat_oleh'] ?? null,
            ]
        );

        // Opsional: bebankan selisih (hanya bila positif = ada potongan siluman, dan belum pernah dibebankan)
        if (! empty($data['bebankan'])) {
            if ($selisih <= 0) {
                return back()->with('error', 'Selisih tidak positif (tidak ada potongan untuk dibebankan).');
            }
            if ($rek->dibebankan) {
                return back()->with('error', 'Selisih periode ini sudah pernah dibebankan.');
            }
            if (empty($data['akun'])) {
                return back()->with('error', 'Pilih akun kas marketplace untuk membebankan selisih.');
            }

            MutasiKas::catat(
                $data['akun'], 'keluar', $selisih, 'pengeluaran',
                'REK-' . $rek->id,
                'Selisih Settlement ' . $data['channel'] . ' · ' . $data['periode'] . ' (rekonsiliasi MP)',
                $data['dicatat_oleh'] ?? null,
                $akhir
            );
            $rek->update(['dibebankan' => true, 'mutasi_ref' => 'REK-' . $rek->id]);
        }

        return redirect()->route('rekonsiliasi.index', ['bulan' => $data['periode']])
            ->with('success', 'Rekonsiliasi ' . $data['channel'] . ' tersimpan. Selisih: Rp ' . number_format($selisih, 0, ',', '.'));
    }

    /**
     * REKONSILIASI SALDO MP (otomatis). Membandingkan saldo marketplace di ERP vs saldo riil,
     * lalu MENJELASKAN selisihnya lewat audit: tiap pesanan cair, net_settlement HARUS sama
     * dengan Σ mutasi kas pesanan itu. Ketidakcocokan = pencatatan yang bolong (mis. refund
     * yang gagal tercatat) — bisa diperbaiki otomatis.
     */
    public function saldo(Request $request)
    {
        $akuns = MasterAkunKas::where('tipe', 'Saldo MP')->orderBy('nama_akun')->get()->map(function ($a) {
            $masuk  = (float) MutasiKas::where('akun', $a->nama_akun)->where('tipe', 'masuk')->sum('jumlah');
            $keluar = (float) MutasiKas::where('akun', $a->nama_akun)->where('tipe', 'keluar')->sum('jumlah');
            return (object) ['nama' => $a->nama_akun, 'saldo' => (float) $a->saldo_awal + $masuk - $keluar];
        });

        // Audit: net_settlement vs Σ mutasi kas per pesanan
        $mutSub = DB::table('mutasi_kas')
            ->select('ref_id', DB::raw("SUM(CASE WHEN tipe='masuk' THEN jumlah ELSE -jumlah END) as mut"))
            ->where('kategori', 'penjualan')->whereNotNull('ref_id')->groupBy('ref_id');

        $gaps = DB::table('penjualan_headers as h')
            ->leftJoinSub($mutSub, 'm', 'm.ref_id', '=', 'h.internal_id')
            ->whereNotNull('h.net_settlement')
            ->where('h.status_pesanan', '!=', 'Batal')
            ->whereRaw('ABS(h.net_settlement - COALESCE(m.mut,0)) >= 1')
            ->orderByDesc('h.tgl_cair_saldo')
            ->get(['h.internal_id', 'h.external_order_id', 'h.channel', 'h.tgl_cair_saldo',
                   'h.net_settlement', 'h.akun_masuk', DB::raw('COALESCE(m.mut,0) as mutasi')])
            ->map(function ($g) {
                $g->gap = round((float) $g->net_settlement - (float) $g->mutasi, 2);
                $net = (float) $g->net_settlement;
                $mut = (float) $g->mutasi;
                // Jelaskan SEBAB selisihnya, biar tak perlu telaah manual.
                if (abs($mut) < 1 && $net < 0) {
                    $g->sebab  = 'Refund / retur belum tercatat';
                    $g->detail = 'Marketplace menarik dana (settlement negatif), tapi uangnya belum dikeluarkan dari saldo di ERP. Barangnya juga perlu ditandai di Laporan Retur (layak jual / rusak).';
                } elseif (abs($mut) < 1 && $net > 0) {
                    $g->sebab  = 'Settlement belum masuk kas';
                    $g->detail = 'Pesanan sudah ditandai Cair, tapi uang masuknya belum tercatat ke saldo marketplace.';
                } elseif ($net < $mut) {
                    $g->sebab  = 'Koreksi settlement (nilai turun)';
                    $g->detail = 'Nilai settlement berubah jadi lebih kecil (mis. refund sebagian). Selisihnya belum dipotong dari saldo.';
                } else {
                    $g->sebab  = 'Koreksi settlement (nilai naik)';
                    $g->detail = 'Nilai settlement berubah jadi lebih besar. Kekurangannya belum ditambahkan ke saldo.';
                }
                return $g;
            });

        $totalGap = (float) $gaps->sum('gap');

        return view('rekonsiliasi.saldo', compact('akuns', 'gaps', 'totalGap'));
    }

    /** Perbaiki otomatis: catat mutasi selisih untuk tiap pesanan yang bolong. */
    public function perbaikiSaldo(Request $request)
    {
        $mutSub = DB::table('mutasi_kas')
            ->select('ref_id', DB::raw("SUM(CASE WHEN tipe='masuk' THEN jumlah ELSE -jumlah END) as mut"))
            ->where('kategori', 'penjualan')->whereNotNull('ref_id')->groupBy('ref_id');

        $gaps = DB::table('penjualan_headers as h')
            ->leftJoinSub($mutSub, 'm', 'm.ref_id', '=', 'h.internal_id')
            ->whereNotNull('h.net_settlement')
            ->where('h.status_pesanan', '!=', 'Batal')
            ->whereRaw('ABS(h.net_settlement - COALESCE(m.mut,0)) >= 1')
            ->get(['h.internal_id', 'h.external_order_id', 'h.channel', 'h.tgl_cair_saldo',
                   'h.net_settlement', 'h.akun_masuk', DB::raw('COALESCE(m.mut,0) as mutasi')]);

        if ($gaps->isEmpty()) return back()->with('success', 'Tidak ada selisih pencatatan — semua sudah cocok.');

        $n = 0; $total = 0;
        DB::transaction(function () use ($gaps, &$n, &$total) {
            foreach ($gaps as $g) {
                $delta = round((float) $g->net_settlement - (float) $g->mutasi, 2);
                if (abs($delta) < 1) continue;
                $akun = $g->akun_masuk ?: (str_contains(strtolower($g->channel), 'shopee') ? 'Saldo Shopee Seller' : 'Saldo TikTok Shop');
                MutasiKas::catat(
                    $akun, $delta > 0 ? 'masuk' : 'keluar', abs($delta), 'penjualan', $g->internal_id,
                    'Settlement ' . $g->external_order_id . ' (perbaikan rekonsiliasi)',
                    auth()->user()->name ?? null, $g->tgl_cair_saldo ?: now()->toDateString()
                );
                $n++; $total += $delta;
            }
        });

        return back()->with('success', "✅ {$n} pesanan diperbaiki. Total penyesuaian saldo: Rp " . number_format($total, 0, ',', '.') . '.');
    }

    private function rentang(string $bulan): array
    {
        $p = Carbon::createFromFormat('Y-m', $bulan);
        return [$p->copy()->startOfMonth()->toDateString(), $p->copy()->endOfMonth()->toDateString()];
    }
}
