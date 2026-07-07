<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\MasterBibit;
use App\Models\MasterProduk;
use App\Models\MasterAkunKas;

class DashboardController extends Controller
{
    public function index()
    {
        $hariIni  = now()->toDateString();
        $awalMgg  = now()->startOfWeek()->toDateString();
        $akhirMgg = now()->endOfWeek()->toDateString();
        $awalBln  = now()->startOfMonth()->toDateString();
        $akhirBln = now()->endOfMonth()->toDateString();

        // Periode pembanding (sebelumnya)
        $kemarin     = now()->subDay()->toDateString();
        $awalMggLalu = now()->subWeek()->startOfWeek()->toDateString();
        $akhirMggLalu= now()->subWeek()->endOfWeek()->toDateString();
        $awalBlnLalu = now()->subMonth()->startOfMonth()->toDateString();
        $akhirBlnLalu= now()->subMonth()->endOfMonth()->toDateString();

        // Omzet (net) per periode — basis tgl_pesanan, non-batal (konsisten dgn grafik penjualan)
        $omzet = fn ($a, $b) => (float) DB::table('penjualan_headers')
            ->where('status_pesanan', '!=', 'Batal')
            ->whereBetween('tgl_pesanan', [$a, $b])
            ->selectRaw('SUM(COALESCE(net_settlement, gmv_kotor - COALESCE(diskon_manual, 0))) as t')
            ->value('t');

        // Persentase perubahan vs periode sebelumnya (null jika tak ada pembanding)
        $pct = fn ($cur, $prev) => ($prev > 0) ? (($cur - $prev) / $prev * 100) : null;

        $jmlPesanan = fn ($a, $b) => DB::table('penjualan_headers')->where('channel', '!=', 'Gratis')->whereBetween('tgl_pesanan', [$a, $b])->count();

        // ── Ringkasan pesanan ──
        $totalPesanan = $jmlPesanan($awalMgg, $akhirMgg);
        $totalPesananBln = $jmlPesanan($awalBln, $akhirBln);
        $totalPesananPct = $pct($totalPesanan, $jmlPesanan($awalMggLalu, $akhirMggLalu));
        $totalPesananBlnPct = $pct($totalPesananBln, $jmlPesanan($awalBlnLalu, $akhirBlnLalu));

        $belumCairQuery = DB::table('penjualan_headers')
            ->where('status_pesanan', '!=', 'Batal')
            ->where('channel', '!=', 'Gratis') // produk gratis bukan "uang belum cair"
            ->where(function ($q) {
                $q->whereNull('status_pembayaran')->orWhereNotIn('status_pembayaran', ['Cair', 'Lunas']);
            });
        $totalBelumCair = (clone $belumCairQuery)->count();

        // Melewati jatuh tempo: Piutang reseller > 7 hari ATAU settlement marketplace belum cair > 12 hari
        $totalLewatTempo = DB::table('penjualan_headers')
            ->where('status_pesanan', '!=', 'Batal')
            ->where('channel', '!=', 'Gratis')
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->where('status_pembayaran', 'Piutang')->whereDate('tgl_pesanan', '<=', now()->subDays(7));
                })->orWhere(function ($q2) {
                    $q2->where(function ($q3) {
                        $q3->whereNull('status_pembayaran')->orWhereNotIn('status_pembayaran', ['Cair', 'Lunas', 'Piutang']);
                    })->whereDate('tgl_pesanan', '<=', now()->subDays(12));
                });
            })
            ->count();

        $bibitWarnings = MasterBibit::whereColumn('stok_ml', '<=', 'threshold_ml')->get();

        // Settlement marketplace belum cair (uang nyangkut)
        $nyangkut = DB::table('penjualan_headers')
            ->where('channel', 'like', 'Marketplace%')
            ->where('status_pembayaran', '!=', 'Cair')
            ->where('status_pesanan', '!=', 'Batal')
            ->selectRaw('COUNT(*) as jml, SUM(gmv_kotor) as nilai')
            ->first();

        // Utang/cicilan: sisa total + tagihan terdekat
        $utangAktifIds = \App\Models\UtangCicilan::where('status', 'aktif')->pluck('id');
        $totalUtangAktif = (float) \App\Models\UtangCicilan::where('status', 'aktif')->sum('total_utang');
        $sudahBayar = (float) \App\Models\CicilanPembayaran::whereIn('utang_cicilan_id', $utangAktifIds)
            ->where('status', 'lunas')->sum('jumlah_bayar');
        $sisaUtang = max(0, $totalUtangAktif - $sudahBayar);
        // Utang pribadi (hutang tunai ke orang) → digabung jadi total hutang di dashboard.
        $sisaUtangPribadi = (float) \App\Models\UtangPribadi::with('bayar')->where('status', 'aktif')->get()
            ->sum(fn($u) => $u->sisa);
        $tagihanTerdekat = \App\Models\CicilanPembayaran::with('utangCicilan.sumberDana')
            ->whereIn('utang_cicilan_id', $utangAktifIds)
            ->where('status', 'belum')
            ->orderBy('periode')->first();

        $finansial = [
            'omzet_hari'       => $omzet($hariIni, $hariIni),
            'omzet_minggu'     => $omzet($awalMgg, $akhirMgg),
            'omzet_bulan'      => $omzet($awalBln, $akhirBln),
            'omzet_hari_pct'   => $pct($omzet($hariIni, $hariIni), $omzet($kemarin, $kemarin)),
            'omzet_minggu_pct' => $pct($omzet($awalMgg, $akhirMgg), $omzet($awalMggLalu, $akhirMggLalu)),
            'omzet_bulan_pct'  => $pct($omzet($awalBln, $akhirBln), $omzet($awalBlnLalu, $akhirBlnLalu)),
            'nyangkut_jml'     => (int) ($nyangkut->jml ?? 0),
            'nyangkut_nilai'   => (float) ($nyangkut->nilai ?? 0),
            'sisa_utang'          => $sisaUtang,
            'sisa_utang_pribadi'  => $sisaUtangPribadi,
            'total_hutang'        => $sisaUtang + $sisaUtangPribadi,
            'tagihan_terdekat'    => $tagihanTerdekat,
        ];

        $recentActivity = $this->recentActivity();

        return view('dashboard', compact('totalPesanan', 'totalPesananBln', 'totalPesananPct', 'totalPesananBlnPct', 'totalBelumCair', 'totalLewatTempo', 'bibitWarnings', 'finansial', 'recentActivity'));
    }

    /** Log aktivitas terbaru sistem (gabungan dari beberapa tabel). */
    private function recentActivity(int $limit = 8): array
    {
        \Carbon\Carbon::setLocale('id');
        $items = collect();

        // Pesanan baru
        DB::table('penjualan_headers')->latest('created_at')->limit($limit)
            ->get(['external_order_id', 'internal_id', 'channel', 'gmv_kotor', 'created_at'])
            ->each(function ($r) use ($items) {
                $items->push([
                    'icon' => '🧾', 'color' => 'bg-indigo-500',
                    'title' => 'Pesanan Baru',
                    'detail' => ($r->external_order_id ?: 'INV-' . strtoupper(substr($r->internal_id, 0, 8))) . ' · ' . $r->channel . ' · Rp ' . number_format($r->gmv_kotor, 0, ',', '.'),
                    'time' => $r->created_at,
                ]);
            });

        // Mutasi kas (pembayaran, pengeluaran, belanja, kasbon, dll)
        DB::table('mutasi_kas')->latest('created_at')->limit($limit + 4)
            ->get(['tipe', 'kategori', 'jumlah', 'keterangan', 'created_at'])
            ->each(function ($r) use ($items) {
                $map = [
                    'penjualan'        => ['💵', 'bg-emerald-500', 'Pembayaran Diterima'],
                    'penerimaan'       => ['💰', 'bg-teal-500', 'Penerimaan Uang'],
                    'modal'            => ['🏦', 'bg-emerald-600', 'Setoran Modal'],
                    'prive'            => ['🔻', 'bg-rose-500', 'Prive (Ambil Pemilik)'],
                    'refund_penjualan' => ['↩️', 'bg-orange-500', 'Refund Penjualan'],
                    'pengeluaran'      => ['➖', 'bg-red-500', 'Pengeluaran'],
                    'kasbon'           => ['👷', 'bg-amber-500', $r->tipe === 'masuk' ? 'Kasbon Dibayar' : 'Kasbon Diberikan'],
                    'withdrawal'       => ['🏦', 'bg-sky-500', 'Tarik Saldo'],
                ];
                $key = $r->kategori;
                if (str_starts_with((string) $r->kategori, 'belanja')) { $m = ['🛒', 'bg-purple-500', 'Belanja']; }
                elseif (str_starts_with((string) $r->kategori, 'transfer') || $r->kategori === 'biaya_transfer') { $m = ['🔄', 'bg-blue-500', 'Transfer Antar Akun']; }
                else { $m = $map[$key] ?? ['💰', 'bg-gray-500', ucfirst((string) $r->kategori)]; }

                $items->push([
                    'icon' => $m[0], 'color' => $m[1], 'title' => $m[2],
                    'detail' => 'Rp ' . number_format($r->jumlah, 0, ',', '.') . ($r->keterangan ? ' · ' . $r->keterangan : ''),
                    'time' => $r->created_at,
                ]);
            });

        // Koreksi stok
        DB::table('koreksi_stoks')->latest('created_at')->limit(3)
            ->get(['nama_item', 'selisih', 'alasan', 'created_at'])
            ->each(function ($r) use ($items) {
                $items->push([
                    'icon' => '📦', 'color' => 'bg-teal-500', 'title' => 'Koreksi Stok',
                    'detail' => $r->nama_item . ' · ' . ($r->selisih > 0 ? '+' : '') . rtrim(rtrim(number_format($r->selisih, 2, ',', '.'), '0'), ',') . ' · ' . $r->alasan,
                    'time' => $r->created_at,
                ]);
            });

        // Produk baru
        DB::table('master_produks')->latest('created_at')->limit(3)
            ->get(['sku_id', 'nama_produk', 'created_at'])
            ->each(function ($r) use ($items) {
                $items->push([
                    'icon' => '📋', 'color' => 'bg-violet-500', 'title' => 'Produk Baru',
                    'detail' => $r->sku_id . ' · ' . $r->nama_produk,
                    'time' => $r->created_at,
                ]);
            });

        // Peracikan (racik pesanan / tester / absolute)
        DB::table('produksi_logs')->latest('created_at')->limit($limit)
            ->get(['tipe', 'ringkasan', 'diracik_oleh', 'created_at'])
            ->each(function ($r) use ($items) {
                $items->push([
                    'icon' => '🧪', 'color' => 'bg-sky-500',
                    'title' => 'Peracikan · ' . $r->tipe,
                    'detail' => ($r->ringkasan ?: $r->tipe) . ($r->diracik_oleh ? ' · oleh ' . $r->diracik_oleh : ''),
                    'time' => $r->created_at,
                ]);
            });

        return $items->filter(fn($i) => !empty($i['time']))
            ->sortByDesc('time')
            ->take($limit)
            ->map(function ($i) {
                $i['waktu'] = \Carbon\Carbon::parse($i['time'])->diffForHumans(['short' => true]);
                return $i;
            })
            ->values()->all();
    }

    /** Agregat pengeluaran per kategori (Belanja/Gaji/Operasional) untuk satu rentang. */
    private function expenseAgg(string $awal, string $akhir): array
    {
        $rows = DB::table('mutasi_kas')->where('tipe', 'keluar')
            ->where(fn($q) => $q->where('kategori', 'pengeluaran')->orWhere('kategori', 'like', 'belanja%'))
            ->whereBetween('tanggal', [$awal, $akhir])
            ->get(['kategori', 'keterangan', 'jumlah']);
        $cat = ['Belanja' => 0.0, 'Gaji' => 0.0, 'Operasional' => 0.0];
        foreach ($rows as $r) {
            if (str_starts_with((string) $r->kategori, 'belanja')) $k = 'Belanja';
            elseif (stripos((string) $r->keterangan, 'Gaji') === 0) $k = 'Gaji';
            else $k = 'Operasional';
            $cat[$k] += (float) $r->jumlah;
        }
        return $cat;
    }

    /** Data breakdown pengeluaran + perbandingan periode sebelumnya. */
    public function expenseBreakdown(Request $request)
    {
        \Carbon\Carbon::setLocale('id');
        $periode = $request->input('periode', 'minggu'); // minggu|minggu_lalu|bulan|bulan_lalu|custom
        $now = now();

        switch ($periode) {
            case 'bulan':
                $awal = $now->copy()->startOfMonth(); $akhir = $now->copy()->endOfMonth();
                $awalPrev = $now->copy()->subMonth()->startOfMonth(); $akhirPrev = $now->copy()->subMonth()->endOfMonth();
                $prevLabel = $now->copy()->subMonth()->translatedFormat('F Y'); break;
            case 'bulan_lalu':
                $awal = $now->copy()->subMonth()->startOfMonth(); $akhir = $now->copy()->subMonth()->endOfMonth();
                $awalPrev = $now->copy()->subMonths(2)->startOfMonth(); $akhirPrev = $now->copy()->subMonths(2)->endOfMonth();
                $prevLabel = $now->copy()->subMonths(2)->translatedFormat('F Y'); break;
            case 'minggu_lalu':
                $awal = $now->copy()->subWeek()->startOfWeek(); $akhir = $now->copy()->subWeek()->endOfWeek();
                $awalPrev = $now->copy()->subWeeks(2)->startOfWeek(); $akhirPrev = $now->copy()->subWeeks(2)->endOfWeek();
                $prevLabel = '2 minggu lalu'; break;
            case 'custom':
                $awal = \Carbon\Carbon::parse($request->input('dari', $now->copy()->startOfMonth()->toDateString()));
                $akhir = \Carbon\Carbon::parse($request->input('sampai', $now->toDateString()));
                if ($akhir->lt($awal)) { [$awal, $akhir] = [$akhir, $awal]; }
                $len = $awal->diffInDays($akhir);
                $akhirPrev = $awal->copy()->subDay(); $awalPrev = $akhirPrev->copy()->subDays($len);
                $prevLabel = $awalPrev->format('d M') . '–' . $akhirPrev->format('d M'); break;
            default: // minggu
                $awal = $now->copy()->startOfWeek(); $akhir = $now->copy()->endOfWeek();
                $awalPrev = $now->copy()->subWeek()->startOfWeek(); $akhirPrev = $now->copy()->subWeek()->endOfWeek();
                $prevLabel = 'minggu lalu'; break;
        }

        $isBulanan = in_array($periode, ['bulan', 'bulan_lalu']) || ($periode === 'custom' && $awal->diffInDays($akhir) > 10);

        // Series harian (rentang berjalan) untuk chart
        $labels = []; $idxByDate = []; $cursor = $awal->copy(); $i = 0;
        while ($cursor->lte($akhir) && $i <= 370) {
            $labels[] = $isBulanan ? $cursor->format('d/m') : $cursor->translatedFormat('D d/m');
            $idxByDate[$cursor->toDateString()] = $i; $cursor->addDay(); $i++;
        }
        $n = count($labels);
        $series = ['Belanja' => array_fill(0, $n, 0), 'Gaji' => array_fill(0, $n, 0), 'Operasional' => array_fill(0, $n, 0)];
        $rows = DB::table('mutasi_kas')->where('tipe', 'keluar')
            ->where(fn($q) => $q->where('kategori', 'pengeluaran')->orWhere('kategori', 'like', 'belanja%'))
            ->whereBetween('tanggal', [$awal->toDateString(), $akhir->toDateString()])
            ->get(['tanggal', 'kategori', 'keterangan', 'jumlah']);
        foreach ($rows as $r) {
            $idx = $idxByDate[\Carbon\Carbon::parse($r->tanggal)->toDateString()] ?? null;
            if ($idx === null) continue;
            if (str_starts_with((string) $r->kategori, 'belanja')) $k = 'Belanja';
            elseif (stripos((string) $r->keterangan, 'Gaji') === 0) $k = 'Gaji';
            else $k = 'Operasional';
            $series[$k][$idx] += (float) $r->jumlah;
        }

        // Perbandingan per kategori + total
        $catCur = $this->expenseAgg($awal->toDateString(), $akhir->toDateString());
        $catPrev = $this->expenseAgg($awalPrev->toDateString(), $akhirPrev->toDateString());
        $pct = fn($cur, $prev) => $prev > 0 ? round(($cur - $prev) / $prev * 100, 1) : null;
        $kategori = [];
        foreach (['Belanja', 'Gaji', 'Operasional'] as $k) {
            $kategori[$k] = ['cur' => $catCur[$k], 'prev' => $catPrev[$k], 'pct' => $pct($catCur[$k], $catPrev[$k])];
        }
        $totalCur = array_sum($catCur); $totalPrev = array_sum($catPrev);

        return response()->json([
            'labels' => $labels,
            'series' => $series,
            'total'  => $totalCur,
            'total_prev' => $totalPrev,
            'total_pct' => $pct($totalCur, $totalPrev),
            'prev_label' => $prevLabel,
            'kategori' => $kategori,
        ]);
    }

    public function grafikData(Request $request)
    {
        $filter = $request->input('filter', 'bulan_ini');
        $today = now()->toDateString();

        switch ($filter) {
            case 'hari_ini':
                $awal = $today;
                $akhir = $today;
                $groupFormat = '%H:00';
                break;
            case 'minggu_ini':
                $awal = now()->startOfWeek()->toDateString();
                $akhir = now()->endOfWeek()->toDateString();
                $groupFormat = '%Y-%m-%d';
                break;
            case 'bulan_lalu':
                $awal = now()->subMonth()->startOfMonth()->toDateString();
                $akhir = now()->subMonth()->endOfMonth()->toDateString();
                $groupFormat = '%Y-%m-%d';
                break;
            case 'custom':
                $awal = $request->input('dari', now()->startOfMonth()->toDateString());
                $akhir = $request->input('sampai', $today);
                $groupFormat = '%Y-%m-%d';
                break;
            default: // bulan_ini
                $awal = now()->startOfMonth()->toDateString();
                $akhir = now()->endOfMonth()->toDateString();
                $groupFormat = '%Y-%m-%d';
                break;
        }

        $isPerJam = $filter === 'hari_ini';

        // Query data penjualan
        $rows = DB::table('penjualan_headers')
            ->where('status_pesanan', '!=', 'Batal')
            ->when($isPerJam,
                fn($q) => $q->whereDate('created_at', $awal),
                fn($q) => $q->whereBetween('tgl_pesanan', [$awal, $akhir])
            )
            ->selectRaw("
                DATE_FORMAT(" . ($isPerJam ? "created_at" : "tgl_pesanan") . ", '{$groupFormat}') as label_key,
                COUNT(*) as jumlah_pesanan,
                SUM(COALESCE(net_settlement, gmv_kotor - COALESCE(diskon_manual, 0))) as total_omset
            ")
            ->groupBy('label_key')
            ->orderBy('label_key')
            ->get()
            ->keyBy('label_key');

        // Jumlah BOTOL (SUM qty) per periode — dari detail, kecuali batal/gratis/induk-bundle.
        $botolRows = DB::table('penjualan_details as d')
            ->join('penjualan_headers as h', 'h.internal_id', '=', 'd.internal_id')
            ->where('h.status_pesanan', '!=', 'Batal')
            ->where('h.channel', '!=', 'Gratis')
            ->where('d.sku_id', 'not like', 'BUNDLE%')
            ->when($isPerJam,
                fn($q) => $q->whereDate('h.created_at', $awal),
                fn($q) => $q->whereBetween('h.tgl_pesanan', [$awal, $akhir])
            )
            ->selectRaw("
                DATE_FORMAT(h." . ($isPerJam ? "created_at" : "tgl_pesanan") . ", '{$groupFormat}') as label_key,
                SUM(d.qty) as total_botol
            ")
            ->groupBy('label_key')
            ->orderBy('label_key')
            ->get()
            ->keyBy('label_key');

        // Bangun label lengkap (isi 0 untuk slot yang kosong)
        $labels = [];
        $dataPesanan = [];
        $dataOmset = [];
        $dataBotol = [];

        if ($isPerJam) {
            for ($h = 0; $h < 24; $h++) {
                $key = sprintf('%02d:00', $h);
                $labels[] = $key;
                $dataPesanan[] = (int) ($rows[$key]->jumlah_pesanan ?? 0);
                $dataOmset[] = (float) ($rows[$key]->total_omset ?? 0);
                $dataBotol[] = (int) ($botolRows[$key]->total_botol ?? 0);
            }
        } else {
            $current = \Carbon\Carbon::parse($awal);
            $end = \Carbon\Carbon::parse($akhir);
            while ($current->lte($end)) {
                $key = $current->format('Y-m-d');
                $labels[] = $current->format('d M');
                $dataPesanan[] = (int) ($rows[$key]->jumlah_pesanan ?? 0);
                $dataOmset[] = (float) ($rows[$key]->total_omset ?? 0);
                $dataBotol[] = (int) ($botolRows[$key]->total_botol ?? 0);
                $current->addDay();
            }
        }

        // Ringkasan
        $totalPesanan = array_sum($dataPesanan);
        $totalOmset = array_sum($dataOmset);
        $totalBotol = array_sum($dataBotol);

        return response()->json([
            'labels' => $labels,
            'pesanan' => $dataPesanan,
            'omset' => $dataOmset,
            'botol' => $dataBotol,
            'ringkasan' => [
                'total_pesanan' => $totalPesanan,
                'total_omset' => $totalOmset,
                'total_botol' => $totalBotol,
            ],
        ]);
    }

    /** Qty & omset per SKU untuk satu rentang (keyed by sku_id). */
    private function produkAgg(string $awal, string $akhir)
    {
        return DB::table('penjualan_details as d')
            ->join('penjualan_headers as h', 'd.internal_id', '=', 'h.internal_id')
            ->join('master_produks as p', 'd.sku_id', '=', 'p.sku_id')
            ->where('h.status_pesanan', '!=', 'Batal')
            ->where('h.channel', '!=', 'Gratis') // produk gratis bukan penjualan
            // Kecualikan baris INDUK bundle (BUNDLE*) — omzet & qty-nya sudah terwakili
            // oleh baris anak (parfum asli, dari_bundle=1). Tanpa ini omzet bundle dobel.
            ->where('d.sku_id', 'not like', 'BUNDLE%')
            ->whereBetween('h.tgl_pesanan', [$awal, $akhir])
            ->selectRaw('d.sku_id, p.nama_produk, p.ukuran_ml, SUM(d.qty) as total_qty, SUM(d.subtotal) as total_omset')
            ->groupBy('d.sku_id', 'p.nama_produk', 'p.ukuran_ml')
            ->get()->keyBy('sku_id');
    }

    /** Net omset & jumlah pesanan per grup channel untuk satu rentang. */
    private function channelAgg(string $awal, string $akhir): array
    {
        $raw = DB::table('penjualan_headers')
            ->where('status_pesanan', '!=', 'Batal')
            ->whereBetween('tgl_pesanan', [$awal, $akhir])
            ->selectRaw('channel, COUNT(*) as jml, SUM(COALESCE(net_settlement, gmv_kotor - COALESCE(diskon_manual,0))) as net_omset')
            ->groupBy('channel')->get();
        $grup = [
            'TikTok'   => ['jml' => 0, 'net_omset' => 0.0], 'Shopee'   => ['jml' => 0, 'net_omset' => 0.0],
            'Offline'  => ['jml' => 0, 'net_omset' => 0.0], 'Website'  => ['jml' => 0, 'net_omset' => 0.0],
            'Reseller' => ['jml' => 0, 'net_omset' => 0.0],
        ];
        foreach ($raw as $row) {
            $ch = strtolower($row->channel ?? '');
            if (str_contains($ch, 'tiktok'))                                     $key = 'TikTok';
            elseif (str_contains($ch, 'shopee'))                                 $key = 'Shopee';
            elseif (str_contains($ch, 'offline') || str_contains($ch, 'refill')) $key = 'Offline';
            elseif (str_contains($ch, 'website'))                                $key = 'Website';
            elseif (str_contains($ch, 'reseller'))                               $key = 'Reseller';
            else continue;
            $grup[$key]['jml'] += (int) $row->jml;
            $grup[$key]['net_omset'] += (float) $row->net_omset;
        }
        return $grup;
    }

    public function stats(Request $request)
    {
        \Carbon\Carbon::setLocale('id');
        $bulan = $request->input('bulan', now()->format('Y-m'));
        $periode = \Carbon\Carbon::createFromFormat('Y-m', $bulan);
        $awal = $periode->copy()->startOfMonth()->toDateString();
        $akhir = $periode->copy()->endOfMonth()->toDateString();
        $prev = $periode->copy()->subMonth();
        $awalPrev = $prev->copy()->startOfMonth()->toDateString();
        $akhirPrev = $prev->copy()->endOfMonth()->toDateString();

        $pct = fn($cur, $p) => $p > 0 ? round(($cur - $p) / $p * 100, 1) : null;

        // ── Produk: top-5 bulan ini + qty bulan sebelumnya ──
        $curProduk = $this->produkAgg($awal, $akhir);
        $prevProduk = $this->produkAgg($awalPrev, $akhirPrev);
        $produkTerbanyak = $curProduk->sortByDesc('total_qty')->take(5)->map(function ($p) use ($prevProduk, $pct) {
            $prevQty = (float) ($prevProduk[$p->sku_id]->total_qty ?? 0);
            $p->qty_prev = $prevQty;
            $p->pct = $pct((float) $p->total_qty, $prevQty);
            return $p;
        })->values();
        $totalQtyCur = (float) $curProduk->sum('total_qty');
        $totalQtyPrev = (float) $prevProduk->sum('total_qty');

        // ── Channel: net per grup + perbandingan ──
        $curCh = $this->channelAgg($awal, $akhir);
        $prevCh = $this->channelAgg($awalPrev, $akhirPrev);
        $channelResult = collect($curCh)->filter(fn($v) => $v['jml'] > 0)
            ->map(function ($v, $k) use ($prevCh, $pct) {
                $prevNet = (float) ($prevCh[$k]['net_omset'] ?? 0);
                return ['channel' => $k, 'jml' => $v['jml'], 'net_omset' => $v['net_omset'],
                        'net_prev' => $prevNet, 'pct' => $pct((float) $v['net_omset'], $prevNet)];
            })->sortByDesc('net_omset')->values();
        $totalNetCur = (float) $channelResult->sum('net_omset');
        $totalNetPrev = (float) collect($prevCh)->sum('net_omset');

        return response()->json([
            'produk_terbanyak'      => $produkTerbanyak,
            'produk_total_qty'      => $totalQtyCur,
            'produk_total_qty_prev' => $totalQtyPrev,
            'produk_total_pct'      => $pct($totalQtyCur, $totalQtyPrev),
            'channel'               => $channelResult,
            'total_net_omset'       => $totalNetCur,
            'total_net_prev'        => $totalNetPrev,
            'total_net_pct'         => $pct($totalNetCur, $totalNetPrev),
            'bulan'                 => $bulan,
            'bulan_label'           => $periode->translatedFormat('F Y'),
            'prev_label'            => $prev->translatedFormat('F Y'),
        ]);
    }
}
