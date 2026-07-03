# Panduan Deploy ERP HAGOS ke Hostinger (subdomain erp.hagosperfume.com)

Runbook langkah-demi-langkah untuk men-deploy ERP HAGOS ke Hostinger sebagai subdomain **erp.hagosperfume.com**, domain utama **hagosperfume.com**.

> Aplikasi ini **Laravel 12 + Vite (Tailwind)**, butuh **PHP 8.2/8.3**, **MySQL/MariaDB**, dan **Composer**. Deploy paling mulus jika paket Hostinger punya **akses SSH** (paket **Business** ke atas). Paket termurah tanpa SSH bisa, tapi jauh lebih repot.

---

## 0. Cek prasyarat paket Hostinger

Di hPanel, pastikan tersedia:
- **SSH Access** (Advanced → SSH Access). Wajib untuk `composer`, `php artisan`, `git`. → Paket **Business/Cloud**.
- **PHP 8.2 atau 8.3** (Advanced → PHP Configuration).
- **MySQL Database** (Databases → MySQL Databases).
- Ekstensi PHP aktif: `zip` (untuk upload XLSX), `pdo_mysql`, `mbstring`, `openssl`, `bcmath`, `gd` — biasanya default aktif; `zip` kadang perlu diaktifkan.
- **Node.js/npm** biasanya **TIDAK ada** di shared hosting. Karena itu **aset front-end kita build di komputer lokal**, lalu ikut dikirim (lihat Bagian 2 & 5).

---

## 1. Siapkan repository Git (dari komputer lokal)

Project belum berupa repo Git. Jalankan di folder project lokal:

```bash
git init
git add .
git commit -m "Initial commit: ERP HAGOS siap deploy"
```

Buat repository **privat** di GitHub/GitLab (mis. `hagos-erp`), lalu:
```bash
git remote add origin git@github.com:AKUN/hagos-erp.git
git branch -M main
git push -u origin main
```

> `.env` sudah otomatis diabaikan Git (tidak ikut ter-push) — memang benar, karena kredensial produksi diset langsung di server.

---

## 2. Build aset front-end DI LOKAL (penting)

Karena server kemungkinan tanpa Node, build CSS/JS di lokal dan **ikutkan ke Git**. Secara default `.gitignore` mengabaikan `/public/build`, jadi lakukan sekali:

1. Build:
   ```bash
   npm run build
   ```
2. Buka `.gitignore`, **hapus/komentari baris** `/public/build` agar hasil build ikut ter-commit:
   ```
   # /public/build      <- dinonaktifkan supaya aset ikut dikirim ke server tanpa Node
   ```
3. Commit:
   ```bash
   git add public/build .gitignore
   git commit -m "Ikutkan aset build untuk deploy"
   git push
   ```

> Setiap kali ada perubahan tampilan/Tailwind: `npm run build` lagi di lokal → commit → push. (Kalau nanti pakai server ber-Node, langkah ini bisa dipindah ke server.)

---

## 3. Buat subdomain di hPanel

hPanel → **Domains → Subdomains**:
- **Subdomain**: `erp`
- **Domain**: `hagosperfume.com`
- Hostinger membuat folder document root, mis. `domains/erp.hagosperfume.com/public_html`.

**Penting (struktur aman Laravel):** kita akan menaruh aplikasi di folder domain tsb, lalu **mengarahkan document root ke folder `/public` aplikasi** (bukan ke root aplikasi), supaya `.env`, `vendor`, dll tidak bisa diakses publik.
- Catat path root-nya, mis. `/home/uXXXXXX/domains/erp.hagosperfume.com`.
- Document root yang benar nanti: `/home/uXXXXXX/domains/erp.hagosperfume.com/public`.
- Di Hostinger, document root subdomain bisa diubah di pengaturan subdomain (arahkan ke `.../public`). Jika UI tidak mengizinkan keluar dari `public_html`, lihat Bagian 11 (alternatif).

---

## 4. Buat database MySQL

