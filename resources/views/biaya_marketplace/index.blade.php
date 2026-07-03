<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hagos ERP - Biaya Marketplace</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">

@php $rp = fn($n) => 'Rp ' . number_format($n, 0, ',', '.'); @endphp

<div class="min-h-screen p-6">
    <header class="mb-6 flex flex-wrap justify-between items-start gap-3">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Analisis Biaya Marketplace</h1>
            <p class="text-gray-500 mt-1">Ke mana GMV-mu "hilang": komisi, iklan, ongkir, biaya layanan. Dari {{ $jml }} order yang sudah cair.</p>
        </div>
        <form method="GET" class="flex items-center gap-2">
            <label class="text-sm text-gray-600">Periode (tgl cair):</label>
            <select name="bulan" onchange="this.form.submit()" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm">
                <option value="">Semua</option>
                @foreach($bulanTersedia as $b)
                    <option value="{{ $b }}" {{ $bulan === $b ? 'selected' : '' }}>{{ \Carbon\Carbon::createFromFormat('Y-m', $b)->translatedFormat('F Y') }}</option>
                @endforeach
            </select>
        </form>
    </header>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-blue-500">
            <p class="text-xs text-gray-500 uppercase tracking-wide">GMV (Omset Kotor)</p>
            <p class="text-xl font-bold text-blue-600 mt-1">{{ $rp($totalGross) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-emerald-500">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Net Diterima</p>
            <p class="text-xl font-bold text-emerald-600 mt-1">{{ $rp($totalNet) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-red-500">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Potongan</p>
            <p class="text-xl font-bold text-red-600 mt-1">{{ $rp($totalPotongan) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-amber-500">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Take Rate (potongan/GMV)</p>
            <p class="text-2xl font-bold text-amber-600 mt-1">{{ number_format($takeRate, 1) }}%</p>
        </div>
    </div>

    @if($jml === 0)
        <div class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-400">Belum ada order marketplace yang cair pada periode ini.</div>
    @else
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Per jenis biaya --}}
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100"><h2 class="font-bold text-gray-800 text-sm">Rincian per Jenis Biaya</h2></div>
            <div class="divide-y divide-gray-100">
                @foreach($perFee as $nama => $jumlah)
                    @php $pct = $totalGross > 0 ? ($jumlah / $totalGross) * 100 : 0; @endphp
                    <div class="px-5 py-3">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm text-gray-700">{{ $nama }}</span>
                            <span class="text-sm font-semibold text-red-600">{{ $rp($jumlah) }} <span class="text-xs text-gray-400">({{ number_format($pct, 1) }}%)</span></span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-1.5">
                            <div class="bg-red-400 h-1.5 rounded-full" style="width: {{ min(100, $totalPotongan > 0 ? ($jumlah / $totalPotongan) * 100 : 0) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Per channel --}}
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100"><h2 class="font-bold text-gray-800 text-sm">Per Channel</h2></div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50"><tr>
                        <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Channel</th>
                        <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">GMV</th>
                        <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Net</th>
                        <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Potongan</th>
                        <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Rate</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($perChannel as $c)
                            @php $rate = $c['gross'] > 0 ? ($c['potongan'] / $c['gross']) * 100 : 0; @endphp
                            <tr>
                                <td class="px-4 py-2 text-gray-800">{{ $c['channel'] }} <span class="text-xs text-gray-400">({{ $c['jml'] }})</span></td>
                                <td class="px-4 py-2 text-right text-gray-600">{{ $rp($c['gross']) }}</td>
                                <td class="px-4 py-2 text-right text-emerald-700">{{ $rp($c['net']) }}</td>
                                <td class="px-4 py-2 text-right text-red-600">{{ $rp($c['potongan']) }}</td>
                                <td class="px-4 py-2 text-right font-bold text-amber-600">{{ number_format($rate, 1) }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <p class="text-xs text-gray-400 mt-4">Potongan = GMV (omset dari settlement) − Net Diterima. Rincian per jenis diambil dari data settlement yang diupload. Order tanpa rincian valid masuk "(Potongan tak terinci)".</p>
</div>
</div>
</body>
</html>
