<?php

namespace App\Http\Controllers;

use App\Models\SumberDana;
use App\Models\UtangCicilan;
use App\Models\CicilanPembayaran;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UtangController extends Controller
{
    public function index()
    {
        $utangAktif = UtangCicilan::with(['sumberDana', 'pembayaran'])
            ->where('status', 'aktif')
            ->orderBy('created_at', 'desc')
            ->get();

        $utangLunas = UtangCicilan::with('sumberDana')
            ->where('status', 'lunas')
            ->orderBy('updated_at', 'desc')
            ->get();

        $notifikasi = $this->getNotifikasi();

        // Ringkasan Utang Pribadi (hutang tunai ke orang) — ditampilkan sebagai bagian dari
        // dashboard monitoring hutang di halaman ini (aksesnya lewat tombol, bukan menu terpisah).
        $utangPribadiAktif = \App\Models\UtangPribadi::with('bayar')->where('status', 'aktif')->get();
        $totalUtangPribadi = (float) $utangPribadiAktif->sum(fn($u) => $u->sisa);
        $jmlUtangPribadi   = $utangPribadiAktif->count();

        // Total sisa utang cicilan (untuk ringkasan gabungan)
        $totalSisaCicilan = (float) $utangAktif->sum(fn($u) => $u->sisa_utang);

        return view('utang.index', compact(
            'utangAktif', 'utangLunas', 'notifikasi',
            'totalUtangPribadi', 'jmlUtangPribadi', 'totalSisaCicilan'
        ));
    }

    public function create()
    {
        $sumberDana = SumberDana::orderBy('nama')->get();
        return view('utang.create', compact('sumberDana'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'sumber_dana_id' => 'required|exists:sumber_dana,id',
            'deskripsi' => 'required|string|max:255',
            'total_utang' => 'required|numeric|min:1',
            'cicilan_per_bulan' => 'required|numeric|min:1',
            'total_bulan' => 'required|integer|min:1|max:360',
            'bulan_mulai' => 'required|date',
            'catatan' => 'nullable|string',
        ]);

        $sumberDana = SumberDana::findOrFail($request->sumber_dana_id);
        $bulanMulai = Carbon::parse($request->bulan_mulai)->startOfMonth();

        $utang = UtangCicilan::create([
            'sumber_dana_id' => $request->sumber_dana_id,
            'deskripsi' => $request->deskripsi,
            'total_utang' => $request->total_utang,
            'cicilan_per_bulan' => $request->cicilan_per_bulan,
            'total_bulan' => $request->total_bulan,
            'bulan_mulai' => $bulanMulai,
            'catatan' => $request->catatan,
            'status' => 'aktif',
        ]);

        // Generate semua periode cicilan
        $this->generatePeriode($utang, $sumberDana);

        return redirect()->route('utang.index')->with('success', 'Utang cicilan berhasil ditambahkan.');
    }

    public function bayar(Request $request, CicilanPembayaran $cicilan)
    {
        $request->validate([
            'jumlah_bayar' => 'required|numeric|min:1',
            'tgl_bayar' => 'required|date',
            'biaya_tambahan' => 'nullable|numeric|min:0',
            'keterangan_biaya' => 'nullable|string|max:255',
            'akun' => 'required|string',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $cicilan) {
            $cicilan->update([
                'jumlah_bayar' => $request->jumlah_bayar,
                'biaya_tambahan' => $request->biaya_tambahan ?? 0,
                'keterangan_biaya' => $request->keterangan_biaya,
                'tgl_bayar' => $request->tgl_bayar,
                'status' => 'lunas',
            ]);

            // F5: uang KELUAR dari akun terpilih = pokok + biaya tambahan (sekali; anti-dobel).
            // kategori 'cicilan' TIDAK dihitung di totalPengeluaran P&L (P&L pakai tabel CicilanPembayaran) → tidak dobel.
            $ref = 'CIC-' . $cicilan->id;
            $sudahKeluar = \App\Models\MutasiKas::where('ref_id', $ref)->where('kategori', 'cicilan')->exists();
            if (! $sudahKeluar) {
                $totalBayar = (float) $request->jumlah_bayar + (float) ($request->biaya_tambahan ?? 0);
                $deskripsi = $cicilan->utangCicilan->deskripsi ?? 'cicilan';
                $periode = \Carbon\Carbon::parse($cicilan->periode)->format('M Y');
                \App\Models\MutasiKas::catat($request->akun, 'keluar', $totalBayar, 'cicilan', $ref,
                    "Bayar cicilan {$deskripsi} · {$periode}", null, $request->tgl_bayar);
            }

            // Cek apakah semua cicilan sudah lunas
            $utang = $cicilan->utangCicilan;
            $belumBayar = $utang->pembayaran()->where('status', 'belum')->count();
            if ($belumBayar === 0) {
                $utang->update(['status' => 'lunas']);
            }
        });

        return redirect()->back()->with('success', 'Pembayaran cicilan berhasil dicatat & kas disesuaikan.');
    }

    public function show(UtangCicilan $utang)
    {
        $utang->load(['sumberDana', 'pembayaran' => function ($q) {
            $q->orderBy('periode');
        }]);
        return view('utang.show', compact('utang'));
    }

    public static function getNotifikasi(): array
    {
        $today = Carbon::today();
        $batas = $today->copy()->addDays(3);

        $tagihan = CicilanPembayaran::with(['utangCicilan.sumberDana'])
            ->where('status', 'belum')
            ->where('periode', '<=', $batas)
            ->orderBy('periode')
            ->get();

        return $tagihan->toArray();
    }

    private function generatePeriode(UtangCicilan $utang, SumberDana $sumberDana): void
    {
        $bulan = Carbon::parse($utang->bulan_mulai);
        $jatuhTempoTgl = $sumberDana->jatuh_tempo_tgl;

        for ($i = 0; $i < $utang->total_bulan; $i++) {
            // Ambil tahun & bulan cicilan ini, pasang tanggal jatuh tempo
            $tglJatuhTempo = Carbon::create($bulan->year, $bulan->month, 1)
                ->endOfMonth(); // fallback ke akhir bulan jika tgl > hari max bulan tsb

            $maxDay = $bulan->daysInMonth;
            $day = min($jatuhTempoTgl, $maxDay);
            $tglJatuhTempo = Carbon::create($bulan->year, $bulan->month, $day);

            CicilanPembayaran::create([
                'utang_cicilan_id' => $utang->id,
                'periode' => $tglJatuhTempo,
                'jumlah_tagihan' => $utang->cicilan_per_bulan,
                'status' => 'belum',
            ]);

            $bulan->addMonth();
        }
    }
}
