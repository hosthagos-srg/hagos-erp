<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\MasterAkunKas;
use App\Models\MasterBibit;
use App\Models\MasterKomponen;
use App\Models\MasterProduk;
use App\Models\MutasiKas;
use App\Models\UtangCicilan;
use App\Models\CicilanPembayaran;
use App\Models\PiutangPribadi;
use App\Models\UtangPribadi;

class NeracaController extends Controller
{
    public function index()
    {
        // ── ASET ──
        // 1. Kas & setara kas (semua akun KECUALI tipe Piutang, agar tidak dobel dgn piutang dari pesanan)
        $masuk = MutasiKas::where('tipe', 'masuk')->selectRaw('akun, SUM(jumlah) t')->groupBy('akun')->pluck('t', 'akun');
        $keluar = MutasiKas::where('tipe', 'keluar')->selectRaw('akun, SUM(jumlah) t')->groupBy('akun')->pluck('t', 'akun');
        $akunKas = MasterAkunKas::where('tipe', '!=', 'Piutang')->get()->map(function ($a) use ($masuk, $keluar) {
            $awal = (float) str_replace(['.', ','], ['', '.'], (string) $a->saldo_awal);
            return (object) ['nama' => $a->nama_akun, 'tipe' => $a->tipe,
                'saldo' => $awal + (float) ($masuk[$a->nama_akun] ?? 0) - (float) ($keluar[$a->nama_akun] ?? 0)];
        });
        $kas = $akunKas->sum('saldo');

        // 2. Piutang: reseller (status Piutang) + settlement marketplace belum cair (perkiraan GMV)
        $piutangReseller = (float) DB::table('penjualan_headers')
            ->where('status_pesanan', '!=', 'Batal')->where('status_pembayaran', 'Piutang')
            ->selectRaw('SUM(gmv_kotor - COALESCE(diskon_manual,0)) as t')->value('t');
        $piutangMP = (float) DB::table('penjualan_headers')
            ->where('status_pesanan', '!=', 'Batal')->where('channel', 'like', 'Marketplace%')
            ->where('status_pembayaran', '!=', 'Cair')
            ->selectRaw('SUM(gmv_kotor) as t')->value('t');
        $piutang = $piutangReseller + $piutangMP;

        // 2b. Piutang pribadi: uang tunai yang DIPINJAMKAN ke orang (aset — sisa belum kembali)
        $piutangPribadi = (float) PiutangPribadi::where('status', 'aktif')->get()->sum(fn($p) => $p->sisa);

        // 3. Persediaan: bibit + komponen + produk jadi (T11)
        $nilaiBibit = (float) MasterBibit::selectRaw('SUM(stok_ml * harga_per_ml) as t')->value('t');
        $nilaiKomponen = (float) MasterKomponen::selectRaw('SUM(stok * harga_satuan) as t')->value('t');
        $nilaiT11 = (float) MasterProduk::selectRaw('SUM(stok_t11 * hpp_t11) as t')->value('t');
        $persediaan = $nilaiBibit + $nilaiKomponen + $nilaiT11;

        $totalAset = $kas + $piutang + $piutangPribadi + $persediaan;

        // ── KEWAJIBAN ──
        $utangIds = UtangCicilan::where('status', 'aktif')->pluck('id');
        $totalUtang = (float) UtangCicilan::where('status', 'aktif')->sum('total_utang');
        $utangDibayar = (float) CicilanPembayaran::whereIn('utang_cicilan_id', $utangIds)->where('status', 'lunas')->sum('jumlah_bayar');
        $sisaUtang = max(0, $totalUtang - $utangDibayar);

        // Utang pribadi: uang tunai yang DIPINJAM dari orang (kewajiban — sisa belum dibayar)
        $utangPribadi = (float) UtangPribadi::where('status', 'aktif')->get()->sum(fn($u) => $u->sisa);

        $totalKewajiban = $sisaUtang + $utangPribadi;

        // ── EKUITAS ──
        $modalBersih = $totalAset - $totalKewajiban; // = kekayaan bersih pemilik
        $modalDisetor = (float) MutasiKas::where('kategori', 'modal')->sum('jumlah')
                      - (float) MutasiKas::where('kategori', 'prive')->sum('jumlah');
        $labaAkumulasi = $modalBersih - $modalDisetor; // laba terkumpul (turunan)

        return view('neraca', compact(
            'akunKas', 'kas', 'piutangReseller', 'piutangMP', 'piutang', 'piutangPribadi',
            'nilaiBibit', 'nilaiKomponen', 'nilaiT11', 'persediaan', 'totalAset',
            'sisaUtang', 'utangPribadi', 'totalKewajiban',
            'modalBersih', 'modalDisetor', 'labaAkumulasi'
        ));
    }
}
