<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Pelanggan;

class PelangganController extends Controller
{
    private array $tipeList = ['Reseller A', 'Reseller B', 'WA', 'Offline', 'Website', 'Lainnya'];

    /** Statistik order per nama_pembeli (non-batal). */
    private function statsByNama()
    {
        return DB::table('penjualan_headers')
            ->whereNotNull('nama_pembeli')->where('nama_pembeli', '!=', '')
            ->where('status_pesanan', '!=', 'Batal')
            ->selectRaw('nama_pembeli,
                COUNT(*) as orders,
                SUM(gmv_kotor - COALESCE(diskon_manual,0)) as total,
                MAX(tgl_pesanan) as last_order,
                SUM(CASE WHEN status_pembayaran = "Piutang" THEN gmv_kotor - COALESCE(diskon_manual,0) ELSE 0 END) as piutang')
            ->groupBy('nama_pembeli')->get()->keyBy('nama_pembeli');
    }

    public function index(Request $request)
    {
        $q = Pelanggan::query()->orderBy('nama');
        if ($request->filled('cari')) {
            $c = $request->cari;
            $q->where(fn($w) => $w->where('nama', 'like', "%$c%")->orWhere('no_hp', 'like', "%$c%")->orWhere('kota', 'like', "%$c%"));
        }
        if ($request->filled('tipe')) $q->where('tipe', $request->tipe);
        $pelanggans = $q->get();

        $stats = $this->statsByNama();

        // Saran impor: nama_pembeli NON-MARKETPLACE (reseller/WA/offline) yg belum terdaftar.
        // Pembeli marketplace dikecualikan karena namanya acak / tidak relevan utk CRM.
        $terdaftar = Pelanggan::pluck('nama')->map(fn($n) => mb_strtolower($n))->flip();
        $saranImpor = DB::table('penjualan_headers')
            ->whereNotNull('nama_pembeli')->where('nama_pembeli', '!=', '')
            ->where('status_pesanan', '!=', 'Batal')
            ->where('channel', 'not like', 'Marketplace%')
            ->distinct()->pluck('nama_pembeli')
            ->filter(fn($n) => !$terdaftar->has(mb_strtolower($n)))->values();

        $totalPiutang = $pelanggans->sum(fn($p) => (float) ($stats[$p->nama]->piutang ?? 0));
        $tipeList = $this->tipeList;

        return view('pelanggan.index', compact('pelanggans', 'stats', 'saranImpor', 'totalPiutang', 'tipeList'));
    }

    /**
     * Laporan Kinerja Pelanggan/Reseller: leaderboard omzet + frekuensi + AOV + recency (dormant).
     * Fokus NON-marketplace (pembeli marketplace anonim/acak). Omzet basis gmv−diskon.
     */
    public function kinerja(Request $request)
    {
        $tipeFilter = $request->input('tipe');
        $dari = $request->input('dari');
        $sampai = $request->input('sampai');
        $dormantHari = 45; // ambang pelanggan pasif

        $q = DB::table('penjualan_headers')
            ->where('status_pesanan', '!=', 'Batal')
            ->where('channel', 'not like', 'Marketplace%')
            ->whereNotNull('nama_pembeli')->where('nama_pembeli', '!=', '');
        if ($dari)   $q->whereDate('tgl_pesanan', '>=', $dari);
        if ($sampai) $q->whereDate('tgl_pesanan', '<=', $sampai);

        $stats = $q->groupBy('nama_pembeli')
            ->selectRaw('nama_pembeli,
                COUNT(*) as orders,
                SUM(gmv_kotor - COALESCE(diskon_manual,0)) as omzet,
                MAX(tgl_pesanan) as last_order,
                MIN(tgl_pesanan) as first_order,
                SUM(CASE WHEN status_pembayaran = "Piutang" THEN gmv_kotor - COALESCE(diskon_manual,0) ELSE 0 END) as piutang')
            ->get();

        // Tipe: dari CRM Pelanggan bila terdaftar, jika tidak → channel dominan pembeli.
        $pelTipe = Pelanggan::pluck('tipe', 'nama')->mapWithKeys(fn($t, $n) => [mb_strtolower($n) => $t]);
        $domChannel = DB::table('penjualan_headers')
            ->where('status_pesanan', '!=', 'Batal')->where('channel', 'not like', 'Marketplace%')
            ->whereNotNull('nama_pembeli')->where('nama_pembeli', '!=', '')
            ->selectRaw('nama_pembeli, channel, COUNT(*) c')->groupBy('nama_pembeli', 'channel')->get()
            ->groupBy('nama_pembeli')->map(fn($g) => $g->sortByDesc('c')->first()->channel);

        $today = now();
        $rows = $stats->map(function ($r) use ($pelTipe, $domChannel, $today, $dormantHari) {
            $orders = (int) $r->orders;
            $omzet = (float) $r->omzet;
            $hariSejak = $r->last_order ? (int) \Carbon\Carbon::parse($r->last_order)->diffInDays($today) : null;
            return (object) [
                'nama' => $r->nama_pembeli,
                'tipe' => $pelTipe[mb_strtolower($r->nama_pembeli)] ?? ($domChannel[$r->nama_pembeli] ?? '—'),
                'orders' => $orders,
                'omzet' => $omzet,
                'aov' => $orders > 0 ? $omzet / $orders : 0,
                'last_order' => $r->last_order,
                'first_order' => $r->first_order,
                'hari_sejak' => $hariSejak,
                'dormant' => $hariSejak !== null && $hariSejak > $dormantHari,
                'piutang' => (float) $r->piutang,
            ];
        });

        if ($tipeFilter) $rows = $rows->where('tipe', $tipeFilter);
        $rows = $rows->sortByDesc('omzet')->values();

        $totalPelanggan = $rows->count();
        $totalOmzet = (float) $rows->sum('omzet');
        $totalPiutang = (float) $rows->sum('piutang');
        $jmlDormant = $rows->where('dormant', true)->count();
        $tipeList = $this->tipeList;

        return view('pelanggan.kinerja', compact(
            'rows', 'tipeFilter', 'dari', 'sampai', 'dormantHari',
            'totalPelanggan', 'totalOmzet', 'totalPiutang', 'jmlDormant', 'tipeList'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama'    => 'required|string|max:255|unique:pelanggan,nama',
            'tipe'    => 'nullable|string|max:50',
            'no_hp'   => 'nullable|string|max:50',
            'kota'    => 'nullable|string|max:100',
            'alamat'  => 'nullable|string',
            'catatan' => 'nullable|string',
            'status'  => 'nullable|in:Aktif,Nonaktif',
        ]);
        Pelanggan::create($request->only('nama', 'tipe', 'no_hp', 'kota', 'alamat', 'catatan', 'status') + ['status' => $request->status ?? 'Aktif']);
        return redirect()->route('pelanggan.index')->with('success', 'Pelanggan ' . $request->nama . ' ditambahkan.');
    }

    public function update(Request $request, Pelanggan $pelanggan)
    {
        $request->validate([
            'nama'    => 'required|string|max:255|unique:pelanggan,nama,' . $pelanggan->id,
            'tipe'    => 'nullable|string|max:50',
            'no_hp'   => 'nullable|string|max:50',
            'kota'    => 'nullable|string|max:100',
            'alamat'  => 'nullable|string',
            'catatan' => 'nullable|string',
            'status'  => 'nullable|in:Aktif,Nonaktif',
        ]);
        $pelanggan->update($request->only('nama', 'tipe', 'no_hp', 'kota', 'alamat', 'catatan', 'status'));
        return redirect()->back()->with('success', 'Data pelanggan diperbarui.');
    }

    /** Impor cepat 1 nama_pembeli jadi pelanggan. */
    public function import(Request $request)
    {
        $request->validate(['nama' => 'required|string', 'tipe' => 'nullable|string']);
        if (!Pelanggan::where('nama', $request->nama)->exists()) {
            // tebak tipe dari channel pesanan terbanyak pembeli ini
            $tipe = $request->tipe ?: DB::table('penjualan_headers')->where('nama_pembeli', $request->nama)
                ->selectRaw('channel, COUNT(*) c')->groupBy('channel')->orderByDesc('c')->value('channel');
            Pelanggan::create(['nama' => $request->nama, 'tipe' => $tipe, 'status' => 'Aktif']);
        }
        return redirect()->back()->with('success', 'Pelanggan ' . $request->nama . ' diimpor.');
    }

    public function destroy(Pelanggan $pelanggan)
    {
        $nama = $pelanggan->nama;
        $pelanggan->delete(); // hanya menghapus data CRM; pesanan tetap utuh
        return redirect()->route('pelanggan.index')->with('success', "Pelanggan {$nama} dihapus.");
    }

    public function show(Pelanggan $pelanggan)
    {
        $orders = DB::table('penjualan_headers as h')
            ->leftJoin(DB::raw('(SELECT internal_id, GROUP_CONCAT(CONCAT(qty,"x ",sku_id) SEPARATOR ", ") produk FROM penjualan_details GROUP BY internal_id) d'), 'd.internal_id', '=', 'h.internal_id')
            ->where('h.nama_pembeli', $pelanggan->nama)
            ->orderBy('h.tgl_pesanan', 'desc')
            ->get(['h.internal_id', 'h.external_order_id', 'h.channel', 'h.tgl_pesanan', 'h.gmv_kotor', 'h.diskon_manual', 'h.status_pesanan', 'h.status_pembayaran', 'd.produk']);

        $totalBelanja = $orders->where('status_pesanan', '!=', 'Batal')->sum(fn($o) => (float) $o->gmv_kotor - (float) ($o->diskon_manual ?? 0));
        $piutang = $orders->where('status_pembayaran', 'Piutang')->where('status_pesanan', '!=', 'Batal')->sum(fn($o) => (float) $o->gmv_kotor - (float) ($o->diskon_manual ?? 0));
        $tipeList = $this->tipeList;

        return view('pelanggan.show', compact('pelanggan', 'orders', 'totalBelanja', 'piutang', 'tipeList'));
    }
}
