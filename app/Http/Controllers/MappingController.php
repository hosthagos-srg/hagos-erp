<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MarketplaceSku;
use App\Models\MasterProduk;

class MappingController extends Controller
{
    /** Halaman utama: HANYA yang belum dipetakan (fokus aksi jodohkan). */
    public function index()
    {
        $unmappedSkus = MarketplaceSku::whereNull('sku_id')
            ->orderBy('platform')->orderBy('marketplace_nama')->get();
        $products = MasterProduk::orderBy('nama_produk')->get();

        $validIds = $products->pluck('sku_id')->flip();
        $mappedCount   = MarketplaceSku::whereNotNull('sku_id')->count();
        $danglingCount = MarketplaceSku::whereNotNull('sku_id')->where('sku_id', '!=', 'SKIP')->get()
            ->filter(fn($m) => !isset($validIds[$m->sku_id]))->count();

        return view('upload.mapping', compact('unmappedSkus', 'products', 'mappedCount', 'danglingCount'));
    }

    /** Halaman terpisah: riwayat/kelola SEMUA peta yang SUDAH dipetakan (audit, ubah, reset). */
    public function kelola()
    {
        $products = MasterProduk::orderBy('nama_produk')->get();
        $valid = $products->pluck('nama_produk', 'sku_id'); // sku_id => nama

        $rows = MarketplaceSku::whereNotNull('sku_id')
            ->orderBy('platform')->orderBy('marketplace_nama')->get()
            ->map(function ($m) use ($valid) {
                $m->status_map = $m->sku_id === 'SKIP' ? 'skip' : (isset($valid[$m->sku_id]) ? 'ok' : 'dangling');
                $m->nama_produk = $m->sku_id !== 'SKIP' ? ($valid[$m->sku_id] ?? null) : null;
                return $m;
            });
        $danglingCount = $rows->where('status_map', 'dangling')->count();

        return view('upload.mapping_kelola', compact('rows', 'products', 'danglingCount'));
    }

    public function store(Request $request)
    {
        $mappings = $request->input('mappings', []); // [mkt_sku_id => sku_id | '' ]
        $back = $request->input('back', 'mapping.index');

        $count = 0;
        foreach ($mappings as $mktId => $skuId) {
            $mkt = MarketplaceSku::find($mktId);
            if (!$mkt) continue;
            $new = ($skuId === '' || $skuId === null) ? null : $skuId;
            if ($mkt->sku_id !== $new) { $mkt->sku_id = $new; $mkt->save(); $count++; }
        }

        return redirect()->route($back === 'mapping.kelola' ? 'mapping.kelola' : 'mapping.index')
            ->with('success', "Berhasil menyimpan $count perubahan peta. Jika ada pesanan tertunda, upload ulang file pesanannya agar masuk.");
    }

    /** Hapus satu baris peta SKU marketplace. */
    public function destroy($id)
    {
        MarketplaceSku::where('id', $id)->delete();
        return redirect()->back()->with('success', 'Peta SKU dihapus.');
    }

    /** Hapus SEMUA yang belum dipetakan (sku_id null). */
    public function destroyDangling()
    {
        $n = MarketplaceSku::whereNull('sku_id')->delete();
        return redirect()->route('mapping.index')->with('success', "$n peta yang belum dipetakan dihapus.");
    }

    /** RESET SEMUA peta: kosongkan sku_id semua baris → jadi belum-dipetakan lagi (untuk perbaiki salah petakan). */
    public function resetAll()
    {
        $n = MarketplaceSku::whereNotNull('sku_id')->update(['sku_id' => null]);
        return redirect()->route('mapping.index')->with('success', "$n peta di-reset (dikosongkan). Silakan petakan ulang di halaman Pemetaan.");
    }
}
