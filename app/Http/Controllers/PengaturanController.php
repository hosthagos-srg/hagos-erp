<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Pengaturan;

class PengaturanController extends Controller
{
    public function index()
    {
        $tglKunci = Pengaturan::tanggalKunci();
        return view('pengaturan.index', compact('tglKunci'));
    }

    /** Set / buka tanggal kunci buku. Kosongkan untuk membuka kunci. */
    public function kunciBuku(Request $request)
    {
        $request->validate(['tgl_kunci' => 'nullable|date']);

        Pengaturan::set(Pengaturan::KUNCI_TGL, $request->tgl_kunci ?: null);

        $msg = $request->filled('tgl_kunci')
            ? 'Buku dikunci sampai ' . \Carbon\Carbon::parse($request->tgl_kunci)->format('d M Y') . '. Transaksi pada/sebelum tanggal itu akan ditolak.'
            : 'Kunci buku dibuka — tidak ada periode terkunci.';

        return redirect()->route('pengaturan.index')->with('success', $msg);
    }

    /**
     * RESET DATA: hapus SEMUA data transaksi (test) + nolkan stok & saldo awal.
     * Master data (produk/resep/bibit/komponen/akun/kategori/karyawan/sumber dana/mapping) & user TETAP.
     * Pengaman: wajib ketik "RESET". Truncate via query mentah → tidak memicu audit log / event.
     */
    public function resetData(Request $request)
    {
        $request->validate(['konfirmasi' => 'required|string'], [], ['konfirmasi' => 'konfirmasi']);
        if (trim((string) $request->konfirmasi) !== 'RESET') {
            return back()->with('error', 'Konfirmasi salah. Ketik persis: RESET');
        }

        // Tabel transaksi yang dihapus total (urutan aman, FK dimatikan sementara)
        $tabel = [
            'penjualan_details', 'penjualan_headers', 'mutasi_kas', 'koreksi_stoks',
            'produksi_logs', 'stok_jadi_logs', 'audit_logs', 'rekonsiliasi_mps',
            'sampel_affiliate', 'belanja_details', 'belanja_headers',
            'cicilan_pembayaran', 'utang_cicilan', 'kasbon', 'gaji', 'pelanggan',
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tabel as $t) {
            if (Schema::hasTable($t)) DB::table($t)->truncate();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Nolkan stok & saldo awal (master tetap, harga/resep tetap)
        DB::table('master_bibits')->update(['stok_ml' => 0]);
        DB::table('master_komponens')->update(['stok' => 0]);
        DB::table('master_produks')->update(['stok_t11' => 0, 'hpp_t11' => 0]);
        DB::table('master_akun_kas')->update(['saldo_awal' => 0]);

        // Buka kunci tutup buku (mulai dari nol)
        DB::table('pengaturan')->where('kunci', Pengaturan::KUNCI_TGL)->delete();

        return redirect()->route('pengaturan.index')->with('success', 'Data transaksi dibersihkan. Stok & saldo awal dinolkan. Sistem siap untuk data baru — silakan input stok awal & saldo awal.');
    }
}
