<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hagos ERP - Laporan P&L</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">

@include('partials.notifikasi_cicilan')

<div class="min-h-screen p-6">

    <header class="mb-6 flex flex-wrap justify-between items-start gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Laporan Laba Rugi (P&L)</h1>
            <p class="text-gray-500 mt-1">{{ $periode->translatedFormat('F Y') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <form method="GET" action="{{ route('laporan.pl') }}" class="flex items-center gap-2">
                <label class="text-sm text-gray-600 font-medium">Periode:</label>
                <select name="bulan" onchange="this.form.submit()"
                    class="border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @foreach($bulanTersedia as $b)
                        <option value="{{ $b }}" {{ $b === $bulan ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::createFromFormat('Y-m', $b)->translatedFormat('F Y') }}
                        </option>
                    @endforeach
                    @if($bulanTersedia->isEmpty())
                        <option value="{{ $bulan }}" selected>{{ $periode->translatedFormat('F Y') }}</option>
                    @endif
                </select>
            </form>
            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-3 py-1.5 bg-gray-700 rounded-md text-xs font-semibold text-white hover:bg-gray-800">← Dashboard</a>
        </div>
    </header>

    {{-- ─── KARTU RINGKASAN ─────────────────────────────────── --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-indigo-500">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Total Pesanan</p>
            <p class="text-3xl font-bold text-indigo-600 mt-1">{{ $totalPesanan }}</p>
            <p class="text-xs text-gray-400 mt-1">pesanan terbayar</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-blue-500">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Net Omset</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">{{ 'Rp ' . number_format($totalOmset, 0, ',', '.') }}</p>
            <p class="text-xs text-gray-400 mt-1">setelah potongan</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-emerald-500">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Laba Kotor</p>
            <p class="text-2xl font-bold {{ $labaKotor >= 0 ? 'text-emerald-600' : 'text-red-600' }} mt-1">
                {{ 'Rp ' . number_format($labaKotor, 0, ',', '.') }}
            </p>
            <p class="text-xs text-gray-400 mt-1">margin {{ number_format($marginKotor, 1) }}%</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 {{ $labaBersih >= 0 ? 'border-teal-500' : 'border-red-500' }}">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Laba Bersih</p>
            <p class="text-2xl font-bold {{ $labaBersih >= 0 ? 'text-teal-600' : 'text-red-600' }} mt-1">
                {{ 'Rp ' . number_format($labaBersih, 0, ',', '.') }}
            </p>
            <p class="text-xs text-gray-400 mt-1">margin {{ number_format($marginBersih, 1) }}%</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ─── LAPORAN P&L UTAMA ──────────────────────────── --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- PENDAPATAN --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-3 bg-blue-50 border-b border-blue-100">
                    <h2 class="font-bold text-blue-800">📈 PENDAPATAN</h2>
                </div>
                <div class="divide-y divide-gray-100">
                    <div class="px-6 py-3 flex justify-between items-center">
                        <div>
                            <p class="text-sm font-medium text-gray-700">Omset Marketplace</p>
                            <p class="text-xs text-gray-400">Cair bulan ini · Net settlement</p>
                        </div>
                        <p class="font-semibold text-gray-900">Rp {{ number_format($omsetMP, 0, ',', '.') }}</p>
                    </div>
                    <div class="px-6 py-3 flex justify-between items-center">
                        <div>
                            <p class="text-sm font-medium text-gray-700">Omset Non-Marketplace</p>
                            <p class="text-xs text-gray-400">WA/COD/Offline · Lunas bulan ini</p>
                        </div>
                        <p class="font-semibold text-gray-900">Rp {{ number_format($omsetNonMP, 0, ',', '.') }}</p>
                    </div>
                    <div class="px-6 py-3 flex justify-between items-center bg-blue-50">
                        <p class="font-bold text-blue-800">Total Net Omset</p>
                        <p class="font-bold text-blue-800 text-lg">Rp {{ number_format($totalOmset, 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>

            {{-- HPP --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-3 bg-orange-50 border-b border-orange-100">
                    <h2 class="font-bold text-orange-800">📦 HARGA POKOK PENJUALAN (HPP)</h2>
                </div>
                <div class="divide-y divide-gray-100">
                    <div class="px-6 py-3 flex justify-between items-center">
                        <div>
                            <p class="text-sm font-medium text-gray-700">HPP Penjualan</p>
                            <p class="text-xs text-gray-400">Bibit + Komponen + Botol + Sticker per botol terjual</p>
                        </div>
                        <p class="font-semibold text-orange-700">Rp {{ number_format($totalHpp, 0, ',', '.') }}</p>
                    </div>
                    <div class="px-6 py-3 flex justify-between items-center {{ $labaKotor >= 0 ? 'bg-emerald-50' : 'bg-red-50' }}">
                        <div>
                            <p class="font-bold {{ $labaKotor >= 0 ? 'text-emerald-800' : 'text-red-800' }}">Laba Kotor</p>
                            <p class="text-xs {{ $labaKotor >= 0 ? 'text-emerald-600' : 'text-red-500' }}">Margin {{ number_format($marginKotor, 1) }}%</p>
                        </div>
                        <p class="font-bold text-lg {{ $labaKotor >= 0 ? 'text-emerald-700' : 'text-red-700' }}">Rp {{ number_format($labaKotor, 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>

            {{-- PENDAPATAN LAIN-LAIN --}}
            @if($totalPenerimaan > 0)
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-3 bg-teal-50 border-b border-teal-100">
                    <h2 class="font-bold text-teal-800">➕ PENDAPATAN LAIN-LAIN</h2>
                </div>
                <div class="px-6 py-3 flex justify-between items-center">
                    <div>
                        <p class="text-sm font-medium text-gray-700">Penerimaan lain (jual tester, patungan, dll)</p>
                        <p class="text-xs text-gray-400">Di luar order penjualan</p>
                    </div>
                    <p class="font-semibold text-teal-700">+ Rp {{ number_format($totalPenerimaan, 0, ',', '.') }}</p>
                </div>
            </div>
            @endif

            {{-- BIAYA OPERASIONAL --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-3 bg-red-50 border-b border-red-100">
                    <h2 class="font-bold text-red-800">💸 BIAYA OPERASIONAL</h2>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse($pengeluaran as $item)
                    <div class="px-6 py-3 flex justify-between items-center">
                        <p class="text-sm text-gray-700">{{ $item->nama_kategori }}</p>
                        <p class="font-semibold text-red-700">Rp {{ number_format($item->total, 0, ',', '.') }}</p>
                    </div>
                    @empty
                    <div class="px-6 py-3 text-sm text-gray-400">— Belum ada pengeluaran bulan ini</div>
                    @endforelse

                    @if($totalCicilan > 0)
                    <div class="px-6 py-3 flex justify-between items-center">
                        <div>
                            <p class="text-sm text-gray-700">Cicilan & Biaya Kartu</p>
                            <p class="text-xs text-gray-400">{{ $cicilanDibayar->count() }} pembayaran cicilan</p>
                        </div>
                        <p class="font-semibold text-red-700">Rp {{ number_format($totalCicilan, 0, ',', '.') }}</p>
                    </div>
                    @endif

                    @if($totalSampel > 0)
                    <div class="px-6 py-3 flex justify-between items-center">
                        <div>
                            <p class="text-sm text-gray-700">Biaya Promo/Sampel Affiliate</p>
                            <p class="text-xs text-gray-400">HPP produk gratis (non-tunai)</p>
                        </div>
                        <p class="font-semibold text-red-700">Rp {{ number_format($totalSampel, 0, ',', '.') }}</p>
                    </div>
                    @endif

                    @if($totalSusut > 0)
                    <div class="px-6 py-3 flex justify-between items-center">
                        <div>
                            <p class="text-sm text-gray-700">Susut Stok (opname)</p>
                            <p class="text-xs text-gray-400">Nilai selisih opname yang hilang (non-tunai)</p>
                        </div>
                        <p class="font-semibold text-red-700">Rp {{ number_format($totalSusut, 0, ',', '.') }}</p>
                    </div>
                    @endif

                    @if(($totalPatungan ?? 0) > 0)
                    <div class="px-6 py-3 flex justify-between items-center bg-emerald-50/40">
                        <div>
                            <p class="text-sm text-gray-700">(−) Patungan biaya bersama (mis. 420F)</p>
                            <p class="text-xs text-gray-400">Kontribusi mitra utk sewa/listrik/internet — mengurangi biaya, bukan pendapatan</p>
                        </div>
                        <p class="font-semibold text-emerald-700">− Rp {{ number_format($totalPatungan, 0, ',', '.') }}</p>
                    </div>
                    @endif

                    @if($totalBiayaOps == 0 && ($totalPatungan ?? 0) == 0)
                    <div class="px-6 py-3 text-sm text-gray-400">— Belum ada data biaya operasional</div>
                    @endif

                    <div class="px-6 py-3 flex justify-between items-center bg-red-50">
                        <p class="font-bold text-red-800">Total Biaya Operasional (bersih)</p>
                        <p class="font-bold text-red-800 text-lg">Rp {{ number_format($totalBiayaOps, 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>

            {{-- LABA BERSIH --}}
            <div class="rounded-xl shadow-sm overflow-hidden {{ $labaBersih >= 0 ? 'bg-teal-600' : 'bg-red-600' }}">
                <div class="px-6 py-5 flex justify-between items-center">
                    <div>
                        <p class="text-white text-sm font-semibold opacity-80">LABA BERSIH</p>
                        <p class="text-white text-xs opacity-60 mt-0.5">Omset − HPP − Biaya Operasional · Margin {{ number_format($marginBersih, 1) }}%</p>
                    </div>
                    <p class="text-white text-3xl font-bold">Rp {{ number_format($labaBersih, 0, ',', '.') }}</p>
                </div>
            </div>

        </div>

        {{-- ─── KOLOM KANAN ─────────────────────────────────── --}}
        <div class="space-y-4">

            {{-- Statistik Pesanan --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100">
                    <h2 class="font-bold text-gray-800 text-sm">📋 Statistik Pesanan Bulan Ini</h2>
                </div>
                <div class="p-5 space-y-2">
                    @php
                        $statusColors = [
                            'Selesai Racik' => 'bg-emerald-100 text-emerald-700',
                            'Menunggu'      => 'bg-yellow-100 text-yellow-700',
                            'Batal'         => 'bg-red-100 text-red-700',
                            'Retur'         => 'bg-orange-100 text-orange-700',
                        ];
                    @endphp
                    @forelse($statPesanan as $status => $jumlah)
                    <div class="flex justify-between items-center">
                        <span class="text-xs px-2 py-0.5 rounded font-semibold {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-600' }}">{{ $status }}</span>
                        <span class="font-bold text-gray-700">{{ $jumlah }}</span>
                    </div>
                    @empty
                    <p class="text-sm text-gray-400">Tidak ada pesanan bulan ini.</p>
                    @endforelse
                </div>
            </div>

            {{-- Omset per Channel --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100">
                    <h2 class="font-bold text-gray-800 text-sm">🏪 Omset per Channel</h2>
                </div>
                @if($omsetPerChannel->isEmpty())
                <div class="px-5 py-4 text-sm text-gray-400">Belum ada data.</div>
                @else
                <div class="divide-y divide-gray-100">
                    @foreach($omsetPerChannel as $ch)
                    <div class="px-5 py-3">
                        <div class="flex justify-between items-center mb-1">
                            <p class="text-sm font-medium text-gray-700">{{ $ch->channel }}</p>
                            <p class="text-sm font-bold text-gray-900">{{ $ch->jml }} pesanan</p>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="text-xs text-gray-400">Net</p>
                            <p class="text-sm font-semibold text-blue-700">Rp {{ number_format($ch->net, 0, ',', '.') }}</p>
                        </div>
                        @if($totalOmset > 0)
                        <div class="mt-1.5 w-full bg-gray-100 rounded-full h-1.5">
                            <div class="bg-blue-500 h-1.5 rounded-full" style="width: {{ min(100, ($ch->net / $totalOmset) * 100) }}%"></div>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Cicilan dibayar bulan ini --}}
            @if($cicilanDibayar->isNotEmpty())
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100">
                    <h2 class="font-bold text-gray-800 text-sm">💳 Cicilan Dibayar Bulan Ini</h2>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach($cicilanDibayar as $c)
                    <div class="px-5 py-3">
                        <p class="text-xs text-gray-500">{{ $c->utangCicilan->sumberDana->nama }}</p>
                        <p class="text-sm font-medium text-gray-800">{{ $c->utangCicilan->deskripsi }}</p>
                        <div class="flex justify-between mt-0.5">
                            <p class="text-xs text-gray-400">Pokok + biaya</p>
                            <p class="text-sm font-semibold text-red-700">
                                Rp {{ number_format($c->jumlah_bayar + $c->biaya_tambahan, 0, ',', '.') }}
                            </p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

        </div>
    </div>
</div>
</div>
</body>
</html>
