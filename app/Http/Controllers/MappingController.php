<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MarketplaceSku;
use App\Models\MasterProduk;

class MappingController extends Controller
{
    public function index()
    {
        $products = MasterProduk::orderBy('nama_produk')->get();
        $valid = $products->pluck('nama_produk', 'sku_id'); // sku_id => nama_produk

        // SEMUA peta (belum dipetakan tampil dulu), lengkap dgn status untuk audit.
        $rows = MarketplaceSku::orderByRaw('sku_id IS NOT NULL')
            ->orderBy('platform')->orderBy('marketplace_nama')->get()
            ->map(function ($m) use ($valid) {
                $m->status_map = is_null($m->sku_id) ? 'kosong'
                    : ($m->sku_id === 'SKIP' ? 'skip'
                    : (isset($valid[$m->sku_id]) ? 'ok' : 'dangling')); // dangling = SKU tak ada di master
                $m->nama_produk = $m->sku_id && $m->sku_id !== 'SKIP' ? ($valid[$m->sku_id] ?? null) : null;
                return $m;
            });

        $unmappedCount = $rows->where('status_map', 'kosong')->count();
        $danglingCount = $rows->where('status_map', 'dangling')->count();

        return view('upload.mapping', compact('rows', 'products', 'unmappedCount', 'danglingCount'));
    }

    public function store(Request $request)
    {
        $mappings = $request->input('mappings', []); // [mkt_sku_id => sku_id | '' ]

        $count = 0;
        foreach ($mappings as $mktId => $skuId) {
            $mkt = MarketplaceSku::find($mktId);
            if (!$mkt) continue;
            $new = ($skuId === '' || $skuId === null) ? null : $skuId; // kosong = jadikan menggantung
            if ($mkt->sku_id !== $new) {
                $mkt->sku_id = $new;
                $mkt->save();
                $count++;
            }
        }

        return redirect()->route('mapping.index')
            ->with('success', "Berhasil menyimpan $count perubahan peta. Jika ada pesanan tertunda, upload ulang file pesanannya agar masuk.");
    }

    /** Hapus satu baris peta SKU marketplace. */
    public function destroy($id)
    {
        MarketplaceSku::where('id', $id)->delete();
        return redirect()->route('mapping.index')->with('success', 'Peta SKU dihapus.');
    }

    /**
     * Hapus SEMUA peta yang masih menggantung (sku_id belum dipetakan).
     * Untuk produk lama yang TAK dijual lagi, pilih "❌ ABAIKAN" agar diabaikan permanen.
     */
    public function destroyDangling()
    {
        $n = MarketplaceSku::whereNull('sku_id')->delete();
        return redirect()->route('mapping.index')->with('success', "$n peta menggantung dihapus.");
    }

    /**
     * RESET SEMUA peta: kosongkan sku_id semua baris (termasuk SKIP) → jadi menggantung lagi.
     * Berguna bila ada kesalahan pemetaan (mis. dari fase development) & ingin petakan ulang.
     * Baris tetap ada; tinggal dipetakan ulang lalu upload ulang file pesanan.
     */
    public function resetAll()
    {
        $n = MarketplaceSku::whereNotNull('sku_id')->update(['sku_id' => null]);
        return redirect()->route('mapping.index')->with('success', "$n peta di-reset (dikosongkan). Silakan petakan ulang semuanya.");
    }
}
