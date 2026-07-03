<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\MasterProduk;
use App\Models\MasterResep;
use App\Models\MasterBibit;
use App\Models\MasterKomponen;
use App\Models\MasterKategori;
use App\Models\StokJadiLog;
use App\Services\HppService;

class StokJadiController extends Controller
{
    public function index()
    {
        $produks = MasterProduk::orderBy('nama_produk')->orderBy('ukuran_ml')->get();
        $alasanList = MasterKategori::where('tipe_kategori', 'Sumber Stok Jadi')->orderBy('id')->pluck('nilai');
        $admins = MasterKategori::where('tipe_kategori', 'Admin')->orderBy('nilai')->pluck('nilai');
        $logs = StokJadiLog::orderBy('created_at', 'desc')->limit(50)->get();

        // Ringkasan nilai inventory produk jadi
        $totalNilai = $produks->sum(fn($p) => (int) $p->stok_t11 * (float) $p->hpp_t11);

        return view('stok_jadi.index', compact('produks', 'alasanList', 'admins', 'logs', 'totalNilai'));
    }

    public function store(Request $request, HppService $hpp)
    {
        $request->validate([
            'sku_id'       => 'required|string|exists:master_produks,sku_id',
            'qty'          => 'required|integer|min:1',
            'alasan'       => 'required|string',
            'dicatat_oleh' => 'nullable|string',
            'catatan'      => 'nullable|string',
        ]);

        $skuId  = $request->sku_id;
        $qty    = (int) $request->qty;
        $alasan = $request->alasan;
        // Salah racik & Produksi batch = produksi nyata -> potong bahan baku. Opname/Lainnya = tidak.
        $produksi = (bool) preg_match('/racik|produksi/i', $alasan);

        try {
            DB::transaction(function () use ($request, $hpp, $skuId, $qty, $alasan, $produksi) {
                $produk = MasterProduk::where('sku_id', $skuId)->firstOrFail();
                $bareHpp = $hpp->bareBottle($skuId);

                if ($produksi) {
                    $resep = MasterResep::where('sku_id', $skuId)->first();
                    if (!$resep || !$resep->bibit_id) {
                        throw new \Exception('Resep / bibit produk ini belum lengkap, tidak bisa potong bahan.');
                    }
                    // Potong bibit
                    $bibit = MasterBibit::where('bibit_id', $resep->bibit_id)->first();
                    if ($bibit) {
                        $bibit->stok_ml = (float) $bibit->stok_ml - ((float) $resep->ml_bibit_utama * $qty);
                        $bibit->save();
                    }
                    // Potong absolute campuran (ml), botol, & sticker utama (botol telanjang)
                    $this->potongKomponen('KMP-ABSC', (float) $resep->ml_absolute * $qty);
                    $this->potongKomponen('KMP-BTL' . (int) $produk->ukuran_ml, $qty);
                    $this->potongKomponen('KMP-STKU', $qty);
                }

                // Tambah ke T11 dengan HPP botol telanjang (rata-rata bergerak)
                $produk->tambahStokJadi($qty, $bareHpp);

                StokJadiLog::catat(
                    $skuId, 'masuk', $qty,
                    $produksi ? 'produksi' : 'opname',
                    $bareHpp, null, $request->dicatat_oleh,
                    $alasan . ($request->catatan ? ' — ' . $request->catatan : '')
                );
            });

            return redirect()->route('stok_jadi.index')->with('success', "Stok produk jadi ditambahkan ($qty pcs)" . ($produksi ? ' & bahan baku dipotong.' : '.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menambah stok jadi: ' . $e->getMessage());
        }
    }

    /** Opname stok produk jadi: setel stok_t11 ke jumlah fisik riil; selisih dicatat. */
    public function opname(Request $request, HppService $hpp)
    {
        $request->validate([
            'sku_id'       => 'required|string|exists:master_produks,sku_id',
            'stok_fisik'   => 'required|integer|min:0',
            'dicatat_oleh' => 'nullable|string',
            'catatan'      => 'nullable|string',
        ]);

        $produk = MasterProduk::where('sku_id', $request->sku_id)->firstOrFail();
        $sistem = (int) $produk->stok_t11;
        $fisik = (int) $request->stok_fisik;
        $selisih = $fisik - $sistem;

        if ($selisih === 0) return redirect()->route('stok_jadi.index')->with('success', 'Stok sudah sesuai, tidak ada koreksi.');

        try {
            DB::transaction(function () use ($produk, $hpp, $request, $sistem, $fisik, $selisih) {
                $bareHpp = $hpp->bareBottle($request->sku_id);
                if ($selisih > 0) {
                    $produk->tambahStokJadi($selisih, $bareHpp); // tambah pakai HPP telanjang (rata-rata bergerak)
                } else {
                    $produk->stok_t11 = $fisik; // kurangi; HPP/pcs tidak berubah
                    $produk->save();
                }
                StokJadiLog::catat(
                    $request->sku_id, $selisih > 0 ? 'masuk' : 'keluar', abs($selisih),
                    'opname', $bareHpp, null, $request->dicatat_oleh,
                    'Opname: sistem ' . $sistem . ' → fisik ' . $fisik . ($request->catatan ? ' — ' . $request->catatan : '')
                );
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal opname: ' . $e->getMessage());
        }

        return redirect()->route('stok_jadi.index')->with('success', "Opname {$request->sku_id}: stok disetel ke {$fisik} pcs (selisih " . ($selisih > 0 ? '+' : '') . "{$selisih}).");
    }

    private function potongKomponen(string $komponenId, float $jumlah): void
    {
        $k = MasterKomponen::where('komponen_id', $komponenId)->first();
        if ($k) {
            $k->stok = (float) $k->stok - $jumlah;
            $k->save();
        }
    }
}
