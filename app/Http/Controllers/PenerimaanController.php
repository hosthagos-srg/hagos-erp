<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MutasiKas;
use App\Models\MasterAkunKas;
use App\Models\MasterKategori;

class PenerimaanController extends Controller
{
    /** Saran kategori (datalist) — boleh diisi bebas juga. */
    private array $kategoriDefault = ['Jual Tester', 'Patungan Listrik & Kontrakan', 'Sumbangan', 'Pendapatan Bunga', 'Lainnya'];

    public function index(Request $request)
    {
        $dari = $request->input('dari');
        $sampai = $request->input('sampai');
        $jenis = $request->input('jenis');

        $query = MutasiKas::where('tipe', 'masuk')->where('kategori', 'penerimaan');
        if ($dari)   $query->whereDate('tanggal', '>=', $dari);
        if ($sampai) $query->whereDate('tanggal', '<=', $sampai);
        if ($jenis) {
            $query->where(function ($q) use ($jenis) {
                $q->where('keterangan', $jenis)->orWhere('keterangan', 'like', $jenis . ' · %');
            });
        }

        $summaryQuery = MutasiKas::where('tipe', 'masuk')->where('kategori', 'penerimaan');
        if ($dari)   $summaryQuery->whereDate('tanggal', '>=', $dari);
        if ($sampai) $summaryQuery->whereDate('tanggal', '<=', $sampai);
        $all = $summaryQuery->get();
        $totalSemua = $all->sum('jumlah');
        $perJenis = $all->groupBy(fn($m) => trim(explode('·', $m->keterangan)[0]))
            ->map(fn($g) => $g->sum('jumlah'))->sortDesc();

        $penerimaans = $query->orderBy('tanggal', 'desc')->orderBy('created_at', 'desc')->paginate(50)->withQueryString();
        $totalFilter = (clone $query)->sum('jumlah');

        // gabung saran default + yang sudah pernah dipakai
        $kategoriList = collect($this->kategoriDefault)
            ->merge($all->map(fn($m) => trim(explode('·', $m->keterangan)[0])))
            ->unique()->filter()->values();
        $akuns = MasterAkunKas::whereNotIn('tipe', ['Piutang'])->orderBy('akun_id')->pluck('nama_akun');
        $admins = MasterKategori::where('tipe_kategori', 'Admin')->orderBy('nilai')->pluck('nilai');

        return view('penerimaan.index', compact(
            'penerimaans', 'totalFilter', 'totalSemua', 'perJenis',
            'kategoriList', 'akuns', 'admins', 'dari', 'sampai', 'jenis'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'akun'       => 'required|string',
            'kategori'   => 'required|string|max:100',
            'jumlah'     => 'required|numeric|min:1',
            'tanggal'    => 'nullable|date',
            'keterangan' => 'nullable|string',
            'oleh'       => 'nullable|string',
        ]);

        MutasiKas::catat(
            $request->akun, 'masuk', (float) $request->jumlah, 'penerimaan',
            null,
            $request->kategori . ($request->keterangan ? ' · ' . $request->keterangan : ''),
            $request->oleh,
            $request->tanggal ?? now()->toDateString()
        );

        return redirect()->route('penerimaan.index')->with('success', 'Penerimaan ' . $request->kategori . ' Rp ' . number_format((float) $request->jumlah, 0, ',', '.') . ' tercatat.');
    }

    public function destroy(string $mutasi)
    {
        $row = MutasiKas::where('mutasi_id', $mutasi)
            ->where('tipe', 'masuk')->where('kategori', 'penerimaan')->firstOrFail();
        $row->delete();

        return redirect()->back()->with('success', 'Penerimaan dihapus & saldo disesuaikan.');
    }
}
