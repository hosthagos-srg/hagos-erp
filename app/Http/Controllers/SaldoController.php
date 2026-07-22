<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\MasterAkunKas;
use App\Models\MutasiKas;

class SaldoController extends Controller
{
    /** Hitung saldo tiap akun (saldo_awal + Σmasuk − Σkeluar). */
    private function buildRows()
    {
        $akuns = MasterAkunKas::orderBy('akun_id')->get();
        $masuk = MutasiKas::where('tipe', 'masuk')->selectRaw('akun, SUM(jumlah) t')->groupBy('akun')->pluck('t', 'akun');
        $keluar = MutasiKas::where('tipe', 'keluar')->selectRaw('akun, SUM(jumlah) t')->groupBy('akun')->pluck('t', 'akun');

        return $akuns->map(function ($a) use ($masuk, $keluar) {
            $awal = (float) str_replace(['.', ','], ['', '.'], (string) $a->saldo_awal);
            $m = (float) ($masuk[$a->nama_akun] ?? 0);
            $k = (float) ($keluar[$a->nama_akun] ?? 0);
            return (object) [
                'akun_id'    => $a->akun_id,
                'nama_akun'  => $a->nama_akun,
                'tipe'       => $a->tipe,
                'fungsi'     => $a->fungsi,
                'saldo_awal' => $awal,
                'masuk'      => $m,
                'keluar'     => $k,
                'saldo'      => $awal + $m - $k,
            ];
        });
    }

    public function withdrawalForm()
    {
        $rows = $this->buildRows();

        // Riwayat WD: 1 WD = 2 entri (keluar MP + masuk Bank) dgn ref sama
        $riwayat = MutasiKas::where('kategori', 'withdrawal')
            ->orderBy('tanggal', 'desc')->orderBy('created_at', 'desc')->limit(60)->get()
            ->groupBy('ref_id')
            ->map(function ($g) {
                $keluar = $g->firstWhere('tipe', 'keluar');
                $masuk = $g->firstWhere('tipe', 'masuk');
                return (object) [
                    'tanggal' => optional($keluar ?? $masuk)->tanggal,
                    'dari'    => $keluar->akun ?? '-',
                    'ke'      => $masuk->akun ?? '-',
                    'jumlah'  => (float) ($keluar->jumlah ?? $masuk->jumlah ?? 0),
                    'ref'     => $g->first()->ref_id,
                ];
            })->sortByDesc('tanggal')->values()->take(30);

        return view('saldo.withdrawal', compact('rows', 'riwayat'));
    }

    /** Opname kas: setel saldo akun ke jumlah fisik riil; selisih dicatat sbg koreksi (bukan income/biaya). */
    public function opnameKas(Request $request)
    {
        $request->validate([
            'akun'        => 'required|string',
            'saldo_fisik' => 'required|numeric',
            'oleh'        => 'nullable|string',
            'catatan'     => 'nullable|string',
        ]);

        $row = $this->buildRows()->firstWhere('nama_akun', $request->akun);
        if (!$row) return back()->with('error', 'Akun tidak ditemukan.');

        $sistem = (float) $row->saldo;
        $fisik = (float) $request->saldo_fisik;
        $selisih = $fisik - $sistem;

        if (abs($selisih) < 0.5) return back()->with('success', 'Saldo sudah sesuai, tidak ada koreksi.');

        MutasiKas::catat(
            $request->akun, $selisih > 0 ? 'masuk' : 'keluar', abs($selisih), 'koreksi_kas', null,
            'Opname kas: sistem Rp ' . number_format($sistem, 0, ',', '.') . ' → fisik Rp ' . number_format($fisik, 0, ',', '.') . ($request->catatan ? ' · ' . $request->catatan : ''),
            $request->oleh, now()->toDateString()
        );

        return back()->with('success', 'Saldo ' . $request->akun . ' dikoreksi ke Rp ' . number_format($fisik, 0, ',', '.') . ' (selisih ' . ($selisih > 0 ? '+' : '') . number_format($selisih, 0, ',', '.') . ').');
    }

