<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\LaporanService;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use Barryvdh\DomPDF\Facade\Pdf;

class LaporanExportController extends Controller
{
    private array $jenisList = [
        'penjualan'   => 'Laporan Penjualan',
        'cashflow'    => 'Laporan Keuangan (Cashflow)',
        'pengeluaran' => 'Laporan Pengeluaran',
        'pl'          => 'Laporan Laba Rugi (P&L)',
    ];

    public function index(Request $request)
    {
        $jenis = $request->input('jenis', 'penjualan');
        if (!isset($this->jenisList[$jenis])) $jenis = 'penjualan';

        $dari = $request->input('dari', now()->startOfMonth()->toDateString());
        $sampai = $request->input('sampai', now()->endOfMonth()->toDateString());

        $report = $this->buildReport($jenis, $dari, $sampai);

        return view('laporan.export', [
            'jenisList' => $this->jenisList,
            'jenis'     => $jenis,
            'dari'      => $dari,
            'sampai'    => $sampai,
            'report'    => $report,
        ]);
    }

    public function excel(Request $request)
    {
        $jenis = $request->input('jenis', 'penjualan');
        $dari = $request->input('dari', now()->startOfMonth()->toDateString());
        $sampai = $request->input('sampai', now()->endOfMonth()->toDateString());
        $report = $this->buildReport($jenis, $dari, $sampai);

        $tmp = storage_path('app/' . uniqid('lap_') . '.xlsx');
        $writer = new Writer();
        $writer->openToFile($tmp);

        $bold = (new Style())->setFontBold();

        // Judul + periode
        $writer->addRow(Row::fromValues([$report['title']], $bold));
        $writer->addRow(Row::fromValues(['Periode: ' . $report['periode_label']]));
        $writer->addRow(Row::fromValues(['']));

        // Header kolom
        $writer->addRow(Row::fromValues(array_map(fn($c) => $c['label'], $report['columns']), $bold));

        // Data (rupiah/int sebagai angka mentah agar bisa dihitung di Excel)
        foreach ($report['rows'] as $r) {
            $cells = [];
            foreach ($report['columns'] as $i => $c) {
                $v = $r[$i] ?? '';
                $cells[] = in_array($c['type'], ['rupiah', 'int']) ? (float) $v : (string) $v;
            }
            $writer->addRow(Row::fromValues($cells));
        }

        // Ringkasan
        if (!empty($report['summary'])) {
            $writer->addRow(Row::fromValues(['']));
            $writer->addRow(Row::fromValues(['RINGKASAN'], $bold));
            foreach ($report['summary'] as $s) {
                $val = in_array($s['type'] ?? 'text', ['rupiah', 'int']) ? (float) $s['value'] : (string) $s['value'];
                $writer->addRow(Row::fromValues([$s['label'], $val]));
            }
        }

        $writer->close();

        $filename = $this->filename($report) . '.xlsx';
        return response()->download($tmp, $filename)->deleteFileAfterSend(true);
    }

    public function pdf(Request $request)
    {
        $jenis = $request->input('jenis', 'penjualan');
        $dari = $request->input('dari', now()->startOfMonth()->toDateString());
        $sampai = $request->input('sampai', now()->endOfMonth()->toDateString());
        $report = $this->buildReport($jenis, $dari, $sampai);

        $orientasi = count($report['columns']) > 6 ? 'landscape' : 'portrait';
        $pdf = Pdf::loadView('laporan.pdf', ['report' => $report])->setPaper('a4', $orientasi);

        return $pdf->download($this->filename($report) . '.pdf');
    }

    private function filename(array $report): string
    {
        return str_replace([' ', '(', ')', '&'], ['_', '', '', ''], $report['title']) . '_' . $report['periode_file'];
    }

    // ─────────────────────────── BUILDERS ───────────────────────────

    private function buildReport(string $jenis, string $dari, string $sampai): array
    {
        $base = [
            'jenis'         => $jenis,
            'title'         => $this->jenisList[$jenis] ?? 'Laporan',
            'periode_label' => Carbon::parse($dari)->format('d M Y') . ' – ' . Carbon::parse($sampai)->format('d M Y'),
            'periode_file'  => Carbon::parse($dari)->format('Ymd') . '-' . Carbon::parse($sampai)->format('Ymd'),
        ];

        $method = 'report' . ucfirst($jenis);
        $data = method_exists($this, $method) ? $this->$method($dari, $sampai) : ['columns' => [], 'rows' => [], 'summary' => []];

        return array_merge($base, $data);
    }

