<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\MutasiKas;
use App\Models\CicilanPembayaran;

class LaporanController extends Controller
{
    /**
     * Laporan Laba per Aroma / Produk. Basis pengakuan sama dgn P&L:
     * MP Cair (by tgl_cair_saldo) + non-MP Lunas (by tgl_pesanan), kecuali Batal, sudah diracik.
     * Laba per baris = margin_satuan × qty (MP = net-basis setelah fee; non-MP = kotor).
     * Omzet per baris = (hpp_satuan + margin_satuan) × qty  → konsisten dgn margin.
     * Catatan: diskon order-level & biaya operasional TIDAK dialokasikan per produk (ini laba kotor produk).
     */
    public function labaProduk(Request $request)
    {
        $bulan = $request->input('bulan', now()->format('Y-m'));
        $periode = Carbon::createFromFormat('Y-m', $bulan);
        $awal = $periode->copy()->startOfMonth()->toDateString();
        $akhir = $periode->copy()->endOfMonth()->toDateString();
        $group = $request->input('group') === 'sku' ? 'sku' : 'aroma';

        $bulanTersedia = DB::table('penjualan_headers')
            ->selectRaw("DATE_FORMAT(tgl_pesanan, '%Y-%m') as bulan")
            ->groupBy('bulan')->orderBy('bulan', 'desc')->pluck('bulan');

        $base = DB::table('penjualan_details as d')
            ->join('penjualan_headers as h', 'h.internal_id', '=', 'd.internal_id')
            ->leftJoin('master_produks as p', 'p.sku_id', '=', 'd.sku_id')
            ->where('h.status_pesanan', '!=', 'Batal')
            ->whereNotNull('d.hpp_satuan')
            ->where(function ($q) use ($awal, $akhir) {
                $q->where(function ($q2) use ($awal, $akhir) {
                    $q2->where('h.status_pembayaran', 'Cair')->whereNotNull('h.tgl_cair_saldo')
                        ->whereBetween('h.tgl_cair_saldo', [$awal, $akhir]);
                })->orWhere(function ($q2) use ($awal, $akhir) {
                    $q2->where('h.status_pembayaran', 'Lunas')->whereBetween('h.tgl_pesanan', [$awal, $akhir]);
                });
            });

        $agg = "SUM(d.qty) as qty,
            SUM(d.hpp_satuan * d.qty) as hpp,
            SUM(d.margin_satuan * d.qty) as laba,
            SUM((d.hpp_satuan + d.margin_satuan) * d.qty) as omzet";

        if ($group === 'sku') {
            $rows = (clone $base)
                ->selectRaw("d.sku_id as kode, MAX(p.nama_produk) as nama, MAX(p.ukuran_ml) as ukuran, {$agg}")
                ->groupBy('d.sku_id')->get();
        } else {
            $rows = (clone $base)
                ->selectRaw("COALESCE(p.sku_aroma, d.sku_id) as kode, MAX(p.nama_produk) as nama, {$agg}")
                ->groupByRaw('COALESCE(p.sku_aroma, d.sku_id)')->get();
        }

        $rows = collect($rows)->map(function ($r) {
            $r->omzet = (float) $r->omzet; $r->hpp = (float) $r->hpp; $r->laba = (float) $r->laba; $r->qty = (int) $r->qty;
            $r->margin = $r->omzet > 0 ? ($r->laba / $r->omzet * 100) : 0;
            return $r;
        })->sortByDesc('laba')->values();

        $totOmzet = $rows->sum('omzet'); $totHpp = $rows->sum('hpp'); $totLaba = $rows->sum('laba'); $totQty = $rows->sum('qty');
        $marginTotal = $totOmzet > 0 ? ($totLaba / $totOmzet * 100) : 0;
        $jmlItem = $rows->count();
        $jmlRugi = $rows->filter(fn($r) => $r->laba < 0)->count();

        return view('laporan.laba_produk', compact(
            'rows', 'bulan', 'periode', 'bulanTersedia', 'group',
            'totOmzet', 'totHpp', 'totLaba', 'totQty', 'marginTotal', 'jmlItem', 'jmlRugi'
        ));
    }

