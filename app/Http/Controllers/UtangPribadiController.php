<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\UtangPribadi;
use App\Models\UtangPribadiBayar;
use App\Models\MutasiKas;
use App\Models\MasterAkunKas;

/**
 * Utang Pribadi — Hagos MEMINJAM uang tunai dari orang (kebalikan Piutang Pribadi).
 * Kas otomatis: meminjam = uang MASUK, membayar = uang KELUAR.
 * Kategori MutasiKas 'utang_pribadi' TIDAK dihitung di P&L (bukan biaya/pendapatan) —
 * hanya perpindahan aset kas ↔ kewajiban.
 */
class UtangPribadiController extends Controller
{
    private const KATEGORI = 'utang_pribadi';

    private function akunList()
    {
        return MasterAkunKas::whereNotIn('tipe', ['Piutang'])->orderBy('akun_id')->pluck('nama_akun');
    }

    public function index()
    {
        $aktif = UtangPribadi::with('bayar')->where('status', 'aktif')
            ->orderBy('tgl_pinjam', 'desc')->get();
        $lunas = UtangPribadi::with('bayar')->where('status', 'lunas')
            ->orderBy('updated_at', 'desc')->get();

        $totalPinjam = $aktif->sum('jumlah_pinjaman');
        $totalSisa   = $aktif->sum(fn($u) => $u->sisa);
        $totalBayar  = $aktif->sum(fn($u) => $u->total_bayar);
        $jmlPemberi  = $aktif->pluck('nama')->unique()->count();

        $akuns = $this->akunList();

        return view('utang_pribadi.index', compact(
            'aktif', 'lunas', 'totalPinjam', 'totalSisa', 'totalBayar', 'jmlPemberi', 'akuns'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama'            => 'required|string|max:255',
            'hubungan'        => 'nullable|string|max:100',
            'jumlah_pinjaman' => 'required|numeric|min:1',
            'tgl_pinjam'      => 'required|date',
            'akun_tujuan'     => 'required|string',
            'catatan'         => 'nullable|string|max:255',
        ]);

        $jumlah = (float) $request->jumlah_pinjaman;

        DB::transaction(function () use ($request, $jumlah) {
            $up = UtangPribadi::create([
                'nama'            => $request->nama,
                'hubungan'        => $request->hubungan,
                'jumlah_pinjaman' => $jumlah,
                'tgl_pinjam'      => $request->tgl_pinjam,
                'akun_tujuan'     => $request->akun_tujuan,
                'catatan'         => $request->catatan,
                'status'          => 'aktif',
            ]);

            // Uang MASUK ke akun tujuan (Hagos menerima pinjaman)
            MutasiKas::catat($request->akun_tujuan, 'masuk', $jumlah, self::KATEGORI, 'UP-' . $up->id,
                'Pinjaman dari ' . $request->nama, auth()->user()->name ?? null, $request->tgl_pinjam);
        });

        $msg = 'Utang dari ' . $request->nama . ' Rp ' . number_format($jumlah, 0, ',', '.') . ' tercatat & kas ditambah.';
        return redirect()->route('utang_pribadi.index')->with('success', $msg);
    }

    public function bayar(Request $request, UtangPribadi $utang)
    {
        $request->validate([
            'jumlah'      => 'required|numeric|min:1',
            'tgl_bayar'   => 'required|date',
            'akun_sumber' => 'required|string',
            'catatan'     => 'nullable|string|max:255',
        ]);

        $jumlah = (float) $request->jumlah;
        $sisa = $utang->sisa;

        if ($jumlah > $sisa + 0.01) {
            return back()->with('error', 'Nominal pembayaran (Rp ' . number_format($jumlah, 0, ',', '.')
                . ') melebihi sisa utang (Rp ' . number_format($sisa, 0, ',', '.') . ').');
        }

        DB::transaction(function () use ($request, $utang, $jumlah) {
            $bayar = UtangPribadiBayar::create([
                'utang_pribadi_id' => $utang->id,
                'jumlah'           => $jumlah,
                'tgl_bayar'        => $request->tgl_bayar,
                'akun_sumber'      => $request->akun_sumber,
                'catatan'          => $request->catatan,
            ]);

            // Uang KELUAR dari akun sumber (Hagos membayar utang)
            MutasiKas::catat($request->akun_sumber, 'keluar', $jumlah, self::KATEGORI, 'UPB-' . $bayar->id,
                'Bayar utang ke ' . $utang->nama, auth()->user()->name ?? null, $request->tgl_bayar);

            if ($utang->fresh()->sisa <= 0.01) {
                $utang->update(['status' => 'lunas']);
            }
        });

        return redirect()->route('utang_pribadi.index')->with('success', 'Pembayaran Rp ' . number_format($jumlah, 0, ',', '.') . ' ke ' . $utang->nama . ' dicatat & kas dipotong.');
    }

    /** Hapus 1 pembayaran: kembalikan kas (masuk lagi) & buka status jika perlu. */
    public function hapusBayar(UtangPribadiBayar $bayar)
    {
        $utang = $bayar->utang;
        DB::transaction(function () use ($bayar, $utang) {
            MutasiKas::where('ref_id', 'UPB-' . $bayar->id)->where('kategori', self::KATEGORI)->delete();
            $bayar->delete();
            if ($utang && $utang->fresh()->sisa > 0.01 && $utang->status === 'lunas') {
                $utang->update(['status' => 'aktif']);
            }
        });

        return back()->with('success', 'Pembayaran dihapus & kas dikembalikan.');
    }

    /** Hapus seluruh utang: batalkan mutasi kas pinjaman + semua pembayarannya. */
    public function destroy(UtangPribadi $utang)
    {
        DB::transaction(function () use ($utang) {
            foreach ($utang->bayar()->get() as $b) { // query fresh, jangan relasi ter-cache
                MutasiKas::where('ref_id', 'UPB-' . $b->id)->where('kategori', self::KATEGORI)->delete();
            }
            MutasiKas::where('ref_id', 'UP-' . $utang->id)->where('kategori', self::KATEGORI)->delete();
            $utang->delete();
        });

        return redirect()->route('utang_pribadi.index')->with('success', 'Utang ' . $utang->nama . ' dihapus & semua mutasi kas terkait dibatalkan.');
    }
}
