<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\MasterProduk;
use App\Models\MasterResep;
use App\Models\MasterHarga;
use App\Models\MasterBibit;
use App\Models\MasterKategori;
use App\Services\HppService;

class MasterProdukController extends Controller
{
    /** Channel yang dipakai untuk input harga jual. */
    private function channels(): array
    {
        $ch = MasterKategori::where('tipe_kategori', 'Channel')->pluck('nilai')->toArray();
        return !empty($ch) ? $ch : ['Marketplace TikTok', 'Marketplace Shopee', 'Offline', 'WA', 'Reseller A', 'Reseller B', 'Refill', 'Website'];
    }

    private function hargaId(string $sku, string $channel): string
    {
        return $sku . '_' . strtoupper(str_replace([' ', '-'], '_', $channel));
    }

    public function index(Request $request)
    {
        $q = MasterProduk::query()->orderBy('sku_id');
        if ($request->filled('cari')) {
            $cari = $request->cari;
            $q->where(function ($w) use ($cari) {
                $w->where('sku_id', 'like', "%{$cari}%")
                  ->orWhere('nama_produk', 'like', "%{$cari}%")
                  ->orWhere('sku_aroma', 'like', "%{$cari}%");
            });
        }
        $produks = $q->paginate(40)->withQueryString();

        $hargaCounts = MasterHarga::whereIn('sku_id', $produks->pluck('sku_id'))
            ->where('harga_jual', '>', 0)
            ->selectRaw('sku_id, COUNT(*) as n')->groupBy('sku_id')->pluck('n', 'sku_id');
        $adaResep = MasterResep::whereIn('sku_id', $produks->pluck('sku_id'))->pluck('sku_id')->flip();

        return view('master_produk.index', compact('produks', 'hargaCounts', 'adaResep'));
    }

    public function create()
    {
        $bibits = MasterBibit::orderBy('sku_aroma')->get(['bibit_id', 'sku_aroma', 'nama_bibit', 'merek_bibit', 'nama_asli', 'harga_per_ml']);
        $channels = $this->channels();
        $bentukList = MasterKategori::where('tipe_kategori', 'Bentuk Produk')->pluck('nilai');
        $kategoriList = MasterProduk::whereNotNull('kategori')->distinct()->pluck('kategori');
        $ukuranList = MasterProduk::whereNotNull('ukuran_ml')->distinct()->orderBy('ukuran_ml')->pluck('ukuran_ml');

        $maxBibit = MasterBibit::max('bibit_id');
        $nextBibitNum = $maxBibit ? ((int) substr($maxBibit, 4)) + 1 : 1;
        $nextBibitId = 'BIB-' . str_pad($nextBibitNum, 3, '0', STR_PAD_LEFT);

        return view('master_produk.create', compact('bibits', 'channels', 'bentukList', 'kategoriList', 'ukuranList', 'nextBibitId'));
    }