    /**
     * Analisis Perputaran & Stok Mati Bibit. Konsumsi = ml bibit dari pesanan diproses
     * (bukan Menunggu/Batal) dalam N bulan terakhir. Menyorot bibit yang stoknya mengendap
     * (tidak/lambat terpakai) = modal terkunci. Catatan: resep mix belum diperhitungkan (single-bibit).
     */
    public function perputaranBibit(Request $request)
    {
        $rentang = (int) $request->input('rentang', 3);
        if (!in_array($rentang, [1, 3, 6, 12])) $rentang = 3;
        $awal = now()->subMonthsNoOverflow($rentang)->toDateString();
        $akhir = now()->toDateString();

        // Konsumsi ml per bibit dalam rentang
        $konsumsi = DB::table('penjualan_details as d')
            ->join('penjualan_headers as h', 'h.internal_id', '=', 'd.internal_id')
            ->join('master_reseps as r', 'r.sku_id', '=', 'd.sku_id')
            ->whereNotIn('h.status_pesanan', ['Menunggu', 'Batal'])
            ->whereNotNull('r.bibit_id')
            ->whereBetween('h.tgl_pesanan', [$awal, $akhir])
            ->groupBy('r.bibit_id')
            ->selectRaw('r.bibit_id, SUM(r.ml_bibit_utama * d.qty) as ml')
            ->pluck('ml', 'bibit_id');

        // Tanggal terakhir bibit terpakai (all-time)
        $lastUsed = DB::table('penjualan_details as d')
            ->join('penjualan_headers as h', 'h.internal_id', '=', 'd.internal_id')
            ->join('master_reseps as r', 'r.sku_id', '=', 'd.sku_id')
            ->whereNotIn('h.status_pesanan', ['Menunggu', 'Batal'])
            ->whereNotNull('r.bibit_id')
            ->groupBy('r.bibit_id')
            ->selectRaw('r.bibit_id, MAX(h.tgl_pesanan) as last')
            ->pluck('last', 'bibit_id');

        $rank = ['mati' => 0, 'lambat' => 1, 'perlu_beli' => 2, 'sehat' => 3, 'habis' => 4];

        $rows = \App\Models\MasterBibit::orderBy('nama_bibit')->get()->map(function ($b) use ($konsumsi, $lastUsed, $rentang, $rank) {
            $stok = (float) $b->stok_ml;
            $harga = (float) $b->harga_per_ml;
            $nilai = $stok * $harga;
            $kons = (float) ($konsumsi[$b->bibit_id] ?? 0);
            $konsBulanan = $rentang > 0 ? $kons / $rentang : 0;
            $coverage = $konsBulanan > 0 ? ($stok / $konsBulanan) : null; // bulan tersisa; null = tak terpakai

            if ($stok <= 0)                                             $status = 'habis';
            elseif ($kons <= 0)                                         $status = 'mati';
            elseif ((float) $b->threshold_ml > 0 && $stok <= (float) $b->threshold_ml) $status = 'perlu_beli';
            elseif ($coverage !== null && $coverage > 6)                $status = 'lambat';
            else                                                        $status = 'sehat';

            return (object) [
                'bibit_id' => $b->bibit_id,
                'nama' => $b->nama_bibit,
                'stok' => $stok,
                'harga' => $harga,
                'nilai' => $nilai,
                'kons' => $kons,
                'kons_bulanan' => $konsBulanan,
                'coverage' => $coverage,
                'last_used' => $lastUsed[$b->bibit_id] ?? null,
                'status' => $status,
                'rank' => $rank[$status],
            ];
        })
        ->sortBy([['rank', 'asc'], ['nilai', 'desc']])
        ->values();

        // Ringkasan
        $nilaiTotal = (float) $rows->sum('nilai');
        $nilaiMati = (float) $rows->where('status', 'mati')->sum('nilai');
        $jmlMati = $rows->where('status', 'mati')->count();
        $jmlLambat = $rows->where('status', 'lambat')->count();
        $jmlPerluBeli = $rows->where('status', 'perlu_beli')->count();

        return view('laporan.perputaran_bibit', compact(
            'rows', 'rentang', 'awal', 'akhir',
            'nilaiTotal', 'nilaiMati', 'jmlMati', 'jmlLambat', 'jmlPerluBeli'
        ));
    }

