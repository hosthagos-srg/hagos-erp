<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Karyawan;
use App\Models\Gaji;

class KaryawanController extends Controller
{
    /** Sisa kasbon per karyawan (kasbon − bayar). */
    public static function sisaKasbonMap()
    {
        return DB::table('kasbon')
            ->selectRaw("karyawan_id, SUM(CASE WHEN tipe='kasbon' THEN jumlah ELSE -jumlah END) as sisa")
            ->groupBy('karyawan_id')->pluck('sisa', 'karyawan_id');
    }

    public function index()
    {
        $karyawans = Karyawan::orderBy('status')->orderBy('nama')->get();
        $sisaKasbon = self::sisaKasbonMap();
        $totalKasbon = $karyawans->sum(fn($k) => max(0, (float) ($sisaKasbon[$k->id] ?? 0)));

        return view('karyawan.index', compact('karyawans', 'sisaKasbon', 'totalKasbon'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama'       => 'required|string|max:255',
            'posisi'     => 'nullable|string|max:255',
            'gaji_pokok' => 'nullable|numeric|min:0',
            'no_hp'      => 'nullable|string|max:50',
            'tgl_masuk'  => 'nullable|date',
            'status'     => 'nullable|in:Aktif,Nonaktif',
            'catatan'    => 'nullable|string',
        ]);

        Karyawan::create([
            'nama'       => $request->nama,
            'posisi'     => $request->posisi,
            'gaji_pokok' => $request->gaji_pokok ?? 0,
            'no_hp'      => $request->no_hp,
            'tgl_masuk'  => $request->tgl_masuk,
            'status'     => $request->status ?? 'Aktif',
            'catatan'    => $request->catatan,
        ]);

        return redirect()->route('karyawan.index')->with('success', 'Karyawan ' . $request->nama . ' ditambahkan.');
    }

    public function update(Request $request, Karyawan $karyawan)
    {
        $request->validate([
            'nama'       => 'required|string|max:255',
            'posisi'     => 'nullable|string|max:255',
            'gaji_pokok' => 'nullable|numeric|min:0',
            'no_hp'      => 'nullable|string|max:50',
            'tgl_masuk'  => 'nullable|date',
            'status'     => 'nullable|in:Aktif,Nonaktif',
            'catatan'    => 'nullable|string',
        ]);

        $karyawan->update($request->only('nama', 'posisi', 'gaji_pokok', 'no_hp', 'tgl_masuk', 'status', 'catatan'));

        return redirect()->back()->with('success', 'Data karyawan diperbarui.');
    }

    public function show(Karyawan $karyawan)
    {
        $kasbon = $karyawan->kasbon()->orderBy('tanggal', 'desc')->orderBy('created_at', 'desc')->get();
        $gaji = $karyawan->gaji()->orderBy('tanggal_bayar', 'desc')->get();
        $sisaKasbon = $karyawan->sisa_kasbon;

        return view('karyawan.show', compact('karyawan', 'kasbon', 'gaji', 'sisaKasbon'));
    }
}
