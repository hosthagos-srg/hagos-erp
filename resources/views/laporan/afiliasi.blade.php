<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Afiliasi - Hagos ERP</title>
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
        <h1 class="text-3xl font-bold text-gray-900">🤝 Laporan Afiliasi</h1>
        <p class="text-gray-500 mt-1">Pesanan yang kena <b>Komisi Afiliasi</b> di settlement TikTok (penanda pesanan dari afiliasi/creator). Terdeteksi otomatis saat upload settlement — <b>tidak mengubah</b> perhitungan HPP/net.</p>
    </header>

    {{-- Filter --}}
    <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('laporan.afiliasi') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Dari</label>
                <input type="date" name="dari" value="{{ $dari }}" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Sampai</label>
                <input type="date" name="sampai" value="{{ $sampai }}" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm">
            </div>
            <button type="submit" class="bg-indigo-600 text-white px-4 py-1.5 rounded-md text-sm font-semibold hover:bg-indigo-700">Terapkan</button>
            <span class="text-xs text-gray-400 ml-auto">{{ $tgl($dari).' – '.$tgl($sampai) }}</span>
        </form>
    </div>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl ring-1 ring-teal-200 shadow-sm p-4">
            <p class="text-xs text-gray-500">Pesanan Afiliasi</p>
            <p class="text-2xl font-bold text-teal-600">{{ $totalPesananAff }}</p>
            <p class="text-xs text-gray-400">{{ number_format($pctPesanan, 1) }}% dari semua pesanan</p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-500">GMV dari Afiliasi</p>
            <p class="text-2xl font-bold text-gray-900">{{ $rp($gmvAff) }}</p>
            <p class="text-xs text-gray-400">{{ number_format($pctGmv, 1) }}% dari total omzet</p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-rose-200 shadow-sm p-4">
            <p class="text-xs text-gray-500">Total Komisi Afiliasi</p>
            <p class="text-2xl font-bold text-rose-600">{{ $rp($komisiAff) }}</p>
            <p class="text-xs text-gray-400">{{ number_format($komisiPct, 1) }}% dari GMV afiliasi</p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-500">Net Diterima (afiliasi)</p>
            <p class="text-2xl font-bold text-emerald-600">{{ $rp($netAff) }}</p>
        </div>
    </div>

    {{-- Detail --}}
    <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100"><h2 class="font-bold text-gray-800 text-sm">Pesanan Afiliasi</h2></div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50"><tr>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Tanggal</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Pesanan</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Channel</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">GMV</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Komisi Afiliasi</th>
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
                            <td class="px-4 py-2 text-right font-semibold text-rose-600">− {{ $rp($d->komisi_afiliasi) }}</td>
                            <td class="px-4 py-2 text-right font-medium text-gray-900">{{ $rp($d->net_settlement ?? $d->gmv_kotor) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400 italic">Belum ada pesanan afiliasi pada periode ini. (Muncul otomatis setelah upload settlement TikTok yang ada komisi afiliasinya.)</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-xs text-gray-400 mt-4">Catatan: settlement menandai pesanan afiliasi + besar komisinya, tapi <b>tidak</b> menyebut creator mana. Untuk ROI per-creator, gabungkan dengan biaya Produk Gratis (menu Produk Gratis) + nama creator dari dashboard Afiliasi TikTok.</p>
</div>
</div>
</body>
</html>
