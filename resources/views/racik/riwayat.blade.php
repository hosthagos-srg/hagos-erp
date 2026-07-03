<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hagos ERP - Riwayat Racik</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">

<div class="min-h-screen p-6">
    <header class="mb-6 flex flex-wrap justify-between items-center gap-3">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Riwayat Racik</h1>
            <p class="text-gray-500 mt-1">Pemantauan pesanan yang sudah diracik.</p>
        </div>
        <a href="{{ route('racik.index') }}" class="text-sm bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">← Antrean Racik</a>
    </header>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-green-500">
            <p class="text-xs text-gray-500">Total Pcs Diracik {{ (request('dari') || request('sampai')) ? '(periode)' : '' }}</p>
            <p class="text-2xl font-bold text-green-600">{{ $totalPcs }}</p>
        </div>
        @foreach($perPeracik->take(3) as $p)
            <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-gray-300">
                <p class="text-xs text-gray-500">{{ $p->oleh ?? '(tanpa nama)' }}</p>
                <p class="text-2xl font-bold text-gray-700">{{ $p->pcs }} <span class="text-sm font-normal text-gray-400">pcs</span></p>
            </div>
        @endforeach
    </div>

    {{-- Filter --}}
    <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Dari (tgl racik)</label>
                <input type="date" name="dari" value="{{ request('dari') }}" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Sampai</label>
                <input type="date" name="sampai" value="{{ request('sampai') }}" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Diracik Oleh</label>
                <select name="diracik_oleh" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm">
                    <option value="">-- Semua --</option>
                    @foreach($admins as $a)<option value="{{ $a }}" {{ request('diracik_oleh') === $a ? 'selected' : '' }}>{{ $a }}</option>@endforeach
                </select>
            </div>
            <button type="submit" class="bg-indigo-600 text-white px-4 py-1.5 rounded-md text-sm font-semibold hover:bg-indigo-700">Filter</button>
            @if(request('dari') || request('sampai') || request('diracik_oleh'))
                <a href="{{ route('racik.riwayat') }}" class="px-4 py-1.5 rounded-md text-sm font-semibold bg-gray-100 text-gray-600 hover:bg-gray-200">Reset</a>
            @endif
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50"><tr>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Tgl Racik</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Order</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Channel</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Produk</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Qty</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">HPP/unit</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Diracik Oleh</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($riwayat as $r)
                        <tr>
                            <td class="px-4 py-2 text-gray-700 whitespace-nowrap">{{ $r->tgl_racik ? \Illuminate\Support\Carbon::parse($r->tgl_racik)->format('d/m/Y') : '-' }}</td>
                            <td class="px-4 py-2 text-gray-600">{{ $r->external_order_id ?: ('INV-' . strtoupper(substr($r->h_internal, 0, 8))) }}@if($r->nama_pembeli)<span class="block text-xs text-gray-400">{{ $r->nama_pembeli }}</span>@endif</td>
                            <td class="px-4 py-2 text-gray-500">{{ $r->channel }}</td>
                            <td class="px-4 py-2 text-gray-700">{{ $r->nama_produk ?? $r->sku_id }} {{ $r->ukuran_ml }}ml @if($r->flag_swap)<span class="text-xs text-amber-600">↔</span>@endif</td>
                            <td class="px-4 py-2 text-right font-bold text-gray-900">{{ $r->qty }}</td>
                            <td class="px-4 py-2 text-right text-gray-500 whitespace-nowrap">{{ $r->hpp_satuan !== null ? 'Rp ' . number_format($r->hpp_satuan, 0, ',', '.') : '-' }}</td>
                            <td class="px-4 py-2"><span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded">{{ $r->diracik_oleh ?? '-' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400 italic">Belum ada riwayat racik.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-100">{{ $riwayat->links() }}</div>
    </div>
</div>
</div>
</body>
</html>