    /**
     * Laporan Retur/Pembatalan: jumlah & nilai pesanan batal per alasan & channel,
     * plus kerugian barang rusak saat retur (stok_jadi_logs tipe 'rusak').
     */
    public function retur(Request $request)
    {
        $dari = $request->input('dari');
        $sampai = $request->input('sampai');
        $channel = $request->input('channel');
        $nilaiExpr = 'gmv_kotor - COALESCE(diskon_manual,0)';

        $base = DB::table('penjualan_headers')->where('status_pesanan', 'Batal');
        if ($dari)    $base->whereDate('tgl_pesanan', '>=', $dari);
        if ($sampai)  $base->whereDate('tgl_pesanan', '<=', $sampai);
        if ($channel) $base->where('channel', $channel);

        $agg = (clone $base)->selectRaw("COUNT(*) c, SUM($nilaiExpr) n")->first();
        $totalBatal = (int) ($agg->c ?? 0);
        $nilaiBatal = (float) ($agg->n ?? 0);

        $perAlasan = (clone $base)
            ->selectRaw("COALESCE(NULLIF(alasan_batal,''),'(tanpa alasan)') alasan, COUNT(*) c, SUM($nilaiExpr) nilai")
            ->groupBy('alasan')->orderByDesc('c')->get();

        $perChannel = (clone $base)
            ->selectRaw("channel, COUNT(*) c, SUM($nilaiExpr) nilai")
            ->groupBy('channel')->orderByDesc('c')->get();

        $detail = (clone $base)->orderByDesc('tgl_pesanan')->orderByDesc('created_at')
            ->get(['internal_id', 'external_order_id', 'channel', 'tgl_pesanan', 'alasan_batal',
                   'gmv_kotor', 'diskon_manual', 'perlu_barang_balik', 'tgl_retur_diterima']);

        // Total order (semua status) di periode & channel sama → hitung tingkat pembatalan
        $totOrderQ = DB::table('penjualan_headers');
        if ($dari)    $totOrderQ->whereDate('tgl_pesanan', '>=', $dari);
        if ($sampai)  $totOrderQ->whereDate('tgl_pesanan', '<=', $sampai);
        if ($channel) $totOrderQ->where('channel', $channel);
        $totalOrder = (int) $totOrderQ->count();
        $tingkatBatal = $totalOrder > 0 ? ($totalBatal / $totalOrder * 100) : 0;

        // Kerugian barang rusak saat retur (modal hangus)
        $rusakQ = DB::table('stok_jadi_logs')->where('tipe', 'rusak');
        if ($dari)   $rusakQ->whereDate('tanggal', '>=', $dari);
        if ($sampai) $rusakQ->whereDate('tanggal', '<=', $sampai);
        $rusakAgg = (clone $rusakQ)->selectRaw('SUM(qty) q, SUM(qty * hpp_per_unit) nilai')->first();
        $rusakQty = (int) ($rusakAgg->q ?? 0);
        $rusakNilai = (float) ($rusakAgg->nilai ?? 0);

        $channels = DB::table('penjualan_headers')->where('status_pesanan', 'Batal')
            ->distinct()->orderBy('channel')->pluck('channel');

        return view('laporan.retur', compact(
            'dari', 'sampai', 'channel', 'channels',
            'totalBatal', 'nilaiBatal', 'perAlasan', 'perChannel', 'detail',
            'totalOrder', 'tingkatBatal', 'rusakQty', 'rusakNilai'
        ));
    }

