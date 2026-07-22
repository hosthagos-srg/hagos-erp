<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Retur / Pembatalan - Hagos ERP</title>
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
        <h1 class="text-3xl font-bold text-gray-900">🚫 Laporan Retur / Pembatalan</h1>
        <p class="text-gray-500 mt-1">Berapa banyak & nilai pesanan yang batal, alasannya, dan kerugian nyata dari barang rusak saat retur. Untuk menekan kebocoran margin.</p>
    </header>

    {{-- Filter --}}
    <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('laporan.retur') }}" class="flex flex-wrap items-end gap-3">
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
                    <option value="">-- Semua --</option>
                    @foreach($channels as $c)<option value="{{ $c }}" {{ $channel === $c ? 'selected' : '' }}>{{ $c }}</option>@endforeach
                </select>
            </div>
            <button type="submit" class="bg-indigo-600 text-white px-4 py-1.5 rounded-md text-sm font-semibold hover:bg-indigo-700">Terapkan</button>
            @if($dari || $sampai || $channel)<a href="{{ route('laporan.retur') }}" class="px-4 py-1.5 rounded-md text-sm text-gray-700 bg-gray-100 hover:bg-gray-200">Reset</a>@endif
            <span class="text-xs text-gray-400 ml-auto">{{ $dari || $sampai ? $tgl($dari).' – '.$tgl($sampai) : 'Semua waktu' }}</span>
        </form>
    </div>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl ring-1 ring-red-200 shadow-sm p-4">
            <p class="text-xs text-gray-500">Pesanan Batal</p>
            <p class="text-2xl font-bold text-red-600">{{ $totalBatal }}</p>
            <p class="text-xs text-gray-400">{{ number_format($tingkatBatal, 1) }}% dari {{ $totalOrder }} order</p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-500">Nilai Pesanan Batal</p>
            <p class="text-2xl font-bold text-gray-900">{{ $rp($nilaiBatal) }}</p>
            <p class="text-xs text-gray-400">omzet yang gagal terjadi</p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-orange-200 shadow-sm p-4">
            <p class="text-xs text-gray-500">🔥 Kerugian Barang Rusak</p>
            <p class="text-2xl font-bold text-orange-600">{{ $rp($rusakNilai) }}</p>
            <p class="text-xs text-gray-400">{{ $rusakQty }} pcs modal hangus</p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-500">Rata²/Pesanan Batal</p>
            <p class="text-2xl font-bold text-gray-700">{{ $rp($totalBatal > 0 ? $nilaiBatal / $totalBatal : 0) }}</p>
        </div>
    </div>

    {{-- Retur Marketplace dari settlement (net_settlement negatif) --}}
    <div class="bg-white rounded-2xl ring-1 ring-rose-100 shadow-sm overflow-hidden mb-6">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-bold text-gray-800 text-sm">↩️ Retur Marketplace (dari settlement)</h2>
            <span class="text-xs text-gray-500">{{ $returMpCount }} order · total rugi <b class="text-rose-600">{{ $rp($returMpRugi) }}</b></span>
        </div>
        @if($returMpCount > 0)
        <div class="px-5 py-2 text-xs text-gray-500 bg-rose-50/50">Uang sudah otomatis terpotong (net settlement negatif). Rugi = dana dikembalikan + biaya produk yang sudah diracik (hangus).</div>
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Order · Tgl</th>
                <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Produk</th>
                <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Net Settlement</th>
                <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">HPP Hangus</th>
                <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Rugi</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($returMp as $r)
                <tr>
                    <td class="px-4 py-2"><div class="font-medium text-gray-800">{{ $r->external_order_id }}</div><div class="text-xs text-gray-400">{{ $r->channel }} · {{ \Illuminate\Support\Carbon::parse($r->tgl_pesanan)->format('d/m/Y') }}</div></td>
                    <td class="px-4 py-2 text-gray-700">@foreach($r->items as $it)<div class="text-xs">{{ $it->nama_produk ?? $it->sku_id }} ×{{ $it->qty }}</div>@endforeach</td>
                    <td class="px-4 py-2 text-right text-rose-600 whitespace-nowrap">{{ $rp($r->net_settlement) }}</td>
                    <td class="px-4 py-2 text-right text-gray-500 whitespace-nowrap">{{ $rp($r->hpp_total) }}</td>
                    <td class="px-4 py-2 text-right font-semibold text-rose-700 whitespace-nowrap">{{ $rp($r->rugi) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="px-5 py-6 text-center text-gray-400 italic text-sm">Tidak ada retur marketplace pada periode ini.</div>
        @endif
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        {{-- Per alasan --}}
        <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100"><h2 class="font-bold text-gray-800 text-sm">Alasan Pembatalan</h2></div>
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50"><tr>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Alasan</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Jumlah</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Nilai</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($perAlasan as $a)
                        <tr>
                            <td class="px-4 py-2 text-gray-800">{{ $a->alasan }}</td>
                            <td class="px-4 py-2 text-right text-gray-700">{{ $a->c }}</td>
                            <td class="px-4 py-2 text-right text-gray-700 whitespace-nowrap">{{ $rp($a->nilai) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-6 text-center text-gray-400 italic">Tidak ada pembatalan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Per channel --}}
        <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100"><h2 class="font-bold text-gray-800 text-sm">Per Channel</h2></div>
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50"><tr>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Channel</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Jumlah</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Nilai</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($perChannel as $c)
                        <tr>
                            <td class="px-4 py-2 text-gray-800">{{ $c->channel }}</td>
                            <td class="px-4 py-2 text-right text-gray-700">{{ $c->c }}</td>
                            <td class="px-4 py-2 text-right text-gray-700 whitespace-nowrap">{{ $rp($c->nilai) }}</td>
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
        <div class="px-5 py-3 border-b border-gray-100"><h2 class="font-bold text-gray-800 text-sm">Daftar Pesanan Batal</h2></div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50"><tr>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Order</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Channel</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Tgl</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Alasan</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Nilai</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Barang</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($detail as $d)
                        <tr>
                            <td class="px-4 py-2"><a href="{{ route('penjualan.show', $d->internal_id) }}" class="text-indigo-600 hover:underline">{{ $d->external_order_id ?? ('INV-'.strtoupper(substr($d->internal_id,0,8))) }}</a></td>
                            <td class="px-4 py-2 text-gray-600">{{ $d->channel }}</td>
                            <td class="px-4 py-2 text-gray-600 whitespace-nowrap">{{ $tgl($d->tgl_pesanan) }}</td>
                            <td class="px-4 py-2 text-gray-700">{{ $d->alasan_batal ?? '—' }}</td>
                            <td class="px-4 py-2 text-right text-gray-700 whitespace-nowrap">{{ $rp((float)$d->gmv_kotor - (float)($d->diskon_manual ?? 0)) }}</td>
                            <td class="px-4 py-2 text-xs whitespace-nowrap">
                                @if($d->tgl_retur_diterima)
                                    <span class="text-emerald-600">✓ diterima {{ $tgl($d->tgl_retur_diterima) }}</span>
                                @elseif($d->perlu_barang_balik)
                                    <span class="text-blue-600">↩️ menunggu balik</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400 italic">Tidak ada pesanan batal pada filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-xs text-gray-400 mt-4">Nilai batal = harga jual − diskon (omzet yang gagal terjadi). Kerugian barang rusak = modal (botol telanjang) yang hangus saat barang balik dalam kondisi rusak. Banyak batal karena satu alasan (mis. "bibit kosong" / "alamat tidak ketemu") = sinyal untuk diperbaiki.</p>
</div>
</div>
</body>
</html>