hPanel → **Databases → MySQL Databases**:
- Buat database, mis. `uXXXX_hagoserp`.
- Buat user + password kuat, mis. `uXXXX_erp`.
- Beri user **All Privileges** ke database itu.
- **Catat**: nama DB, user, password, host (biasanya `localhost`).

---

## 5. Kirim kode ke server

### Cara A — Git via SSH (rekomendasi)
Login SSH (hPanel → SSH Access memberi host/port/user), lalu:
```bash
cd ~/domains/erp.hagosperfume.com
# clone ke folder ini (pakai "." agar isinya langsung di sini)
git clone git@github.com:AKUN/hagos-erp.git .
```
(Jika perlu, set SSH key/deploy key GitHub agar clone repo privat berhasil.)

### Cara B — Tanpa SSH (File Manager / FTP)
- Build lokal + `composer install --no-dev` lokal, lalu **upload seluruh folder** (termasuk `vendor/` dan `public/build/`) via File Manager/FTP ke `~/domains/erp.hagosperfume.com`.
- Lebih berat & lambat; update berikutnya juga repot. Kalau memungkinkan, upgrade ke paket ber-SSH.

---

## 6. Install dependency (SSH)

```bash
cd ~/domains/erp.hagosperfume.com
composer install --no-dev --optimize-autoloader
```
(Cara B: sudah termasuk `vendor/` dari upload, langkah ini dilewati.)

---

## 7. Konfigurasi `.env` produksi

Buat file `.env` di root aplikasi (`~/domains/erp.hagosperfume.com/.env`). Isi minimal:

```env
APP_NAME="HAGOS ERP"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://erp.hagosperfume.com
APP_TIMEZONE=Asia/Jakarta

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=uXXXX_hagoserp
DB_USERNAME=uXXXX_erp
DB_PASSWORD=**********

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true

QUEUE_CONNECTION=database
MAIL_MAILER=log
```

Lalu generate kunci aplikasi:
```bash
php artisan key:generate         # mengisi APP_KEY otomatis
```

> `APP_DEBUG=false` & `APP_ENV=production` = menutup **Blocker #1** (mode debug). `SESSION_SECURE_COOKIE=true` mengunci cookie hanya lewat HTTPS.

---

## 8. Migrasi database & data awal

```bash
php artisan migrate --force
php artisan db:seed --force        # HANYA jika ada seeder master (kategori/komponen/akun dasar)
php artisan storage:link           # symlink storage publik (aman dijalankan)
```