    public function modalForm()
    {
        $rows = $this->buildRows();
        $riwayat = MutasiKas::whereIn('kategori', ['modal', 'prive'])
            ->orderBy('tanggal', 'desc')->orderBy('created_at', 'desc')->limit(60)->get();
        return view('saldo.modal', compact('rows', 'riwayat'));
    }

    /** Setoran modal pemilik (uang masuk kas — BUKAN income, tidak ke P&L). */
    public function storeModal(Request $request)
    {
        $request->validate([
            'akun' => 'required|string', 'jumlah' => 'required|numeric|min:1',
            'tanggal' => 'nullable|date', 'catatan' => 'nullable|string', 'oleh' => 'nullable|string',
        ]);
        MutasiKas::catat($request->akun, 'masuk', (float) $request->jumlah, 'modal', null,
            'Setoran modal' . ($request->catatan ? ' · ' . $request->catatan : ''),
            $request->oleh, $request->tanggal ?? now()->toDateString());
        return redirect()->route('saldo.modal_form')->with('success', 'Setoran modal Rp ' . number_format((float) $request->jumlah, 0, ',', '.') . ' tercatat.');
    }

    /** Prive — pengambilan pemilik (uang keluar kas — BUKAN biaya, tidak ke P&L). */
    public function storePrive(Request $request)
    {
        $request->validate([
            'akun' => 'required|string', 'jumlah' => 'required|numeric|min:1',
            'tanggal' => 'nullable|date', 'catatan' => 'nullable|string', 'oleh' => 'nullable|string',
        ]);
        MutasiKas::catat($request->akun, 'keluar', (float) $request->jumlah, 'prive', null,
            'Prive (ambil pemilik)' . ($request->catatan ? ' · ' . $request->catatan : ''),
            $request->oleh, $request->tanggal ?? now()->toDateString());
        return redirect()->route('saldo.modal_form')->with('success', 'Prive Rp ' . number_format((float) $request->jumlah, 0, ',', '.') . ' tercatat.');
    }

    public function transferForm()
    {
        $rows = $this->buildRows();

        $riwayat = MutasiKas::whereIn('kategori', ['transfer_keluar', 'transfer_masuk', 'biaya_transfer'])
            ->orderBy('tanggal', 'desc')->orderBy('created_at', 'desc')->limit(90)->get()
            ->groupBy('ref_id')
            ->map(function ($g) {
                $keluar = $g->firstWhere('kategori', 'transfer_keluar');
                $masuk = $g->firstWhere('kategori', 'transfer_masuk');
                $biaya = $g->firstWhere('kategori', 'biaya_transfer');
                return (object) [
                    'tanggal' => optional($keluar ?? $masuk)->tanggal,
                    'dari'    => $keluar->akun ?? '-',
                    'ke'      => $masuk->akun ?? '-',
                    'jumlah'  => (float) ($keluar->jumlah ?? 0),
                    'biaya'   => (float) ($biaya->jumlah ?? 0),
                    'ref'     => $g->first()->ref_id,
                ];
            })->sortByDesc('tanggal')->values()->take(30);

        return view('saldo.transfer', compact('rows', 'riwayat'));
    }

    public function index(Request $request)
    {
        $rows = $this->buildRows();
        $totalSaldo = $rows->sum('saldo');

        $query = MutasiKas::orderBy('tanggal', 'desc')->orderBy('created_at', 'desc');
        if ($request->filled('akun')) {
            $query->where('akun', $request->akun);
        }
        $mutasis = $query->limit(60)->get();

        // Kategori pengeluaran (kecuali Belanja Bibit/Komponen yang lewat modul Belanja)
        $kategoriPengeluaran = \App\Models\MasterKategori::where('tipe_kategori', 'Kategori Pengeluaran')
            ->whereNotIn('nilai', ['Belanja Bibit', 'Belanja Komponen'])
            ->orderBy('id')->pluck('nilai');
        $admins = \App\Models\MasterKategori::where('tipe_kategori', 'Admin')->orderBy('nilai')->pluck('nilai');

        return view('saldo.index', compact('rows', 'totalSaldo', 'mutasis', 'kategoriPengeluaran', 'admins'));
    }

