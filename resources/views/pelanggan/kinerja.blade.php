<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kinerja Pelanggan/Reseller - Hagos ERP</title>
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
    <header class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">🏅 Kinerja Pelanggan / Reseller</h1>
            <p class="text-gray-500 mt-1">Peringkat pelanggan non-marketplace berdasarkan omzet + frekuensi, nilai rata-rata order, dan <b>deteksi pasif</b> (tidak order &gt; {{ $dormantHari }} hari). Untuk retensi & upsell.</p>
        </div>
        <a href="{{ route('pelanggan.index') }}" class="text-indigo-600 hover:text-indigo-900 text-sm">Kelola CRM →</a>
    </header>

    {{-- Filter --}}
    <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('pelanggan.kinerja') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Tipe</label>
                <select name="tipe" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm bg-white">
                    <option value="">-- Semua --</option>
                    @foreach($tipeList as $t)<option value="{{ $t }}" {{ $tipeFilter === $t ? 'selected' : '' }}>{{ $t }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Dari</label>
                <input type="date" name="dari" value="{{ $dari }}" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Sampai</label>
                <input type="date" name="sampai" value="{{ $sampai }}" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm">
            </div>
            <button type="submit" class="bg-indigo-600 text-white px-4 py-1.5 rounded-md text-sm font-semibold hover:bg-indigo-700">Terapkan</button>
            @if($tipeFilter || $dari || $sampai)<a href="{{ route('pelanggan.kinerja') }}" class="px-4 py-1.5 rounded-md text-sm text-gray-700 bg-gray-100 hover:bg-gray-200">Reset</a>@endif
            <span class="text-xs text-gray-400 ml-auto">{{ $dari || $sampai ? 'Periode: '.$tgl($dari).' – '.$tgl($sampai) : 'Semua waktu' }}</span>
        </form>
    </div>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-500">Jumlah Pelanggan</p>
            <p class="text-2xl font-bold text-gray-900">{{ $totalPelanggan }}</p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-emerald-200 shadow-sm p-4">
            <p class="text-xs text-gray-500">Total Omzet (dari mereka)</p>
            <p class="text-2xl font-bold text-emerald-600">{{ $rp($totalOmzet) }}</p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-red-200 shadow-sm p-4">
            <p class="text-xs text-gray-500">😴 Pelanggan Pasif</p>
            <p class="text-2xl font-bold text-red-600">{{ $jmlDormant }}</p>
            <p class="text-xs text-gray-400">tak order &gt; {{ $dormantHari }} hari</p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-amber-200 shadow-sm p-4">
            <p class="text-xs text-gray-500">Total Piutang Berjalan</p>
            <p class="text-2xl font-bold text-amber-600">{{ $rp($totalPiutang) }}</p>
        </div>
    </div>

    {{-- Tabel --}}
    <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50"><tr>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">#</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Pelanggan</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Tipe</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Order</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Omzet</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Rata²/Order</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Order Terakhir</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Piutang</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rows as $i => $r)
                        <tr class="{{ $r->dormant ? 'bg-red-50' : ($i < 3 ? 'bg-emerald-50/40' : '') }}">
                            <td class="px-4 py-2 text-gray-400 font-medium">{{ $i + 1 }}</td>
                            <td class="px-4 py-2">
                                <a href="{{ route('pelanggan.index', ['cari' => $r->nama]) }}" class="font-medium text-indigo-600 hover:underline">{{ $r->nama }}</a>
                            </td>
                            <td class="px-4 py-2 text-gray-600">{{ $r->tipe }}</td>
                            <td class="px-4 py-2 text-right text-gray-700">{{ $r->orders }}</td>
                            <td class="px-4 py-2 text-right font-semibold text-gray-900 whitespace-nowrap">{{ $rp($r->omzet) }}</td>
                            <td class="px-4 py-2 text-right text-gray-600 whitespace-nowrap">{{ $rp($r->aov) }}</td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <span class="text-gray-600">{{ $tgl($r->last_order) }}</span>
                                @if($r->hari_sejak !== null)
                                    <span class="text-xs {{ $r->dormant ? 'text-red-600 font-semibold' : 'text-gray-400' }}">({{ $r->hari_sejak }}h lalu{{ $r->dormant ? ' · pasif' : '' }})</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-right whitespace-nowrap {{ $r->piutang > 0 ? 'text-amber-600 font-medium' : 'text-gray-400' }}">{{ $r->piutang > 0 ? $rp($r->piutang) : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400 italic">Belum ada data pelanggan non-marketplace pada filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-xs text-gray-400 mt-4">Fokus channel non-marketplace (Offline/WA/Reseller/Website) — pembeli marketplace tidak masuk (nama acak). Baris hijau = 3 teratas, merah = pasif (&gt;{{ $dormantHari }} hari tanpa order — kandidat di-follow up). Omzet = harga jual − diskon (belum dikurangi HPP).</p>
</div>
</div>
</body>
</html>
