<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PenjualanController;
use App\Http\Controllers\RacikController;
use App\Http\Controllers\ProduksiInternalController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\Auth\LoginController;

// ─── Autentikasi (publik) ───────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
});
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// ─── Semua route bisnis wajib login ─────────────────────
Route::middleware('auth')->group(function () {

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard/grafik', [DashboardController::class, 'grafikData'])->name('dashboard.grafik');
Route::post('/dashboard/target', [DashboardController::class, 'simpanTarget'])->name('dashboard.target');
Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');
Route::get('/dashboard/expense-breakdown', [DashboardController::class, 'expenseBreakdown'])->name('dashboard.expense_breakdown');

// Penjualan
Route::get('/penjualan', [PenjualanController::class, 'index'])->name('penjualan.index');
Route::get('/penjualan/create', [PenjualanController::class, 'create'])->name('penjualan.create');
Route::get('/penjualan/harga-lookup', [PenjualanController::class, 'hargaLookup'])->name('penjualan.harga_lookup');
Route::get('/penjualan/perlu-cek', [PenjualanController::class, 'perluCek'])->name('penjualan.perlu_cek');
Route::get('/penjualan/perlu-barang-balik', [PenjualanController::class, 'perluBarangBalik'])->name('penjualan.perlu_barang_balik');
Route::post('/penjualan', [PenjualanController::class, 'store'])->name('penjualan.store');
Route::get('/penjualan/{internal_id}', [PenjualanController::class, 'show'])->name('penjualan.show');
Route::post('/penjualan/{internal_id}/status', [PenjualanController::class, 'updateStatus'])->name('penjualan.update_status');
Route::post('/penjualan/{internal_id}/extra-tester', [PenjualanController::class, 'updateExtraTester'])->name('penjualan.update_tester');
Route::delete('/penjualan/{internal_id}', [PenjualanController::class, 'destroy'])->name('penjualan.destroy');

// Pelanggan / Reseller (CRM)
Route::get('/pelanggan', [App\Http\Controllers\PelangganController::class, 'index'])->name('pelanggan.index');
Route::post('/pelanggan', [App\Http\Controllers\PelangganController::class, 'store'])->name('pelanggan.store');
Route::post('/pelanggan/import', [App\Http\Controllers\PelangganController::class, 'import'])->name('pelanggan.import');
Route::get('/pelanggan-kinerja', [App\Http\Controllers\PelangganController::class, 'kinerja'])->name('pelanggan.kinerja');
Route::get('/pelanggan/{pelanggan}', [App\Http\Controllers\PelangganController::class, 'show'])->name('pelanggan.show');
Route::post('/pelanggan/{pelanggan}', [App\Http\Controllers\PelangganController::class, 'update'])->name('pelanggan.update');
Route::delete('/pelanggan/{pelanggan}', [App\Http\Controllers\PelangganController::class, 'destroy'])->name('pelanggan.destroy');

// Produk Gratis / Sampel Affiliate
Route::get('/sampel', [App\Http\Controllers\SampelController::class, 'index'])->name('sampel.index');
Route::post('/sampel', [App\Http\Controllers\SampelController::class, 'store'])->name('sampel.store');
Route::delete('/sampel/{sampel}', [App\Http\Controllers\SampelController::class, 'destroy'])->name('sampel.destroy');

// Peracikan (Gudang)
Route::get('/racik', [RacikController::class, 'index'])->name('racik.index');
Route::get('/racik/riwayat', [RacikController::class, 'riwayat'])->name('racik.riwayat');
Route::post('/racik/check-stock', [RacikController::class, 'checkStock'])->name('racik.check_stock');
Route::post('/racik/adjust-stock', [RacikController::class, 'adjustStock'])->name('racik.adjust_stock');
Route::post('/racik', [RacikController::class, 'process'])->name('racik.process');

// Produksi Internal (Tester & Absolute)
Route::get('/produksi', [ProduksiInternalController::class, 'index'])->name('produksi.index');
Route::get('/produksi/riwayat', [ProduksiInternalController::class, 'riwayat'])->name('produksi.riwayat');
Route::post('/produksi/tester/check-stock', [ProduksiInternalController::class, 'checkStock'])->name('produksi.tester.check_stock');
Route::post('/produksi/tester/adjust-stock', [ProduksiInternalController::class, 'adjustStock'])->name('produksi.tester.adjust_stock');
Route::post('/produksi/absolute', [ProduksiInternalController::class, 'storeAbsolute'])->name('produksi.absolute.store');
Route::post('/produksi/tester', [ProduksiInternalController::class, 'storeTester'])->name('produksi.tester.store');

// Stok Produk Jadi (T11)
Route::get('/stok-jadi', [App\Http\Controllers\StokJadiController::class, 'index'])->name('stok_jadi.index');
Route::post('/stok-jadi', [App\Http\Controllers\StokJadiController::class, 'store'])->name('stok_jadi.store');
Route::post('/stok-jadi/opname', [App\Http\Controllers\StokJadiController::class, 'opname'])->name('stok_jadi.opname');

// Saldo & Cashflow
Route::get('/saldo', [App\Http\Controllers\SaldoController::class, 'index'])->name('saldo.index');
Route::get('/saldo/modal', [App\Http\Controllers\SaldoController::class, 'modalForm'])->name('saldo.modal_form');
Route::post('/saldo/modal/setor', [App\Http\Controllers\SaldoController::class, 'storeModal'])->name('saldo.modal_setor');
Route::post('/saldo/modal/prive', [App\Http\Controllers\SaldoController::class, 'storePrive'])->name('saldo.modal_prive');
Route::get('/saldo/withdrawal', [App\Http\Controllers\SaldoController::class, 'withdrawalForm'])->name('saldo.withdrawal_form');
Route::get('/saldo/transfer', [App\Http\Controllers\SaldoController::class, 'transferForm'])->name('saldo.transfer_form');
Route::post('/saldo/transfer', [App\Http\Controllers\SaldoController::class, 'transfer'])->name('saldo.transfer');
Route::post('/saldo/pengeluaran', [App\Http\Controllers\SaldoController::class, 'pengeluaran'])->name('saldo.pengeluaran');
Route::post('/saldo/patungan', [App\Http\Controllers\SaldoController::class, 'patungan'])->name('saldo.patungan');
Route::post('/saldo/opname-kas', [App\Http\Controllers\SaldoController::class, 'opnameKas'])->name('saldo.opname_kas');
Route::post('/saldo/withdrawal', [App\Http\Controllers\SaldoController::class, 'withdrawal'])->name('saldo.withdrawal');

// Belanja Bibit & Komponen (T2/T3)
Route::get('/belanja', [App\Http\Controllers\BelanjaController::class, 'index'])->name('belanja.index');
Route::get('/belanja/create', [App\Http\Controllers\BelanjaController::class, 'create'])->name('belanja.create');
Route::post('/belanja', [App\Http\Controllers\BelanjaController::class, 'store'])->name('belanja.store');
Route::post('/belanja/{belanja_id}/status', [App\Http\Controllers\BelanjaController::class, 'updateStatus'])->name('belanja.update_status');
// Penerimaan per item + retur + selesai + hapus bersih
Route::post('/belanja/item/{batch_id}/terima', [App\Http\Controllers\BelanjaController::class, 'terimaItem'])->name('belanja.item_terima');
Route::post('/belanja/item/{batch_id}/retur', [App\Http\Controllers\BelanjaController::class, 'returItem'])->name('belanja.item_retur');
Route::post('/belanja/item/{batch_id}/refund', [App\Http\Controllers\BelanjaController::class, 'refundItem'])->name('belanja.item_refund');
Route::post('/belanja/{belanja_id}/selesai', [App\Http\Controllers\BelanjaController::class, 'selesai'])->name('belanja.selesai');
Route::delete('/belanja/{belanja_id}', [App\Http\Controllers\BelanjaController::class, 'destroy'])->name('belanja.destroy');

// Margin Watchdog
Route::get('/margin', [App\Http\Controllers\MarginController::class, 'index'])->name('margin.index');

// Resep Mix (racikan multi-bibit)
Route::get('/resep-mix', [App\Http\Controllers\ResepMixController::class, 'index'])->name('resep_mix.index');
Route::post('/resep-mix', [App\Http\Controllers\ResepMixController::class, 'store'])->name('resep_mix.store');
Route::post('/resep-mix/preview', [App\Http\Controllers\ResepMixController::class, 'preview'])->name('resep_mix.preview');
Route::delete('/resep-mix/{sku}', [App\Http\Controllers\ResepMixController::class, 'destroy'])->name('resep_mix.destroy');

// Master Produk
Route::get('/master-produk', [App\Http\Controllers\MasterProdukController::class, 'index'])->name('master_produk.index');
Route::get('/master-produk/create', [App\Http\Controllers\MasterProdukController::class, 'create'])->name('master_produk.create');
Route::post('/master-produk/hpp-preview', [App\Http\Controllers\MasterProdukController::class, 'hppPreview'])->name('master_produk.hpp_preview');
Route::post('/master-produk', [App\Http\Controllers\MasterProdukController::class, 'store'])->name('master_produk.store');
Route::get('/master-produk/aroma/{aroma}', [App\Http\Controllers\MasterProdukController::class, 'detail'])->name('master_produk.detail');
Route::post('/master-produk/aroma/{aroma}/bibit', [App\Http\Controllers\MasterProdukController::class, 'updateBibit'])->name('master_produk.update_bibit');
Route::post('/master-produk/produk/{sku}/detail', [App\Http\Controllers\MasterProdukController::class, 'updateProduk'])->name('master_produk.update_produk');
Route::post('/master-produk/produk/{sku}/harga', [App\Http\Controllers\MasterProdukController::class, 'updateHarga'])->name('master_produk.update_harga');

// Karyawan, Kasbon, Gaji
Route::get('/karyawan', [App\Http\Controllers\KaryawanController::class, 'index'])->name('karyawan.index');
Route::post('/karyawan', [App\Http\Controllers\KaryawanController::class, 'store'])->name('karyawan.store');
Route::get('/karyawan/{karyawan}', [App\Http\Controllers\KaryawanController::class, 'show'])->name('karyawan.show');
Route::post('/karyawan/{karyawan}', [App\Http\Controllers\KaryawanController::class, 'update'])->name('karyawan.update');
Route::get('/kasbon', [App\Http\Controllers\KasbonController::class, 'index'])->name('kasbon.index');
Route::post('/kasbon', [App\Http\Controllers\KasbonController::class, 'store'])->name('kasbon.store');
Route::post('/kasbon/bayar', [App\Http\Controllers\KasbonController::class, 'bayar'])->name('kasbon.bayar');
Route::get('/gaji', [App\Http\Controllers\GajiController::class, 'index'])->name('gaji.index');
Route::post('/gaji', [App\Http\Controllers\GajiController::class, 'store'])->name('gaji.store');
Route::put('/gaji/{gaji}', [App\Http\Controllers\GajiController::class, 'update'])->name('gaji.update');

// Piutang & Aging
Route::get('/piutang', [App\Http\Controllers\PiutangController::class, 'index'])->name('piutang.index');

// Piutang Pribadi (pinjaman ke kerabat/keluarga)
Route::get('/piutang-pribadi', [App\Http\Controllers\PiutangPribadiController::class, 'index'])->name('piutang_pribadi.index');
Route::post('/piutang-pribadi', [App\Http\Controllers\PiutangPribadiController::class, 'store'])->name('piutang_pribadi.store');
Route::post('/piutang-pribadi/{piutang}/bayar', [App\Http\Controllers\PiutangPribadiController::class, 'bayar'])->name('piutang_pribadi.bayar');
Route::delete('/piutang-pribadi/bayar/{bayar}', [App\Http\Controllers\PiutangPribadiController::class, 'hapusBayar'])->name('piutang_pribadi.hapus_bayar');
Route::delete('/piutang-pribadi/{piutang}', [App\Http\Controllers\PiutangPribadiController::class, 'destroy'])->name('piutang_pribadi.destroy');

// Utang Pribadi (Hagos meminjam uang tunai dari orang — kebalikan Piutang Pribadi)
Route::get('/utang-pribadi', [App\Http\Controllers\UtangPribadiController::class, 'index'])->name('utang_pribadi.index');
Route::post('/utang-pribadi', [App\Http\Controllers\UtangPribadiController::class, 'store'])->name('utang_pribadi.store');
Route::post('/utang-pribadi/{utang}/bayar', [App\Http\Controllers\UtangPribadiController::class, 'bayar'])->name('utang_pribadi.bayar');
Route::delete('/utang-pribadi/bayar/{bayar}', [App\Http\Controllers\UtangPribadiController::class, 'hapusBayar'])->name('utang_pribadi.hapus_bayar');
Route::delete('/utang-pribadi/{utang}', [App\Http\Controllers\UtangPribadiController::class, 'destroy'])->name('utang_pribadi.destroy');

// Proyeksi Arus Kas
Route::get('/proyeksi-kas', [App\Http\Controllers\ProyeksiKasController::class, 'index'])->name('proyeksi_kas.index');

// Penerimaan Uang (pemasukan lain di luar order: jual tester, patungan, dll)
Route::get('/penerimaan', [App\Http\Controllers\PenerimaanController::class, 'index'])->name('penerimaan.index');
Route::post('/penerimaan', [App\Http\Controllers\PenerimaanController::class, 'store'])->name('penerimaan.store');
Route::delete('/penerimaan/{mutasi}', [App\Http\Controllers\PenerimaanController::class, 'destroy'])->name('penerimaan.destroy');

// Pengeluaran
Route::get('/pengeluaran', [App\Http\Controllers\PengeluaranController::class, 'index'])->name('pengeluaran.index');
Route::post('/pengeluaran', [App\Http\Controllers\PengeluaranController::class, 'store'])->name('pengeluaran.store');
Route::delete('/pengeluaran/{mutasi}', [App\Http\Controllers\PengeluaranController::class, 'destroy'])->name('pengeluaran.destroy');

// Operasi baris pesanan (pecah bundle / mix custom) — sebelum racik
Route::post('/pesanan/detail/{detail}/split-bundle', [App\Http\Controllers\PesananLineController::class, 'splitBundle'])->name('pesanan.split_bundle');
Route::post('/pesanan/detail/{detail}/set-mix', [App\Http\Controllers\PesananLineController::class, 'setMix'])->name('pesanan.set_mix');
Route::post('/pesanan/detail/{detail}/change-aroma', [App\Http\Controllers\PesananLineController::class, 'changeAroma'])->name('pesanan.change_aroma');
Route::delete('/pesanan/detail/{detail}/clear-mix', [App\Http\Controllers\PesananLineController::class, 'clearMix'])->name('pesanan.clear_mix');

// Budgeting (Anggaran Mingguan — amplop ketat, reset bulanan)
Route::get('/budgeting', [App\Http\Controllers\BudgetingController::class, 'index'])->name('budgeting.index');
Route::post('/budgeting', [App\Http\Controllers\BudgetingController::class, 'store'])->name('budgeting.store');
Route::delete('/budgeting/{anggaran}', [App\Http\Controllers\BudgetingController::class, 'destroy'])->name('budgeting.destroy');

// Stok Bahan (Bibit & Komponen)
Route::get('/stok', [App\Http\Controllers\StokController::class, 'index'])->name('stok.index');
Route::post('/stok/koreksi', [App\Http\Controllers\StokController::class, 'koreksi'])->name('stok.koreksi');
Route::post('/stok/bibit/{bibit}/update', [App\Http\Controllers\StokController::class, 'updateBibit'])->name('stok.update_bibit');
Route::post('/stok/komponen/{komponen}/update', [App\Http\Controllers\StokController::class, 'updateKomponen'])->name('stok.update_komponen');

// Laporan P&L
Route::get('/laporan/pl', [App\Http\Controllers\LaporanController::class, 'pl'])->name('laporan.pl');
Route::get('/laporan/laba-produk', [App\Http\Controllers\LaporanController::class, 'labaProduk'])->name('laporan.laba_produk');
Route::get('/laporan/perputaran-bibit', [App\Http\Controllers\LaporanController::class, 'perputaranBibit'])->name('laporan.perputaran_bibit');
Route::get('/laporan/retur', [App\Http\Controllers\LaporanController::class, 'retur'])->name('laporan.retur');
Route::get('/laporan/diskon', [App\Http\Controllers\LaporanController::class, 'diskon'])->name('laporan.diskon');
Route::get('/laporan/afiliasi', [App\Http\Controllers\LaporanController::class, 'afiliasi'])->name('laporan.afiliasi');
Route::get('/laporan/pajak', [App\Http\Controllers\LaporanController::class, 'pajak'])->name('laporan.pajak');
Route::get('/laporan/bibit-terpakai', [App\Http\Controllers\LaporanBibitController::class, 'index'])->name('laporan.bibit');
Route::get('/neraca', [App\Http\Controllers\NeracaController::class, 'index'])->name('neraca.index');
Route::get('/biaya-marketplace', [App\Http\Controllers\BiayaMarketplaceController::class, 'index'])->name('biaya_marketplace.index');

// Pusat Laporan & Export
Route::get('/laporan/export', [App\Http\Controllers\LaporanExportController::class, 'index'])->name('laporan.export');
Route::get('/laporan/export/excel', [App\Http\Controllers\LaporanExportController::class, 'excel'])->name('laporan.export.excel');
Route::get('/laporan/export/pdf', [App\Http\Controllers\LaporanExportController::class, 'pdf'])->name('laporan.export.pdf');

// Utang & Cicilan
Route::get('/utang', [App\Http\Controllers\UtangController::class, 'index'])->name('utang.index');
Route::get('/utang/create', [App\Http\Controllers\UtangController::class, 'create'])->name('utang.create');
Route::post('/utang', [App\Http\Controllers\UtangController::class, 'store'])->name('utang.store');
Route::get('/utang/{utang}', [App\Http\Controllers\UtangController::class, 'show'])->name('utang.show');
Route::post('/utang/cicilan/{cicilan}/bayar', [App\Http\Controllers\UtangController::class, 'bayar'])->name('utang.bayar');

// Upload Marketplace & Settlement
Route::get('/upload', [UploadController::class, 'index'])->name('upload.index');
Route::post('/upload/pesanan', [UploadController::class, 'processPesanan'])->name('upload.pesanan');
Route::post('/upload/settlement', [UploadController::class, 'processSettlement'])->name('upload.settlement');

// Mapping SKU
Route::get('/upload/mapping', [App\Http\Controllers\MappingController::class, 'index'])->name('mapping.index');
Route::post('/upload/mapping', [App\Http\Controllers\MappingController::class, 'store'])->name('mapping.store');
Route::get('/upload/mapping/kelola', [App\Http\Controllers\MappingController::class, 'kelola'])->name('mapping.kelola');
Route::post('/upload/mapping/reset-all', [App\Http\Controllers\MappingController::class, 'resetAll'])->name('mapping.reset_all');
Route::delete('/upload/mapping/dangling', [App\Http\Controllers\MappingController::class, 'destroyDangling'])->name('mapping.destroy_dangling');
Route::delete('/upload/mapping/{id}', [App\Http\Controllers\MappingController::class, 'destroy'])->name('mapping.destroy');

// Pengaturan - Kelola User
Route::get('/users', [App\Http\Controllers\UserController::class, 'index'])->name('users.index');
Route::post('/users', [App\Http\Controllers\UserController::class, 'store'])->name('users.store');
Route::put('/users/{user}', [App\Http\Controllers\UserController::class, 'update'])->name('users.update');
Route::delete('/users/{user}', [App\Http\Controllers\UserController::class, 'destroy'])->name('users.destroy');

// Log Aktivitas (audit trail)
Route::get('/log-aktivitas', [App\Http\Controllers\AuditLogController::class, 'index'])->name('audit.index');

// Pengaturan (tutup buku / kunci periode)
Route::get('/pengaturan', [App\Http\Controllers\PengaturanController::class, 'index'])->name('pengaturan.index');
Route::post('/pengaturan/kunci-buku', [App\Http\Controllers\PengaturanController::class, 'kunciBuku'])->name('pengaturan.kunci_buku');
Route::post('/pengaturan/reset-data', [App\Http\Controllers\PengaturanController::class, 'resetData'])->name('pengaturan.reset_data');

// Rekonsiliasi Marketplace
Route::get('/rekonsiliasi-mp', [App\Http\Controllers\RekonsiliasiMpController::class, 'index'])->name('rekonsiliasi.index');
Route::post('/rekonsiliasi-mp', [App\Http\Controllers\RekonsiliasiMpController::class, 'store'])->name('rekonsiliasi.store');

}); // ← akhir grup auth
