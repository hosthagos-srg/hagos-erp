# HAGOS ERP — Panduan untuk Claude

ERP internal HAGOS Parfum (parfum inspired lokal): penjualan multi-channel (TikTok/Shopee/Offline/Reseller/Website), racik/produksi, stok bibit & komponen, HPP, kas & cashflow, laporan P&L.

**Stack:** Laravel 12 · Blade · MySQL · Tailwind v4 (via `@tailwindcss/vite`) · Alpine · Chart.js · TomSelect
**Bahasa:** Berkomunikasilah dengan user (Ekal) dalam **Bahasa Indonesia**.

---

## 🚨 ATURAN KESELAMATAN — BACA DULU

### 1. Data produksi (live) adalah DATA ASLI
Sejak 2026-07-05 sistem live dipakai untuk transaksi nyata.
- **LIVE = JANGAN pernah reset/hapus/timpa.** Ke live hanya push **KODE** lewat git.
- Perubahan **DATA/master** tidak ikut git → apply terpisah via **SQL di phpMyAdmin** atau lewat UI produksi.
- Selalu **konfirmasi ke user** sebelum menyarankan apa pun yang mengubah live.

### 2. Isolasi database (lokal)
Folder ini (`hagos-erp-claude`) adalah copy dari `hagos-erp` yang dipakai tool lain.
- Folder ini **HANYA** menyentuh DB **`hagos_erp_claude`**.
- **JANGAN PERNAH** menjalankan query tulis/migrate terhadap DB **`hagos_erp`** — itu milik tool lain.
- Lokal = sandbox, boleh direset. Sinkronisasi hanya **satu arah: Produksi → Lokal** (ekspor dump live, impor ke lokal). Live tak pernah disentuh.

### 3. Cek sebelum hapus
Pernah terjadi data yang disangka "test" ternyata pesanan asli. Kalau sesuatu terlihat seperti sampah data, **periksa & tanya dulu**, jangan langsung hapus.

---

## Environment (Windows + Laragon)

**PHP tidak ada di PATH.** Pakai binary Laragon:
```
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe
```
Contoh: `& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' artisan migrate`
Kalau versi berubah: `Get-ChildItem 'C:\laragon\bin\php' -Recurse -Filter php.exe | Select -First 1`

**MySQL** (root, tanpa password), sering mati sendiri — start ulang:
```
/c/laragon/bin/mysql/mysql-8.4.3-winx64/bin/mysqld.exe --defaults-file="/c/laragon/bin/mysql/mysql-8.4.3-winx64/my.ini"
```

**Node:** `C:\Program Files\nodejs`

**Ekstensi `zip` wajib aktif** (`extension=zip` di php.ini) — upload settlement/pesanan XLSX pakai OpenSpout yang butuh `ZipArchive`. Gejala kalau mati: `Class "ZipArchive" not found`. Setelah ubah php.ini, **restart Laragon**.

---

## Aturan Ngoding

### Tailwind: WAJIB `npm run build`
Project pakai **build production** (`public/build/manifest.json` ada, `public/hot` tidak). Setiap menambah class Tailwind yang **belum pernah dipakai** di blade manapun, class itu tak ada di CSS ter-compile → styling tak jalan. **Jalankan `npm run build` setelah edit blade dengan class baru**, lalu user refresh Ctrl+F5.

### Encoding: JANGAN pakai PowerShell Set-Content untuk file berisi emoji
Windows PowerShell 5.1 membaca file sebagai Windows-1252, bukan UTF-8 → emoji `🏆` rusak jadi `ðŸ†`, `─` jadi `â"€`. Untuk bulk edit pakai tool **Edit** per-file, atau .NET langsung (`[IO.File]::ReadAllText($p,[Text.Encoding]::UTF8)` + `WriteAllBytes` UTF8-no-BOM).

### Kolom angka = DECIMAL
Kolom numerik master sudah DECIMAL/INT — **jangan parsing/format string** saat menulis ke DB.

### ⚠️ Jebakan NULL di query keuangan
`NULL NOT LIKE 'x'` di MySQL = **NULL, bukan TRUE** → baris ber-NULL diam-diam **terbuang**. Ini pernah bikin seluruh biaya operasional hilang dari P&L (laba kelebihan) karena filter `ref_id NOT LIKE 'GAJI-%'` ikut membuang semua `ref_id NULL`. **Selalu eksplisit:** `WHERE ref_id IS NULL OR ref_id NOT LIKE 'GAJI-%'`. Hati-hati tiap menambah filter `NOT LIKE` / `!=` pada query keuangan.

---

## Arsitektur Inti

| Service | Tanggung jawab |
|---|---|
| `HppService::computeBreakdown` | Hitung HPP: lapis1 (bibit/absolute/botol/box) + lapis2 (gaji). Tester lewat `KMP-TSTR`. |
| `RacikService` | Racik & kembalikan stok; T11-dulu-baru-fresh; `komponen_usage` untuk potong stok. |
| `LaporanService::summary` | **Sumber tunggal** angka P&L. Dipakai halaman P&L **dan** Dashboard. |
| `ProduksiInternalController` | Produksi tester & campur absolute (gudang internal). |

