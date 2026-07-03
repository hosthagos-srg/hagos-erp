<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hagos ERP - Proyeksi Arus Kas</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">

@php $rp = fn($n) => 'Rp ' . number_format($n, 0, ',', '.'); @endphp

<div class="min-h-screen p-6">
    <header class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Proyeksi Arus Kas</h1>
        <p class="text-gray-500 mt-1">Perkiraan kas {{ $horizon }} minggu ke depan dari arus yang sudah bisa diprediksi.</p>
    </header>

    {{-- Asumsi / filter --}}
    <div class="bg-white rounded-xl shadow-sm p-4 mb-5">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Horizon (minggu)</label>
                <input type="number" name="minggu" value="{{ $horizon }}" min="2" max="26" class="w-24 border border-gray-300 rounded-md px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Lag settlement cair (hari)</label>
                <input type="number" name="lag" value="{{ $lag }}" min="0" max="60" class="w-24 border border-gray-300 rounded-md px-3 py-1.5 text-sm">
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="gaji" value="1" {{ $includeGaji ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600"> Sertakan estimasi gaji
            </label>
            <button type="submit" class="bg-indigo-600 text-white px-4 py-1.5 rounded-md text-sm font-semibold hover:bg-indigo-700">Terapkan</button>
        </form>
        <p class="text-xs text-gray-400 mt-2">Asumsi: settlement marketplace cair {{ $lag }} hari setelah pesanan (net = GMV − take rate {{ number_format($takeRate*100,1) }}%); piutang reseller masuk H+7; gaji = Σ gaji pokok aktif ({{ $rp($gajiBulanan) }}/bln) tiap tgl 1.</p>
    </div>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 {{ $saldoAwal < 0 ? 'border-red-500' : 'border-gray-300' }}">
            <p class="text-xs text-gray-500">Kas Saat Ini</p>
            <p class="text-xl font-bold {{ $saldoAwal < 0 ? 'text-red-600' : 'text-gray-800' }}">{{ $rp($saldoAwal) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-emerald-500">
            <p class="text-xs text-gray-500">Perkiraan Masuk</p>
            <p class="text-xl font-bold text-emerald-600">{{ $rp($totalIn) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-red-500">
            <p class="text-xs text-gray-500">Perkiraan Keluar</p>
            <p class="text-xl font-bold text-red-600">{{ $rp($totalOut) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 {{ $minSaldo < 0 ? 'border-red-500' : 'border-emerald-500' }}">
            <p class="text-xs text-gray-500">Saldo Terendah</p>
            <p class="text-xl font-bold {{ $minSaldo < 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ $rp($minSaldo) }}</p>
        </div>
    </div>

    @if($mingguKritis)
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 rounded-r-lg px-4 py-3">
            <p class="text-sm font-bold text-red-800">⚠ Potensi kas minus di minggu {{ $mingguKritis }}</p>
            <p class="text-xs text-red-600 mt-0.5">Saldo proyeksi menyentuh negatif. Pertimbangkan tunda pengeluaran, percepat penagihan piutang, atau siapkan modal.</p>
        </div>
    @endif

    {{-- Tabel mingguan --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50"><tr>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Minggu</th>
                    <th class="px-4 py-2 text-right text-xs text-emerald-600 uppercase">Settlement</th>
                    <th class="px-4 py-2 text-right text-xs text-emerald-600 uppercase">Piutang</th>
                    <th class="px-4 py-2 text-right text-xs text-red-500 uppercase">Cicilan</th>
                    <th class="px-4 py-2 text-right text-xs text-red-500 uppercase">Gaji</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Net</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-700 uppercase">Saldo Akhir</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($weeks as $i => $w)
                        <tr class="{{ $w['saldo'] < 0 ? 'bg-red-50' : '' }}">
                            <td class="px-4 py-2 text-gray-700">{{ $w['label'] }} @if($i === 0)<span class="text-xs text-indigo-500">(skrg)</span>@endif</td>
                            <td class="px-4 py-2 text-right text-emerald-700">{{ $w['settlement'] > 0 ? $rp($w['settlement']) : '-' }}</td>
                            <td class="px-4 py-2 text-right text-emerald-700">{{ $w['piutang'] > 0 ? $rp($w['piutang']) : '-' }}</td>
                            <td class="px-4 py-2 text-right text-red-600">{{ $w['cicilan'] > 0 ? $rp($w['cicilan']) : '-' }}</td>
                            <td class="px-4 py-2 text-right text-red-600">{{ $w['gaji'] > 0 ? $rp($w['gaji']) : '-' }}</td>
                            <td class="px-4 py-2 text-right font-semibold {{ $w['net'] < 0 ? 'text-red-600' : 'text-gray-900' }}">{{ ($w['net'] >= 0 ? '+' : '') . $rp($w['net']) }}</td>
                            <td class="px-4 py-2 text-right font-bold {{ $w['saldo'] < 0 ? 'text-red-600' : 'text-gray-900' }}">{{ $rp($w['saldo']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-xs text-gray-400 mt-4">Ini perkiraan, bukan angka pasti — settlement & penagihan bisa meleset dari asumsi. Atur "lag" & horizon di atas sesuai pengalamanmu. Pengeluaran rutin non-gaji (sewa/listrik) belum diproyeksikan otomatis.</p>
</div>
</div>
</body>
</html>
