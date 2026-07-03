<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MutasiKas;
use App\Models\MasterKategori;
use App\Models\MasterAkunKas;

class PengeluaranController extends Controller
{
    private function kategoriList()
    {
        return MasterKategori::where('tipe_kategori', 'Kategori Pengeluaran')
            ->whereNotIn('nilai', ['Belanja Bibit', 'Belanja Komponen'])
            ->orderBy('id')->pluck('nilai');
    }

    public function index(Request $request)
    {
        $dari = $request->input('dari');
        $sampai = $request->input('sampai');
        $jenis = $request->input('jenis');

        $query = MutasiKas::where('tipe', 'keluar')->where('kategori', 'pengeluaran');

        if ($dari)   $query->whereDate('tanggal', '>=', $dari);
        if ($sampai) $query->whereDate('tanggal', '<=', $sampai);
        if ($jenis) {
            $query->where(function ($q) use ($jenis) {
                $q->where('keterangan', $jenis)->orWhere('keterangan', 'like', $jenis . ' · %');
            });
        }

        // Total & breakdown per jenis (ikut filter, kecuali filter jenis)
        $summaryQuery = MutasiKas::where('tipe', 'keluar')->where('kategori', 'pengeluaran');
        if ($dari)   $summaryQuery->whereDate('tanggal', '>=', $dari);
        if ($sampai) $summaryQuery->whereDate('tanggal', '<=', $sampai);
        $allForSummary = $summaryQuery->get();
        $totalSemua = $allForSummary->sum('jumlah');
        $perJenis = $allForSummary->groupBy(fn($m) => trim(explode('·', $m->keterangan)[0]))
            ->map(fn($g) => $g->sum('jumlah'))->sortDesc();

        $pengeluarans = $query->orderBy('tanggal', 'desc')->orderBy('created_at', 'desc')->paginate(50)->withQueryString();
        $totalFilter = (clone $query)->sum('jumlah');

        $kategoriList = $this->kategoriList();
        $akuns = MasterAkunKas::whereNotIn('tipe', ['Piutang'])->orderBy('akun_id')->pluck('nama_akun');
        $admins = MasterKategori::where('tipe_kategori', 'Admin')->orderBy('nilai')->pluck('nilai');

        return view('pengeluaran.index', compact(
            'pengeluarans', 'totalFilter', 'totalSemua', 'perJenis',
            'kategoriList', 'akuns', 'admins', 'dari', 'sampai', 'jenis'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'akun'       => 'required|string',
            'kategori'   => 'required|string',
            'jumlah'     => 'required|numeric|min:1',
            'tanggal'    => 'nullable|date',
            'keterangan' => 'nullable|string',
            'oleh'       => 'nullable|string',
        ]);

        $jumlah = (float) $request->jumlah;

        // Cek saldo akun sebelum potong: izinkan tapi peringatkan bila jadi minus
        $akunRow = MasterAkunKas::where('nama_akun', $request->akun)->first();
        $saldoAwal = $akunRow ? (float) str_replace(['.', ','], ['', '.'], (string) $akunRow->saldo_awal) : 0;
        $masuk = (float) MutasiKas::where('akun', $request->akun)->where('tipe', 'masuk')->sum('jumlah');
        $keluar = (float) MutasiKas::where('akun', $request->akun)->where('tipe', 'keluar')->sum('jumlah');
        $saldoSekarang = $saldoAwal + $masuk - $keluar;

        MutasiKas::catat(
            $request->akun, 'keluar', $jumlah, 'pengeluaran',
            null,
            $request->kategori . ($request->keterangan ? ' · ' . $request->keterangan : ''),
            $request->oleh,
            $request->tanggal ?? now()->toDateString()
        );

        $msg = 'Pengeluaran ' . $request->kategori . ' Rp ' . number_format($jumlah, 0, ',', '.') . ' tercatat.';
        if ($saldoSekarang - $jumlah < 0) {
            $msg .= ' ⚠ Saldo ' . $request->akun . ' jadi minus: Rp ' . number_format($saldoSekarang - $jumlah, 0, ',', '.')
                 . ' (saldo sebelumnya Rp ' . number_format($saldoSekarang, 0, ',', '.') . ').';
        }

        return redirect()->route('pengeluaran.index')->with('success', $msg);
    }

    public function destroy(string $mutasi)
    {
        // Hanya boleh hapus entri pengeluaran (jaga-jaga jangan kena income dll)
        $row = MutasiKas::where('mutasi_id', $mutasi)
            ->where('tipe', 'keluar')->where('kategori', 'pengeluaran')->firstOrFail();
        $row->delete();

        return redirect()->back()->with('success', 'Pengeluaran dihapus & saldo dikembalikan.');
    }
}
