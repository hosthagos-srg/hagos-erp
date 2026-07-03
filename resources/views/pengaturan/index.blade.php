<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - Hagos ERP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">
    <div class="min-h-screen p-6">
        <header class="mb-6">
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Pengaturan</h1>
            <p class="text-sm text-gray-500 mt-1">Tutup buku (kunci periode)</p>
        </header>

        @if (session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 text-sm rounded-md px-4 py-2">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-md px-4 py-2">{{ session('error') }}</div>
        @endif

        <div class="max-w-xl bg-white rounded-2xl shadow-sm ring-1 ring-gray-200/70 p-6">
            <h2 class="text-base font-bold text-gray-900 mb-1">🔒 Tutup Buku (Kunci Periode)</h2>
            <p class="text-sm text-gray-500 mb-4">
                Transaksi keuangan bertanggal <b>pada atau sebelum</b> tanggal kunci akan <b>ditolak</b>
                (penerimaan, pengeluaran, transfer, WD, modal/prive, belanja, gaji, kasbon, cicilan).
                Gunakan ini setelah laporan suatu bulan selesai agar angkanya tidak berubah lagi.
            </p>

            <div class="mb-4 rounded-lg px-4 py-3 text-sm {{ $tglKunci ? 'bg-amber-50 text-amber-800' : 'bg-gray-50 text-gray-500' }}">
                @if($tglKunci)
                    Status: <b>Terkunci sampai {{ \Carbon\Carbon::parse($tglKunci)->format('d M Y') }}</b>
                @else
                    Status: <b>Tidak ada periode terkunci</b> (semua tanggal boleh).
                @endif
            </div>

            <form method="POST" action="{{ route('pengaturan.kunci_buku') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kunci sampai tanggal</label>
                    <input type="date" name="tgl_kunci" value="{{ $tglKunci }}"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <p class="text-xs text-gray-400 mt-1">Kosongkan lalu simpan untuk <b>membuka</b> kunci.</p>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-5 py-2 rounded-md">Simpan</button>
                    @if($tglKunci)
                        <button type="submit" name="tgl_kunci" value="" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold px-5 py-2 rounded-md">Buka Kunci</button>
                    @endif
                </div>
            </form>
        </div>

        {{-- ─── DANGER ZONE: Reset Data ─────────────────────── --}}
        <div class="max-w-xl mt-8 bg-white rounded-2xl shadow-sm ring-1 ring-red-200 p-6">
            <h2 class="text-base font-bold text-red-700 mb-1">⚠️ Reset Data (Zona Berbahaya)</h2>
            <p class="text-sm text-gray-600 mb-3">
                Menghapus <b>SEMUA data transaksi</b> (pesanan, mutasi kas, settlement, racik, belanja, gaji/kasbon,
                utang, audit log, pelanggan, dll) dan <b>menolkan stok &amp; saldo awal</b>.
                <br><b class="text-gray-800">Yang TETAP ada:</b> master produk/resep/bibit/komponen/akun/kategori, karyawan, sumber dana, mapping SKU, dan akun user.
            </p>
            <div class="mb-4 rounded-lg bg-red-50 text-red-700 text-xs px-4 py-3">
                🚨 <b>Tidak bisa dibatalkan.</b> Pakai ini hanya untuk membersihkan data uji sebelum go-live.
                Setelah memakai data real, <b>backup database dulu</b> sebelum reset.
            </div>
            <form method="POST" action="{{ route('pengaturan.reset_data') }}" class="space-y-3"
                  onsubmit="return confirm('YAKIN reset? Semua data transaksi akan dihapus permanen & stok/saldo dinolkan. Tindakan ini tidak bisa dibatalkan.');">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ketik <b class="text-red-700">RESET</b> untuk konfirmasi</label>
                    <input type="text" name="konfirmasi" autocomplete="off" placeholder="RESET"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500">
                </div>
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-5 py-2 rounded-md">
                    Hapus Semua Data Transaksi &amp; Nolkan Stok/Saldo
                </button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
