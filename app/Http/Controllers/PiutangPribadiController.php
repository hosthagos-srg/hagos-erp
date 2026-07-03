<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PiutangPribadi;
use App\Models\PiutangPribadiBayar;
use App\Models\MutasiKas;
use App\Models\MasterAkunKas;

/**
 * Piutang Pribadi — pinjaman uang ke kerabat/keluarga (non-dagang).
 * Kas otomatis: pinjam = uang KELUAR, dikembalikan = uang MASUK.
 * Kategori MutasiKas 'piutang_pribadi' TIDAK dihitung di P&L (bukan biaya/pendapatan) —
 * hanya perpindahan aset kas ↔ piutang.
 */
class PiutangPribadiController extends Controller
{
    private const KATEGORI = 'piutang_pribadi';

    private function akunList()
    {
        return MasterAkunKas::whereNotIn('tipe', ['Piutang'])->orderBy('akun_id')->pluck('nama_akun');
    }

    /** Saldo akun saat ini (saldo_awal + Σmasuk − Σkeluar). */
    private function saldoAkun(string $akun): float
    {
        $row = MasterAkunKas::where('nama_akun', $akun)->first();
        $awal = $row ? (float) str_replace(['.', ','], ['', '.'], (string) $row->saldo_awal) : 0;
        $masuk = (float) MutasiKas::where('akun', $akun)->where('tipe', 'masuk')->sum('jumlah');
        $keluar = (float) MutasiKas::where('akun', $akun)->where('tipe', 'keluar')->sum('jumlah');
        return $awal + $masuk - $keluar;
    }

    public function index()
    {
        $aktif = PiutangPribadi::with('bayar')->where('status', 'aktif')
            ->orderBy('tgl_pinjam', 'desc')->get();
        $lunas = PiutangPribadi::with('bayar')->where('status', 'lunas')
            ->orderBy('updated_at', 'desc')->get();

        $totalDipinjamkan = $aktif->sum('jumlah_pinjaman');
        $totalSisa = $aktif->sum(fn($p) => $p->sisa);
        $totalKembali = $aktif->sum(fn($p) => $p->total_bayar);
        $jmlPeminjam = $aktif->pluck('nama')->unique()->count();

        $akuns = $this->akunList();

        return view('piutang_pribadi.index', compact(
            'aktif', 'lunas', 'totalDipinjamkan', 'totalSisa', 'totalKembali', 'jmlPeminjam', 'akuns'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama'            => 'required|string|max:255',
            'hubungan'        => 'nullable|string|max:100',
            'jumlah_pinjaman' => 'required|numeric|min:1',
            'tgl_pinjam'      => 'required|date',
            'akun_sumber'     => 'required|string',
            'catatan'         => 'nullable|string|max:255',
        ]);

        $jumlah = (float) $request->jumlah_pinjaman;
        $saldoSebelum = $this->saldoAkun($request->akun_sumber);

        DB::transaction(function () use ($request, $jumlah) {
            $pp = PiutangPribadi::create([
                'nama'            => $request->nama,
                'hubungan'        => $request->hubungan,
                'jumlah_pinjaman' => $jumlah,
                'tgl_pinjam'      => $request->tgl_pinjam,
                'akun_sumber'     => $request->akun_sumber,
                'catatan'         => $request->catatan,
                'status'          => 'aktif',
            ]);

            // Uang KELUAR dari akun sumber
            MutasiKas::catat($request->akun_sumber, 'keluar', $jumlah, self::KATEGORI, 'PP-' . $pp->id,
                'Pinjaman ke ' . $request->nama, auth()->user()->name ?? null, $request->tgl_pinjam);
        });

        $msg = 'Pinjaman ke ' . $request->nama . ' Rp ' . number_format($jumlah, 0, ',', '.') . ' tercatat & kas dipotong.';
        if ($saldoSebelum - $jumlah < 0) {
            $msg .= ' ⚠ Saldo ' . $request->akun_sumber . ' jadi minus: Rp ' . number_format($saldoSebelum - $jumlah, 0, ',', '.') . '.';
        }

        return redirect()->route('piutang_pribadi.index')->with('success', $msg);
    }

    public function bayar(Request $request, PiutangPribadi $piutang)
    {
        $request->validate([
            'jumlah'     => 'required|numeric|min:1',
            'tgl_bayar'  => 'required|date',
            'akun_masuk' => 'required|string',
            'catatan'    => 'nullable|string|max:255',
        ]);

        $jumlah = (float) $request->jumlah;
        $sisa = $piutang->sisa;

        if ($jumlah > $sisa + 0.01) {
            return back()->with('error', 'Nominal pengembalian (Rp ' . number_format($jumlah, 0, ',', '.')
                . ') melebihi sisa piutang (Rp ' . number_format($sisa, 0, ',', '.') . ').');
        }

        DB::transaction(function () use ($request, $piutang, $jumlah) {
            $bayar = PiutangPribadiBayar::create([
                'piutang_pribadi_id' => $piutang->id,
                'jumlah'             => $jumlah,
                'tgl_bayar'          => $request->tgl_bayar,
                'akun_masuk'         => $request->akun_masuk,
                'catatan'            => $request->catatan,
            ]);

            // Uang MASUK ke akun tujuan
            MutasiKas::catat($request->akun_masuk, 'masuk', $jumlah, self::KATEGORI, 'PPB-' . $bayar->id,
                'Pengembalian pinjaman dari ' . $piutang->nama, auth()->user()->name ?? null, $request->tgl_bayar);

            // Lunas jika sisa habis
            if ($piutang->fresh()->sisa <= 0.01) {
                $piutang->update(['status' => 'lunas']);
            }
        });

        return redirect()->route('piutang_pribadi.index')->with('success', 'Pengembalian Rp ' . number_format($jumlah, 0, ',', '.') . ' dari ' . $piutang->nama . ' dicatat & kas ditambah.');
    }

    /** Hapus 1 pembayaran: kembalikan kas & buka lagi status jika perlu. */
    public function hapusBayar(PiutangPribadiBayar $bayar)
    {
        $piutang = $bayar->piutang;
        DB::transaction(function () use ($bayar, $piutang) {
            MutasiKas::where('ref_id', 'PPB-' . $bayar->id)->where('kategori', self::KATEGORI)->delete();
            $bayar->delete();
            if ($piutang && $piutang->fresh()->sisa > 0.01 && $piutang->status === 'lunas') {
                $piutang->update(['status' => 'aktif']);
            }
        });

        return back()->with('success', 'Pembayaran dihapus & kas dikembalikan.');
    }

    /** Hapus seluruh piutang: batalkan mutasi kas pinjaman + semua pengembaliannya. */
    public function destroy(PiutangPribadi $piutang)
    {
        DB::transaction(function () use ($piutang) {
            // Hapus mutasi kas tiap pembayaran
            foreach ($piutang->bayar as $b) {
                MutasiKas::where('ref_id', 'PPB-' . $b->id)->where('kategori', self::KATEGORI)->delete();
            }
            // Hapus mutasi kas pinjaman awal
            MutasiKas::where('ref_id', 'PP-' . $piutang->id)->where('kategori', self::KATEGORI)->delete();
            // Cascade hapus baris pembayaran + piutang
            $piutang->delete();
        });

        return redirect()->route('piutang_pribadi.index')->with('success', 'Piutang ' . $piutang->nama . ' dihapus & semua mutasi kas terkait dibatalkan.');
    }
}