> Untuk **data riil** (stok, saldo kas, harga, resep) — jangan pakai data uji. Lihat Bagian 12 (menutup **Blocker #4**).

---

## 9. Aktifkan SSL (HTTPS) + paksa HTTPS

- hPanel → **Security → SSL** → pasang **SSL gratis** untuk `erp.hagosperfume.com` (Let's Encrypt, otomatis).
- Tunggu sampai status **Active**.
- Karena `APP_URL` sudah `https://...` dan `SESSION_SECURE_COOKIE=true`, aplikasi mengarah ke HTTPS.
- Jika muncul **redirect loop** atau **mixed content** (aset ter-load via http), tambahkan trust proxy — lihat Bagian 11.

> Ini menutup **Blocker #3** (hosting + HTTPS).

---

## 10. Optimasi cache produksi

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
Pastikan folder writable (kalau ada error permission):
```bash
chmod -R 775 storage bootstrap/cache
```

Buka **https://erp.hagosperfume.com** → halaman login harus muncul.

---

## 11. Catatan struktur & troubleshooting

**Document root ke `/public`:** kalau Hostinger tak mengizinkan mengarahkan doc root ke luar `public_html`, alternatifnya:
- Taruh isi folder `public/` aplikasi ke dalam `public_html` subdomain, dan pindahkan sisa aplikasi (app, vendor, dll) satu level di atasnya; lalu **edit `public_html/index.php`** agar path `require` menunjuk ke lokasi baru (`__DIR__.'/../nama_folder_app/vendor/autoload.php'` dan `bootstrap/app.php`). Cara ini umum di shared hosting.
- Cara paling bersih tetap: set document root subdomain langsung ke `.../public`.

**Error 500 setelah deploy:** cek `storage/logs/laravel.log`. Penyebab umum: `APP_KEY` kosong, permission `storage`/`bootstrap/cache`, atau kredensial DB salah.

**Redirect loop / mixed content (di belakang proxy Hostinger):** edit `bootstrap/app.php`, di bagian `->withMiddleware(function (Middleware $middleware) {` tambahkan:
```php
$middleware->trustProxies(at: '*');
```
lalu `php artisan config:cache`. Ini membuat Laravel mempercayai header `X-Forwarded-Proto` dari proxy sehingga deteksi HTTPS benar.

**Halaman putih / aset tak muncul:** pastikan `public/build/` ikut ter-deploy (Bagian 2) dan `APP_URL` benar.

---

## 12. Reset data uji → isi data awal riil (Blocker #4)

Sebelum dipakai transaksi sungguhan:
1. Pakai fitur **Pengaturan → Reset Data** (menghapus transaksi + menolkan stok/saldo) untuk mulai bersih. (Atau mulai dari database kosong hasil `migrate` di server.)
2. Input **saldo awal riil**:
   - Master Bibit & Komponen: **stok fisik** + **harga per ml/unit** riil.
   - Akun Kas: **saldo awal** tiap akun (bank, cash, gateway).
   - Master Produk + Master Harga (per channel) + Resep (termasuk Resep Mix).
   - Karyawan, utang/cicilan berjalan, piutang berjalan.
3. Untuk pesanan yang **sedang berjalan** saat go-live, ikuti prosedur "Sudah Dikirim (Go-Live)" di Gudang Racik (hitung HPP tanpa memotong stok, karena bahan sudah terpakai sebelum sistem mulai).

---

## 13. Backup otomatis (Blocker #2)

- hPanel → **Files → Backups**: pastikan **backup otomatis** aktif (Hostinger menyediakan; frekuensi tergantung paket).
- Tambahan disarankan — **backup database terjadwal** via **Cron Jobs** (hPanel → Advanced → Cron Jobs), mis. harian:
  ```bash
  mysqldump -u uXXXX_erp -p'PASSWORD' uXXXX_hagoserp | gzip > ~/backups/erp_$(date +\%F).sql.gz
  ```
- **Simpan salinan di luar server** (Google Drive/S3) secara berkala.
- **Uji restore** sekali agar yakin backup bisa dipulihkan.
- **Sebelum tiap deploy besar** (yang ada `migrate`), backup DB dulu.

---

## 14. Alur update berikutnya (rutin, tanpa upload folder)

Setelah live, memperbarui aplikasi cukup:
```bash
# di lokal
npm run build          # jika ada perubahan tampilan
git add -A && git commit -m "perbaikan X" && git push

# di server (SSH)
cd ~/domains/erp.hagosperfume.com
php artisan down
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan up
```
Bisa dibungkus jadi `deploy.sh` (lihat dokumen *Panduan-Deploy-dan-Perbaikan-Error.md*).

---

## Ringkasan pemetaan ke 5 Blocker Go-Live

| Blocker | Ditutup di |
|---------|------------|
| #1 Mode debug/production | Bagian 7 (`.env` produksi) |
| #2 Backup DB | Bagian 13 |
| #3 Hosting + HTTPS | Bagian 3, 9 |
| #4 Reset data + saldo awal riil | Bagian 12 (dikerjakan owner) |
| #5 Uji alur berisiko (batal/salah-racik/retur/bonus tester) | **dikerjakan di kode SEBELUM deploy** (belum termasuk di runbook ini) |

> **Blocker #5 belum selesai** dan sebaiknya diuji/diperbaiki di kode **sebelum** go-live, karena menyangkut uang & stok riil.
