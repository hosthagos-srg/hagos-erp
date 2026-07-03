<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\MasterProduk;
use App\Models\MasterResep;
use App\Models\MasterResepBibit;
use App\Models\MasterBibit;
use App\Services\HppService;

/**
 * Resep Mix — komposisi >1 bibit dalam 1 botol (mis. "Skndls x Dunsblue").
 * Menempel ke resep sebuah SKU (resep_id = sku_id) lewat tabel master_resep_bibit.
 * Saat ada baris mix, HPP & racik otomatis pakai komposisi ini (mengalahkan bibit tunggal).
 */
class ResepMixController extends Controller
{
    public function __construct(private HppService $hpp) {}

    public function index()
    {
        // SKU yang punya komposisi mix
        $rows = MasterResepBibit::orderBy('resep_id')->get()->groupBy('resep_id');

        $produks = MasterProduk::orderBy('sku_id')->get()->keyBy('sku_id');
        $bibits  = MasterBibit::orderBy('nama_bibit')->get();
        $bibitById = $bibits->keyBy('bibit_id');

        $mixes = [];
        foreach ($rows as $resepId => $items) {
            $resep = MasterResep::where('resep_id', $resepId)->first();
            $sku = $resep->sku_id ?? $resepId;
            $produk = $produks[$sku] ?? null;
            $totalMl = (float) $items->sum('ml');
            $hppUnit = $produk ? (float) $this->hpp->breakdown($sku, 'Offline')['hpp_per_unit'] : 0;
            $mixes[] = [
                'sku'      => $sku,
                'nama'     => $produk->nama_produk ?? $sku,
                'ukuran'   => $produk->ukuran_ml ?? '-',
                'total_ml' => $totalMl,
                'hpp'      => $hppUnit,
                'items'    => $items->map(fn($it) => [
                    'bibit_id' => $it->bibit_id,
                    'nama'     => $bibitById[$it->bibit_id]->nama_bibit ?? $it->bibit_id,
                    'ml'       => (float) $it->ml,
                ])->all(),
            ];
        }

        return view('resep_mix.index', compact('mixes', 'produks', 'bibits'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'sku_id'      => 'required|string',
            'bibit_id'    => 'required|array|min:2',
            'bibit_id.*'  => 'required|string',
            'ml'          => 'required|array|min:2',
            'ml.*'        => 'required|numeric|min:0.0001',
        ]);

        $sku = $request->sku_id;
        $produk = MasterProduk::where('sku_id', $sku)->first();
        if (!$produk) {
            return back()->with('error', "SKU {$sku} tidak ditemukan di Master Produk. Buat produknya dulu.");
        }

        // Pastikan resep induk ada (untuk absolute & tester). Bila belum, buat kerangka.
        $resep = MasterResep::firstOrCreate(
            ['sku_id' => $sku],
            ['resep_id' => $sku, 'ml_absolute' => 0, 'jml_tester' => 0]
        );
        $resepId = $resep->resep_id ?: $sku;

        // Gabungkan bibit yang sama (jika terpilih dobel)
        $combined = [];
        foreach ($request->bibit_id as $i => $bid) {
            if (!$bid) continue;
            $ml = (float) ($request->ml[$i] ?? 0);
            if ($ml <= 0) continue;
            $combined[$bid] = ($combined[$bid] ?? 0) + $ml;
        }
        if (count($combined) < 2) {
            return back()->with('error', 'Resep mix minimal 2 bibit berbeda dengan ml > 0.');
        }

        DB::transaction(function () use ($resepId, $sku, $combined) {
            MasterResepBibit::where('resep_id', $resepId)->delete();
            foreach ($combined as $bibitId => $ml) {
                MasterResepBibit::create([
                    'resep_id' => $resepId,
                    'sku_id'   => $sku,
                    'bibit_id' => $bibitId,
                    'ml'       => $ml,
                ]);
            }
        });

        return redirect()->route('resep_mix.index')
            ->with('success', "Resep mix {$sku} disimpan (" . count($combined) . " bibit, total " . rtrim(rtrim(number_format(array_sum($combined), 4, '.', ''), '0'), '.') . " ml).");
    }

    public function destroy(string $sku)
    {
        $resep = MasterResep::where('sku_id', $sku)->first();
        $resepId = $resep->resep_id ?? $sku;
        MasterResepBibit::where('resep_id', $resepId)->orWhere('sku_id', $sku)->delete();

        return redirect()->route('resep_mix.index')->with('success', "Resep mix {$sku} dihapus (kembali ke bibit tunggal).");
    }

    /** AJAX: preview HPP dari komposisi mix mentah (sebelum simpan). */
    public function preview(Request $request)
    {
        $sku = $request->input('sku_id');
        $produk = MasterProduk::where('sku_id', $sku)->first();
        $resep = MasterResep::where('sku_id', $sku)->first();
        if (!$produk) return response()->json(['ok' => false, 'msg' => 'SKU tidak ditemukan']);

        $components = [];
        foreach ((array) $request->input('bibit_id', []) as $i => $bid) {
            if (!$bid) continue;
            $components[] = ['bibit_id' => $bid, 'ml' => (float) (($request->input('ml', [])[$i]) ?? 0)];
        }
        if (count($components) < 1) return response()->json(['ok' => false, 'msg' => 'Isi bibit dulu']);

        $bd = $this->hpp->previewHppBlend(
            $components,
            $resep ? (float) $resep->ml_absolute : 0,
            $resep ? (float) $resep->jml_tester : 0,
            (int) $produk->ukuran_ml,
            'Offline'
        );

        return response()->json([
            'ok' => true,
            'hpp_per_unit' => $bd['hpp_per_unit'],
            'lapis1_per_unit' => $bd['lapis1_per_unit'],
            'total_ml' => array_sum(array_column($components, 'ml')),
        ]);
    }
}
