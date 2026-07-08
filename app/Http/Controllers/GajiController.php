<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Gaji;
use App\Models\Kasbon;
use App\Models\Karyawan;
use App\Models\MutasiKas;
use App\Models\MasterAkunKas;
use App\Models\MasterKategori;

class GajiController extends Controller
{
    public function index(Request $request)
    {
        $query = Gaji::with('karyawan')->orderBy('tanggal_bayar', 'desc')->orderBy('created_at', 'desc');
        if ($request->filled('karyawan_id')) {
            $query->where('karyawan_id', $request->karyawan_id);
        }
        $riwayat = $query->paginate(40)->withQueryString();

        $karyawans = Karyawan::where('status', 'Aktif')->orderBy('nama')->get();
        $sisaKasbon = KaryawanController::sisaKasbonMap();
        $akuns = MasterAkunKas::whereNotIn('tipe', ['Piutang', 'Saldo MP'])->orderBy('akun_id')->pluck('nama_akun');
        $admins = MasterKategori::where('tipe_kategori', 'Admin')->orderBy('nilai')->pluck('nilai');

        return view('gaji.index', compact('riwayat', 'karyawans', 'sisaKasbon', 'akuns', 'admins'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'karyawan_id'     => 'required|exists:karyawan,id',
            'periode'         => 'required|string|max:50',
            'bulan_biaya'     => 'required|date_format:Y-m', // bulan biaya masuk laba (mis. 2026-06)
            'tanggal_bayar'   => 'required|date',
            'gaji_pokok'      => 'required|numeric|min:0',
            'tunjangan'       => 'nullable|numeric|min:0',
            'potongan_kasbon' => 'nullable|numeric|min:0',
            'potongan_lain'   => 'nullable|numeric|min:0',
            'akun'            => 'required|string',
            'dicatat_oleh'    => 'nullable|string',
            'catatan'         => 'nullable|string',
        ]);

        $karyawan = Karyawan::findOrFail($request->karyawan_id);

        $pokok      = (float) $request->gaji_pokok;
        $tunjangan  = (float) ($request->tunjangan ?? 0);
        $potKasbon  = (float) ($request->potongan_kasbon ?? 0);
        $potLain    = (float) ($request->potongan_lain ?? 0);

        // Validasi potongan kasbon tidak melebihi sisa
        $sisaKasbon = $karyawan->sisa_kasbon;
        if ($potKasbon > $sisaKasbon) {
            return back()->withInput()->withErrors(['potongan_kasbon' => 'Potongan kasbon (Rp ' . number_format($potKasbon, 0, ',', '.') . ') melebihi sisa kasbon (Rp ' . number_format($sisaKasbon, 0, ',', '.') . ').']);
        }

        $biayaGaji  = $pokok + $tunjangan - $potLain;       // biaya tenaga kerja (masuk P&L)
        $gajiBersih = $biayaGaji - $potKasbon;              // uang tunai yang diterima karyawan
        if ($gajiBersih < 0) {
            return back()->withInput()->withErrors(['potongan_kasbon' => 'Total potongan melebihi gaji.']);
        }

        DB::transaction(function () use ($request, $karyawan, $pokok, $tunjangan, $potKasbon, $potLain, $biayaGaji, $gajiBersih) {
            $gaji = Gaji::create([
                'karyawan_id'     => $karyawan->id,
                'periode'         => $request->periode,
                'bulan_biaya'     => $request->bulan_biaya . '-01', // simpan sbg tgl 1 bulan biaya
                'tanggal_bayar'   => $request->tanggal_bayar,
                'gaji_pokok'      => $pokok,
                'tunjangan'       => $tunjangan,
                'potongan_kasbon' => $potKasbon,
                'potongan_lain'   => $potLain,
                'gaji_bersih'     => $gajiBersih,
                'akun'            => $request->akun,
                'dicatat_oleh'    => $request->dicatat_oleh,
                'catatan'         => $request->catatan,
            ]);

            // Biaya gaji → Pengeluaran (masuk P&L). Nilai = biaya tenaga kerja (pokok+tunjangan−potongan lain).
            if ($biayaGaji > 0) {
                MutasiKas::catat($request->akun, 'keluar', $biayaGaji, 'pengeluaran', 'GAJI-' . $gaji->id,
                    'Gaji · ' . $karyawan->nama . ' · ' . $request->periode, $request->dicatat_oleh, $request->tanggal_bayar);
            }

            // Potong kasbon → utang karyawan turun + kas "masuk" (mengimbangi, net kas = gaji bersih)
            if ($potKasbon > 0) {
                Kasbon::create([
                    'karyawan_id' => $karyawan->id,
                    'tanggal'     => $request->tanggal_bayar,
                    'tipe'        => 'bayar',
                    'jumlah'      => $potKasbon,
                    'metode'      => 'Potong Gaji',
                    'akun'        => $request->akun,
                    'gaji_id'     => $gaji->id,
                    'keterangan'  => 'Potong gaji ' . $request->periode,
                    'dicatat_oleh'=> $request->dicatat_oleh,
                ]);

                MutasiKas::catat($request->akun, 'masuk', $potKasbon, 'kasbon', 'GAJI-' . $gaji->id,
                    'Potong kasbon dari gaji ' . $karyawan->nama, $request->dicatat_oleh, $request->tanggal_bayar);
            }
        });

        return redirect()->route('gaji.index')->with('success', 'Gaji ' . $karyawan->nama . ' (' . $request->periode . ') Rp ' . number_format($gajiBersih, 0, ',', '.') . ' dibayarkan.');
    }
}
