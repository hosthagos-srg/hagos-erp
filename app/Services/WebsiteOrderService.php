<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Klien untuk MENARIK pesanan dari API website e-commerce Hagos (hagosperfume.com).
 * Alur: login (dapat JWT) -> GET /orders (Bearer).
 *
 * CATATAN: parsing response -> pesanan ERP (mapToErpOrder) belum final; menunggu
 * contoh bentuk response asli (jalankan `php artisan website:tarik --dry`).
 */
class WebsiteOrderService
{
    private ?string $token = null;

    private function base(): string
    {
        return rtrim((string) config('services.hagos_web.base_url'), '/');
    }

    /** Login ke website -> simpan JWT. Return true jika berhasil. */
    public function login(): bool
    {
        $login = config('services.hagos_web.login');
        $password = config('services.hagos_web.password');
        if (!$login || !$password) {
            throw new \RuntimeException('HAGOS_WEB_LOGIN / HAGOS_WEB_PASSWORD belum diset di .env.');
        }

        $res = Http::acceptJson()->timeout(30)
            ->post($this->base() . '/auth/login', ['login' => $login, 'password' => $password]);

        if (!$res->successful()) {
            throw new \RuntimeException('Login website gagal (HTTP ' . $res->status() . '): ' . $res->body());
        }

        // Token bisa di beberapa lokasi umum — coba yang paling mungkin.
        $this->token = $res->json('token')
            ?? $res->json('access_token')
            ?? $res->json('data.token')
            ?? $res->json('data.access_token');

        if (!$this->token) {
            throw new \RuntimeException('Login berhasil tapi token tidak ditemukan di response. Body: ' . $res->body());
        }
        return true;
    }

    /** Ambil daftar pesanan dari admin (mentah). $params mis. ['status' => 'paid']. */
    public function getOrders(array $params = []): array
    {
        if (!$this->token) $this->login();

        $res = Http::acceptJson()->withToken($this->token)->timeout(30)
            ->get($this->base() . '/orders', $params);

        if (!$res->successful()) {
            throw new \RuntimeException('Ambil pesanan gagal (HTTP ' . $res->status() . '): ' . $res->body());
        }
        return $res->json() ?? [];
    }

    /** Ambil detail 1 pesanan. */
    public function getOrderDetail(string $id): array
    {
        if (!$this->token) $this->login();
        $res = Http::acceptJson()->withToken($this->token)->timeout(30)
            ->get($this->base() . '/orders/' . $id);
        return $res->successful() ? ($res->json() ?? []) : [];
    }

    /**
     * Ambil array daftar order dari response mentah (buka pembungkus success/data dll).
     */
    public function extractOrderList(array $raw): array
    {
        foreach (['data', 'orders', 'items', 'result'] as $k) {
            if (isset($raw[$k]) && is_array($raw[$k])) return array_values(array_filter($raw[$k], 'is_array'));
        }
        // Sudah berupa list langsung?
        if (isset($raw[0]) && is_array($raw[0])) return $raw;
        return [];
    }

    /** Ambil nilai pertama yang ada dari beberapa jalur (dot-notation). */
    private function pick(array $arr, array $paths, $default = null)
    {
        foreach ($paths as $p) {
            $val = data_get($arr, $p);
            if (!is_null($val) && $val !== '') return $val;
        }
        return $default;
    }

    /** Ubah string kemasan ("30 ML", "30ml", "50 ML") -> ukuran integer (30/50). */
    private function parseUkuran(?string $kemasan): int
    {
        if (!$kemasan) return 0;
        return (int) preg_replace('/[^0-9]/', '', $kemasan);
    }

