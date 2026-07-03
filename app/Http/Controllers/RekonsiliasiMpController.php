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

    private function rentang(string $bulan): array
    {
        $p = Carbon::createFromFormat('Y-m', $bulan);
        return [$p->copy()->startOfMonth()->toDateString(), $p->copy()->endOfMonth()->toDateString()];
    }
}