    /**
     * Laporan Diskon Diberikan — khusus penjualan NON-marketplace (Offline/Reseller/Website).
     * Diskon marketplace datang dari settlement, bukan "diskon yang kita beri", jadi dikecualikan.
     */
    public function diskon(Request $request)
    {
        $dari = $request->input('dari', now()->startOfMonth()->toDateString());
        $sampai = $request->input('sampai', now()->toDateString());
        $channel = $request->input('channel');

        $base = DB::table('penjualan_headers')
            ->where('status_pesanan', '!=', 'Batal')
            ->where('channel', 'not like', 'Marketplace%')
            ->where('channel', '!=', 'Gratis')
            ->where('diskon_manual', '>', 0);
        if ($dari)    $base->whereDate('tgl_pesanan', '>=', $dari);
        if ($sampai)  $base->whereDate('tgl_pesanan', '<=', $sampai);
        if ($channel) $base->where('channel', $channel);

        $agg = (clone $base)->selectRaw('COUNT(*) c, SUM(diskon_manual) d, SUM(gmv_kotor) g')->first();
        $totalPesanan    = (int) ($agg->c ?? 0);
        $totalDiskon     = (float) ($agg->d ?? 0);
        $totalOmzetKotor = (float) ($agg->g ?? 0);
        $rataDiskonPct   = $totalOmzetKotor > 0 ? $totalDiskon / $totalOmzetKotor * 100 : 0;

        $perChannel = (clone $base)->selectRaw('channel, COUNT(*) c, SUM(diskon_manual) d, SUM(gmv_kotor) g')
            ->groupBy('channel')->orderByDesc('d')->get();

        $perPelanggan = (clone $base)->selectRaw("COALESCE(NULLIF(nama_pembeli,''),'(tanpa nama)') pembeli, COUNT(*) c, SUM(diskon_manual) d")
            ->groupBy('pembeli')->orderByDesc('d')->limit(15)->get();

        $detail = (clone $base)->orderByDesc('tgl_pesanan')->orderByDesc('created_at')
            ->get(['internal_id', 'external_order_id', 'channel', 'tgl_pesanan', 'nama_pembeli', 'gmv_kotor', 'diskon_manual']);

        $channels = DB::table('penjualan_headers')->where('channel', 'not like', 'Marketplace%')
            ->where('channel', '!=', 'Gratis')->where('diskon_manual', '>', 0)
            ->distinct()->orderBy('channel')->pluck('channel');

        return view('laporan.diskon', compact('dari', 'sampai', 'channel', 'channels',
            'totalPesanan', 'totalDiskon', 'totalOmzetKotor', 'rataDiskonPct', 'perChannel', 'perPelanggan', 'detail'));
    }

    /**
     * Laporan Afiliasi — pesanan yang kena "Komisi Afiliasi" di settlement TikTok (penanda afiliasi).
     * Menjawab: berapa besar afiliasi berkontribusi & berapa komisi yang dibayar.
     */
    public function afiliasi(Request $request)
    {
        $dari = $request->input('dari', now()->startOfMonth()->toDateString());
        $sampai = $request->input('sampai', now()->toDateString());

        $base = DB::table('penjualan_headers')
            ->where('status_pesanan', '!=', 'Batal')
            ->where('komisi_afiliasi', '>', 0)
            ->whereBetween('tgl_pesanan', [$dari, $sampai]);

        $agg = (clone $base)->selectRaw('COUNT(*) c, SUM(gmv_kotor) g, SUM(komisi_afiliasi) k, SUM(COALESCE(net_settlement, gmv_kotor)) net')->first();
        $totalPesananAff = (int) ($agg->c ?? 0);
        $gmvAff    = (float) ($agg->g ?? 0);
        $komisiAff = (float) ($agg->k ?? 0);
        $netAff    = (float) ($agg->net ?? 0);

        // Pembanding: seluruh penjualan (non-batal, non-gratis) di periode → untuk hitung %
        $totAll = DB::table('penjualan_headers')->where('status_pesanan', '!=', 'Batal')->where('channel', '!=', 'Gratis')
            ->whereBetween('tgl_pesanan', [$dari, $sampai])->selectRaw('COUNT(*) c, SUM(gmv_kotor) g')->first();
        $pctPesanan = ($totAll->c ?? 0) > 0 ? $totalPesananAff / $totAll->c * 100 : 0;
        $pctGmv     = ($totAll->g ?? 0) > 0 ? $gmvAff / $totAll->g * 100 : 0;
        $komisiPct  = $gmvAff > 0 ? $komisiAff / $gmvAff * 100 : 0;

        $detail = (clone $base)->orderByDesc('tgl_pesanan')->orderByDesc('created_at')
            ->get(['internal_id', 'external_order_id', 'channel', 'tgl_pesanan', 'nama_pembeli', 'gmv_kotor', 'komisi_afiliasi', 'net_settlement']);

        return view('laporan.afiliasi', compact('dari', 'sampai', 'totalPesananAff', 'gmvAff', 'komisiAff', 'netAff',
            'pctPesanan', 'pctGmv', 'komisiPct', 'detail'));
    }