    private function reportPenjualan(string $dari, string $sampai): array
    {
        $sub = '(SELECT pd.internal_id, SUM(pd.hpp_satuan*pd.qty) AS hpp, SUM(pd.qty) AS qty, '
             . "GROUP_CONCAT(CONCAT(pd.qty,'x ',COALESCE(mp.nama_produk, pd.sku_id),' ',COALESCE(mp.ukuran_ml,''),'ml') SEPARATOR ', ') AS produk "
             . 'FROM penjualan_details pd LEFT JOIN master_produks mp ON mp.sku_id = pd.sku_id GROUP BY pd.internal_id) d';

        $rows = DB::table('penjualan_headers as h')
            ->leftJoin(DB::raw($sub), 'd.internal_id', '=', 'h.internal_id')
            ->where('h.status_pesanan', '!=', 'Batal')
            ->whereBetween('h.tgl_pesanan', [$dari, $sampai])
            ->orderBy('h.tgl_pesanan')
            ->selectRaw('h.tgl_pesanan, h.external_order_id, h.internal_id, h.channel, h.status_pembayaran,
                h.gmv_kotor, h.diskon_manual, h.net_settlement, d.hpp, d.qty, d.produk')
            ->get();

        $data = []; $tGmv = $tNet = $tHpp = $tMargin = 0;
        foreach ($rows as $r) {
            $net = $r->net_settlement !== null ? (float) $r->net_settlement : ((float) $r->gmv_kotor - (float) $r->diskon_manual);
            $hpp = (float) $r->hpp;
            $margin = $net - $hpp;
            $tGmv += (float) $r->gmv_kotor; $tNet += $net; $tHpp += $hpp; $tMargin += $margin;
            $data[] = [
                Carbon::parse($r->tgl_pesanan)->format('d/m/Y'),
                $r->external_order_id ?: ('INV-' . strtoupper(substr($r->internal_id, 0, 8))),
                $r->channel,
                $r->produk,
                (int) $r->qty,
                (float) $r->gmv_kotor,
                (float) $r->diskon_manual,
                $net,
                $hpp,
                $margin,
                $r->status_pembayaran,
            ];
        }

        return [
            'columns' => [
                ['label' => 'Tgl', 'type' => 'text'],
                ['label' => 'Order ID', 'type' => 'text'],
                ['label' => 'Channel', 'type' => 'text'],
                ['label' => 'Produk', 'type' => 'text'],
                ['label' => 'Qty', 'type' => 'int'],
                ['label' => 'GMV', 'type' => 'rupiah'],
                ['label' => 'Diskon', 'type' => 'rupiah'],
                ['label' => 'Net', 'type' => 'rupiah'],
                ['label' => 'HPP', 'type' => 'rupiah'],
                ['label' => 'Margin', 'type' => 'rupiah'],
                ['label' => 'Status', 'type' => 'text'],
            ],
            'rows' => $data,
            'summary' => [
                ['label' => 'Jumlah Pesanan', 'value' => count($data), 'type' => 'int'],
                ['label' => 'Total GMV', 'value' => $tGmv, 'type' => 'rupiah'],
                ['label' => 'Total Net Omzet', 'value' => $tNet, 'type' => 'rupiah'],
                ['label' => 'Total HPP', 'value' => $tHpp, 'type' => 'rupiah'],
                ['label' => 'Total Margin', 'value' => $tMargin, 'type' => 'rupiah'],
            ],
        ];
    }

    private function reportCashflow(string $dari, string $sampai): array
    {
        $rows = DB::table('mutasi_kas')
            ->whereBetween('tanggal', [$dari, $sampai])
            ->orderBy('tanggal')->orderBy('created_at')
            ->get();

        $data = []; $tMasuk = $tKeluar = 0;
        foreach ($rows as $r) {
            $masuk = $r->tipe === 'masuk' ? (float) $r->jumlah : 0;
            $keluar = $r->tipe === 'keluar' ? (float) $r->jumlah : 0;
            $tMasuk += $masuk; $tKeluar += $keluar;
            $data[] = [
                Carbon::parse($r->tanggal)->format('d/m/Y'),
                $r->akun,
                $r->kategori,
                $masuk,
                $keluar,
                $r->keterangan,
            ];
        }

        return [
            'columns' => [
                ['label' => 'Tgl', 'type' => 'text'],
                ['label' => 'Akun', 'type' => 'text'],
                ['label' => 'Kategori', 'type' => 'text'],
                ['label' => 'Masuk', 'type' => 'rupiah'],
                ['label' => 'Keluar', 'type' => 'rupiah'],
                ['label' => 'Keterangan', 'type' => 'text'],
            ],
            'rows' => $data,
            'summary' => [
                ['label' => 'Total Masuk', 'value' => $tMasuk, 'type' => 'rupiah'],
                ['label' => 'Total Keluar', 'value' => $tKeluar, 'type' => 'rupiah'],
                ['label' => 'Arus Kas Bersih', 'value' => $tMasuk - $tKeluar, 'type' => 'rupiah'],
            ],
        ];
    }

    private function reportPengeluaran(string $dari, string $sampai): array
    {
        $rows = DB::table('mutasi_kas')
            ->where('tipe', 'keluar')
            ->where('kategori', 'pengeluaran')
            ->whereBetween('tanggal', [$dari, $sampai])
            ->orderBy('tanggal')->get();

        $data = []; $total = 0; $perKategori = [];
        foreach ($rows as $r) {
            $kat = trim(explode('·', $r->keterangan)[0] ?? 'Lainnya');
            $total += (float) $r->jumlah;
            $perKategori[$kat] = ($perKategori[$kat] ?? 0) + (float) $r->jumlah;
            $data[] = [
                Carbon::parse($r->tanggal)->format('d/m/Y'),
                $kat,
                $r->akun,
                (float) $r->jumlah,
                $r->keterangan,
            ];
        }

        $summary = [['label' => 'Total Pengeluaran', 'value' => $total, 'type' => 'rupiah']];
        arsort($perKategori);
        foreach ($perKategori as $k => $v) {
            $summary[] = ['label' => '— ' . $k, 'value' => $v, 'type' => 'rupiah'];
        }

        return [
            'columns' => [
                ['label' => 'Tgl', 'type' => 'text'],
                ['label' => 'Kategori', 'type' => 'text'],
                ['label' => 'Akun', 'type' => 'text'],
                ['label' => 'Jumlah', 'type' => 'rupiah'],
                ['label' => 'Keterangan', 'type' => 'text'],
            ],
            'rows' => $data,
            'summary' => $summary,
        ];
    }

    private function reportPl(string $dari, string $sampai): array
    {
        $s = app(LaporanService::class)->summary($dari, $sampai);

        $rows = [
            ['Omzet Marketplace (Cair)', $s['omsetMP']],
            ['Omzet Non-Marketplace (Lunas)', $s['omsetNonMP']],
            ['TOTAL NET OMZET', $s['totalOmset']],
            ['HPP Penjualan', -$s['totalHpp']],
            ['LABA KOTOR', $s['labaKotor']],
            ['Pendapatan Lain-lain (tester/patungan)', $s['totalPenerimaan']],
            ['Biaya Operasional (Pengeluaran)', -$s['totalPengeluaran']],
            ['Cicilan & Biaya Kartu', -$s['totalCicilan']],
            ['Total Biaya Operasional', -$s['totalBiayaOps']],
            ['LABA BERSIH', $s['labaBersih']],
        ];

        return [
            'columns' => [
                ['label' => 'Keterangan', 'type' => 'text'],
                ['label' => 'Nilai', 'type' => 'rupiah'],
            ],
            'rows' => $rows,
            'summary' => [
                ['label' => 'Margin Kotor', 'value' => number_format($s['marginKotor'], 1) . '%', 'type' => 'text'],
                ['label' => 'Margin Bersih', 'value' => number_format($s['marginBersih'], 1) . '%', 'type' => 'text'],
            ],
        ];
    }
}
