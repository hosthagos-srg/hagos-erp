# Integrasi Pesanan Otomatis — Website & Hagos App ↔ ERP HAGOS

Dokumen ini untuk **developer Website & Hagos App**. Tujuannya: menyepakati skema agar setiap pesanan dari Website / Hagos App **langsung masuk otomatis** ke sistem ERP HAGOS (tanpa input manual), plus daftar keputusan yang perlu kita sepakati bersama.

> Catatan: channel Marketplace (TikTok/Shopee) tidak termasuk di sini — itu tetap lewat upload file. Dokumen ini khusus channel **Website** dan **Hagos App** yang sistemnya kita kontrol sendiri.

---

## 0. ⭐ JAWABAN DEVELOPER & KONTRAK FINAL (dikonfirmasi Ekal 2026-07-17)

> Ini keputusan final hasil tanya-jawab dengan developer website. **Menggantikan usulan "Push" di bawah** — modelnya jadi **PULL**. Bagian 1–9 di bawah tetap sebagai referensi desain awal.

**Arsitektur: PULL — ERP menarik dari Website.** Bukan website yang push ke ERP, tapi **ERP memanggil API website `hagosperfume.com`** untuk menarik pesanan.

| # | Pertanyaan | Jawaban final |
|---|---|---|
| 1 | Cara koneksi | **PULL** — ERP menarik data dari API website hagosperfume.com |
| 2 | SKU | **ERP sediakan tabel pemetaan** (`website_sku_maps`: `website_sku` → `sku_aroma`; ukuran dari `kemasan`; bentuk REG) |
| 3 | Harga | **Website & ERP dijamin sama** |
| 4 | Pembayaran | Uang masuk **kas "Midtrans"** |
| 5 | Trigger | Hanya tarik pesanan yang **sudah dibayar** (`payment_status: "paid"`) |
| 6 | Resi | Diinput di **website**; saat resi masuk status jadi **`on_delivery`** (ERP: sudah dikirim + resi) |
| 7 | Pembatalan | Manual — pelanggan ajukan komplain → **admin cek manual** (tak ada auto-cancel) |
| 8 | Payload | Contoh JSON lengkap dari website (lihat di bawah) |
| 9 | Volume & auth | **< 50 pesanan/hari**; auth website = **Bearer token JWT** (ERP jadi klien) |
| 10 | Sinkron balik | **Tidak perlu** (ERP tak kirim stok/harga balik ke website) |

### Pemetaan payload website → ERP (kontrak)
| Field ERP | Ambil dari payload website |
|---|---|
| `channel` | `"Website"` |
| `external_order_id` (anti-dobel) | `id` (UUID order website) |
| `tgl_pesanan` | `created_at` |
| `status_pembayaran` | `Lunas` (karena `payment_status="paid"`) |
| `akun_masuk` | **Midtrans** |
| `nama_pembeli` | `shipping_recipient_name` / `user.name` |
| `gmv_kotor` | `total_price` (subtotal produk; **bukan** `final_total`) |
| `diskon_manual` | `discount_amount` |
| item → `sku_id` ERP | map `items[].product.sku` (mis. "HGS053") via `website_sku_maps` → `sku_aroma`, gabung `kemasan` ("30 ML"→30) → `HGS053-30-REG` |
| item `qty` | `items[].quantity` |
| resi & kurir | `tracking_number`, `courier`, `courier_service` (saat `status="on_delivery"`) |

**Catatan biaya:** `shipping_cost`, `handling_fee`, `insurance_fee` = titipan ke kurir/gateway → **BUKAN pendapatan ERP**, tak masuk gmv. Pesanan masuk berstatus **Menunggu** → antre Gudang Racik (HPP/stok saat diracik, alur normal).

### ❓ Masih perlu dari developer sebelum bisa konek
1. **URL endpoint** pull-nya (mis. `GET https://hagosperfume.com/api/orders?payment_status=paid&since=...`) + apakah bisa filter "sejak timestamp/paged".
2. **Cara ERP dapat JWT**: endpoint login (kirim kredensial → token) atau token statis jangka panjang untuk ERP? (kredensial/token diisi Ekal, bukan di-commit).
3. **`tester_type` (mis. "Random")** artinya apa untuk ERP — bonus tester acak yang ikut produk, atau metadata saja?

