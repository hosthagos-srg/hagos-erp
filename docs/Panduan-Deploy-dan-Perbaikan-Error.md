# Panduan Deploy & Perbaikan Error (Setelah Live)

Dokumen ini menjelaskan **cara memperbarui kode dan memperbaiki error** setelah ERP HAGOS di-hosting — **tanpa** harus download data lalu upload ulang seluruh folder web setiap kali. Ditujukan untuk pemilik + developer/admin hosting.

---

## 1. Prinsip Dasar: Pisahkan "Kode" dan "Data"

Ini konsep terpenting. Jangan pernah mencampur keduanya.

| Bagian | Tempatnya | Kalau ada error | Cara update |
|--------|-----------|-----------------|-------------|
| **Kode** (file `.php`, Blade, JS, dll) | folder aplikasi di server | ~99% bug ada di sini | lewat **Git** (bukan upload folder) |
| **Data** (pesanan, stok, kas, dll) | **database MySQL** di server | jarang jadi sumber bug | tetap di server; jangan diutak-atik untuk memperbaiki bug |

**Kesimpulan:** error hampir selalu bug di **kode**. Jadi **tidak perlu** menarik data produksi dan me-replace file. Data riil tetap aman di database server; yang diperbaiki cukup kodenya.

> Menarik salinan database HANYA diperlukan bila ingin *mereproduksi* masalah dengan data mirip-produksi di komputer lokal — itupun cukup ekspor DB (dump), bukan mengganti file.

---

## 2. Alur Perbaikan Error yang Benar (pakai Git)

**Setup sekali di awal:** kode disimpan di repository **Git** (GitHub/GitLab, bisa private). Server hosting menyimpan salinan kode yang sama dan mengambil pembaruan lewat `git pull`.

**Loop saat ada error:**

```
1. Owner mengirim PESAN ERROR-nya (dari log)  — bukan seluruh folder
2. Perbaikan dilakukan di folder kode lokal
3. commit + push ke Git            →  git push
4. Server menarik perubahan        →  git pull   (hanya file yang berubah; hitungan detik)
5. Jalankan langkah pasca-deploy    (lihat Bagian 6)
```

Tidak ada upload folder berulang. Yang berpindah hanya baris kode yang berubah.

**Lebih otomatis (opsional): Auto-deploy.**
Begitu `git push`, server otomatis menarik & memperbarui sendiri (via webhook / Laravel Forge / Ploi / GitHub Actions). Push → live dalam ~1 menit tanpa menyentuh server secara manual.

---

## 3. Cara Mengetahui Error-nya Apa (paling penting)

Di produksi, `APP_DEBUG=false` (wajib) — sehingga error **tidak tampil di layar pengguna** (aman), tetapi **tercatat di log**.

**Sumber utama: file log Laravel**
```
storage/logs/laravel.log
```
Cukup buka file ini di server, salin **beberapa baris error paling bawah/terbaru**, lalu kirim untuk diperbaiki. Isinya memuat pesan error + lokasi baris file penyebabnya.

**Opsional (sangat disarankan): Error Tracking otomatis — Sentry**
- Pasang paket Sentry (ada paket gratis) → setiap error otomatis terkirim ke dashboard + email.
- Kelebihan: tak perlu buka server; notifikasi lengkap dengan lokasi baris, langkah pemicu, dan data teknis. Tinggal diteruskan untuk diperbaiki.

---

## 4. Yang Perlu Dikirim Saat Ada Error

Cukup **salah satu** dari ini (TIDAK perlu seluruh folder web):

1. **Pesan error** dari `storage/logs/laravel.log` (beberapa baris terakhir), atau
2. **Notifikasi Sentry** (kalau sudah dipasang), atau
3. **Screenshot** halaman error + **langkah yang memicunya** (klik apa, isi apa).

Karena kode lokal = kode server (lewat Git), perbaikan bisa langsung dikerjakan di lokal tanpa perlu salinan produksi.

---

## 5. Pilihan Hosting (agar alur di atas bisa jalan)

| Pilihan | Cocok? | Catatan |
|---------|:------:|---------|
| **VPS + Laravel Forge / Ploi** | ⭐ Terbaik | Khusus Laravel: deploy Git 1 klik, SSL otomatis, backup terjadwal, scheduler. |
| **VPS biasa (dengan SSH)** | Baik | Fleksibel, tapi setup manual (web server, PHP, MySQL, SSL). |
| **Shared hosting dengan SSH + Git** | Cukup | Pastikan ada fitur "Git Version Control" + akses SSH (mis. sebagian cPanel). |
| **Hosting hanya File Manager (drag-drop)** | ❌ Hindari | Inilah yang memaksa upload folder berulang. Tidak disarankan untuk Laravel. |

