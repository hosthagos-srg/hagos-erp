<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MarketplaceSku;
use App\Models\MasterProduk;

class MappingController extends Controller
{
    public function index()
    {
        $unmappedSkus = MarketplaceSku::whereNull('sku_id')->get();
        $products = MasterProduk::all();

        return view('upload.mapping', compact('unmappedSkus', 'products'));
    }

    public function store(Request $request)
    {
        $mappings = $request->input('mappings', []); // [mkt_sku_id => sku_id]

        $count = 0;
        foreach ($mappings as $mktId => $skuId) {
            if ($skuId !== null && $skuId !== '') {
                $mkt = MarketplaceSku::find($mktId);
                if ($mkt) {
                    $mkt->sku_id = $skuId;
                    $mkt->save();
                    $count++;
                }
            }
        }

        return redirect()->route('upload.index')->with('success', "Berhasil memetakan $count SKU! Silakan upload ulang file pesanan Anda agar pesanan yang tadi tertunda bisa masuk.");
    }
}