### Contoh payload (dari developer)
```json
(lihat Bagian 8 di bawah untuk contoh JSON penuh)
```

---

## 1. Tujuan

Saat ada pesanan baru (dan/atau dibayar) di Website atau Hagos App, data pesanan otomatis terkirim ke ERP dan langsung menjadi pesanan yang siap diproses (racik → kirim), lengkap dengan perhitungan HPP, stok, dan kas — memakai mesin yang sudah berjalan di ERP.

---

## 2. Rekomendasi Arsitektur: REST API "Push"

ERP menyediakan **satu endpoint aman**. Website/App **memanggil endpoint itu** setiap kali ada pesanan baru.

```
Website / Hagos App                         ERP HAGOS
      │                                          │
 order dibuat / dibayar                          │
      │   POST /api/orders  (HTTPS + token)      │
      ├─────────────────────────────────────────►
      │                          validasi + buat pesanan
      │                          (PenjualanHeader + Detail),
      │                          masuk antrean Racik
      ◄───────────── 201 { status, internal_id } ┤
```

Alternatif bila Website tidak bisa memanggil ERP:
- **Webhook** (secara teknis sama dengan di atas — Website menembakkan event ke ERP).
- **Pull terjadwal** (ERP menarik data dari API Website tiap X menit) — dipakai hanya jika push tidak memungkinkan; tidak real-time.

Rekomendasi: **Push REST API**.

---

## 3. Spesifikasi Endpoint (usulan)

### 3.1 Buat pesanan
```
POST  https://<domain-erp>/api/orders
Headers:
  Authorization: Bearer <API_TOKEN_RAHASIA>
  Content-Type: application/json
  Accept: application/json
```

**Contoh request body:**
```json
{
  "external_order_id": "WEB-2026-0001",
  "channel": "Website",
  "buyer": {
    "nama": "Budi Santoso",
    "no_hp": "08123456789"
  },
  "items": [
    { "sku_id": "HGS001-50-REG", "qty": 2 },
    { "sku_id": "HGS009-30-REG", "qty": 1 }
  ],
  "payment": {
    "status": "Lunas",
    "akun": "Payment Gateway",
    "metode": "QRIS"
  },
  "shipping": {
    "metode": "Dikirim",
    "no_resi": null
  },
  "diskon": 10000,
  "catatan": "Tolong dibungkus rapi"
}
```

**Contoh response sukses (201):**
```json
{
  "status": "ok",
  "internal_id": "a1b2c3d4-....",
  "message": "Pesanan diterima & masuk antrean racik."
}
```

**Contoh response gagal (422):**
```json
{
  "status": "error",
  "message": "SKU tidak dikenal: HGS999-99",
  "errors": { "items.0.sku_id": ["SKU tidak ditemukan di Master Produk"] }
}
```

**Kode status yang dipakai:**
| Kode | Arti |
|------|------|
| 201  | Pesanan berhasil dibuat |
| 200  | Pesanan sudah pernah dibuat (duplikat `external_order_id`) — dikembalikan data yang sama, tidak digandakan |
| 401  | Token salah/tidak ada |
| 422  | Data tidak valid (SKU tak dikenal, qty 0, dll) |
| 500  | Error server |

### 3.2 Update nomor resi (menyusul, opsional)
```
PATCH https://<domain-erp>/api/orders/{external_order_id}/resi
Body: { "no_resi": "JX1234567890" }
```

### 3.3 Batal / update status (opsional)
```
PATCH https://<domain-erp>/api/orders/{external_order_id}/status
Body: { "status": "Batal", "alasan": "Dibatalkan pembeli" }
```

---

## 4. Penjelasan Field Payload

