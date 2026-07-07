<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\SampelAffiliate;
use App\Models\MasterProduk;
use App\Models\MasterKategori;
use App\Models\PenjualanHeader;
use App\Models\PenjualanDetail;
use App\Services\HppService;
use App\Services\RacikService;

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
            // HPP diestimasi dari resep (utk laporan biaya promo); stok DIPOTONG saat diracik nanti.
            $bd = $hpp->breakdown($sku, 'Offline', $qty);
            $hppSatuan = (float) ($bd['hpp_per_unit'] ?? 0);
            $totalHpp  = (float) ($bd['hpp_total'] ?? $hppSatuan * $qty);

            // Buat pesanan "Gratis" (harga 0) yang MASUK ANTREAN RACIK.
            // status_pembayaran 'Gratis' → otomatis dikecualikan dari P&L (bukan omzet).
            $header = PenjualanHeader::create([
                'channel'           => 'Gratis',
                'metode_pengiriman' => 'Kirim',
                'tgl_pesanan'       => $request->tanggal,
                'status_pesanan'    => 'Menunggu',
                'status_pembayaran' => 'Gratis',
                'gmv_kotor'         => 0,
                'diskon_manual'     => 0,
                'nama_pembeli'      => $request->nama_affiliate,
                'ekstra_tester'     => 0,
                'catatan'           => 'Produk gratis · ' . $request->platform . ($request->catatan ? ' · ' . $request->catatan : ''),
                'diracik_oleh'      => null,
            ]);
            PenjualanDetail::create([
                'internal_id'  => $header->internal_id,
                'sku_id'       => $sku,
                'qty'          => $qty,
                'harga_satuan' => 0,
                'subtotal'     => 0,
                'hpp_satuan'   => null,   // null → muncul di antrean racik
                'margin_satuan' => null,
            ]);

            SampelAffiliate::create([
                'tanggal'        => $request->tanggal,
                'platform'       => $request->platform,
                'nama_affiliate' => $request->nama_affiliate,
                'sku_id'         => $sku,
                'internal_id'    => $header->internal_id,
                'qty'            => $qty,
                'hpp_satuan'     => round($hppSatuan, 2),
                'total_hpp'      => round($totalHpp, 2),
                'catatan'        => $request->catatan,
                'dicatat_oleh'   => $request->dicatat_oleh,
            ]);
        });

        return redirect()->route('sampel.index')->with('success', "Produk gratis {$qty}x dicatat & masuk ANTREAN RACIK untuk {$request->nama_affiliate} ({$request->platform}). Stok bibit terpotong saat diracik.");
    }

    public function destroy(SampelAffiliate $sampel, RacikService $racik)
    {
        DB::transaction(function () use ($sampel, $racik) {
            $header = $sampel->internal_id ? PenjualanHeader::with('details')->find($sampel->internal_id) : null;
            if ($header) {
                // Jika sudah diracik (stok terpotong) → kembalikan stok dulu, baru hapus pesanannya.
                if ($header->details->whereNotNull('hpp_satuan')->count() > 0) {
                    $racik->kembalikanStokRacik($header);
                }
                $header->details()->delete();
                $header->delete();
            }
            $sampel->delete();
        });

        return redirect()->back()->with('success', 'Catatan produk gratis & pesanan racik-nya dihapus (stok dikembalikan bila sudah diracik).');
    }
}
