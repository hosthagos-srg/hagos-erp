# 📘 Panduan Lengkap HAGOS ERP

> Sistem Keuangan & Operasional HAGOS Parfum
> Dokumen ini menjelaskan **apa**, **cara pakai**, **contoh nyata**, dan **aturan** tiap fitur — plus **skenario Go-Live**.

---

## Daftar Isi
1. [Ikhtisar & Alur Besar](#1-ikhtisar--alur-besar)
2. [Login & Hak Akses](#2-login--hak-akses)
3. [Dashboard](#3-dashboard)
4. [Penjualan (Input Manual)](#4-penjualan-input-manual)
5. [Marketplace: Upload, Mapping, Settlement, Rekonsiliasi](#5-marketplace)
6. [Gudang Racik](#6-gudang-racik)
7. [Produksi Internal (Tester & Absolute)](#7-produksi-internal)
8. [Stok Bahan & Stok Produk Jadi](#8-stok)
9. [Belanja Bibit & Komponen](#9-belanja)
10. [Keuangan (Saldo, Modal/Prive, Transfer, WD)](#10-keuangan)
11. [Penerimaan & Pengeluaran](#11-penerimaan--pengeluaran)
12. [Utang & Cicilan](#12-utang--cicilan)
13. [Karyawan, Kasbon, Gaji](#13-karyawan-kasbon-gaji)
14. [Pelanggan (CRM) & Produk Gratis](#14-pelanggan-crm)
15. [Master Produk & Mesin HPP](#15-master-produk)
16. [Laporan (P&L, Neraca, Margin, Proyeksi)](#16-laporan)
17. [Pengaturan (User, Log Aktivitas, Tutup Buku)](#17-pengaturan)
18. [🚀 Skenario GO-LIVE](#18-skenario-go-live)
19. [Konsep Akuntansi Penting](#19-konsep-akuntansi-penting)
20. [Troubleshooting](#20-troubleshooting)

---

## 1. Ikhtisar & Alur Besar

HAGOS ERP mencatat **operasional** (stok, racik, produksi) dan **keuangan** (kas, omzet, HPP, laba) dalam satu sistem.

**Alur utama sebuah pesanan:**

```
Pesanan masuk → Racik (potong stok + hitung HPP) → Dikirim → Settlement cair (uang masuk) → Laporan
```

- **Marketplace (TikTok/Shopee):** pesanan masuk lewat **upload file**, lalu diracik di Gudang Racik, lalu **settlement** di-upload saat dana cair.
- **Non-marketplace (Offline/WA/Reseller):** diinput manual & **langsung diracik** saat input (pembeli dilayani langsung).

**Prinsip angka:**
- **Omzet marketplace** diakui saat **dana cair** (settlement), bukan saat order masuk.
- **HPP** dihitung saat racik (sudah termasuk bibit, botol, packaging, tester).
- **Laba** = Omzet − HPP − Biaya Operasional.

---

## 2. Login & Hak Akses

**Apa:** Gerbang keamanan. Semua halaman wajib login.

**Cara pakai:**
- Buka aplikasi → halaman login → masukkan **email + password**.
- Keluar lewat tombol **Keluar** di pojok kiri bawah.

**Aturan:**
- Saat ini semua user berperan **owner** (akses penuh).
- **Anti brute-force:** salah password 5× → akun dikunci sementara ~1 menit.
- Kelola akun di **Pengaturan → Kelola User** (tambah/edit/hapus, reset password). Tidak bisa hapus akun sendiri; minimal harus ada 1 user.

---

## 3. Dashboard

**Apa:** Ringkasan kondisi bisnis sekali pandang.

**Isi:**
- **Strip omzet:** hari ini / minggu / bulan, dengan **% naik-turun** vs periode sebelumnya.
- **Alert:** settlement belum cair (uang nyangkut), sisa utang.
- **Ringkasan pesanan:** minggu/bulan, lewat jatuh tempo, belum cair.
- **Grafik penjualan** (filter hari/minggu/bulan/custom).
- **Produk Terjual Terbanyak** & **Ringkasan per Channel** — bisa **pilih bulan sendiri-sendiri**, dengan **kesimpulan naik/turun %** vs bulan sebelumnya.
- **Breakdown Pengeluaran** — periode **Minggu Ini / Minggu Lalu / Bulan Ini / Bulan Lalu / Custom**, dengan kesimpulan (pengeluaran naik = merah, turun = hijau).
- **Aktivitas terbaru** & peringatan stok bibit menipis.

**Contoh:** Buka Dashboard, di kartu "Ringkasan per Channel" pilih **Mei 2026** → muncul *"▼ Net omset turun 8% vs April 2026"*, dan tiap channel ada badge ▲/▼. Di kartu "Produk Terbanyak" kamu bisa pilih bulan lain tanpa mengubah kartu channel.

---

## 4. Penjualan (Input Manual)

**Apa:** Catat penjualan non-marketplace (Offline, WA, Reseller, Website, Refill).

**Cara pakai (langkah):**
1. Klik **+ Input Penjualan**.
2. Pilih **SKU Produk**, **Qty**, **Channel**.
3. Isi **Nama Pemesan** + **No. HP** (penting untuk CRM).
4. Pilih **Metode Pengiriman**, **Status Pembayaran** (Lunas/Piutang), **Akun Pembayaran**.
5. (Opsional) Diskon, Ekstra Tester.
6. Lihat **Detail Harga** (live: harga, HPP, margin, net).
7. **Simpan**.

**Yang terjadi otomatis:**
- Pesanan **langsung diracik** (stok terpotong, HPP & margin terhitung).
- Jika **Lunas** + akun dipilih → uang **masuk** ke akun kas.
- **CRM otomatis:** nama + No. HP tersimpan ke daftar **Pelanggan**.

**Contoh nyata:**
> Budi beli **1 botol Boshell 30ml** via **WA**, bayar transfer **BCA**, HP 0812xxxx.
> → Input: SKU Boshell-30, channel WA, Lunas, akun BCA, nama Budi, HP 0812xxxx.
> → Hasil: stok bibit Boshell −18ml, HPP ±Rp45.000 terhitung, BCA +harga jual, pelanggan "Budi" otomatis terdaftar.

---

## 5. Marketplace
### Upload Pesanan, Mapping SKU, Settlement, Rekonsiliasi

### 5.1 Upload Pesanan
**Apa:** Impor pesanan TikTok/Shopee dari file export Seller Center.

**Cara pakai:**
1. **Penjualan → Upload Marketplace**.
2. Pilih platform (**TikTok**/**Shopee**), pilih file pesanan (.csv/.xlsx), upload.
3. Sistem buat pesanan status **Menunggu** (masuk antrean racik).

**Aturan:**
- **Anti-duplikat:** upload ulang file yang sama → baris lama dilewati (tidak dobel).
- Pesanan dibatalkan di file → dilewati.

### 5.2 Mapping SKU
**Apa:** Cocokkan nama produk marketplace ke SKU internal (sengaja **manual** agar tidak salah-petakan).

**Cara pakai:**
1. **Penjualan → Mapping SKU**.
2. Untuk produk yang belum dikenali, pilih SKU yang benar.
3. Setelah dipetakan, **upload ulang** file pesanan agar pesanan yang tadi terlewat ikut masuk.

### 5.3 Settlement (Dana Cair)
**Apa:** Tandai pesanan **Cair** & catat uang masuk, dari file income/settlement.

**Cara pakai:**
1. **Upload Marketplace** → bagian **Settlement** → pilih platform + file → upload.
2. Sistem mencocokkan per **Order ID**, set **net_settlement**, status **Cair**, dan **uang masuk** ke akun "Saldo TikTok Shop" / "Saldo Shopee Seller".

**Aturan penting:**
- **Omzet diakui pada tanggal rilis dana dari FILE** (Shopee: "Tanggal Dana Dilepaskan"; TikTok: "Waktu pembayaran pesanan") — bukan tanggal upload. Jadi omzet jatuh di bulan yang benar.
- **Re-upload aman** (idempoten): uang tidak dobel.
- Biaya marketplace (admin/komisi) sudah otomatis **net** di omzet.

### 5.4 Rekonsiliasi Marketplace
**Apa:** Tangkap **biaya siluman** marketplace (iklan, denda, adjustment) yang tidak nempel ke order tertentu.

**Cara pakai:**
1. **Keuangan → Rekonsiliasi Marketplace**, pilih bulan.
2. Sistem tampilkan **net settlement versi sistem** per channel.
3. Cek **saldo riil** di marketplace/mutasi bank, isi di kolom **Dana riil diterima**.
4. Sistem hitung **selisih**. Centang **"Catat selisih sbg beban"** + pilih akun → selisih masuk Pengeluaran (laba terkoreksi).

**Contoh nyata:**
> Sistem catat net TikTok **Rp20.000.000**, tapi Saldo TikTok riil **Rp18.500.000**.
> → Selisih **Rp1.500.000** (ternyata: iklan Rp900rb + refund Rp400rb + dana ketahan Rp200rb).
> → Bebankan selisih → laba turun Rp1,5jt sesuai kenyataan.

---

## 6. Gudang Racik

**Apa:** Proses meracik pesanan yang masuk antrean (potong stok + hitung HPP). Tampil **50 pesanan per halaman**.

**Cara pakai:**
1. **Produksi → Gudang Racik**.
2. Centang pesanan yang mau diproses (default tercentang semua).
3. Set **Diracik oleh** (bisa massal lewat "Set diracik oleh").
4. Pilih **Aksi** per baris atau massal lewat **"Set aksi (terpilih)"**:
   - **Racik Baru (Potong Stok)** — meracik dari bahan baru. *Default untuk operasi normal.*
   - **Sudah Dikirim (Go-Live · tanpa potong stok)** — untuk pesanan yang sudah diracik & dikirim sebelum sistem mulai (lihat [Go-Live](#18-skenario-go-live)).
   - **Ambil dari Retur (T11)** — penuhi dari stok produk jadi.
   - **Batalkan Pesanan**.
5. **Proses Racik Semua yang Terpilih**.

**Aturan:**
- Jika stok **bibit atau botol** kurang saat "Racik Baru" → muncul **popup 2 langkah**:
  1. **Peringatan** (daftar bahan yang kurang) dengan 3 pilihan:
     - **Batalkan** — tidak jadi diracik (pakai bila fisik benar-benar kosong).
     - **Proses yang stoknya cukup (lewati N)** — proses pesanan yang bahannya cukup, **lewati otomatis** yang kurang (tetap di antrean, tak perlu tandai ulang). *Muncul saat sebagian pesanan masih bisa dibuat.*
     - **Lanjutkan** — ke langkah 2.
  2. Jika Lanjutkan → **input stok fisik riil** → **Update Stok & Lanjut** (proses semua) atau **Kembali**.
- Pesanan diproses **per halaman** (50). Untuk semua, racik halaman 1 → lanjut halaman berikutnya.

---

## 7. Produksi Internal

**Apa:** Produksi internal **Tester** & **Absolute Campuran** (bahan setengah jadi).

**Cara pakai:**
- **Produksi → Produksi Internal**.
- **Tester:** pilih aroma + jumlah botol → stok bibit & komponen terpotong, stok Tester Jadi bertambah (HPP rata-rata).
- **Absolute:** input ml murni + denat → menghasilkan Absolute Campuran (HPP rata-rata).

**Aturan:** Jika **bibit atau botol tester** kurang saat cek → muncul **popup 2 langkah** (sama seperti Gudang Racik): peringatan → Batalkan / Lanjutkan → input stok fisik riil. (Jika produksi tetap dipaksa jalan dengan stok kurang, muncul peringatan stok minus.)

---

## 8. Stok

### Stok Bahan (Bibit & Komponen)
- **Produk & Stok → Stok Bahan**: lihat stok bibit/komponen + **nilai inventory** + peringatan menipis.
- **Koreksi/Opname:** set stok ke jumlah fisik riil. Selisih tercatat sebagai data (T12).
- **Susut (selisih negatif)** otomatis masuk **biaya di P&L** (mis. bibit menguap/tumpah).

### Stok Produk Jadi (T11)
- Botol jadi siap kirim (mis. hasil retur yang masih layak). Dipakai lebih dulu saat racik bila tersedia.

**Contoh:** Opname bibit Boshell: sistem 5.004ml, fisik 4.994ml → susut 10ml × HPP Rp1.804 = **Rp18.040** masuk biaya P&L.

---

## 9. Belanja

**Apa:** Catat pembelian bibit/komponen. Stok naik + harga rata-rata (weighted average) diperbarui + kas keluar.

**Cara pakai:**
1. **Pembelian → Belanja → Buat Belanja**.
2. Pilih jenis (bibit/komponen), supplier, tanggal, **akun bayar**.
3. Tambah item (qty + harga total), voucher/ongkir/biaya layanan opsional.
4. Status: **Dipesan → Dikirim → Diterima**. Saat **Diterima**, stok masuk + harga rata-rata terupdate.

**Contoh nyata:**
> Beli **100ml bibit Boshell Rp200.000**, bayar BCA, status Diterima.
> → Stok Boshell +100ml. Harga rata-rata: dari (4.904×1.800 + 100×2.000)/5.004 = **Rp1.804/ml**. BCA −Rp200.000.

**Aturan:** Belanja **Dibatalkan** → uang dikembalikan ke akun (sekali).

---

## 10. Keuangan

**Apa:** Kelola kas semua akun (BCA, Bank Jago, Seabank, ShopeePay, Kas Tunai, Saldo MP, dll).

- **Saldo & Cashflow:** lihat saldo tiap akun (saldo awal + masuk − keluar).
- **Modal & Prive:** setor modal pemilik (kas naik, bukan income) / prive (kas turun, bukan biaya).
- **Transfer Antar Akun:** pindah dana A→B (+ biaya transfer opsional).
- **Tarik Dana (WD):** saldo marketplace → bank.
- **Opname Kas:** set saldo akun ke jumlah fisik riil (selisih = koreksi, bukan income/biaya).

**Contoh nyata:**
> Transfer **Rp500.000** BCA → Bank Jago, biaya Rp5.000 (potong pengirim).
> → BCA −505.000, Bank Jago +500.000. Total kas −5.000 (biaya).

**Aturan:** Pengeluaran/transfer yang membuat saldo **minus** → **tetap jalan** tapi muncul peringatan saldo minus.

---

## 11. Penerimaan & Pengeluaran

- **Penerimaan Uang:** pemasukan di luar order (jual tester, patungan, dll). Kas naik, masuk "pendapatan lain" di P&L.
- **Pengeluaran:** biaya operasional (listrik, internet, **iklan**, ongkir ditanggung penjual, dll). Kas turun, masuk biaya P&L.

**Aturan:** Hapus penerimaan/pengeluaran → terekam di **Log Aktivitas** (jejak siapa & isinya tidak hilang).

---

## 12. Utang & Cicilan

**Apa:** Lacak utang yang dicicil (kartu kredit, paylater).

**Cara pakai:**
1. **Keuangan → Utang & Cicilan → Buat Utang**: pilih sumber dana, total utang, cicilan/bulan, jumlah bulan.
2. Sistem buat jadwal cicilan otomatis.
3. **Bayar cicilan:** klik bayar → isi jumlah + biaya tambahan + **akun pembayar** → kas keluar.

**Aturan penting (akuntansi):**
- Bayar cicilan **mengurangi saldo kas** (lewat akun yang dipilih).
- HAGOS pakai **cash-basis (Cara B):** biaya barang yang dibeli pakai cicilan diakui **saat bayar cicilan**. **Jangan** mencatat barang itu lagi di Belanja/Pengeluaran (nanti dobel).

---

## 13. Karyawan, Kasbon, Gaji

- **Karyawan:** data karyawan.
- **Kasbon:** beri kasbon (kas keluar, utang karyawan naik) / pelunasan (kas masuk).
- **Gaji:** bayar gaji (pokok + tunjangan − potongan). Bisa **potong kasbon** langsung.

**Contoh nyata:**
> Adzim kasbon Rp500.000 (BCA). Lalu gaji pokok Rp2.000.000, potong kasbon Rp500.000.
> → Kas keluar bersih **Rp1.500.000** (gaji bersih), sisa kasbon Adzim **lunas (0)**. Biaya gaji Rp2.000.000 masuk P&L.

---

## 14. Pelanggan (CRM)

- **Penjualan → Pelanggan:** daftar pelanggan + statistik order + piutang.
- Pesanan **non-marketplace** otomatis mendaftarkan pelanggan (nama + No. HP) → database CRM terbangun sendiri dari transaksi harian. Cocok untuk follow-up/broadcast.
- **Produk Gratis (Sampel Affiliate):** catat pemberian produk gratis ke affiliate. HPP-nya otomatis masuk **beban promosi** di P&L, stok produk jadi berkurang.

---

## 15. Master Produk

**Apa:** Kelola produk, resep (bibit, ml, tester), dan harga jual per channel.

- **Produk & Stok → Master Produk → Tambah**: pilih/buat aroma, ukuran, resep, harga per channel.
- **Mesin HPP** menghitung HPP **berbeda per channel** otomatis.

**Contoh nyata (HPP Boshell 30ml):**
| Channel | HPP |
|---|---|
| Marketplace (TikTok/Shopee) | ±Rp45.400 (ada box + tester) |
| Offline / Reseller A | ±Rp40.600 |
| Reseller B | ±Rp37.800 |
| Refill (tanpa botol) | ±Rp33.000 |

---

## 16. Laporan

- **Laporan P&L:** Omzet − HPP = Laba Kotor; dikurangi Biaya Operasional (pengeluaran, cicilan, sampel, **susut stok**) = Laba Bersih. Pilih bulan.
- **Neraca:** posisi aset/kewajiban.
- **Margin Watchdog:** pantau margin per produk.
- **Proyeksi Arus Kas** & **Biaya Marketplace**.
- **Pusat Laporan & Export:** unduh **Excel** & **PDF**.

**Catatan:** Omzet marketplace muncul di bulan **dana cair** (tgl settlement). HPP harus sudah ada (produk sudah diracik) agar margin benar.

---

## 17. Pengaturan

- **Kelola User:** tambah/edit/hapus akun, reset password.
- **Log Aktivitas (Audit Trail):** jejak siapa **membuat/mengubah/menghapus** data finansial & stok — termasuk **isi data sebelum dihapus**. Filter per aksi/user/modul/tanggal.
- **Tutup Buku (Kunci Periode):** kunci sampai tanggal tertentu → transaksi bertanggal ≤ tanggal itu **ditolak** (agar laporan bulan yang sudah final tidak berubah). Kosongkan untuk buka kunci.

**Contoh:** Setelah laporan Mei selesai, set Tutup Buku = **31 Mei 2026**. Jika ada yang coba input transaksi tanggal 15 Mei → ditolak dengan pesan jelas.

---

## 18. 🚀 Skenario GO-LIVE

Saat mulai pakai sistem dengan **data real**, ikuti urutan ini agar angka akurat sejak hari pertama.

### Langkah 0 — Reset data test
Pastikan DB sudah **bersih dari data uji** (hanya master data + akun owner). *(Minta reset jika belum.)*

### Langkah 1 — Master Data
Pastikan lengkap & benar:
- **Bibit** (nama, harga per ml) & **Komponen** (harga satuan).
- **Master Produk** (resep + harga jual per channel).
- **Akun Kas** (BCA, Bank Jago, Saldo TikTok, Saldo Shopee, dll) + **saldo awal** tiap akun.
- **Kategori** pengeluaran, **Karyawan**, **Sumber Dana** (kartu kredit/paylater).

### Langkah 2 — Stok Awal
Input **stok fisik SEKARANG** (hasil hitung/timbang riil):
- Bibit (ml), Komponen (unit), Stok Produk Jadi (T11), Tester Jadi.
- Gunakan **Opname** di Stok Bahan untuk set angkanya.
- ⚠️ Angka ini **sudah tidak termasuk** bahan untuk pesanan yang lagi jalan (karena sudah dipakai) — itu benar, jangan dihitung mundur.

### Langkah 3 — Saldo Awal Kas
Isi **saldo awal** tiap akun = saldo riil rekening/dompet saat go-live.

### Langkah 4 — Pesanan In-Flight (sudah dikirim, belum settle)
Pesanan TikTok/Shopee yang **sudah diracik & dikirim** tapi dananya belum cair:
1. **Upload file pesanan** TikTok & Shopee (yang in-flight). (Petakan SKU unmatched dulu bila ada.)
2. Buka **Gudang Racik** → centang semua → **Set aksi (terpilih) = "Sudah Dikirim (Go-Live)"** → **Proses**.
   - → HPP terhitung, pesanan jadi "Selesai Racik", **stok TIDAK dipotong** (bahan sudah terpakai sebelum sistem).
3. Saat settlement cair nanti → **upload settlement** → pesanan jadi Cair, uang masuk, **omzet + margin benar** (HPP sudah ada).

### Langkah 5 — Piutang Reseller (jika ada)
Pesanan reseller yang **belum dibayar** saat go-live:
- Input via **Input Penjualan**, status **Piutang**. Saat dibayar → buka pesanan → **Terima Pembayaran** → uang masuk.

### Langkah 6 — Mulai operasi normal
Setelah itu, semua pesanan baru ikut alur normal (upload/input → racik → settlement).

> **Tips:** setelah bulan pertama go-live selesai dan dicek, gunakan **Tutup Buku** agar angkanya terkunci.

---

## 19. Konsep Akuntansi Penting

- **Pengakuan omzet marketplace:** saat **dana cair** (tanggal settlement dari file), bukan saat order.
- **HPP:** dihitung saat racik; sudah mencakup bibit + botol + packaging + tester. Margin marketplace dihitung **final** saat settlement (net − HPP).
- **Susut stok** (opname selisih negatif) = **biaya** di P&L (kerugian non-tunai).
- **Modal/Prive** bukan income/biaya — hanya pergerakan kas pemilik.
- **Cicilan (cash-basis / Cara B):** biaya barang yang dibeli pakai kredit diakui **saat bayar cicilan**. Jangan dicatat dobel di Belanja.
- **Rekonsiliasi MP:** jaring pengaman untuk biaya siluman (iklan/denda) yang tidak nempel ke order.

---

## 20. Troubleshooting

| Masalah | Penyebab & Solusi |
|---|---|
| "Terjadi kesalahan saat memeriksa stok" di racik | Form terlalu panjang. Sudah dibatasi **50/halaman** — proses per halaman. |
| Produk tidak masuk saat upload pesanan | SKU belum dipetakan → **Mapping SKU** lalu **upload ulang**. |
| Omzet tidak muncul di bulan yang diharapkan | Omzet MP diakui di bulan **dana cair** (settlement), bukan order. Cek di bulan settlement. |
| Margin terlihat terlalu besar (mendekati 100%) | Pesanan **belum diracik** (HPP masih kosong). Racik dulu, baru margin benar. |
| Transaksi ditolak "Periode sudah dikunci" | Periode di-Tutup Buku. Buka kunci di **Pengaturan → Tutup Buku** bila perlu. |
| Saldo akun minus | Pengeluaran melebihi saldo. Cek **saldo awal** sudah diisi (Master Akun Kas) atau ada pemasukan yang belum dicatat. |
| Login terkunci | Salah password 5×. Tunggu ~1 menit lalu coba lagi. |

---

*Dokumen ini mengikuti versi sistem saat ditulis. Bila ada fitur berubah, perbarui dokumen ini.*
