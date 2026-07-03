<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Klien untuk MENARIK pesanan dari API website e-commerce Hagos (hagosperfume.com).
 * Alur: login (dapat JWT) -> GET /admin/orders (Bearer).
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
            ->get($this->base() . '/admin/orders', $params);

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
            ->get($this->base() . '/admin/orders/' . $id);
        return $res->successful() ? ($res->json() ?? []) : [];
    }

    /**
     * TODO (setelah tahu bentuk response asli): ubah 1 pesanan website -> array siap simpan ke ERP:
     * [external_order_id, channel='Website', buyer{nama,no_hp}, items[{sku_id,qty}], payment{status,akun}, ...]
     * Item SKU dipetakan via tabel website_sku_maps (website_ref -> sku_id).
     */
    public function mapToErpOrder(array $orderWebsite): array
    {
        // Placeholder — diisi setelah struktur response dikonfirmasi.
        return [];
    }
}