    /**
     * Ubah 1 pesanan website -> array ternormalisasi siap dibuat jadi pesanan ERP.
     * SKU langsung: product.sku = sku_aroma ERP; kemasan -> ukuran -> sku_id "{sku}-{ukuran}-REG".
     * Tahan dua bentuk: list /orders (items[].product.sku) & detail (item_produk[].sku).
     * Validasi tiap sku_id ke Master Produk; item tak dikenal ditandai di `unmatched`.
     */
    public function mapToErpOrder(array $o): array
    {
        $externalId = $this->pick($o, ['midtrans_order_id', 'id_pesanan', 'order_number', 'id']);
        $websiteId  = $this->pick($o, ['id', 'id_pesanan']);
        $payStatus  = strtolower((string) $this->pick($o, ['payment_status', 'pembayaran.status_pembayaran', 'status_pembayaran'], ''));
        $paid       = in_array($payStatus, ['paid', 'lunas', 'settlement', 'capture', 'success'], true);
        $createdRaw = $this->pick($o, ['created_at', 'tanggal_pesanan']);
        try { $tgl = $createdRaw ? \Illuminate\Support\Carbon::parse($createdRaw)->timezone(config('app.timezone'))->toDateString() : now()->toDateString(); }
        catch (\Throwable $e) { $tgl = now()->toDateString(); }

        // Daftar item bisa di 'items' (list) atau 'item_produk' (detail idealan)
        $rawItems = $this->pick($o, ['items', 'item_produk'], []);
        $rawItems = is_array($rawItems) ? $rawItems : [];

        $items = [];
        $unmatched = [];
        $subtotalProduk = 0;
        foreach ($rawItems as $it) {
            $sku     = $this->pick($it, ['product.sku', 'sku']);
            $kemasan = $this->pick($it, ['kemasan', 'product.kemasan', 'variasi']);
            $ukuran  = $this->parseUkuran($kemasan);
            $qty     = (int) $this->pick($it, ['quantity', 'kuantitas', 'qty'], 1);
            $harga   = (float) $this->pick($it, ['price', 'harga_satuan', 'harga'], 0);
            $nama    = $this->pick($it, ['product_name', 'nama_produk', 'product.name'], $sku);
            $skuId   = ($sku && $ukuran > 0) ? "{$sku}-{$ukuran}-REG" : null;

            $matched = $skuId ? \App\Models\MasterProduk::where('sku_id', $skuId)->exists() : false;
            $row = [
                'sku_id'      => $skuId,
                'sku_aroma'   => $sku,
                'ukuran_ml'   => $ukuran,
                'qty'         => max(1, $qty),
                'harga_satuan'=> $harga,
                'nama'        => $nama,
                'kemasan'     => $kemasan,
                'tester_type' => $this->pick($it, ['tester_type']),
                'notes'       => $this->pick($it, ['notes', 'catatan']),
                'matched'     => $matched,
            ];
            if (!$matched) $unmatched[] = ($skuId ?: "{$sku}/{$kemasan}") . " ({$nama})";
            $items[] = $row;
            $subtotalProduk += $harga * max(1, $qty);
        }

        return [
            'external_order_id' => $externalId,
            'website_id'        => $websiteId,
            'channel'           => 'Website',
            'paid'              => $paid,
            'payment_status'    => $payStatus,
            'status_web'        => $this->pick($o, ['status', 'status_pesanan']),
            'tgl_pesanan'       => $tgl,
            'buyer' => [
                // Endpoint detail website memakai field flat shipping_* (nama penerima = pembeli).
                'nama'  => $this->pick($o, ['shipping_recipient_name', 'pelanggan.nama_lengkap', 'customer.name', 'buyer.nama', 'nama_pembeli']),
                'no_hp' => $this->normalizeHp($this->pick($o, ['shipping_phone_number', 'pelanggan.nomor_telepon', 'customer.phone', 'buyer.no_hp', 'no_hp'])),
                'email' => $this->pick($o, ['user.email', 'pelanggan.email', 'customer.email', 'email']),
            ],
            'alamat'          => $this->buildAlamat($o),
            'kurir'           => trim(($this->pick($o, ['courier'], '') . ' ' . $this->pick($o, ['courier_service'], ''))) ?: null,
            'items'           => $items,
            'unmatched'       => $unmatched,
            'subtotal_produk' => round($subtotalProduk, 2),
            'ongkir'          => (float) $this->pick($o, ['shipping_cost', 'pengiriman.biaya_ongkir', 'pengiriman.total_ongkir', 'pembayaran.rincian_biaya.total_ongkir'], 0),
            'diskon'          => (float) $this->pick($o, ['discount_amount', 'pembayaran.rincian_biaya.diskon_voucher', 'diskon'], 0)
                                  + (float) $this->pick($o, ['points_discount'], 0),
            'total_bayar'     => (float) $this->pick($o, ['final_total', 'pembayaran.rincian_biaya.total_keseluruhan'], 0),
            'resi'            => $this->pick($o, ['tracking_number', 'pengiriman.nomor_resi', 'no_resi']),
            'bank'            => $this->pick($o, ['bank', 'pembayaran.bank_tujuan']),
        ];
    }