    /**
     * Laporan Pajak PPh Final UMKM 0,5% (PP 55/2022). Basis = peredaran bruto per bulan.
     * Skema 'pribadi' = WP Orang Pribadi, Rp500 jt/tahun pertama BEBAS PPh (dihitung kumulatif).
     * Skema 'penuh' = 0,5% atas seluruh bruto (mis. WP Badan). Estimasi — konfirmasi ke konsultan pajak.
     */
    public function pajak(Request $request)
    {
        $tahun = (int) $request->input('tahun', now()->year);
        $skema = $request->input('skema') === 'penuh' ? 'penuh' : 'pribadi';
        $rate = 0.005;
        $batasBebas = 500_000_000;

        // Peredaran bruto per bulan (non-Batal, by tgl_pesanan). MP = gross (sebelum fee), non-MP = gmv−diskon.
        $raw = DB::table('penjualan_headers')
            ->where('status_pesanan', '!=', 'Batal')
            ->whereYear('tgl_pesanan', $tahun)
            ->selectRaw("MONTH(tgl_pesanan) bln,
                SUM(CASE WHEN channel LIKE 'Marketplace%'
                    THEN COALESCE(NULLIF(gross_settlement,0), gmv_kotor)
                    ELSE gmv_kotor - COALESCE(diskon_manual,0) END) bruto")
            ->groupBy('bln')->pluck('bruto', 'bln');

        $rows = [];
        $kumBruto = 0; $totBruto = 0; $totKena = 0; $totPph = 0;
        for ($m = 1; $m <= 12; $m++) {
            $bruto = (float) ($raw[$m] ?? 0);
            $kumBruto += $bruto;
            if ($skema === 'pribadi') {
                $kena = $kumBruto > $batasBebas ? min($bruto, $kumBruto - $batasBebas) : 0;
            } else {
                $kena = $bruto;
            }
            $pph = $kena * $rate;
            $rows[] = (object) [
                'bln' => $m, 'bruto' => $bruto, 'kum_bruto' => $kumBruto, 'kena' => $kena, 'pph' => $pph,
            ];
            $totBruto += $bruto; $totKena += $kena; $totPph += $pph;
        }

        $tahunTersedia = DB::table('penjualan_headers')
            ->selectRaw('DISTINCT YEAR(tgl_pesanan) th')->orderByDesc('th')->pluck('th');
        $sisaBebas = max(0, $batasBebas - $totBruto);

        return view('laporan.pajak', compact(
            'rows', 'tahun', 'skema', 'rate', 'batasBebas',
            'totBruto', 'totKena', 'totPph', 'tahunTersedia', 'sisaBebas'
        ));
    }

    public function pl(Request $request, \App\Services\LaporanService $laporan)
    {
        // Periode: default bulan ini
        $bulan = $request->input('bulan', now()->format('Y-m'));
        $periode = Carbon::createFromFormat('Y-m', $bulan);
        $awal = $periode->copy()->startOfMonth()->toDateString();
        $akhir = $periode->copy()->endOfMonth()->toDateString();

        // Daftar bulan tersedia (dari tgl_pesanan penjualan)
        $bulanTersedia = DB::table('penjualan_headers')
            ->selectRaw("DATE_FORMAT(tgl_pesanan, '%Y-%m') as bulan")
            ->groupBy('bulan')
            ->orderBy('bulan', 'desc')
            ->pluck('bulan');

        // ─── ANGKA HEADLINE (sumber tunggal lewat service) ───
        $s = $laporan->summary($awal, $akhir);
        $omsetMP = $s['omsetMP']; $omsetNonMP = $s['omsetNonMP']; $totalOmset = $s['totalOmset'];
        $totalPesanan = $s['totalPesanan']; $totalHpp = $s['totalHpp'];
        $labaKotor = $s['labaKotor']; $marginKotor = $s['marginKotor'];
        $totalPengeluaran = $s['totalPengeluaran']; $totalCicilan = $s['totalCicilan'];
        $totalSampel = $s['totalSampel']; $totalSusut = $s['totalSusut']; $totalPenerimaan = $s['totalPenerimaan'];
        $totalPatungan = $s['totalPatungan'];
        $totalBiayaOps = $s['totalBiayaOps']; $labaBersih = $s['labaBersih']; $marginBersih = $s['marginBersih'];

        // Breakdown omset per channel
        $omsetPerChannel = DB::table('penjualan_headers')
            ->where('status_pesanan', '!=', 'Batal')
            ->where(function ($q) use ($awal, $akhir) {
                $q->where(function ($q2) use ($awal, $akhir) {
                    // Marketplace Cair
                    $q2->where('status_pembayaran', 'Cair')
                        ->whereNotNull('tgl_cair_saldo')
                        ->whereBetween('tgl_cair_saldo', [$awal, $akhir]);
                })->orWhere(function ($q2) use ($awal, $akhir) {
                    // Non-marketplace Lunas
                    $q2->where('status_pembayaran', 'Lunas')
                        ->whereBetween('tgl_pesanan', [$awal, $akhir]);
                });
            })
            ->selectRaw("channel, COUNT(*) as jml, SUM(net_settlement) as net_mp, SUM(gmv_kotor - diskon_manual) as net_nonmp, SUM(gmv_kotor) as gmv")
            ->groupBy('channel')
            ->orderBy('gmv', 'desc')
            ->get()
            ->map(function ($r) {
                $isMP = str_contains(strtolower($r->channel), 'marketplace') ||
                        in_array(strtolower($r->channel), ['tiktok shop', 'shopee', 'tokopedia', 'lazada']);
                $net = $isMP ? (float) $r->net_mp : (float) $r->net_nonmp;
                return (object) [
                    'channel' => $r->channel,
                    'jml' => $r->jml,
                    'net' => $net,
                    'gmv' => (float) $r->gmv,
                ];
            });

        // ─── DETAIL untuk tampilan (totalnya dari service di atas) ───
        // Rincian pengeluaran per kategori
        $pengeluaran = MutasiKas::where('tipe', 'keluar')
            ->where('kategori', 'pengeluaran')
            ->whereBetween('tanggal', [$awal, $akhir])
            ->selectRaw("TRIM(SUBSTRING_INDEX(keterangan, '·', 1)) as nama_kategori, SUM(jumlah) as total")
            ->groupBy('nama_kategori')
            ->orderBy('total', 'desc')
            ->get();

        // Cicilan dibayar bulan ini (rincian)
        $cicilanDibayar = CicilanPembayaran::with('utangCicilan.sumberDana')
            ->where('status', 'lunas')
            ->whereBetween('tgl_bayar', [$awal, $akhir])
            ->get();

        // ─── STATISTIK PESANAN ────────────────────────────────
        $statPesanan = DB::table('penjualan_headers')
            ->whereBetween('tgl_pesanan', [$awal, $akhir])
            ->selectRaw("status_pesanan, COUNT(*) as n")
            ->groupBy('status_pesanan')
            ->pluck('n', 'status_pesanan');

        return view('laporan.pl', compact(
            'bulan', 'periode', 'bulanTersedia',
            'omsetMP', 'omsetNonMP', 'totalOmset', 'totalPesanan',
            'omsetPerChannel',
            'totalHpp', 'labaKotor', 'marginKotor',
            'pengeluaran', 'totalPengeluaran',
            'cicilanDibayar', 'totalCicilan', 'totalSampel', 'totalSusut', 'totalPenerimaan',
            'totalPatungan', 'totalBiayaOps', 'labaBersih', 'marginBersih',
            'statPesanan',
        ));
    }
}