### `track_stok` harus dihormati
Komponen konsumabel ber-`track_stok='Tidak'` (stiker, kartu, shrink, bahan packing) **tidak dilacak, tidak dipotong, tidak boleh minus**. Pernah ada bug: produksi tester hardcode potong stok stiker tester tanpa cek flag → stok minus terus & warning palsu. **Selalu gate pemotongan/warning stok pada `track_stok === 'Ya'`.**

### Konsumabel keluar dari HPP
Sejak 2026-07-09: sticker utama, kartu ucapan, shrink, bahan packing **di-nol-kan di HPP** (harga master tetap utuh) dan dicatat sebagai **Pengeluaran nyata** (kategori "Bahan Packing"). **Gaji packing tetap di HPP.** `komponen_usage` (potong stok) tak berubah. HPP order lama yang sudah tersimpan **tidak** dihitung ulang.

---

## Aturan Bisnis & Akuntansi

**Basis cash-basis apa adanya.** Biaya dicatat saat uang benar-benar keluar; laba bulanan boleh naik-turun karena timing (mis. sewa dibayar front-loaded). Disepakati user — jangan diam-diam diubah jadi accrual.

| Hal | Aturan |
|---|---|
| **Cicilan (Cara B)** | Barang yang dibeli via kredit/cicilan **JANGAN dicatat lagi** di Belanja/Pengeluaran — cukup lewat modul Utang → pembayaran cicilan. Pokok cicilan **memang** biaya di P&L. Mencatat 2× = laba salah. |
| **Gaji (Cara C)** | Dibebankan ke **`bulan_biaya`** (accrual), bukan tanggal bayar. Kas tetap keluar di tanggal bayar; mutasi `GAJI-%` dikecualikan dari pengeluaran agar tak dobel/salah bulan. |
| **Patungan biaya bersama** | Kontribusi mitra luar (nebeng kontrakan) = kas masuk tapi **BUKAN pendapatan** → **pengurang biaya operasional** (kategori mutasi `patungan`). Bulan mitra libur = 0 patungan = HAGOS tanggung penuh (otomatis benar). |
| **Pinjaman pribadi** | Pelunasan piutang **bukan pendapatan** → kategori `piutang_pribadi`, bukan `penerimaan`. |
| **Produk MIX** | Hanya kemasan **50ml**. `MIX-CUSTOM-50-REG` untuk mapping mix marketplace (aroma diisi per-pesanan via "Set Mix" saat racik). SKU berawalan `MIX` otomatis masuk sesi racik. |
| **Channel `Gratis`** | Dikecualikan dari metrik penjualan; tetap masuk antrean racik agar stok bibit terpotong. |

### Omzet: Dashboard vs P&L **memang beda** (bukan bug)
- **Dashboard** = semua pesanan non-batal **by `tgl_pesanan`**, apapun status bayarnya (termasuk yang belum cair) → mengukur *aktivitas penjualan*.
- **P&L** = MP **Cair** by `tgl_cair_saldo` + non-MP **Lunas** by `tgl_pesanan` → mengukur *pendapatan yang diakui*. Wajib basis cair, kalau tidak laba mengakui uang yang belum tentu masuk.

---

## Alur Deploy

1. Bangun & **uji di LOKAL** dulu (kalau perlu uji terhadap data nyata: tarik dump live → impor ke lokal).
2. Commit & `git push origin main`.
3. User di produksi: `git pull origin main` lalu `php artisan optimize:clear`.
4. Perubahan **DATA/master** (kategori baru, koreksi data) → tulis file SQL ke `storage/db_backups/` (gitignored), user jalankan di **phpMyAdmin live**. Buat SQL yang **aman diulang** (`WHERE NOT EXISTS`) dan beri tahu user hasil yang diharapkan ("1 row affected").
5. Kalau ada class Tailwind baru → `npm run build` **sebelum** commit (ikutkan `public/build`).

---

## Cara Kerja dengan Ekal

- **Interaktif & kolaboratif**, bukan agent yang kerja sendiri lalu menyodorkan hasil. Bangun satu per satu, jelaskan sambil jalan.
- **Kalau buntu atau kurang data, MINTA** (file, dump, screenshot) — **jangan menebak**.
- Jangan diam-diam mengubah perhitungan HPP/settlement yang sudah benar. Kalau perlu diubah, **minta konfirmasi eksplisit** dan **buktikan dengan uji** bahwa data lama tak rusak sebelum push.
- Setiap perbaikan angka keuangan: **buktikan dengan hitungan** (sebelum vs sesudah), jangan cuma klaim.

---

## Dokumentasi lain

- `docs/Panduan-Hagos-ERP.md` — panduan fitur lengkap
- `docs/Panduan-Deploy-Hostinger.md` — deploy produksi
- `docs/Panduan-Deploy-dan-Perbaikan-Error.md` — troubleshooting
- `docs/Integrasi-API-Website-HagosApp.md` + `docs/HAGOS-API.postman_collection.json` — API website

> **Catatan:** repo ini **publik**. Jangan pernah commit `.env`, kredensial, dump database, atau angka keuangan internal (modal, margin, gaji, target omzet).