    /** Normalisasi no HP: website menyimpan tanpa 0 depan (mis. "8123.." → "08123..") */
    private function normalizeHp(?string $hp): ?string
    {
        if (!$hp) return null;
        $hp = preg_replace('/[^0-9]/', '', $hp);
        if ($hp === '') return null;
        if (str_starts_with($hp, '62')) $hp = '0' . substr($hp, 2);
        elseif ($hp[0] !== '0') $hp = '0' . $hp;
        return $hp;
    }

    /** Rangkai alamat pengiriman dari field flat shipping_* (untuk catatan/ref). */
    private function buildAlamat(array $o): ?string
    {
        $bagian = array_filter([
            $this->pick($o, ['shipping_street_address']),
            $this->pick($o, ['shipping_city']),
            $this->pick($o, ['shipping_province']),
            $this->pick($o, ['shipping_postal_code']),
        ]);
        return count($bagian) ? implode(', ', $bagian) : null;
    }

    /**
     * Gabungkan data DETAIL (buyer/alamat/ongkir/resi) ke hasil map LIST.
     * Item & payment tetap dari LIST (sudah dikonfirmasi); detail hanya melengkapi
     * field yang kosong. Aman bila bentuk detail belum pasti.
     */
    public function mergeDetail(array $base, array $detailRaw): array
    {
        if (empty($detailRaw)) return $base;
        // Detail bisa terbungkus {success,data:{...}}
        $d = $detailRaw['data'] ?? $detailRaw;
        if (isset($d[0]) && is_array($d[0])) $d = $d[0];
        $md = $this->mapToErpOrder(is_array($d) ? $d : []);
        foreach (['nama', 'no_hp', 'email'] as $f) {
            if (empty($base['buyer'][$f]) && !empty($md['buyer'][$f])) $base['buyer'][$f] = $md['buyer'][$f];
        }
        if (empty($base['resi']) && !empty($md['resi']))     $base['resi'] = $md['resi'];
        if (($base['ongkir'] ?? 0) == 0 && ($md['ongkir'] ?? 0) > 0) $base['ongkir'] = $md['ongkir'];
        // Diskon voucher/poin sering hanya ada di DETAIL (LIST ringkas) — wajib ikut, kalau tidak
        // omzet & kas kelebihan sebesar diskon.
        if (($base['diskon'] ?? 0) == 0 && ($md['diskon'] ?? 0) > 0) $base['diskon'] = $md['diskon'];
        foreach (['alamat', 'kurir'] as $f) {
            if (empty($base[$f]) && !empty($md[$f])) $base[$f] = $md[$f];
        }
        return $base;
    }

    /** Pastikan akun kas Midtrans ada. Return nama akun. */
    public function ensureAkunMidtrans(): string
    {
        $nama = (string) (config('services.hagos_web.akun_kas') ?: 'Midtrans');
        $akun = \App\Models\MasterAkunKas::where('nama_akun', $nama)->first();
        if (!$akun) {
            $akun = new \App\Models\MasterAkunKas();
            $akun->akun_id   = 'KAS-MIDTRANS';
            $akun->nama_akun = $nama;
            $akun->tipe      = 'Gateway';
            $akun->saldo_awal = 0;
            $akun->fungsi    = 'Saldo pembayaran website (Midtrans), sebelum ditarik ke bank';
            $akun->save();
        }
        return $nama;
    }

