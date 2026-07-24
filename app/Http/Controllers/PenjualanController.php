<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MasterProduk;
use App\Models\MasterKategori;
use App\Models\MasterResep;
use App\Models\MasterHarga;
use App\Models\MasterBibit;
use App\Models\MasterKomponen;
use App\Models\PenjualanHeader;
use App\Models\PenjualanDetail;
use Illuminate\Support\Facades\DB;

class PenjualanController extends Controller
{
    public function index(Request $request)
    {
        // Default: tanggal pesanan TERBARU (termuda) di atas; created_at sebagai pemecah seri.
        $query = PenjualanHeader::with('details.produk')
            ->orderBy('tgl_pesanan', 'desc')
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('external_order_id', 'like', "%{$search}%")
                  ->orWhere('internal_id', 'like', "%{$search}%")
                  ->orWhere('no_resi', 'like', "%{$search}%")
                  ->orWhere('nama_pembeli', 'like', "%{$search}%");
            });
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tgl_pesanan', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('start_date')) {
            $query->where('tgl_pesanan', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $query->where('tgl_pesanan', '<=', $request->end_date);
        }

        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }

        // Toggle monitoring: hanya pesanan yang dananya BELUM CAIR (settlement belum masuk).
        if ($request->boolean('belum_cair')) {
            $query->where('status_pembayaran', 'Belum Cair');
        }

        // Retur marketplace yang sudah ditangani (dari log stok jadi 'Retur MP%').
        $returMpHandled = DB::table('stok_jadi_logs')->where('sumber', 'like', 'Retur MP%')
            ->whereNotNull('ref_id')->distinct()->pluck('ref_id')->flip();

        // Toggle dari banner: tampilkan HANYA pesanan retur marketplace yang belum ditangani.
        if ($request->boolean('retur')) {
            $query->where('net_settlement', '<', 0)
                  ->where('status_pesanan', '!=', 'Batal')
                  ->whereNotIn('internal_id', $returMpHandled->keys()->all() ?: ['-']);
        }

        $pesanans = $query->paginate(50)->withQueryString();
        
        $channels = PenjualanHeader::select('channel')->distinct()->pluck('channel');
        $produks = MasterProduk::all();
        $alasanBatal = MasterKategori::where('tipe_kategori', 'Alasan Batal')->orderBy('id')->pluck('nilai');
        $akuns = \App\Models\MasterAkunKas::whereNotIn('tipe', ['Saldo MP', 'Piutang'])->orderBy('akun_id')->pluck('nama_akun');

        // Tracking settlement/COD: ringkas — tampilkan 5 teratas + total (detail lengkap di halaman khusus).
        $perluCekCount = $this->perluCekQuery()->count();
        $perluCek = $this->perluCekQuery()->orderBy('tgl_pesanan')->limit(5)->get();

        // Pesanan batal yang menunggu barang fisik kembali (untuk notice + halaman monitoring).
        $barangBalikCount = $this->perluBarangBalikQuery()->count();

        // Pesanan yang PERLU AKSI PRA-RACIK sebelum diracik (agar tak terlewat):
        //  - Pecah Bundle: ada baris produk bentuk 'BUNDLE' (belum dipecah).
        //  - Set Mix: ada baris SKU 'MIX*' yang BELUM ada komposisi (resep_blend null) & bukan mix-tetap.
        $mixTetapSkus = \App\Models\MasterResepBibit::distinct()->pluck('sku_id')->all();
        $perluAksiCount = PenjualanDetail::query()
            ->join('penjualan_headers as h', 'penjualan_details.internal_id', '=', 'h.internal_id')
            ->leftJoin('master_produks as mp', 'penjualan_details.sku_id', '=', 'mp.sku_id')
            ->where('h.status_pesanan', 'Menunggu')
            ->where(function ($q) use ($mixTetapSkus) {
                $q->where('mp.bentuk', 'BUNDLE')
                  ->orWhere(function ($q2) use ($mixTetapSkus) {
                      $q2->where('penjualan_details.sku_id', 'like', 'MIX%')
                         ->whereNull('penjualan_details.resep_blend');
                      if (!empty($mixTetapSkus)) $q2->whereNotIn('penjualan_details.sku_id', $mixTetapSkus);
                  });
            })
            ->distinct()->count(DB::raw('h.internal_id'));

        // Jumlah retur marketplace yang belum ditangani (untuk banner). $returMpHandled dihitung di atas.
        $returMpCount = PenjualanHeader::where('net_settlement', '<', 0)
            ->where('status_pesanan', '!=', 'Batal')
            ->whereNotIn('internal_id', $returMpHandled->keys()->all() ?: ['-'])
            ->count();

        return view('penjualan.index', compact('pesanans', 'perluCek', 'perluCekCount', 'barangBalikCount', 'channels', 'produks', 'alasanBatal', 'akuns', 'mixTetapSkus', 'perluAksiCount', 'returMpHandled', 'returMpCount'));
    }

    /**
     * Query pesanan marketplace belum cair yang perlu dicek manual (tracking settlement/COD):
     * belum pernah dicek & umur > 12 hari, ATAU sudah dicek tapi > 3 hari sejak cek terakhir.
     */
    private function perluCekQuery()
    {
        return PenjualanHeader::where('channel', 'like', 'Marketplace%')
            ->where('status_pembayaran', '!=', 'Cair')
            ->where('status_pesanan', '!=', 'Batal')
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereNull('tgl_dicek')->whereDate('tgl_pesanan', '<=', now()->subDays(12));
                })->orWhere(function ($q2) {
                    $q2->whereNotNull('tgl_dicek')->whereDate('tgl_dicek', '<=', now()->subDays(3));
                });
            });
    }

    /**
     * Daftar MONITORING: semua pesanan marketplace yang dananya belum cair & sudah masuk
     * pantauan (pernah dicek ATAU umur > 12 hari). Beda dgn perluCekQuery (notifikasi):
     * pesanan TETAP di sini walau baru dicek — hilang hanya saat dana Cair. Biar tak kehilangan jejak.
     */
    private function monitoringQuery()
    {
        return PenjualanHeader::where('channel', 'like', 'Marketplace%')
            ->where('status_pembayaran', '!=', 'Cair')
            ->where('status_pesanan', '!=', 'Batal')
            ->where(function ($q) {
                $q->whereNotNull('tgl_dicek')
                    ->orWhereDate('tgl_pesanan', '<=', now()->subDays(12));
            });
    }

    /** Query pesanan batal yang menunggu barang fisik kembali (belum dikonfirmasi diterima). */
    private function perluBarangBalikQuery()
    {
        return PenjualanHeader::where('status_pesanan', 'Batal')
            ->where('perlu_barang_balik', true)
            ->whereNull('tgl_retur_diterima');
    }

    /** Halaman khusus monitoring pesanan batal yang menunggu barang kembali. */
    public function perluBarangBalik(Request $request)
    {
        $items = $this->perluBarangBalikQuery()->orderBy('tgl_pesanan')->get();
        $total = $items->count();
        return view('penjualan.perlu_barang_balik', compact('items', 'total'));
    }

    /** Halaman khusus monitoring pesanan yang perlu dicek (settlement/COD belum cair). */
    public function perluCek(Request $request)
    {
        $allCount = $this->monitoringQuery()->count();      // total dalam pantauan (tetap tampil)
        $dueCount = $this->perluCekQuery()->count();         // yang jatuh tempo dicek (dasar notifikasi)

        $q = $this->monitoringQuery();
        if ($request->filled('channel')) {
            $q->where('channel', $request->channel);
        }
        // Belum pernah dicek didahulukan, lalu tertua.
        $items = $q->orderByRaw('tgl_dicek IS NOT NULL')->orderBy('tgl_pesanan')->get();

        $perChannel = $this->monitoringQuery()->get()->groupBy('channel')->map->count();
        $belumPernahDicek = $this->monitoringQuery()->whereNull('tgl_dicek')->count();
        $channels = PenjualanHeader::where('channel', 'like', 'Marketplace%')->distinct()->orderBy('channel')->pluck('channel');

        return view('penjualan.perlu_cek', compact('items', 'allCount', 'dueCount', 'perChannel', 'belumPernahDicek', 'channels'));
    }

    /**
     * Channel sumber HARGA jual. 'Offline (Tanpa Box + Tester)' tidak punya baris harga
     * sendiri di Master Harga → memakai basis harga Reseller A (sesuai kesepakatan channel ini).
     */
    private function hargaChannel(string $channel): string
    {
        return $channel === 'Offline (Tanpa Box + Tester)' ? 'Reseller A' : $channel;
    }

    /** AJAX: lookup harga jual + estimasi HPP/margin untuk input pesanan manual. */
    public function hargaLookup(Request $request, \App\Services\HppService $hpp)
    {
        $sku = $request->input('sku_id');
        $channel = $request->input('channel');
        $qty = max(1, (int) $request->input('qty', 1));
        $ekstra = (int) $request->input('ekstra_tester', 0);
        $diskon = (float) $request->input('diskon_manual', 0);
        $metode = $request->input('metode_pengiriman');

        $hargaRow = ($sku && $channel) ? MasterHarga::where('sku_id', $sku)->where('channel', $this->hargaChannel($channel))->first() : null;
        $hargaJual = $hargaRow ? (float) $hargaRow->harga_jual : 0;
        $subtotal = $hargaJual * $qty;
        $net = max(0, $subtotal - $diskon);

        $hppTotal = 0; $hppPerUnit = 0;
        if ($sku && $channel) {
            $isShipped = $metode ? ($metode === 'Dikirim') : null;
            $bd = $hpp->breakdown($sku, $channel, $qty, $ekstra, $isShipped);
            $hppTotal = $bd['hpp_total'];
            $hppPerUnit = $bd['hpp_per_unit'];
        }
        $margin = $net - $hppTotal;

        return response()->json([
            'ada_harga'    => (bool) $hargaRow,
            'harga_jual'   => $hargaJual,
            'qty'          => $qty,
            'subtotal'     => $subtotal,
            'diskon'       => $diskon,
            'net'          => $net,
            'hpp_total'    => $hppTotal,
            'hpp_per_unit' => $hppPerUnit,
            'margin'       => $margin,
        ]);
    }

    public function create()
    {
        $produks = MasterProduk::all();
        // Channel untuk INPUT MANUAL. Marketplace (TikTok/Shopee) sengaja disembunyikan —
        // pesanan marketplace hanya masuk lewat Upload, bukan input manual.
        $channelOptions = [
            'Offline',
            'Offline (Tanpa Box + Tester)',
            'WA',
            'Reseller A',
            'Reseller B',
            'Refill',
            'Website',
        ];
        $admins = MasterKategori::where('tipe_kategori', 'Admin')->orderBy('nilai')->pluck('nilai');
        $akuns = \App\Models\MasterAkunKas::whereNotIn('tipe', ['Saldo MP', 'Piutang'])->orderBy('akun_id')->pluck('nama_akun');

        // Peta harga jual {sku_id: {channel: harga}} untuk hitung subtotal tiap baris di form (live).
        // 'Offline (Tanpa Box + Tester)' tak punya baris harga → turunkan dari Reseller A.
        $hargaMap = MasterHarga::where('harga_jual', '>', 0)->get(['sku_id', 'channel', 'harga_jual'])
            ->groupBy('sku_id')->map(function ($g) {
                $map = $g->pluck('harga_jual', 'channel');
                if (isset($map['Reseller A']) && !isset($map['Offline (Tanpa Box + Tester)'])) {
                    $map['Offline (Tanpa Box + Tester)'] = $map['Reseller A'];
                }
                return $map;
            });

        return view('penjualan.create', compact('produks', 'channelOptions', 'admins', 'akuns', 'hargaMap'));
    }

    public function show($internal_id, \App\Services\HppService $hpp)
    {
        $header = PenjualanHeader::where('internal_id', $internal_id)->firstOrFail();
        $details = PenjualanDetail::select('penjualan_details.*', 'master_produks.nama_produk', 'master_produks.ukuran_ml')
            ->join('master_produks', 'penjualan_details.sku_id', '=', 'master_produks.sku_id')
            ->where('internal_id', $internal_id)
            ->get();

        // Metode pengiriman pesanan ini (override Lapis 2). Null = ikut default channel.
        $isShipped = is_null($header->metode_pengiriman) ? null : ($header->metode_pengiriman === 'Dikirim');

        // Rincian HPP per produk (lapis mana saja yang dipakai channel ini)
        $breakdowns = [];
        foreach ($details as $d) {
            $breakdowns[$d->detail_id] = $hpp->breakdown($d->sku_id, $header->channel, (int) $d->qty, (float) ($header->ekstra_tester ?? 0), $isShipped);
        }
        $cls = $hpp->klasifikasiChannel($header->channel);
        // Cerminkan metode aktual pesanan untuk badge tampilan
        $cls['is_shipped'] = is_null($isShipped) ? $cls['is_shipped'] : $isShipped;

        // Nama aroma asal (untuk tampilan tukar aroma)
        $asliMap = MasterProduk::whereIn('sku_id', $details->pluck('sku_id_asli')->filter())
            ->pluck('nama_produk', 'sku_id');

        // Laba KOTOR per unit = Omset − HPP (SEBELUM potongan platform). Beda dgn margin_satuan
        // (yang net, sesudah potongan). Omset dialokasi ke tiap produk proporsional dari subtotal.
        $butuhSettlement = $cls['butuh_settlement'];
        $omsetOrder = $butuhSettlement
            ? ($header->gross_settlement ? (float) $header->gross_settlement : (float) $header->gmv_kotor)
            : (float) $header->gmv_kotor;
        $sumSub = $details->sum(fn($d) => (float) $d->harga_satuan * (int) $d->qty);
        $labaKotor = [];
        foreach ($details as $d) {
            if (is_null($d->hpp_satuan)) { $labaKotor[$d->detail_id] = null; continue; }
            $qtyD = max(1, (int) $d->qty);
            $subD = (float) $d->harga_satuan * (int) $d->qty;
            $allocOmset = $sumSub > 0 ? $omsetOrder * ($subD / $sumSub) : ($omsetOrder / max(1, $details->count()));
            $labaKotor[$d->detail_id] = round(($allocOmset / $qtyD) - (float) $d->hpp_satuan, 2);
        }

        // Untuk aksi pra-racik (pecah bundle / mix custom) — hanya relevan saat pesanan masih 'Menunggu'.
        $allProduks = MasterProduk::orderBy('sku_id')->get(['sku_id', 'nama_produk', 'ukuran_ml']);
        $allBibits = \App\Models\MasterBibit::orderBy('nama_bibit')->get(['bibit_id', 'nama_bibit']);
        $akuns = \App\Models\MasterAkunKas::whereNotIn('tipe', ['Saldo MP', 'Piutang'])->orderBy('akun_id')->pluck('nama_akun');

        return view('penjualan.show', compact('header', 'details', 'breakdowns', 'cls', 'asliMap', 'labaKotor', 'allProduks', 'allBibits', 'akuns'));
    }

    public function store(Request $request, \App\Services\HppService $hpp, \App\Services\RacikService $racikService)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.sku_id' => 'required|string',
            'items.*.qty' => 'required|integer|min:1',
            'channel' => 'required|string',
            'metode_pengiriman' => 'nullable|in:Dikirim,Ambil Langsung',
            'nama_pembeli' => 'nullable|string',
            'no_hp_pembeli' => 'nullable|string|max:30',
            'external_order_id' => 'nullable|string',
            'ekstra_tester' => 'nullable|integer|min:0',
            'diskon_manual' => 'nullable|numeric|min:0',
            'diterima_oleh' => 'nullable|string',
            'status_pembayaran' => 'nullable|string',
            'akun_pembayaran' => 'nullable|string',
        ]);

        // GUARD: pesanan non-marketplace yang LUNAS wajib punya akun penerima uang,
        // biar uang masuk selalu tercatat (cegah kasus "uang hilang" — pesanan jadi tapi kas kosong).
        $isMpGuard = $hpp->klasifikasiChannel($request->channel)['is_marketplace'];
        $statusGuard = $request->status_pembayaran ?: 'Lunas';
        if (!$isMpGuard && $statusGuard === 'Lunas' && !$request->akun_pembayaran) {
            return back()->withInput()
                ->withErrors(['akun_pembayaran' => 'Akun penerima uang wajib diisi untuk pesanan Lunas.'])
                ->with('error', '⚠️ Gagal simpan: Akun Pembayaran WAJIB diisi untuk pesanan Lunas — supaya uang masuk tercatat. Pesanan belum dibuat.');
        }

        $autoRacik = false;
        try {
            DB::transaction(function () use ($request, $hpp, $racikService, &$autoRacik) {
                $channel = $request->channel;

                // Metode pengiriman: pakai pilihan user; jika kosong ikut default channel
                $metode = $request->metode_pengiriman
                    ?: ($hpp->defaultDikirim($channel) ? 'Dikirim' : 'Ambil Langsung');

                // Pesanan non-marketplace (offline/WA/reseller/refill) diracik LANGSUNG saat input,
                // sesuai workflow riil (pembeli langsung dilayani). Marketplace masuk antrean racik.
                $isMarketplace = $hpp->klasifikasiChannel($channel)['is_marketplace'];

                // Hitung tiap baris item + total (GMV = jumlah subtotal semua baris)
                $lines = [];
                $gmv = 0;
                foreach ($request->items as $item) {
                    $skuId = $item['sku_id'];
                    $qty = (int) $item['qty'];
                    if ($qty < 1) continue;
                    $harga = MasterHarga::where('sku_id', $skuId)->where('channel', $this->hargaChannel($channel))->first();
                    $hargaJual = $harga ? (float) $harga->harga_jual : 0;
                    $subtotal = $hargaJual * $qty;
                    $gmv += $subtotal;
                    $lines[] = ['sku_id' => $skuId, 'qty' => $qty, 'harga' => $hargaJual, 'subtotal' => $subtotal];
                }

                // Tentukan status pembayaran
                $statusPembayaran = $request->status_pembayaran ?? 'Lunas';
                if ($channel == 'Offline (Tanpa Box + Tester)' && !$request->status_pembayaran) {
                    $statusPembayaran = 'Lunas';
                }

                $ekstraOrig = (int) ($request->ekstra_tester ?? 0);

                // 1. Simpan T1a (PenjualanHeader) — GMV = total semua baris
                $header = PenjualanHeader::create([
                    'channel' => $channel,
                    'metode_pengiriman' => $metode,
                    'tgl_pesanan' => now()->toDateString(),
                    'status_pesanan' => 'Menunggu',
                    'status_pembayaran' => $statusPembayaran,
                    'akun_masuk' => $request->akun_pembayaran,
                    'gmv_kotor' => $gmv,
                    'diskon_manual' => $request->diskon_manual ?? 0,
                    'nama_pembeli' => $request->nama_pembeli,
                    'no_hp_pembeli' => $request->no_hp_pembeli,
                    'external_order_id' => $request->external_order_id,
                    'ekstra_tester' => $ekstraOrig,
                ]);

                // 2. Simpan T1b (PenjualanDetail) untuk tiap baris
                $details = [];
                foreach ($lines as $ln) {
                    $details[] = PenjualanDetail::create([
                        'internal_id' => $header->internal_id,
                        'sku_id' => $ln['sku_id'],
                        'qty' => $ln['qty'],
                        'harga_satuan' => $ln['harga'],
                        'subtotal' => $ln['subtotal'],
                        'hpp_satuan' => null,
                        'margin_satuan' => null,
                    ]);
                }

                // Produk MIX (SKU berawalan 'MIX') butuh SESI RACIK untuk komposisi/pecah aroma →
                // jangan auto-racik walau non-marketplace; biarkan mengantre (status Menunggu).
                $adaMix = false;
                foreach ($lines as $ln) {
                    if (str_starts_with((string) $ln['sku_id'], 'MIX')) { $adaMix = true; break; }
                }

                // Auto-racik hanya untuk NON-marketplace & NON-mix (offline/WA/reseller/refill biasa):
                // racik langsung tiap baris (potong stok + hitung HPP).
                if (!$isMarketplace && !$adaMix) {
                    // Set peracik lebih dulu agar tercatat benar di log racik.
                    $header->diracik_oleh = $request->diterima_oleh ?: 'Input Manual';
                    // Ekstra tester bersifat per-PESANAN → hanya dibebankan ke baris pertama agar tidak dobel.
                    foreach ($details as $i => $detail) {
                        $header->ekstra_tester = ($i === 0) ? $ekstraOrig : 0;
                        $racikService->racik($detail, $header);
                    }
                    $header->ekstra_tester = $ekstraOrig; // simpan nilai asli di header
                    $header->status_pesanan = 'Selesai Racik';
                    $header->tgl_racik = now()->toDateString();
                    $header->save();
                    $autoRacik = true;
                }

                // Marketplace: uang dicatat saat SETTLEMENT (cair) — berhenti di sini.
                if ($isMarketplace) return;

                // Non-marketplace (TERMASUK mix yang mengantre): uang MASUK bila sudah Lunas.
                // (offline/WA/reseller dibayar langsung; mix tetap sudah dibayar walau racik menyusul.)
                $net = (float) $gmv - (float) ($request->diskon_manual ?? 0);
                if ($statusPembayaran === 'Lunas' && $request->akun_pembayaran && $net > 0) {
                    \App\Models\MutasiKas::catat($request->akun_pembayaran, 'masuk', $net, 'penjualan', $header->internal_id, 'Penjualan ' . $channel . ($request->nama_pembeli ? ' · ' . $request->nama_pembeli : ''));
                }

                // CRM: daftarkan/lengkapi pelanggan dari pesanan non-marketplace (nama + no HP)
                if ($request->filled('nama_pembeli')) {
                    $pelanggan = \App\Models\Pelanggan::firstOrCreate(
                        ['nama' => trim($request->nama_pembeli)],
                        ['tipe' => $channel, 'no_hp' => $request->no_hp_pembeli, 'status' => 'Aktif']
                    );
                    if (! $pelanggan->wasRecentlyCreated && empty($pelanggan->no_hp) && $request->filled('no_hp_pembeli')) {
                        $pelanggan->update(['no_hp' => $request->no_hp_pembeli]);
                    }
                }
            });

            $msg = $autoRacik
                ? 'Pesanan tersimpan & langsung diracik (stok terpotong, HPP terhitung).'
                : 'Pesanan masuk ke antrean Racik!';
            return redirect()->route('penjualan.create')->with('success', $msg);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function updateStatus(Request $request, $internal_id, \App\Services\HppService $hpp)
    {
        $header = PenjualanHeader::where('internal_id', $internal_id)->firstOrFail();
        
        $action = $request->input('action');
        $oldStatus = $header->status_pesanan;

        // Tandai pesanan sudah dicek (tracking settlement belum cair). Notif muncul lagi 3 hari kemudian.
        // Pesanan TETAP di daftar monitoring; simpan juga keterangan hasil cek (opsional).
        if ($action === 'cek_pesanan') {
            $header->tgl_dicek = now()->toDateString();
            $header->jumlah_dicek = (int) $header->jumlah_dicek + 1;
            if ($request->has('catatan_cek')) {
                $header->catatan_cek = trim((string) $request->input('catatan_cek')) ?: null;
            }
            $header->save();
            return redirect()->back()->with('success', "Pesanan ditandai sudah dicek (ke-{$header->jumlah_dicek}). Tetap di monitoring; notif muncul lagi jika 3 hari belum cair.");
        }

        // Terima pembayaran Piutang (reseller A bayar) -> status Cair + uang masuk
        if ($action === 'terima_pembayaran') {
            if ($header->status_pembayaran === 'Cair') {
                return redirect()->back()->with('error', 'Pesanan ini sudah Cair.');
            }
            $akun = $request->input('akun');
            if (!$akun) {
                return redirect()->back()->with('error', 'Pilih akun penerima pembayaran.');
            }
            $net = (float) $header->gmv_kotor - (float) ($header->diskon_manual ?? 0);
            $header->status_pembayaran = 'Cair';
            $header->akun_masuk = $akun;
            $header->tgl_cair_saldo = now()->toDateString();
            $header->save();

            $sudahMasuk = \App\Models\MutasiKas::where('ref_id', $internal_id)->where('kategori', 'penjualan')->exists();
            if (!$sudahMasuk && $net > 0) {
                \App\Models\MutasiKas::catat($akun, 'masuk', $net, 'penjualan', $internal_id, 'Pelunasan piutang ' . $header->channel . ($header->nama_pembeli ? ' · ' . $header->nama_pembeli : ''));
            }
            return redirect()->back()->with('success', 'Pembayaran diterima. Pesanan jadi Cair, uang masuk ke ' . $akun . '.');
        }

        // Perbaiki AKUN MASUK yang terlewat (pesanan Lunas non-marketplace tapi akun/uang masuk kosong).
        if ($action === 'set_akun_masuk') {
            $akun = $request->input('akun');
            if (!$akun) {
                return redirect()->back()->with('error', 'Pilih akun penerima uang.');
            }
            $header->akun_masuk = $akun;
            $header->save();

            $sudahMasuk = \App\Models\MutasiKas::where('ref_id', $internal_id)->where('kategori', 'penjualan')->exists();
            if (!$sudahMasuk) {
                $net = (float) $header->gmv_kotor - (float) ($header->diskon_manual ?? 0);
                if ($net > 0) {
                    \App\Models\MutasiKas::catat($akun, 'masuk', $net, 'penjualan', $internal_id, 'Penjualan ' . $header->channel . ($header->nama_pembeli ? ' · ' . $header->nama_pembeli : '') . ' (akun disusulkan)');
                    return redirect()->back()->with('success', 'Akun masuk diisi & uang Rp ' . number_format($net, 0, ',', '.') . ' dicatat MASUK ke ' . $akun . '.');
                }
            }
            return redirect()->back()->with('success', 'Akun masuk diperbarui ke ' . $akun . '. (Uang sudah tercatat sebelumnya — tidak dibuat lagi, aman dari dobel.)');
        }

        if ($action === 'batal') {
            if (!$request->filled('alasan_batal')) {
                return redirect()->back()->with('error', 'Alasan pembatalan wajib dipilih.');
            }

            $sudahDiracik = in_array($oldStatus, ['Selesai Racik', 'Dikirim', 'Selesai']);
            // Kondisi barang: 'gudang' = botol ada di tempat (langsung T11); 'menunggu' = barang masih balik
            $kondisi = $request->input('kondisi_barang', 'gudang');

            $header->status_pesanan = 'Batal';
            $header->alasan_batal = $request->input('alasan_batal');
            $header->perlu_barang_balik = ($sudahDiracik && $kondisi === 'menunggu');
            $header->save();

            // Jika uang penjualan sudah masuk akun -> kembalikan (refund), sekali
            $masuk = \App\Models\MutasiKas::where('ref_id', $internal_id)->where('kategori', 'penjualan')->first();
            $sudahRefund = \App\Models\MutasiKas::where('ref_id', $internal_id)->where('kategori', 'refund_penjualan')->exists();
            if ($masuk && !$sudahRefund) {
                \App\Models\MutasiKas::catat($masuk->akun, 'keluar', (float) $masuk->jumlah, 'refund_penjualan', $internal_id, 'Refund pembatalan penjualan');
            }

            if (!$sudahDiracik) {
                // Belum diracik: tidak ada botol fisik
                return redirect()->back()->with('success', 'Pesanan dibatalkan. (Belum diracik, stok tidak terpengaruh.)');
            }

            if ($kondisi === 'menunggu') {
                // Botol masih di pembeli/ekspedisi -> BELUM masuk T11; tunggu konfirmasi "Barang Diterima"
                return redirect()->back()->with('success', 'Pesanan dibatalkan (menunggu barang balik). Klik "Barang Diterima" saat barang sampai agar masuk Stok Jadi (T11).');
            }

            // Botol ada di gudang -> langsung masuk T11 (bibit sudah dipotong saat racik; nilai = botol telanjang)
            $details = PenjualanDetail::where('internal_id', $internal_id)->get();
            foreach ($details as $detail) {
                $produk = MasterProduk::where('sku_id', $detail->sku_id)->first();
                if ($produk) {
                    $bare = $hpp->bareBottle($detail->sku_id);
                    $produk->tambahStokJadi((int) $detail->qty, $bare);
                    \App\Models\StokJadiLog::catat($detail->sku_id, 'masuk', (int) $detail->qty, 'batal', $bare, $internal_id, null, 'Batal: ' . $header->alasan_batal);
                }
            }
            return redirect()->back()->with('success', 'Pesanan dibatalkan. Botol masuk Stok Jadi (T11) karena sudah diracik & ada di gudang.');
        }

        // Konfirmasi barang batal (yang menunggu balik) SUDAH diterima -> baru masuk T11
        if ($action === 'terima_barang') {
            if ($header->status_pesanan !== 'Batal' || !$header->perlu_barang_balik) {
                return redirect()->back()->with('error', 'Hanya pesanan batal yang menunggu barang balik yang bisa dikonfirmasi.');
            }
            if (!is_null($header->tgl_retur_diterima)) {
                return redirect()->back()->with('error', 'Barang pesanan ini sudah dikonfirmasi diterima sebelumnya.');
            }

            // Kondisi barang saat kembali: 'layak' = bisa dijual (masuk T11); 'rusak' = tidak masuk stok (kerugian)
            $kondisi = $request->input('kondisi_terima', 'layak');
            $rusak = ($kondisi === 'rusak');

            $details = PenjualanDetail::where('internal_id', $internal_id)->get();
            foreach ($details as $detail) {
                $produk = MasterProduk::where('sku_id', $detail->sku_id)->first();
                if (!$produk) continue;
                $bare = $hpp->bareBottle($detail->sku_id);
                if ($rusak) {
                    // Barang rusak -> TIDAK masuk T11, dicatat sebagai kerugian (modal hangus)
                    \App\Models\StokJadiLog::catat($detail->sku_id, 'rusak', (int) $detail->qty, 'retur', $bare, $internal_id, null, 'Barang rusak saat retur — kerugian: ' . $header->alasan_batal);
                } else {
                    $produk->tambahStokJadi((int) $detail->qty, $bare);
                    \App\Models\StokJadiLog::catat($detail->sku_id, 'masuk', (int) $detail->qty, 'retur', $bare, $internal_id, null, 'Retur diterima (layak jual): ' . $header->alasan_batal);
                }
            }
            $header->tgl_retur_diterima = now()->toDateString();
            $header->save();

            return redirect()->back()->with('success', $rusak
                ? 'Barang diterima dalam kondisi RUSAK — tidak masuk stok, dicatat sebagai kerugian.'
                : 'Barang diterima (layak jual) & masuk Stok Jadi (T11). Bisa dijual lagi.');
        }

        // Tukar Aroma (bibit kosong): hanya pesanan Menunggu (belum diracik).
        // Ganti SKU/aroma sebuah baris; harga TETAP; aroma asal tercatat; HPP dihitung saat racik.
        if ($action === 'tukar_aroma') {
            if ($oldStatus !== 'Menunggu') {
                return redirect()->back()->with('error', 'Tukar aroma hanya untuk pesanan yang masih Menunggu (belum diracik).');
            }
            $newSku = $request->input('sku_id_baru');
            $detailId = $request->input('detail_id');
            if (!$newSku) {
                return redirect()->back()->with('error', 'Pilih aroma pengganti.');
            }
            if (!MasterProduk::where('sku_id', $newSku)->exists()) {
                return redirect()->back()->with('error', 'Aroma pengganti tidak valid.');
            }

            $detail = PenjualanDetail::where('internal_id', $internal_id)
                ->when($detailId, fn($q) => $q->where('detail_id', $detailId))
                ->first();
            if (!$detail) {
                return redirect()->back()->with('error', 'Detail pesanan tidak ditemukan.');
            }

            if ($newSku !== $detail->sku_id) {
                if (is_null($detail->sku_id_asli)) {
                    $detail->sku_id_asli = $detail->sku_id; // catat aroma asal
                }
                $detail->sku_id = $newSku;
                $detail->flag_swap = 1;
                // harga_satuan TETAP; HPP & margin dihitung saat diracik dgn aroma baru
                $detail->save();
            }

            $namaBaru = MasterProduk::where('sku_id', $newSku)->value('nama_produk');
            return redirect()->back()->with('success', "Aroma ditukar ke '{$namaBaru}'. Pesanan tetap di antrean racik, harga tidak berubah.");
        }

        return redirect()->back()->with('error', 'Aksi tidak dikenali.');
    }

    public function updateExtraTester(Request $request, $internal_id)
    {
        $header = PenjualanHeader::where('internal_id', $internal_id)->firstOrFail();
        $extra = (int) $request->input('ekstra_tester', 0);
        
        if (in_array($header->status_pesanan, ['Selesai Racik', 'Dikirim', 'Selesai'])) {
            return redirect()->back()->with('error', 'Hanya pesanan yang masih Antre yang bisa diubah bonus testernya karena stok fisik sudah dipotong.');
        }

        $header->ekstra_tester = $extra;
        $header->save();

        return redirect()->back()->with('success', 'Bonus tester berhasil diperbarui ke ' . $extra . ' pcs.');
    }

    /**
     * HAPUS BERSIH pesanan — seperti tidak pernah diinput. Balik semua efek:
     * stok (bibit/komponen/T11) bila sudah diracik, mutasi kas, log stok jadi,
     * log produksi, audit; lalu hapus detail & header. Butuh konfirmasi di UI.
     */
    public function destroy($internal_id, \App\Services\RacikService $racikService)
    {
        $header = PenjualanHeader::where('internal_id', $internal_id)->first();
        if (!$header) {
            return redirect()->route('penjualan.index')->with('error', 'Pesanan tidak ditemukan.');
        }

        try {
            DB::transaction(function () use ($header, $internal_id, $racikService) {
                // 1. Kembalikan stok yang terpotong saat racik (aman bila belum diracik)
                $racikService->kembalikanStokRacik($header);

                // 2. Hapus jejak kas & log terkait pesanan ini
                \App\Models\MutasiKas::where('ref_id', $internal_id)->delete();
                \App\Models\StokJadiLog::where('ref_id', $internal_id)->delete();
                \App\Models\ProduksiLog::where('detail_text', 'like', '%' . $internal_id . '%')->delete();

                // 3. Hapus audit trail header & detail-nya
                $detailIds = PenjualanDetail::where('internal_id', $internal_id)->pluck('detail_id')->all();
                \App\Models\AuditLog::where('auditable_type', \App\Models\PenjualanHeader::class)
                    ->where('auditable_id', $internal_id)->delete();
                if (!empty($detailIds)) {
                    \App\Models\AuditLog::where('auditable_type', \App\Models\PenjualanDetail::class)
                        ->whereIn('auditable_id', $detailIds)->delete();
                }

                // 4. Hapus baris & header
                PenjualanDetail::where('internal_id', $internal_id)->delete();
                $header->delete();
            });

            return redirect()->route('penjualan.index')->with('success', 'Pesanan dihapus bersih. Stok & kas dikembalikan seperti sebelum diinput.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal menghapus pesanan: ' . $e->getMessage());
        }
    }
}
