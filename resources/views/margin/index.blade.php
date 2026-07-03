<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hagos ERP - Margin Watchdog</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">

@php $rp = fn($n) => 'Rp ' . number_format($n, 0, ',', '.'); @endphp

<div class="min-h-screen p-6">
    <header class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Margin Watchdog</h1>
        <p class="text-gray-500 mt-1">HPP terkini tiap produk × channel vs harga jualnya. Saat harga bibit naik, SKU yang jadi rugi/tipis muncul di sini.</p>
    </header>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-red-500">
            <p class="text-xs text-gray-500">🔴 Rugi (HPP &gt; harga)</p>
            <p class="text-2xl font-bold text-red-600">{{ $countRugi }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-amber-500">
            <p class="text-xs text-gray-500">🟡 Margin Tipis (&lt; {{ rtrim(rtrim(number_format($threshold,1),'0'),'.') }}%)</p>
            <p class="text-2xl font-bold text-amber-600">{{ $countTipis }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-emerald-500">
            <p class="text-xs text-gray-500">🟢 Aman</p>
            <p class="text-2xl font-bold text-emerald-600">{{ $evaluated - $countRugi - $countTipis }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-gray-300">
            <p class="text-xs text-gray-500">Total Dievaluasi</p>
            <p class="text-2xl font-bold text-gray-700">{{ $evaluated }}</p>
            <p class="text-xs text-gray-400">SKU × channel berharga</p>
        </div>
    </div>

    {{-- Filter --}}
    <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
        <form method="GET" action="{{ route('margin.index') }}" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Channel</label>
                <select name="channel" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm">
                    <option value="">-- Semua Channel --</option>
                    @foreach($channels as $c)<option value="{{ $c }}" {{ $channel === $c ? 'selected' : '' }}>{{ $c }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Ambang margin "tipis" (%)</label>
                <input type="number" name="threshold" value="{{ rtrim(rtrim(number_format($threshold,1),'0'),'.') }}" min="0" max="100" step="1" class="w-28 border border-gray-300 rounded-md px-3 py-1.5 text-sm">
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="all" value="1" {{ $showAll ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600"> Tampilkan juga yang aman
            </label>
            <button type="submit" class="bg-indigo-600 text-white px-4 py-1.5 rounded-md text-sm font-semibold hover:bg-indigo-700">Terapkan</button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100">
            <h2 class="font-bold text-gray-800 text-sm">{{ $showAll ? 'Semua SKU × Channel' : 'Perlu Perhatian (Rugi & Tipis)' }} — urut terburuk di atas</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50"><tr>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Produk</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Channel</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Harga Jual</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">HPP Kini</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Margin</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Margin %</th>
                    <th class="px-4 py-2 text-center text-xs text-gray-500 uppercase">Aksi</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rows as $r)
                        @php
                            $cls = $r->status === 'rugi' ? 'bg-red-50' : ($r->status === 'tipis' ? 'bg-amber-50' : '');
                            $badge = $r->status === 'rugi' ? 'bg-red-100 text-red-700' : ($r->status === 'tipis' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700');
                            $label = $r->status === 'rugi' ? 'RUGI' : ($r->status === 'tipis' ? 'TIPIS' : 'Aman');
                        @endphp
                        <tr class="{{ $cls }}">
                            <td class="px-4 py-2"><span class="text-xs font-semibold px-2 py-0.5 rounded {{ $badge }}">{{ $label }}</span></td>
                            <td class="px-4 py-2 text-gray-800">{{ $r->nama }} <span class="text-gray-400">{{ $r->ukuran }}ml</span><span class="block text-xs text-gray-400">{{ $r->sku_id }}</span></td>
                            <td class="px-4 py-2 text-gray-600">{{ $r->channel }}</td>
                            <td class="px-4 py-2 text-right text-gray-700 whitespace-nowrap">{{ $rp($r->harga) }}</td>
                            <td class="px-4 py-2 text-right text-orange-700 whitespace-nowrap">{{ $rp($r->hpp) }}</td>
                            <td class="px-4 py-2 text-right font-semibold {{ $r->margin < 0 ? 'text-red-600' : 'text-gray-900' }} whitespace-nowrap">{{ $r->margin < 0 ? '−' : '' }}{{ $rp(abs($r->margin)) }}</td>
                            <td class="px-4 py-2 text-right font-bold {{ $r->status === 'rugi' ? 'text-red-600' : ($r->status === 'tipis' ? 'text-amber-600' : 'text-emerald-600') }}">{{ number_format($r->marginPct, 1) }}%</td>
                            <td class="px-4 py-2 text-center">
                                <a href="{{ route('master_produk.detail', $r->aroma) }}" class="text-xs text-indigo-600 hover:underline">Ubah Harga →</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-8 text-center text-emerald-600 italic">✓ Semua produk margin-nya aman. Tidak ada yang rugi/tipis.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-xs text-gray-400 mt-4">HPP dihitung real-time dari harga bibit terkini (rata-rata bergerak) + komponen + tester + fulfillment sesuai channel. Klik "Ubah Harga" untuk naikkan harga jual di Master Produk.</p>
</div>
</div>
</body>
</html>