    /** Withdrawal: tarik saldo marketplace -> bank. */
    public function withdrawal(Request $request)
    {
        $request->validate([
            'dari_akun' => 'required|string|different:ke_akun',
            'ke_akun'   => 'required|string',
            'jumlah'    => 'required|numeric|min:1',
            'tanggal'   => 'nullable|date',
            'catatan'   => 'nullable|string',
        ]);
        $dari = $request->dari_akun;
        $ke = $request->ke_akun;
        $jumlah = (float) $request->jumlah;
        $tgl = $request->tanggal ?? now()->toDateString();
        $ref = 'WD-' . strtoupper(substr((string) \Illuminate\Support\Str::uuid(), 0, 8));
        $cat = $request->catatan;

        try {
            DB::transaction(function () use ($dari, $ke, $jumlah, $ref, $tgl, $cat) {
                MutasiKas::catat($dari, 'keluar', $jumlah, 'withdrawal', $ref, "Withdrawal ke {$ke}" . ($cat ? " · {$cat}" : ''), null, $tgl);
                MutasiKas::catat($ke, 'masuk', $jumlah, 'withdrawal', $ref, "Withdrawal dari {$dari}" . ($cat ? " · {$cat}" : ''), null, $tgl);
            });
            return redirect()->route('saldo.index')->with('success', "Withdrawal Rp " . number_format($jumlah, 0, ',', '.') . " dari {$dari} ke {$ke} berhasil.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal withdrawal: ' . $e->getMessage());
        }
    }

    /** Catat pengeluaran operasional (uang keluar dari akun). */
    public function pengeluaran(Request $request)
    {
        $request->validate([
            'akun'      => 'required|string',
            'kategori'  => 'required|string',
            'jumlah'    => 'required|numeric|min:1',
            'tanggal'   => 'nullable|date',
            'keterangan'=> 'nullable|string',
            'oleh'      => 'nullable|string',
        ]);

        MutasiKas::catat(
            $request->akun, 'keluar', (float) $request->jumlah, 'pengeluaran',
            null,
            $request->kategori . ($request->keterangan ? ' · ' . $request->keterangan : ''),
            $request->oleh,
            $request->tanggal ?? now()->toDateString()
        );

        return redirect()->route('saldo.index')->with('success', 'Pengeluaran ' . $request->kategori . ' Rp ' . number_format((float) $request->jumlah, 0, ',', '.') . ' tercatat.');
    }

    /** Patungan biaya bersama (mis. 420F). Kas MASUK, BUKAN pendapatan — jadi pengurang biaya ops di P&L. */
    public function patungan(Request $request)
    {
        $request->validate([
            'akun'    => 'required|string',
            'jumlah'  => 'required|numeric|min:1',
            'dari'    => 'required|string|max:100',
            'untuk'   => 'nullable|string|max:100',
            'tanggal' => 'nullable|date',
            'oleh'    => 'nullable|string',
        ]);

        MutasiKas::catat(
            $request->akun, 'masuk', (float) $request->jumlah, 'patungan',
            null,
            'Patungan ' . $request->dari . ($request->untuk ? ' · ' . $request->untuk : ''),
            $request->oleh,
            $request->tanggal ?? now()->toDateString()
        );

        return redirect()->route('saldo.index')->with('success', 'Patungan dari ' . $request->dari . ' Rp ' . number_format((float) $request->jumlah, 0, ',', '.') . ' tercatat (mengurangi biaya operasional, bukan pendapatan).');
    }

    /** Transfer antar akun + biaya transfer opsional (potong dari pengirim/penerima). */
    public function transfer(Request $request)
    {
        $request->validate([
            'dari_akun'     => 'required|string|different:ke_akun',
            'ke_akun'       => 'required|string',
            'jumlah'        => 'required|numeric|min:1',
            'biaya_transfer'=> 'nullable|numeric|min:0',
            'potong_biaya'  => 'nullable|in:pengirim,penerima',
            'tanggal'       => 'nullable|date',
            'catatan'       => 'nullable|string',
        ]);

        $dari = $request->dari_akun;
        $ke = $request->ke_akun;
        $jumlah = (float) $request->jumlah;
        $biaya = (float) ($request->biaya_transfer ?? 0);
        $tgl = $request->tanggal ?? now()->toDateString();
        $ref = 'TRF-' . strtoupper(substr((string) \Illuminate\Support\Str::uuid(), 0, 8));
        $catatan = $request->catatan;

        try {
            DB::transaction(function () use ($dari, $ke, $jumlah, $biaya, $request, $tgl, $ref, $catatan) {
                MutasiKas::catat($dari, 'keluar', $jumlah, 'transfer_keluar', $ref, "Transfer ke {$ke}" . ($catatan ? " · {$catatan}" : ''), null, $tgl);
                MutasiKas::catat($ke, 'masuk', $jumlah, 'transfer_masuk', $ref, "Transfer dari {$dari}" . ($catatan ? " · {$catatan}" : ''), null, $tgl);

                if ($biaya > 0) {
                    $akunBiaya = ($request->potong_biaya === 'penerima') ? $ke : $dari;
                    MutasiKas::catat($akunBiaya, 'keluar', $biaya, 'biaya_transfer', $ref, "Biaya transfer ({$dari} → {$ke})", null, $tgl);
                }
            });

            return redirect()->route('saldo.index')->with('success', "Transfer Rp " . number_format($jumlah, 0, ',', '.') . " dari {$dari} ke {$ke} berhasil" . ($biaya > 0 ? " (biaya Rp " . number_format($biaya, 0, ',', '.') . ")" : "") . ".");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal transfer: ' . $e->getMessage());
        }
    }

    /**
     * Edit nominal transfer yang salah input. HANYA admin + wajib konfirmasi password.
     * Update kedua kaki (transfer_keluar + transfer_masuk, ref sama) agar saldo tetap konsisten.
     */
    public function updateTransfer(Request $request)
    {
        $user = auth()->user();
        if (! $user || $user->role !== 'admin') {
            return redirect()->route('saldo.transfer_form')->with('error', '⛔ Hanya admin yang boleh mengedit transfer.');
        }

        $request->validate([
            'ref'      => 'required|string',
            'jumlah'   => 'required|numeric|min:1',
            'password' => 'required|string',
        ]);

        if (! \Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            return redirect()->route('saldo.transfer_form')->with('error', '❌ Password salah — edit transfer dibatalkan.');
        }

        $legs = MutasiKas::where('ref_id', $request->ref)
            ->whereIn('kategori', ['transfer_keluar', 'transfer_masuk'])->get();
        if ($legs->isEmpty()) {
            return redirect()->route('saldo.transfer_form')->with('error', 'Transfer tidak ditemukan.');
        }

        $jumlahLama = (float) ($legs->firstWhere('kategori', 'transfer_keluar')->jumlah ?? 0);
        $jumlahBaru = (float) $request->jumlah;

        DB::transaction(function () use ($legs, $jumlahBaru) {
            foreach ($legs as $leg) {
                $leg->jumlah = $jumlahBaru;
                $leg->save();
            }
        });

        return redirect()->route('saldo.transfer_form')->with('success',
            '✅ Transfer diperbarui: Rp ' . number_format($jumlahLama, 0, ',', '.') . ' → Rp ' . number_format($jumlahBaru, 0, ',', '.') . '.');
    }
}
