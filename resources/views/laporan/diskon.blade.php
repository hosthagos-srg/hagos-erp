<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Diskon Diberikan - Hagos ERP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">

@php
    $rp = fn($n) => 'Rp ' . number_format($n, 0, ',', '.');
    $tgl = fn($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d/m/Y') : '-';
@endphp

<div class="min-h-screen p-6 max-w-6xl mx-auto">
    <header class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">🏷️ Laporan Diskon Diberikan</h1>
        <p class="text-gray-500 mt-1">Total diskon yang kamu beri pada penjualan <b>non-marketplace</b> (Offline/Reseller/Website). Diskon marketplace tidak dihitung (itu potongan settlement, bukan diskon yang kamu beri).</p>
    </header>

    {{-- Filter --}}
    <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('laporan.diskon') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Dari</label>
                <input type="date" name="dari" value="{{ $dari }}" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Sampai</label>
                <input type="date" name="sampai" value="{{ $sampai }}" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Channel</label>
                <select name="channel" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm bg-white">
                    <option value="">-- Semua non-MP --</option>
                    @foreach($channels as $c)<option value="{{ $c }}" {{ $channel === $c ? 'selected' : '' }}>{{ $c }}</option>@endforeach
                </select>
            </div>
            <button type="submit" class="bg-indigo-600 text-white px-4 py-1.5 rounded-md text-sm font-semibold hover:bg-indigo-700">Terapkan</button>
            @if($channel)<a href="{{ route('laporan.diskon', ['dari'=>$dari,'sampai'=>$sampai]) }}" class="px-4 py-1.5 rounded-md text-sm text-gray-700 bg-gray-100 hover:bg-gray-200">Reset</a>@endif
            <span class="text-xs text-gray-400 ml-auto">{{ $tgl($dari).' – '.$tgl($sampai) }}</span>
        </form>
    </div>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl ring-1 ring-rose-200 shadow-sm p-4">
            <p class="text-xs text-gray-500">Total Diskon Diberikan</p>
            <p class="text-2xl font-bold text-rose-600">{{ $rp($totalDiskon) }}</p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-500">Pesanan Berdiskon</p>
            <p class="text-2xl font-bold text-gray-900">{{ $totalPesanan }}</p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-500">% Diskon thd Omzet Kotor</p>
            <p class="text-2xl font-bold text-amber-600">{{ number_format($rataDiskonPct, 1) }}%</p>
            <p class="text-xs text-gray-400">dari {{ $rp($totalOmzetKotor) }}</p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-500">Rata²/Pesanan</p>
            <p class="text-2xl font-bold text-gray-700">{{ $rp($totalPesanan > 0 ? $totalDiskon / $totalPesanan : 0) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Per Channel --}}
        <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100"><h2 class="font-bold text-gray-800 text-sm">Diskon per Channel</h2></div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50"><tr>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Channel</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Pesanan</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Total Diskon</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($perChannel as $r)
                        <tr>
                            <td class="px-4 py-2 text-gray-700">{{ $r->channel }}</td>
                            <td class="px-4 py-2 text-right text-gray-500">{{ $r->c }}</td>
                            <td class="px-4 py-2 text-right font-semibold text-rose-600">{{ $rp($r->d) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-6 text-center text-gray-400 italic">Tidak ada diskon pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Per Pelanggan --}}
        <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100"><h2 class="font-bold text-gray-800 text-sm">Top Penerima Diskon</h2></div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50"><tr>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Pelanggan</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Pesanan</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Total Diskon</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($perPelanggan as $r)
                        <tr>
                            <td class="px-4 py-2 text-gray-700">{{ $r->pembeli }}</td>
                            <td class="px-4 py-2 text-right text-gray-500">{{ $r->c }}</td>
                            <td class="px-4 py-2 text-right font-semibold text-rose-600">{{ $rp($r->d) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-6 text-center text-gray-400 italic">—</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Detail --}}
    <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100"><h2 class="font-bold text-gray-800 text-sm">Rincian Pesanan Berdiskon</h2></div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50"><tr>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Tanggal</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Pesanan</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Channel</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Omzet Kotor</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Diskon</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Net</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($detail as $d)
                        <tr>
                            <td class="px-4 py-2 text-gray-500 whitespace-nowrap">{{ $tgl($d->tgl_pesanan) }}</td>
                            <td class="px-4 py-2">
                                <a href="{{ route('penjualan.show', $d->internal_id) }}" class="text-indigo-600 hover:underline font-medium">{{ $d->external_order_id ?: ('INV-'.strtoupper(substr($d->internal_id,0,8))) }}</a>
                                @if($d->nama_pembeli)<span class="block text-xs text-gray-400">{{ $d->nama_pembeli }}</span>@endif
                            </td>
                            <td class="px-4 py-2 text-gray-600">{{ $d->channel }}</td>
                            <td class="px-4 py-2 text-right text-gray-600">{{ $rp($d->gmv_kotor) }}</td>
                            <td class="px-4 py-2 text-right font-semibold text-rose-600">− {{ $rp($d->diskon_manual) }}</td>
                            <td class="px-4 py-2 text-right font-medium text-gray-900">{{ $rp((float)$d->gmv_kotor - (float)$d->diskon_manual) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400 italic">Belum ada pesanan berdiskon pada periode & channel ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-xs text-gray-400 mt-4">Diskon di sini = potongan harga yang kamu beri sendiri (kolom "Diskon" di form Pesanan Baru non-marketplace). Diskon sudah otomatis mengurangi omzet di Laporan Laba/Rugi.</p>
</div>
</div>
</body>
</html>