| Field | Wajib | Keterangan |
|-------|:-----:|------------|
| `external_order_id` | Ya | ID unik pesanan dari Website/App. Dipakai untuk **anti-dobel** (idempotensi). |
| `channel` | Ya | `"Website"` atau `"Hagos App"`. Harus sesuai daftar channel di ERP. |
| `buyer.nama` | Ya | Nama pemesan (juga masuk data Pelanggan/CRM di ERP). |
| `buyer.no_hp` | Disarankan | Nomor HP pembeli (untuk CRM). |
| `items[]` | Ya | Daftar produk. Minimal 1 baris. |
| `items[].sku_id` | Ya | **SKU produk sesuai Master Produk ERP** (lihat bagian SKU Mapping). |
| `items[].qty` | Ya | Jumlah, integer ≥ 1. |
| `payment.status` | Ya | `"Lunas"` (sudah bayar) atau `"Piutang"` (belum bayar / COD). |
| `payment.akun` | Jika Lunas | Nama akun kas tujuan uang masuk (mis. "Payment Gateway"). |
| `payment.metode` | Opsional | QRIS/Transfer/VA/dll (untuk catatan). |
| `shipping.metode` | Opsional | `"Dikirim"` (default) atau `"Ambil Langsung"`. |
| `shipping.no_resi` | Opsional | Boleh null saat order masuk, di-update belakangan. |
| `diskon` | Opsional | Potongan harga (Rp). Default 0. |
| `catatan` | Opsional | Catatan bebas. |

---

## 5. Aturan Penting (harus dipahami kedua sisi)

1. **Autentikasi.** Endpoint dilindungi **token rahasia** (mis. Bearer token / API key). Hanya **server** Website/App yang boleh memanggil — jangan pernah dari sisi browser/klien, agar token tidak bocor.

2. **Idempotensi (anti-dobel).** ERP memakai `external_order_id` sebagai kunci unik. Jika request terkirim lebih dari sekali (retry/webhook ganda), ERP mengenali dan **tidak menggandakan** pesanan.

3. **SKU Mapping.** Produk yang dikirim harus memakai `sku_id` yang **cocok dengan Master Produk ERP**. Dua pilihan:
   - **(a)** Website/App menyimpan `sku_id` ERP langsung di tiap produk (paling sederhana & akurat), atau
   - **(b)** ERP menyediakan tabel pemetaan (SKU Website → sku_id ERP).
   Jika SKU tidak dikenal, pesanan **ditolak** (422) atau ditandai "perlu review" — perlu disepakati.

4. **Harga.** Disarankan **ERP yang menentukan harga** dari Master Harga channel Website/App (bukan menerima harga mentah), agar konsisten dengan HPP & margin. Jika Website perlu mengirim harga sendiri, ERP tetap memvalidasi. Perlu disepakati.

5. **Alur setelah masuk.** Pesanan masuk berstatus **"Menunggu"** dan masuk **antrean Gudang Racik**. HPP dihitung & stok dipotong saat diracik (alur yang sudah berjalan). Untuk pesanan "Dikirim", biaya fulfillment ikut dihitung otomatis.

6. **Pembayaran.**
   - `Lunas` → uang tercatat masuk ke akun kas yang ditentukan (perlu sepakati **nama akunnya**, mis. "Payment Gateway" / "Saldo Website").
   - `Piutang` → tercatat sebagai piutang, uang masuk saat dilunasi.

7. **Resi.** Boleh kosong saat order masuk, lalu di-update via endpoint resi begitu ekspedisi memberi nomor. (Nomor resi juga sudah bisa dicari di ERP.)

8. **Pembatalan/refund.** Perlu mekanisme agar pesanan yang dibatalkan/refund di Website ikut ter-update di ERP (endpoint status di 3.3).

---

## 6. Opsional: Sinkron Dua Arah (tahap lanjut)

Selain Website→ERP, ERP juga bisa mengirim balik ke Website (via endpoint `GET` yang dibaca Website):
- **Stok tersedia** — agar Website tidak menjual barang yang habis.
- **Status pesanan** — Diproses / Dikirim / Selesai.
- **Harga terkini** per produk.

Tidak wajib di tahap awal; bisa menyusul.

---

## 7. Pembagian Tugas

**Sisi ERP HAGOS:**
- Menyediakan endpoint `POST /api/orders` (beserta auth token, validasi, idempotensi).
- Membuat pesanan memakai mesin order/racik/HPP yang sudah ada.
- (Opsional) tabel SKU mapping, endpoint update resi & status, endpoint sinkron balik.

**Sisi Website / Hagos App:**
- Memanggil endpoint ERP saat pesanan dibuat/dibayar (idealnya dari server, bukan klien).
- Mengirim payload sesuai kontrak di atas.
- Menyimpan/mengirim `sku_id` yang sesuai Master Produk ERP.
- Menyimpan `internal_id` dari respons ERP (untuk rujukan/update berikutnya).

> Effort inti pengembangan kecil–sedang. Yang paling menyita waktu adalah **menyepakati kontrak data** (SKU mapping, akun pembayaran, aturan harga), bukan kodenya.

