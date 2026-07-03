<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pajak PPh Final - Hagos ERP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">

@php
    $rp = fn($n) => 'Rp ' . number_format($n, 0, ',', '.');
    $bulanID = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
@endphp

<div class="min-h-screen p-6 max-w-5xl mx-auto">
    <header class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">🧾 Laporan Pajak — PPh Final UMKM 0,5%</h1>
        <p class="text-gray-500 mt-1">Estimasi PPh Final (PP 55/2022) atas peredaran bruto per bulan tahun {{ $tahun }}. Untuk membantu setoran bulanan — angka final tetap konfirmasi ke konsultan pajak.</p>
    </header>

    {{-- Filter --}}
    <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('laporan.pajak') }}" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Tahun</label>
                <select name="tahun" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm">
                    @forelse($tahunTersedia as $th)<option value="{{ $th }}" {{ $tahun == $th ? 'selected' : '' }}>{{ $th }}</option>
                    @empty<option value="{{ $tahun }}">{{ $tahun }}</option>@endforelse
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Skema Wajib Pajak</label>
                <select name="skema" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm bg-white">
                    <option value="pribadi" {{ $skema === 'pribadi' ? 'selected' : '' }}>Orang Pribadi (bebas Rp500 jt/th pertama)</option>
                    <option value="penuh" {{ $skema === 'penuh' ? 'selected' : '' }}>Penuh 0,5% (mis. WP Badan / tanpa fasilitas)</option>
                </select>
            </div>
            <button type="submit" class="bg-indigo-600 text-white px-4 py-1.5 rounded-md text-sm font-semibold hover:bg-indigo-700">Terapkan</button>
        </form>
    </div>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-500">Peredaran Bruto {{ $tahun }}</p>
            <p class="text-2xl font-bold text-gray-900">{{ $rp($totBruto) }}</p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-500">Bruto Kena Pajak</p>
            <p class="text-2xl font-bold text-indigo-600">{{ $rp($totKena) }}</p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-emerald-200 shadow-sm p-4">
            <p class="text-xs text-gray-500">Total PPh Final (0,5%) {{ $tahun }}</p>
            <p class="text-2xl font-bold text-emerald-700">{{ $rp($totPph) }}</p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-500">{{ $skema === 'pribadi' ? 'Sisa Jatah Bebas (Rp500 jt)' : 'Skema' }}</p>
            <p class="text-2xl font-bold text-gray-700">{{ $skema === 'pribadi' ? $rp($sisaBebas) : 'Penuh 0,5%' }}</p>
        </div>
    </div>

    @if($skema === 'pribadi')
    <div class="mb-4 bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg text-sm">
        ℹ️ Skema <b>Orang Pribadi</b>: peredaran bruto <b>Rp500 juta pertama per tahun</b> tidak kena PPh Final. PPh 0,5% hanya dihitung atas bruto yang melewati batas itu (kumulatif sepanjang tahun).
    </div>
    @endif

    {{-- Tabel bulanan --}}
    <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Bulan</th>
                <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Peredaran Bruto</th>
                <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Kumulatif</th>
                <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Bruto Kena Pajak</th>
                <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">PPh 0,5%</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($rows as $r)
                    <tr class="{{ $r->pph > 0 ? '' : 'text-gray-400' }}">
                        <td class="px-4 py-2 {{ $r->pph > 0 ? 'text-gray-800' : '' }}">{{ $bulanID[$r->bln] }}</td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">{{ $rp($r->bruto) }}</td>
                        <td class="px-4 py-2 text-right whitespace-nowrap text-gray-500">{{ $rp($r->kum_bruto) }}</td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">{{ $r->kena > 0 ? $rp($r->kena) : '—' }}</td>
                        <td class="px-4 py-2 text-right whitespace-nowrap font-semibold {{ $r->pph > 0 ? 'text-emerald-700' : '' }}">{{ $r->pph > 0 ? $rp($r->pph) : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 font-semibold">
                <tr>
                    <td class="px-4 py-2">TOTAL {{ $tahun }}</td>
                    <td class="px-4 py-2 text-right">{{ $rp($totBruto) }}</td>
                    <td class="px-4 py-2"></td>
                    <td class="px-4 py-2 text-right">{{ $rp($totKena) }}</td>
                    <td class="px-4 py-2 text-right text-emerald-700">{{ $rp($totPph) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <p class="text-xs text-gray-400 mt-4">
        <b>Peredaran bruto</b> = nilai penjualan kotor pesanan non-batal per tgl pesanan (Marketplace pakai nilai <i>gross</i> sebelum potongan platform; non-marketplace = harga jual − diskon). Potongan/fee marketplace &amp; biaya operasional <b>bukan pengurang</b> peredaran bruto.
        ⚠️ Ini <b>estimasi</b> untuk bantu pencatatan; tarif, kewajiban, &amp; batas fasilitas bisa berubah — konfirmasikan ke konsultan/aturan pajak terbaru. Jika sudah dikenakan tarif normal (bukan final), skema ini tidak berlaku.
    </p>
</div>
</div>
</body>
</html>