    /**
     * Simpan 1 pesanan website (hasil mapToErpOrder) ke ERP. Idempoten via external_order_id.
     * Return kode status: created | dup | skip_unpaid | skip_no_id | skip_unmatched | error.
     * Uang masuk = subtotal produk (ongkir diabaikan) ke akun Midtrans. Masuk antrean racik.
     */
    public function simpanKeErp(array $m): string
    {
        if (empty($m['external_order_id'])) return 'skip_no_id';
        if (empty($m['paid']))              return 'skip_unpaid';

        // Tolak bila ada item tak dikenal (jangan buat pesanan sebagian) — biar SKU dilengkapi dulu.
        if (!empty($m['unmatched']))        return 'skip_unmatched';
        $items = array_values(array_filter($m['items'], fn($i) => $i['matched'] && $i['sku_id']));
        if (count($items) === 0)            return 'skip_unmatched';

        if (\App\Models\PenjualanHeader::where('external_order_id', $m['external_order_id'])->exists()) return 'dup';

        $akun = $this->ensureAkunMidtrans();
        $subtotal = 0;
        foreach ($items as $i) $subtotal += $i['harga_satuan'] * $i['qty'];
        // Diskon voucher/poin dari website: gmv tetap KOTOR, diskon_manual diisi, uang masuk = net.
        // (konvensi ERP: omzet non-MP = gmv_kotor − diskon_manual). Guard: diskon tak boleh > subtotal.
        $diskon = min((float) ($m['diskon'] ?? 0), $subtotal);
        $net = $subtotal - $diskon;

        \Illuminate\Support\Facades\DB::transaction(function () use ($m, $items, $akun, $subtotal, $diskon, $net) {
            $header = \App\Models\PenjualanHeader::create([
                'channel'           => 'Website',
                'metode_pengiriman' => 'Dikirim',
                'tgl_pesanan'       => $m['tgl_pesanan'],
                'status_pesanan'    => 'Menunggu',           // masuk antrean gudang racik
                'status_pembayaran' => 'Lunas',              // hanya order paid yang ditarik
                'akun_masuk'        => $akun,
                'gmv_kotor'         => $subtotal,            // omzet KOTOR produk (ongkir diabaikan)
                'diskon_manual'     => $diskon,              // diskon voucher/poin website
                'nama_pembeli'      => $m['buyer']['nama'] ?: 'Pelanggan Website',
                'no_hp_pembeli'     => $m['buyer']['no_hp'],
                'external_order_id' => $m['external_order_id'],
                'no_resi'           => $m['resi'] ?: null,
                'ekstra_tester'     => 0,
            ]);

            foreach ($items as $ln) {
                \App\Models\PenjualanDetail::create([
                    'internal_id'  => $header->internal_id,
                    'sku_id'       => $ln['sku_id'],
                    'qty'          => $ln['qty'],
                    'harga_satuan' => $ln['harga_satuan'],
                    'subtotal'     => $ln['harga_satuan'] * $ln['qty'],
                    'hpp_satuan'   => null,
                    'margin_satuan'=> null,
                ]);
            }

            // Uang sudah diterima via Midtrans → catat masuk NET (subtotal − diskon), anti-dobel via ref_id
            if ($net > 0 && !\App\Models\MutasiKas::where('ref_id', $header->internal_id)->where('kategori', 'penjualan')->exists()) {
                \App\Models\MutasiKas::catat($akun, 'masuk', $net, 'penjualan', $header->internal_id, 'Penjualan Website · ' . ($m['buyer']['nama'] ?: $m['external_order_id']), null, $m['tgl_pesanan']);
            }

            // CRM: hanya bila nama pembeli asli (bukan fallback)
            if (!empty($m['buyer']['nama'])) {
                $p = \App\Models\Pelanggan::firstOrCreate(
                    ['nama' => trim($m['buyer']['nama'])],
                    ['tipe' => 'Website', 'no_hp' => $m['buyer']['no_hp'], 'status' => 'Aktif']
                );
                if (!$p->wasRecentlyCreated && empty($p->no_hp) && !empty($m['buyer']['no_hp'])) {
                    $p->update(['no_hp' => $m['buyer']['no_hp']]);
                }
            }
        });

        return 'created';
    }
}
