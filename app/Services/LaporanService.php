<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\MutasiKas;
use App\Models\CicilanPembayaran;

/**
 * Sumber tunggal angka headline P&L (omset, HPP, biaya, laba bersih) untuk satu periode.
 * Dipakai LaporanController (halaman P&L) & DashboardController (strip finansial) agar konsisten.
 */
class LaporanService
{
    public function summary(string $awal, string $akhir): array
    {
        // ── Pendapatan ──
        $penjualanMP = DB::table('penjualan_headers')
            ->where('status_pesanan', '!=', 'Batal')
            ->where('status_pembayaran', 'Cair')
            ->whereNotNull('tgl_cair_saldo')
            ->whereBetween('tgl_cair_saldo', [$awal, $akhir])
            ->selectRaw('COUNT(*) as jml, SUM(net_settlement) as net')
            ->first();

        $penjualanNonMP = DB::table('penjualan_headers')
            ->where('status_pesanan', '!=', 'Batal')
            ->where('status_pembayaran', 'Lunas')
            ->whereBetween('tgl_pesanan', [$awal, $akhir])
            ->selectRaw('COUNT(*) as jml, SUM(gmv_kotor - diskon_manual) as net')
            ->first();

        $omsetMP      = (float) ($penjualanMP->net ?? 0);
        $omsetNonMP   = (float) ($penjualanNonMP->net ?? 0);
        $totalOmset   = $omsetMP + $omsetNonMP;
        $totalPesanan = ((int) ($penjualanMP->jml ?? 0)) + ((int) ($penjualanNonMP->jml ?? 0));

        // ── HPP ──
        $hppMP = DB::table('penjualan_details as d')
            ->join('penjualan_headers as h', 'd.internal_id', '=', 'h.internal_id')
            ->where('h.status_pesanan', '!=', 'Batal')
            ->where('h.status_pembayaran', 'Cair')
            ->whereNotNull('h.tgl_cair_saldo')
            ->whereBetween('h.tgl_cair_saldo', [$awal, $akhir])
            ->selectRaw('SUM(d.hpp_satuan * d.qty) as t')->value('t');

        $hppNonMP = DB::table('penjualan_details as d')
            ->join('penjualan_headers as h', 'd.internal_id', '=', 'h.internal_id')
            ->where('h.status_pesanan', '!=', 'Batal')
            ->where('h.status_pembayaran', 'Lunas')
            ->whereBetween('h.tgl_pesanan', [$awal, $akhir])
            ->selectRaw('SUM(d.hpp_satuan * d.qty) as t')->value('t');

        $totalHpp   = (float) ($hppMP ?? 0) + (float) ($hppNonMP ?? 0);
        $labaKotor  = $totalOmset - $totalHpp;
        $marginKotor = $totalOmset > 0 ? ($labaKotor / $totalOmset) * 100 : 0;

        // ── Biaya operasional ──
        // Gaji dibebankan ke BULAN BIAYA (accrual), bukan tanggal bayar — biar laba per bulan
        // akurat walau transfer di bulan berikutnya. Kas tetap keluar di tanggal_bayar (mutasi
        // 'GAJI-%' dikecualikan di sini agar tak dobel/salah bulan).
        $pengeluaranNonGaji = (float) MutasiKas::where('tipe', 'keluar')
            ->where('kategori', 'pengeluaran')
            ->where('ref_id', 'not like', 'GAJI-%')
            ->whereBetween('tanggal', [$awal, $akhir])
            ->sum('jumlah');
        $totalGaji = (float) DB::table('gaji')
            ->whereBetween('bulan_biaya', [$awal, $akhir])
            ->selectRaw('SUM(gaji_pokok + tunjangan - potongan_lain) as t')->value('t');
        $totalPengeluaran = round($pengeluaranNonGaji + $totalGaji, 2);

        $cicilan = CicilanPembayaran::where('status', 'lunas')
            ->whereBetween('tgl_bayar', [$awal, $akhir])
            ->selectRaw('SUM(jumlah_bayar) as bayar, SUM(biaya_tambahan) as biaya')
            ->first();
        $totalCicilan = (float) ($cicilan->bayar ?? 0) + (float) ($cicilan->biaya ?? 0);

        // Biaya promo/sampel affiliate (non-tunai: HPP produk gratis)
        $totalSampel = (float) DB::table('sampel_affiliate')
            ->whereBetween('tanggal', [$awal, $akhir])->sum('total_hpp');

        // Susut stok dari opname (selisih NEGATIF × HPP item) — kerugian non-tunai.
        // Surplus (selisih positif) sengaja tidak dibukukan (konservatif).
        $susutBibit = (float) DB::table('koreksi_stoks as k')
            ->join('master_bibits as b', 'k.item_id', '=', 'b.bibit_id')
            ->where('k.item_type', 'bibit')->where('k.selisih', '<', 0)
            ->whereBetween('k.tanggal', [$awal, $akhir])
            ->selectRaw('SUM(ABS(k.selisih) * b.harga_per_ml) as t')->value('t');
        $susutKomponen = (float) DB::table('koreksi_stoks as k')
            ->join('master_komponens as m', 'k.item_id', '=', 'm.komponen_id')
            ->where('k.item_type', 'komponen')->where('k.selisih', '<', 0)
            ->whereBetween('k.tanggal', [$awal, $akhir])
            ->selectRaw('SUM(ABS(k.selisih) * m.harga_satuan) as t')->value('t');
        $totalSusut = round((float) $susutBibit + (float) $susutKomponen, 2);

        // Pendapatan lain-lain (jual tester, dll) — kategori 'penerimaan'
        $totalPenerimaan = (float) MutasiKas::where('tipe', 'masuk')->where('kategori', 'penerimaan')
            ->whereBetween('tanggal', [$awal, $akhir])->sum('jumlah');

        // Patungan biaya bersama (mis. 420F untuk sewa/listrik/internet) — BUKAN pendapatan,
        // tapi PENGURANG biaya operasional. Bulan mereka libur → 0 → HAGOS tanggung penuh (benar).
        $totalPatungan = (float) MutasiKas::where('tipe', 'masuk')->where('kategori', 'patungan')
            ->whereBetween('tanggal', [$awal, $akhir])->sum('jumlah');

        $totalBiayaOps = $totalPengeluaran + $totalCicilan + $totalSampel + $totalSusut - $totalPatungan;
        $labaBersih    = $labaKotor + $totalPenerimaan - $totalBiayaOps;
        $marginBersih  = $totalOmset > 0 ? ($labaBersih / $totalOmset) * 100 : 0;

        return compact(
            'omsetMP', 'omsetNonMP', 'totalOmset', 'totalPesanan',
            'totalHpp', 'labaKotor', 'marginKotor', 'totalPenerimaan',
            'totalPengeluaran', 'totalCicilan', 'totalSampel', 'totalSusut', 'totalPatungan', 'totalBiayaOps',
            'labaBersih', 'marginBersih'
        );
    }
}
