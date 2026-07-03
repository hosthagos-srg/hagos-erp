<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Kasbon;
use App\Models\Karyawan;
use App\Models\MutasiKas;
use App\Models\MasterAkunKas;
use App\Models\MasterKategori;

class KasbonController extends Controller
{
    public function index(Request $request)
    {
        $query = Kasbon::with('karyawan')->orderBy('tanggal', 'desc')->orderBy('created_at', 'desc');
        if ($request->filled('karyawan_id')) {
            $query->where('karyawan_id', $request->karyawan_id);
        }
        $riwayat = $query->paginate(40)->withQueryString();

        $karyawans = Karyawan::where('status', 'Aktif')->orderBy('nama')->get();
        $sisaKasbon = KaryawanController::sisaKasbonMap();
        $akuns = MasterAkunKas::whereNotIn('tipe', ['Piutang'])->orderBy('akun_id')->pluck('nama_akun');
        $admins = MasterKategori::where('tipe_kategori', 'Admin')->orderBy('nilai')->pluck('nilai');
        $totalKasbonAktif = $karyawans->sum(fn($k) => max(0, (float) ($sisaKasbon[$k->id] ?? 0)));

        return view('kasbon.index', compact('riwayat', 'karyawans', 'sisaKasbon', 'akuns', 'admins', 'totalKasbonAktif'));
    }

    /** Beri kasbon (uang keluar dari kas → utang karyawan naik). Bukan biaya. */
    public function store(Request $request)
    {
        $request->validate([
            'karyawan_id' => 'required|exists:karyawan,id',
            'tanggal'     => 'required|date',
            'jumlah'      => 'required|numeric|min:1',
            'akun'        => 'required|string',
            'keterangan'  => 'nullable|string',
            'dicatat_oleh'=> 'nullable|string',
        ]);

        $karyawan = Karyawan::findOrFail($request->karyawan_id);

        DB::transaction(function () use ($request, $karyawan) {
            Kasbon::create([
                'karyawan_id' => $karyawan->id,
                'tanggal'     => $request->tanggal,
                'tipe'        => 'kasbon',
                'jumlah'      => $request->jumlah,
                'akun'        => $request->akun,
                'keterangan'  => $request->keterangan,
                'dicatat_oleh'=> $request->dicatat_oleh,
            ]);

            MutasiKas::catat($request->akun, 'keluar', (float) $request->jumlah, 'kasbon', null,
                'Kasbon ' . $karyawan->nama . ($request->keterangan ? ' · ' . $request->keterangan : ''),
                $request->dicatat_oleh, $request->tanggal);
        });

        return redirect()->route('kasbon.index')->with('success', 'Kasbon ' . $karyawan->nama . ' Rp ' . number_format((float) $request->jumlah, 0, ',', '.') . ' dicatat.');
    }

    /** Pelunasan kasbon TUNAI (uang masuk ke kas → utang karyawan turun). */
    public function bayar(Request $request)
    {
        $request->validate([
            'karyawan_id' => 'required|exists:karyawan,id',
            'tanggal'     => 'required|date',
            'jumlah'      => 'required|numeric|min:1',
            'akun'        => 'required|string',
            'dicatat_oleh'=> 'nullable|string',
        ]);

        $karyawan = Karyawan::findOrFail($request->karyawan_id);
        $sisa = $karyawan->sisa_kasbon;
        if ((float) $request->jumlah > $sisa) {
            return back()->withErrors(['jumlah' => 'Jumlah bayar (Rp ' . number_format((float) $request->jumlah, 0, ',', '.') . ') melebihi sisa kasbon (Rp ' . number_format($sisa, 0, ',', '.') . ').']);
        }

        DB::transaction(function () use ($request, $karyawan) {
            Kasbon::create([
                'karyawan_id' => $karyawan->id,
                'tanggal'     => $request->tanggal,
                'tipe'        => 'bayar',
                'jumlah'      => $request->jumlah,
                'metode'      => 'Tunai',
                'akun'        => $request->akun,
                'keterangan'  => 'Pelunasan kasbon (tunai)',
                'dicatat_oleh'=> $request->dicatat_oleh,
            ]);

            MutasiKas::catat($request->akun, 'masuk', (float) $request->jumlah, 'kasbon', null,
                'Bayar kasbon ' . $karyawan->nama, $request->dicatat_oleh, $request->tanggal);
        });

        return redirect()->route('kasbon.index')->with('success', 'Pelunasan kasbon ' . $karyawan->nama . ' Rp ' . number_format((float) $request->jumlah, 0, ',', '.') . ' tercatat.');
    }
}
