<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MasterProduk;
use App\Models\MasterResep;
use App\Models\MasterBibit;
use App\Models\MasterHarga;
use App\Services\HppService;

class MarginController extends Controller
{
    public function __construct(private HppService $hpp) {}

    /** Channel offline khusus: harga ikut Reseller A, HPP tanpa box + tanpa tester. */
    private const CH_OFFLINE_NOBOX = 'Offline (Tanpa Box + Tester)';
    private const CH_NOBOX_BASIS   = 'Reseller A';

    public function index(Request $request)
    {
        $threshold = max(0, (float) $request->input('threshold', 20)); // % margin minimum dianggap "aman"
        $showAll = $request->boolean('all');
        $channel = $request->input('channel');

        // Preload (hindari N+1)
        $produks = MasterProduk::all()->keyBy('sku_id');
        $reseps  = MasterResep::all()->keyBy('sku_id');
        $bibits  = MasterBibit::all()->keyBy('bibit_id');

        // Daftar channel utk dropdown filter (+ channel virtual Offline Tanpa Box)
        $channels = MasterHarga::where('harga_jual', '>', 0)->distinct()->orderBy('channel')->pluck('channel')
            ->push(self::CH_OFFLINE_NOBOX)->unique()->values();

        // Kumpulkan sumber baris: {sku_id, harga_jual, channel utk HPP, label channel}
        $sources = [];

        // 1) Baris channel real (kecuali user memfilter khusus ke channel virtual)
        if ($channel !== self::CH_OFFLINE_NOBOX) {
            $reals = MasterHarga::where('harga_jual', '>', 0)
                ->when($channel, fn($q) => $q->where('channel', $channel))
                ->get();
            foreach ($reals as $h) {
                $sources[] = ['sku' => $h->sku_id, 'harga' => (float) $h->harga_jual, 'ch_hpp' => $h->channel, 'label' => $h->channel];
            }
        }

        // 2) Baris virtual "Offline (Tanpa Box + Tester)": harga ikut Reseller A, HPP tanpa box+tester
        if ($channel === null || $channel === '' || $channel === self::CH_OFFLINE_NOBOX) {
            $basis = MasterHarga::where('channel', self::CH_NOBOX_BASIS)->where('harga_jual', '>', 0)->get();
            foreach ($basis as $h) {
                $sources[] = ['sku' => $h->sku_id, 'harga' => (float) $h->harga_jual, 'ch_hpp' => self::CH_OFFLINE_NOBOX, 'label' => self::CH_OFFLINE_NOBOX];
            }
        }

        $rows = [];
        $countRugi = 0; $countTipis = 0; $evaluated = 0;

        foreach ($sources as $s) {
            $p = $produks[$s['sku']] ?? null;
            if (!$p) continue;
            $r = $reseps[$s['sku']] ?? null;
            $b = ($r && $r->bibit_id) ? ($bibits[$r->bibit_id] ?? null) : null;

            $bd = $this->hpp->previewHpp(
                $b ? (float) $b->harga_per_ml : 0,
                $r ? (float) $r->ml_bibit_utama : 0,
                $r ? (float) $r->ml_absolute : 0,
                $r ? (float) $r->jml_tester : 0,
                (int) $p->ukuran_ml,
                $s['ch_hpp'],
                $b->nama_bibit ?? '-'
            );

            $hpp = (float) $bd['hpp_per_unit'];
            $harga = $s['harga'];
            $margin = $harga - $hpp;
            $marginPct = $harga > 0 ? ($margin / $harga) * 100 : 0;
            $evaluated++;

            $status = $margin < 0 ? 'rugi' : ($marginPct < $threshold ? 'tipis' : 'aman');
            if ($status === 'rugi') $countRugi++;
            elseif ($status === 'tipis') $countTipis++;

            if (!$showAll && $status === 'aman') continue;

            $rows[] = (object) [
                'sku_id'    => $p->sku_id,
                'aroma'     => $p->sku_aroma,
                'nama'      => $p->nama_produk,
                'ukuran'    => $p->ukuran_ml,
                'channel'   => $s['label'],
                'harga'     => $harga,
                'hpp'       => $hpp,
                'margin'    => $margin,
                'marginPct' => $marginPct,
                'status'    => $status,
            ];
        }

        usort($rows, fn($a, $b) => $a->marginPct <=> $b->marginPct); // terburuk di atas

        return view('margin.index', compact('rows', 'threshold', 'showAll', 'countRugi', 'countTipis', 'evaluated', 'channels', 'channel'));
    }
}