    /** AJAX: preview HPP per channel dari nilai resep mentah. */
    public function hppPreview(Request $request, HppService $hpp)
    {
        $hargaPerMl = (float) $request->input('harga_per_ml', 0);
        $mlBibit = (float) $request->input('ml_bibit_utama', 0);
        $mlAbs = (float) $request->input('ml_absolute', 0);
        $jmlTester = (float) $request->input('jml_tester', 0);
        $ukuran = (int) $request->input('ukuran_ml', 0);
        $namaBibit = $request->input('nama_bibit', '-');

        $perChannel = [];
        foreach ($this->channels() as $ch) {
            $b = $hpp->previewHpp($hargaPerMl, $mlBibit, $mlAbs, $jmlTester, $ukuran, $ch, $namaBibit);
            $perChannel[$ch] = $b['hpp_per_unit'];
        }
        $ref = $hpp->previewHpp($hargaPerMl, $mlBibit, $mlAbs, $jmlTester, $ukuran, 'Offline', $namaBibit);

        return response()->json([
            'per_channel' => $perChannel,
            'ref' => [
                'hpp_per_unit' => $ref['hpp_per_unit'],
                'bare_per_unit' => $ref['bare_per_unit'],
                'lapis1_per_unit' => $ref['lapis1_per_unit'],
                'tester_total' => $ref['tester']['total'],
                'lapis1' => $ref['lapis1'],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validChannels = $this->channels();

        $request->validate([
            'aroma_mode'     => 'required|in:existing,baru',
            'bibit_id'       => 'required_if:aroma_mode,existing|nullable|string',
            'b_sku_aroma'    => 'required_if:aroma_mode,baru|nullable|string|max:50',
            'b_nama_bibit'   => 'required_if:aroma_mode,baru|nullable|string|max:255',
            'b_merek'        => 'nullable|string|max:255',
            'b_nama_asli'    => 'nullable|string|max:255',
            'b_harga_per_ml' => 'required_if:aroma_mode,baru|nullable|numeric|min:0',
            'b_stok_ml'      => 'nullable|numeric|min:0',
            'b_threshold_ml' => 'nullable|numeric|min:0',

            'bentuk'         => 'nullable|string|max:50',
            'kategori'       => 'nullable|string|max:100',

            'varian'                  => 'required|array|min:1',
            'varian.*.ukuran'         => 'required|numeric|min:1',
            'varian.*.nama_produk'    => 'required|string|max:255',
            'varian.*.konsentrasi'    => 'nullable|string|max:50',
            'varian.*.ml_bibit_utama' => 'required|numeric|min:0',
            'varian.*.ml_absolute'    => 'required|numeric|min:0',
            'varian.*.jml_tester'     => 'required|numeric|min:0',
            'varian.*.harga'          => 'required|array',
        ]);

        $bentuk = $request->bentuk ?: 'REG';

        // Tentukan sku_aroma (existing dari bibit, baru dari input)
        if ($request->aroma_mode === 'existing') {
            $bibitExisting = MasterBibit::where('bibit_id', $request->bibit_id)->first();
            if (!$bibitExisting) return back()->withInput()->withErrors(['bibit_id' => 'Aroma tidak ditemukan.']);
            $skuAroma = $bibitExisting->sku_aroma;
        } else {
            $skuAroma = $request->b_sku_aroma;
        }

        // Validasi tiap varian: minimal 1 channel berharga + sku_id unik
        $varianBersih = [];
        foreach ($request->input('varian', []) as $v) {
            $ukuran = (int) $v['ukuran'];
            $sku = $skuAroma . '-' . $ukuran . '-' . $bentuk;

            $hargaTerisi = collect($v['harga'] ?? [])->filter(fn($x) => is_numeric($x) && (float) $x > 0);
            if ($hargaTerisi->isEmpty()) {
                return back()->withInput()->withErrors(['varian' => "Varian {$ukuran}ml: isi harga jual minimal 1 channel."]);
            }
            if (MasterProduk::where('sku_id', $sku)->exists()) {
                return back()->withInput()->withErrors(['varian' => "SKU {$sku} sudah ada. Ganti ukuran/bentuk."]);
            }
            $v['sku_id'] = $sku;
            $v['ukuran'] = $ukuran;
            $varianBersih[] = $v;
        }

        try {
            DB::transaction(function () use ($request, $skuAroma, $bentuk, $varianBersih, $validChannels) {
                // Bibit
                if ($request->aroma_mode === 'baru') {
                    $maxBibit = MasterBibit::max('bibit_id');
                    $nextNum = $maxBibit ? ((int) substr($maxBibit, 4)) + 1 : 1;
                    $bibitId = 'BIB-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
                    MasterBibit::create([
                        'bibit_id'     => $bibitId,
                        'sku_aroma'    => $request->b_sku_aroma,
                        'nama_bibit'   => $request->b_nama_bibit,
                        'merek_bibit'  => $request->b_merek,
                        'nama_asli'    => $request->b_nama_asli,
                        'harga_per_ml' => $request->b_harga_per_ml,
                        'stok_ml'      => $request->b_stok_ml ?? 0,
                        'threshold_ml' => $request->b_threshold_ml ?? 0,
                        'status'       => 'Aktif',
                        'stok_awal'    => $request->b_stok_ml ?? 0,
                        'harga_awal'   => $request->b_harga_per_ml,
                    ]);
                    $bibit = MasterBibit::find($bibitId);
                } else {
                    $bibit = MasterBibit::where('bibit_id', $request->bibit_id)->firstOrFail();
                }

                foreach ($varianBersih as $v) {
                    $sku = $v['sku_id'];
                    MasterProduk::create([
                        'sku_id'      => $sku,
                        'sku_aroma'   => $bibit->sku_aroma,
                        'bibit_id'    => $bibit->bibit_id,
                        'nama_produk' => $v['nama_produk'],
                        'kategori'    => $request->kategori ?: null,
                        'ukuran_ml'   => $v['ukuran'],
                        'bentuk'      => $bentuk,
                        'status'      => 'Aktif',
                    ]);
                    MasterResep::create([
                        'resep_id'       => $sku,
                        'sku_id'         => $sku,
                        'bibit_id'       => $bibit->bibit_id,
                        'konsentrasi'    => $v['konsentrasi'] ?? null,
                        'ml_bibit_utama' => $v['ml_bibit_utama'],
                        'ml_absolute'    => $v['ml_absolute'],
                        'jml_tester'     => $v['jml_tester'],
                    ]);
                    foreach ($v['harga'] as $channel => $hargaJual) {
                        if (!in_array($channel, $validChannels)) continue;
                        if (!is_numeric($hargaJual) || (float) $hargaJual <= 0) continue;
                        MasterHarga::create([
                            'harga_id'   => $this->hargaId($sku, $channel),
                            'sku_id'     => $sku,
                            'channel'    => $channel,
                            'harga_jual' => $hargaJual,
                            'status'     => 'Aktif',
                        ]);
                    }
                }
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Gagal menyimpan produk: ' . $e->getMessage());
        }

        $n = count($varianBersih);
        return redirect()->route('master_produk.detail', $skuAroma)->with('success', "{$n} varian produk {$skuAroma} berhasil dibuat & siap dijual.");
    }

    // ─────────────────────── DETAIL & EDIT PER AROMA ───────────────────────

    public function detail(string $aroma, HppService $hpp)
    {
        $bibit = MasterBibit::where('sku_aroma', $aroma)->first();
        $produks = MasterProduk::where('sku_aroma', $aroma)->orderBy('ukuran_ml')->get();

        if (!$bibit && $produks->isEmpty()) {
            abort(404, 'Aroma tidak ditemukan.');
        }

        $channels = $this->channels();
        $reseps = MasterResep::whereIn('sku_id', $produks->pluck('sku_id'))->get()->keyBy('sku_id');
        $hargaRows = MasterHarga::whereIn('sku_id', $produks->pluck('sku_id'))->get()
            ->groupBy('sku_id')->map(fn($g) => $g->keyBy('channel'));

        // HPP per varian (basis Offline reguler) untuk info margin
        $hppPerSku = [];
        foreach ($produks as $p) {
            $hppPerSku[$p->sku_id] = $hpp->breakdown($p->sku_id, 'Offline')['hpp_per_unit'];
        }

        return view('master_produk.detail', compact('bibit', 'produks', 'channels', 'reseps', 'hargaRows', 'hppPerSku', 'aroma'));
    }

    public function updateBibit(Request $request, string $aroma)
    {
        $bibit = MasterBibit::where('sku_aroma', $aroma)->firstOrFail();
        $request->validate([
            'nama_bibit'   => 'required|string|max:255',
            'merek_bibit'  => 'nullable|string|max:255',
            'nama_asli'    => 'nullable|string|max:255',
            'harga_per_ml' => 'required|numeric|min:0',
            'threshold_ml' => 'nullable|numeric|min:0',
        ]);
        $bibit->update($request->only('nama_bibit', 'merek_bibit', 'nama_asli', 'harga_per_ml', 'threshold_ml'));
        return back()->with('success', 'Data aroma/bibit diperbarui.');
    }

    public function updateProduk(Request $request, string $sku)
    {
        $produk = MasterProduk::where('sku_id', $sku)->firstOrFail();
        $request->validate([
            'nama_produk'    => 'required|string|max:255',
            'kategori'       => 'nullable|string|max:100',
            'bentuk'         => 'nullable|string|max:50',
            'status'         => 'nullable|string|max:50',
            'konsentrasi'    => 'nullable|string|max:50',
            'ml_bibit_utama' => 'required|numeric|min:0',
            'ml_absolute'    => 'required|numeric|min:0',
            'jml_tester'     => 'required|numeric|min:0',
        ]);

        $produk->update($request->only('nama_produk', 'kategori', 'bentuk', 'status'));

        MasterResep::updateOrCreate(
            ['sku_id' => $sku],
            [
                'resep_id'       => $sku,
                'bibit_id'       => $produk->bibit_id,
                'konsentrasi'    => $request->konsentrasi,
                'ml_bibit_utama' => $request->ml_bibit_utama,
                'ml_absolute'    => $request->ml_absolute,
                'jml_tester'     => $request->jml_tester,
            ]
        );

        return back()->with('success', "Detail & resep {$sku} diperbarui.");
    }

    public function updateHarga(Request $request, string $sku)
    {
        $produk = MasterProduk::where('sku_id', $sku)->firstOrFail();
        $validChannels = $this->channels();

        foreach ($request->input('harga', []) as $channel => $hargaJual) {
            if (!in_array($channel, $validChannels)) continue;
            $hargaId = $this->hargaId($sku, $channel);

            if (is_numeric($hargaJual) && (float) $hargaJual > 0) {
                MasterHarga::updateOrCreate(
                    ['sku_id' => $sku, 'channel' => $channel],
                    ['harga_id' => $hargaId, 'harga_jual' => $hargaJual, 'status' => 'Aktif']
                );
            } else {
                // kosong / 0 → hapus harga channel ini
                MasterHarga::where('sku_id', $sku)->where('channel', $channel)->delete();
            }
        }

        return back()->with('success', "Harga jual {$sku} diperbarui.");
    }
}
