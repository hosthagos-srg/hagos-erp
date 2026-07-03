<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Laba per Produk - Hagos ERP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">

@php
    $rp = fn($n) => 'Rp ' . number_format($n, 0, ',', '.');
    $bulanID = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
@endphp

<div class="min-h-screen p-6 max-w-6xl mx-auto">
    <header class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">🏆 Laba per {{ $group === 'sku' ? 'Produk (SKU)' : 'Aroma' }}</h1>
            <p class="text-gray-500 mt-1">Peringkat profitabilitas {{ $bulanID[(int)$periode->month] }} {{ $periode->year }} — omzet, HPP, dan laba tiap {{ $group === 'sku' ? 'SKU' : 'aroma' }} (urut laba tertinggi). Fokuskan stok & promo ke yang paling untung.</p>
        </div>
        <a href="{{ route('laporan.pl') }}" class="text-indigo-600 hover:text-indigo-900 text-sm">Laporan P&amp;L →</a>
    </header>

    {{-- Filter --}}
    <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('laporan.laba_produk') }}" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Bulan</label>
                <select name="bulan" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm">
                    @forelse($bulanTersedia as $b)
                        <option value="{{ $b }}" {{ $bulan === $b ? 'selected' : '' }}>{{ $bulanID[(int)substr($b,5,2)] }} {{ substr($b,0,4) }}</option>
                    @empty
                        <option value="{{ $bulan }}">{{ $bulan }}</option>
                    @endforelse
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Dikelompokkan per</label>
                <select name="group" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm bg-white">
                    <option value="aroma" {{ $group === 'aroma' ? 'selected' : '' }}>Aroma (gabung semua ukuran)</option>
                    <option value="sku" {{ $group === 'sku' ? 'selected' : '' }}>SKU (per ukuran)</option>
                </select>
            </div>
            <button type="submit" class="bg-indigo-600 text-white px-4 py-1.5 rounded-md text-sm font-semibold hover:bg-indigo-700">Terapkan</button>
        </form>
    </div>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-500">Total Omzet (net)</p>
            <p class="text-xl font-bold text-gray-900">{{ $rp($totOmzet) }}</p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-500">Total HPP</p>
            <p class="text-xl font-bold text-orange-600">{{ $rp($totHpp) }}</p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-emerald-200 shadow-sm p-4">
            <p class="text-xs text-gray-500">Total Laba Kotor</p>
            <p class="text-xl font-bold {{ $totLaba >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $rp($totLaba) }}</p>
            <p class="text-xs text-gray-400">margin {{ number_format($marginTotal, 1) }}%</p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-500">Total Terjual</p>
            <p class="text-xl font-bold text-indigo-600">{{ number_format($totQty, 0, ',', '.') }} <span class="text-sm font-normal text-gray-400">pcs</span></p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-4 {{ $jmlRugi > 0 ? 'ring-red-200' : '' }}">
            <p class="text-xs text-gray-500">{{ $group === 'sku' ? 'SKU' : 'Aroma' }} · Rugi</p>
            <p class="text-xl font-bold text-gray-700">{{ $jmlItem }} <span class="text-sm font-normal {{ $jmlRugi > 0 ? 'text-red-600' : 'text-gray-400' }}">· {{ $jmlRugi }} rugi</span></p>
        </div>
    </div>

    {{-- Tabel --}}
    <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50"><tr>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">#</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">{{ $group === 'sku' ? 'SKU / Produk' : 'Aroma' }}</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Terjual</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Omzet</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">HPP</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Laba</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Margin</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rows as $i => $r)
                        <tr class="{{ $r->laba < 0 ? 'bg-red-50' : ($i < 3 ? 'bg-emerald-50/40' : '') }}">
                            <td class="px-4 py-2 text-gray-400 font-medium">{{ $i + 1 }}</td>
                            <td class="px-4 py-2">
                                <div class="font-medium text-gray-800">{{ $r->nama ?? $r->kode }}
                                    @if($group === 'sku' && !empty($r->ukuran))<span class="text-gray-400">{{ $r->ukuran }}ml</span>@endif
                                </div>
                                <div class="text-xs text-gray-400">{{ $r->kode }}</div>
                            </td>
                            <td class="px-4 py-2 text-right text-gray-700 whitespace-nowrap">{{ number_format($r->qty, 0, ',', '.') }} pcs</td>
                            <td class="px-4 py-2 text-right text-gray-700 whitespace-nowrap">{{ $rp($r->omzet) }}</td>
                            <td class="px-4 py-2 text-right text-orange-700 whitespace-nowrap">{{ $rp($r->hpp) }}</td>
                            <td class="px-4 py-2 text-right font-semibold whitespace-nowrap {{ $r->laba >= 0 ? 'text-emerald-700' : 'text-red-600' }}">{{ $r->laba < 0 ? '−' : '' }}{{ $rp(abs($r->laba)) }}</td>
                            <td class="px-4 py-2 text-right font-bold whitespace-nowrap {{ $r->margin >= 0 ? 'text-gray-700' : 'text-red-600' }}">{{ number_format($r->margin, 1) }}%</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400 italic">Belum ada penjualan yang tercatat (cair/lunas) di periode ini.</td></tr>
                    @endforelse
                </tbody>
                @if($rows->isNotEmpty())
                <tfoot class="bg-gray-50 font-semibold">
                    <tr>
                        <td class="px-4 py-2" colspan="2">TOTAL</td>
                        <td class="px-4 py-2 text-right">{{ number_format($totQty, 0, ',', '.') }} pcs</td>
                        <td class="px-4 py-2 text-right">{{ $rp($totOmzet) }}</td>
                        <td class="px-4 py-2 text-right text-orange-700">{{ $rp($totHpp) }}</td>
                        <td class="px-4 py-2 text-right {{ $totLaba >= 0 ? 'text-emerald-700' : 'text-red-600' }}">{{ $rp($totLaba) }}</td>
                        <td class="px-4 py-2 text-right">{{ number_format($marginTotal, 1) }}%</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    <p class="text-xs text-gray-400 mt-4">Omzet = basis net (marketplace setelah potongan platform; non-marketplace = harga jual). Laba = omzet − HPP (laba <b>kotor per produk</b>). Baris hijau = 3 teratas, merah = rugi. Diskon order-level &amp; biaya operasional tidak dialokasikan per produk — untuk laba bersih usaha lihat Laporan P&amp;L.</p>
</div>
</div>
</body>
</html>