Syarat minimal yang harus dimiliki hosting: **PHP versi sesuai**, **MySQL/MariaDB**, **Composer**, dan idealnya **akses SSH + Git**.

---

## 6. Hal Wajib Dijaga Saat Deploy

Beberapa kesalahan klasik saat memperbarui aplikasi — hindari ini:

1. **JANGAN menimpa file `.env` di server.** File ini berisi password database produksi & `APP_KEY`. Kalau tertimpa, aplikasi bisa rusak / kehilangan akses. `.env` di-set sekali di server dan tidak ikut Git (memang di-*ignore*).
2. **JANGAN menimpa/replace database.** Perubahan struktur tabel dijalankan lewat migration, bukan mengganti file.
3. **Pastikan flag produksi benar** di `.env` server:
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://domainmu.com
   ```
4. **Langkah pasca-`git pull`** (bisa dibungkus jadi 1 skrip — lihat Bagian 7):
   ```bash
   composer install --no-dev --optimize-autoloader   # dependency (tanpa dev)
   php artisan migrate --force                        # terapkan perubahan struktur DB
   npm ci && npm run build                            # build aset (CSS/JS)
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
   > Catatan: setiap ada penambahan class Tailwind baru, `npm run build` wajib dijalankan.

---

## 7. Contoh Skrip Deploy Satu-Perintah

Simpan sebagai `deploy.sh` di server. Sekali `git pull` bermasalah/perlu update, cukup jalankan `bash deploy.sh`.

```bash
#!/usr/bin/env bash
set -e   # berhenti jika ada langkah gagal

echo ">> Menarik kode terbaru..."
git pull origin main

echo ">> Menyalakan mode maintenance..."
php artisan down || true

echo ">> Update dependency..."
composer install --no-dev --optimize-autoloader

echo ">> Migrasi database..."
php artisan migrate --force

echo ">> Build aset front-end..."
npm ci
npm run build

echo ">> Refresh cache..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ">> Mematikan mode maintenance..."
php artisan up

echo ">> Selesai."
```

- `php artisan down` / `up` = "mode maintenance": pengunjung melihat halaman "sedang pemeliharaan" selama proses, agar tidak ada transaksi setengah jadi.
- `set -e` = kalau satu langkah gagal, skrip berhenti (tidak lanjut dengan kondisi rusak).

---

## 8. Backup (wajib sebelum & selama live)

Karena data berisi uang & stok, backup **tidak bisa ditawar**:
- **Database**: backup otomatis harian (Forge/Ploi menyediakan; atau cron `mysqldump` ke storage/cloud).
- **File upload** (jika ada): ikut dibackup.
- **Simpan salinan di luar server** (mis. Google Drive/S3) — jangan hanya di server yang sama.
- **Uji restore** minimal sekali, supaya yakin backup benar-benar bisa dipulihkan.

Sebelum tiap deploy besar (migrasi struktur), **backup dulu** — agar bisa dikembalikan jika ada masalah.

---

## 9. Ringkasan Alur

```
Error muncul
     │
     ▼
Owner ambil pesan error dari laravel.log / Sentry  ──►  kirim
     │
     ▼
Perbaikan kode di lokal  ──►  git commit + push
     │
     ▼
Server: git pull + deploy.sh  (data riil tak tersentuh)
     │
     ▼
Selesai — tanpa upload folder, tanpa ganti data
```

**Intinya:** dengan **Git + logging (+ Sentry) + backup**, perbaikan error jadi cepat dan aman. Data produksi tidak pernah tersentuh saat memperbaiki bug.

---

## 10. Langkah Persiapan (saat mau go-live)

1. Pilih hosting yang mendukung **SSH + Git** (idealnya Forge/Ploi).
2. Simpan kode ke repository **Git** privat.
3. Set `.env` produksi di server (`APP_ENV=production`, `APP_DEBUG=false`, DB, `APP_URL`, `APP_KEY`).
4. Pasang **backup otomatis** + coba **restore**.
5. (Disarankan) pasang **Sentry** untuk pantau error.
6. Siapkan **`deploy.sh`** untuk update satu-perintah.

Setelah hosting dipilih, langkah 2–6 bisa dibantu disiapkan (repo Git, skrip deploy, konfigurasi) beserta pengujiannya.