---

## 8. Pertanyaan untuk Developer (mohon dijawab)

Agar bisa lanjut, tolong bantu jawab hal-hal berikut:

1. **Cara koneksi:** Apakah Website/App bisa **memanggil API ERP secara langsung** (push), atau lebih mudah kami yang **menarik data** dari API kalian (pull)? Kalau push, dari server atau dari mana?

2. **SKU:** Apakah produk di Website/App bisa **menyimpan `sku_id` ERP** langsung? Atau kalian ingin kami yang menyediakan **tabel pemetaan** (SKU kalian → SKU ERP)? Bisa kirim contoh daftar SKU/varian di Website?

3. **Harga:** Harga jual ditentukan oleh **ERP** (dari master harga) atau dikirim dari **Website**? Apakah harga Website & ERP dijamin sama?

4. **Pembayaran:** Untuk pesanan yang sudah dibayar online, uangnya masuk ke **akun kas apa** yang harus kami catat? (mis. nama gateway/rekening). Apakah ada skema COD/belum bayar?

5. **Trigger pengiriman data:** Pesanan dikirim ke ERP saat **dibuat**, atau saat **sudah dibayar**? (memengaruhi status awal)

6. **Nomor resi:** Resi diinput di Website/App lalu dikirim ke ERP, atau diproses di ERP? Kapan tersedianya?

7. **Pembatalan/refund:** Bagaimana alur pembatalan di Website/App, dan apakah perlu otomatis membatalkan pesanan di ERP?

8. **Format data:** Apakah format payload JSON di atas cocok? Ada field lain yang perlu ditambah (mis. ongkir, alamat, kode voucher)?

9. **Volume & keamanan:** Perkiraan berapa pesanan per hari? Apakah kalian punya standar autentikasi tertentu (Bearer token cukup, atau perlu signature/HMAC)?

10. **Sinkron balik (opsional):** Apakah nanti perlu ERP mengirim balik **stok/harga/status** ke Website? Kalau ya, kapan?

---

## 9. Langkah Selanjutnya

1. Developer menjawab pertanyaan di Bagian 8.
2. Kita kunci **kontrak payload final** + cara SKU mapping + akun pembayaran.
3. Tim ERP membangun endpoint + pengujian.
4. Uji coba bersama (sandbox) sebelum go-live.

Silakan diskusikan dan beri masukan. Terima kasih.

---

## Appendix A — Contoh Payload Order Website (kontrak nyata, 2026-07-17)

Struktur objek order dari `hagosperfume.com` yang akan ditarik ERP:

```json
{
  "id": "177b15cf-eb52-4dc3-b9e1-3fbdf06c950a",
  "shipping_recipient_name": "Sofa Ramadhan",
  "shipping_city": "Cilegon",
  "shipping_province": "Banten",
  "shipping_cost": "9000",
  "handling_fee": "1500.00",
  "insurance_fee": "0.00",
  "status": "on_delivery",
  "payment_status": "paid",
  "payment_type": "va",
  "bank": "bni",
  "tracking_number": "ABCDE123456",
  "courier": "sicepat",
  "courier_service": "reg",
  "va_number": "9889387523052497",
  "midtrans_order_id": "ORD-20260704-57SMBW",
  "biteship_order_id": "e5d22726-fe7a-4ca4-8901-95dbfb807178",
  "total_price": "75000.00",
  "discount_amount": "0.00",
  "final_total": "85500.00",
  "created_at": "2026-07-04T01:18:19.000000Z",
  "items": [
    {
      "product_name": "Scanleathery",
      "tester_type": "Random",
      "kemasan": "30 ML",
      "quantity": 1,
      "price": "75000.00",
      "subtotal": "75000.00",
      "product": { "sku": "HGS053", "name": "Scanleathery" }
    }
  ],
  "user": { "name": "sofa", "email": "sofa@gmail.com" }
}
```

**Field kunci ERP:** `id` (anti-dobel) · `payment_status=paid` (syarat tarik) · `total_price` (gmv) · `discount_amount` · `items[].product.sku` + `kemasan` (→ SKU ERP via website_sku_maps) · `tracking_number`/`courier` · `created_at` (tgl_pesanan). Titipan (shipping/handling/insurance) bukan pendapatan.
