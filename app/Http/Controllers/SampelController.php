<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\SampelAffiliate;
use App\Models\MasterProduk;
use App\Models\MasterResep;
use App\Models\MasterBibit;
use App\Models\MasterKategori;
use App\Models\StokJadiLog;
use App\Services\HppService;

class SampelController extends Controller
{
    private array $platformList = ['TikTok', 'Shopee', 'Lainnya'];

    public function index(Request $request)
    {
        $query = SampelAffiliate::with('produk')->orderBy('tanggal', 'desc')->orderBy('created_at', 'desc');
        if ($request->filled('dari'))     $query->whereDate('tanggal', '>=', $request->dari);
        if ($request->filled('sampai'))   $query->whereDate('tanggal', '<=', $request->sampai);
        if ($request->filled('platform')) $query->where('platform', $request->platform);

        // Ringkasan (ikut filter tanggal/platform)
        $summaryQ = SampelAffiliate::query();
        if ($request->filled('dari'))     $summaryQ->whereDate('tanggal', '>=', $request->dari);
        if ($request->filled('sampai'))   $summaryQ->whereDate('tanggal', '<=', $request->sampai);
        if ($request->filled('platform')) $summaryQ->where('platform', $request->platform);
        $all = $summaryQ->get();
        $totalQty = $all->sum('qty');
        $totalHpp = $all->sum('total_hpp');
        $perPlatform = $all->groupBy('platform')->map(fn($g) => ['qty' => $g->sum('qty'), 'hpp' => $g->sum('total_hpp')]);

        $riwayat = $query->paginate(40)->withQueryString();

        $produks = MasterProduk::orderBy('nama_produk')->get(['sku_id', 'nama_produk', 'ukuran_ml']);
        $admins = MasterKategori::where('tipe_kategori', 'Admin')->orderBy('nilai')->pluck('nilai');
        $platformList = $this->platformList;

        return view('sampel.index', compact('riwayat', 'produks', 'admins', 'platformList', 'totalQty', 'totalHpp', 'perPlatform'));
    }

    public function store(Request $request, HppService $hpp)
    {
        $request->validate([
            'tanggal'        => 'required|date',
            'platform'       => 'required|string|max:50',
            'nama_affiliate' => 'required|string|max:255',
            'sku_id'         => 'required|exists:master_produks,sku_id',
            'qty'            => 'required|integer|min:1',
            'catatan'        => 'nullable|string',
            'dicatat_oleh'   => 'nullable|string',
        ]);

        $sku = $request->sku_id;
        $qty = (int) $request->qty;

        DB::transaction(function () use ($request, $hpp, $sku, $qty) {
            $produk = MasterProduk::where('sku_id', $sku)->first();
            $resep = MasterResep::where('sku_id', $sku)->first();

            // T11 dulu, sisanya racik baru (pola sama dgn RacikService)
            $stokT11 = $produk ? (int) $produk->stok_t11 : 0;
            $hppT11  = $produk ? (float) $produk->hpp_t11 : 0;
            $bare    = $hpp->bareBottle($sku); // HPP botol telanjang utk racik baru

            $qtyT11 = min($qty, max(0, $stokT11));
            $qtyRacik = $qty - $qtyT11;

            // Potong T11
            if ($qtyT11 > 0 && $produk) {
                $produk->stok_t11 -= $qtyT11;
                $produk->save();
                StokJadiLog::catat($sku, 'keluar', $qtyT11, 'sampel', $hppT11, 'SAMPEL', $request->dicatat_oleh);
            }
            // Potong bibit + komponen botol telanjang (botol, absolute) utk racik baru
            if ($qtyRacik > 0) {
                if ($resep && $resep->bibit_id) {
                    $bibit = MasterBibit::where('bibit_id', $resep->bibit_id)->first();
                    if ($bibit) {
                        $bibit->stok_ml = (float) $bibit->stok_ml - ((float) $resep->ml_bibit_utama * $qtyRacik);
                        $bibit->save();
                    }
                }
                // Komponen ber-stok pada botol telanjang (botol + absolute) — selaras dgn bareBottle
                foreach (($hpp->breakdown($sku, 'Offline')['komponen_usage']['bare'] ?? []) as $u) {
                    $komp = \App\Models\MasterKomponen::where('komponen_id', $u['id'])->first();
                    if ($komp && strtolower((string) $komp->track_stok) !== 'tidak') {
                        $komp->stok = (float) $komp->stok - ($u['qty'] * $qtyRacik);
                        $komp->save();
                    }
                }
            }

            $totalHpp = ($qtyT11 * $hppT11) + ($qtyRacik * $bare);
            $hppSatuan = $qty > 0 ? $totalHpp / $qty : 0;

            SampelAffiliate::create([
                'tanggal'        => $request->tanggal,
                'platform'       => $request->platform,
                'nama_affiliate' => $request->nama_affiliate,
                'sku_id'         => $sku,
                'qty'            => $qty,
                'hpp_satuan'     => round($hppSatuan, 2),
                'total_hpp'      => round($totalHpp, 2),
                'catatan'        => $request->catatan,
                'dicatat_oleh'   => $request->dicatat_oleh,
            ]);
        });

        return redirect()->route('sampel.index')->with('success', "Produk gratis {$qty}x dicatat untuk {$request->nama_affiliate} ({$request->platform}).");
    }

    public function destroy(SampelAffiliate $sampel)
    {
        // Kembalikan stok (balikkan ke T11 sebagai botol telanjang, paling aman & tak ganggu bibit yg sudah terpakai)
        DB::transaction(function () use ($sampel) {
            $produk = MasterProduk::where('sku_id', $sampel->sku_id)->first();
            if ($produk) {
                $produk->stok_t11 = (int) $produk->stok_t11 + (int) $sampel->qty;
                $produk->save();
                StokJadiLog::catat($sampel->sku_id, 'masuk', $sampel->qty, 'batal_sampel', $sampel->hpp_satuan, 'SAMPEL', null);
            }
            $sampel->delete();
        });

        return redirect()->back()->with('success', 'Catatan produk gratis dihapus & stok dikembalikan ke Stok Produk Jadi.');
    }
}
