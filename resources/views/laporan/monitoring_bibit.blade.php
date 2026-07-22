<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Harga Bibit - Hagos ERP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">

@php
    $rp  = fn($n) => 'Rp ' . number_format((float)$n, 0, ',', '.');
    $rp2 = fn($n) => 'Rp ' . rtrim(rtrim(number_format((float)$n, 2, ',', '.'), '0'), ',');
    $ml  = fn($n) => rtrim(rtrim(number_format((float)$n, 2, ',', '.'), '0'), ',');
    $tgl = fn($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d/m/Y') : '—';
    $badge = [
        'under'   => ['⚠ HPP kemurahan', 'bg-red-100 text-red-700 border border-red-200'],
        'over'    => ['HPP kemahalan', 'bg-amber-100 text-amber-700 border border-amber-200'],
        'sync'    => ['Sinkron', 'bg-emerald-100 text-emerald-700 border border-emerald-200'],
        'no_data' => ['Belum ada beli', 'bg-gray-100 text-gray-500 border border-gray-200'],
    ];
    // Data untuk modal riwayat, keyed by bibit_id (dipakai Alpine).
    $modalData = $rows->mapWithKeys(fn($r) => [$r->bibit_id => ['nama' => $r->nama, 'master' => $r->master, 'riwayat' => $r->riwayat]]);
@endphp

<div class="p-4 sm:p-6 max-w-7xl mx-auto" x-data="{ sel: null, rows: {{ \Illuminate\Support\Js::from($modalData) }} }">

    <div class="mb-5">
        <h1 class="text-2xl font-bold text-gray-900">Monitoring Harga Bibit</h1>
        <p class="text-gray-600 mt-1 text-sm">Bibit ≈ 70% HPP. Pantau harga & deteksi <b>drift</b> — saat harga master (dipakai HPP) tertinggal dari harga beli nyata, margin diam-diam bocor.</p>
    </div>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        <div class="bg-white rounded-lg border border-red-200 p-4">
            <div class="text-xs text-gray-500">⚠ HPP kemurahan (margin bocor)</div>
            <div class="text-2xl font-bold text-red-600 mt-1">{{ $jmlUnder }} bibit</div>
            <div class="text-xs text-gray-500 mt-1">nilai stok {{ $rp($nilaiUnder) }}</div>
        </div>
        <div class="bg-white rounded-lg border border-amber-200 p-4">
            <div class="text-xs text-gray-500">HPP kemahalan</div>
            <div class="text-2xl font-bold text-amber-600 mt-1">{{ $jmlOver }} bibit</div>
        </div>
        <div class="bg-white rounded-lg border border-emerald-200 p-4">
            <div class="text-xs text-gray-500">Sinkron (harga akurat)</div>
            <div class="text-2xl font-bold text-emerald-600 mt-1">{{ $jmlSync }} bibit</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="text-xs text-gray-500">Total nilai stok bibit</div>
            <div class="text-2xl font-bold text-gray-800 mt-1">{{ $rp($nilaiTotal) }}</div>
        </div>
    </div>

    <div class="bg-indigo-50 border border-indigo-100 rounded-lg px-4 py-3 mb-4 text-xs text-indigo-800">
        <b>Cara baca:</b> <span class="text-red-700 font-semibold">HPP kemurahan</span> = harga master &lt; harga beli terakhir → HPP menghitung bibit terlalu murah, laba tampil lebih besar dari nyata (perlu update harga master).
        <span class="text-amber-700 font-semibold">HPP kemahalan</span> = master &gt; harga beli (HPP konservatif). Klik <b>Riwayat</b> untuk lihat tren harga tiap bibit.
    </div>

    {{-- Tabel utama --}}
    <div class="bg-white rounded-lg border border-gray-200 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Bibit</th>
                    <th class="px-4 py-3 text-right">Harga Master<br><span class="normal-case font-normal">(dipakai HPP)</span></th>
                    <th class="px-4 py-3 text-right">Harga Beli Terakhir</th>
                    <th class="px-4 py-3 text-left">Supplier · Tgl</th>
                    <th class="px-4 py-3 text-center">Drift</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-right">Stok · Nilai</th>
                    <th class="px-4 py-3 text-center">Riwayat</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($rows as $r)
                <tr class="{{ $r->status === 'under' ? 'bg-red-50/40' : '' }} hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-900">{{ $r->nama }}</div>
                        <div class="text-xs text-gray-400">{{ $r->bibit_id }}{{ $r->merek ? ' · '.$r->merek : '' }}</div>
                    </td>
                    <td class="px-4 py-3 text-right font-medium">{{ $rp2($r->master) }}<span class="text-gray-400 text-xs">/ml</span></td>
                    <td class="px-4 py-3 text-right">{{ $r->beli !== null ? $rp2($r->beli).'/ml' : '—' }}</td>
                    <td class="px-4 py-3 text-left text-xs text-gray-600">{{ $r->supplier ?: '—' }}<br><span class="text-gray-400">{{ $tgl($r->beli_tgl) }}</span></td>
                    <td class="px-4 py-3 text-center">
                        @if($r->drift !== null)
                            <span class="font-semibold {{ $r->status==='under' ? 'text-red-600' : ($r->status==='over' ? 'text-amber-600' : 'text-gray-500') }}">
                                {{ $r->drift > 0 ? '+' : '' }}{{ number_format($r->drift, 1, ',', '.') }}%
                            </span>
                        @else <span class="text-gray-300">—</span> @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-block px-2 py-1 rounded text-xs font-medium {{ $badge[$r->status][1] }}">{{ $badge[$r->status][0] }}</span>
                    </td>
                    <td class="px-4 py-3 text-right text-xs">
                        {{ $ml($r->stok) }} ml<br><span class="text-gray-500">{{ $rp($r->nilai) }}</span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($r->n_beli > 0)
                            <button type="button" @click="sel = rows['{{ $r->bibit_id }}']"
                                class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">📈 Riwayat ({{ $r->n_beli }})</button>
                        @else <span class="text-gray-300 text-xs">—</span> @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Modal Riwayat & Tren --}}
    <div x-show="sel" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="fixed inset-0 bg-gray-900/40" @click="sel = null"></div>
        <div class="relative bg-white rounded-lg shadow-xl w-full max-w-lg max-h-[80vh] overflow-y-auto">
            <div class="px-5 py-4 border-b flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-gray-900">Riwayat Harga · <span x-text="sel?.nama"></span></h3>
                    <p class="text-xs text-gray-500">Harga master sekarang (HPP): <b x-text="sel ? 'Rp ' + Number(sel.master).toLocaleString('id-ID') + '/ml' : ''"></b></p>
                </div>
                <button @click="sel = null" class="text-gray-400 hover:text-gray-700 text-xl leading-none">&times;</button>
            </div>
            <div class="p-5">
                <table class="w-full text-sm">
                    <thead class="text-xs text-gray-500 uppercase border-b">
                        <tr><th class="text-left py-2">Tanggal</th><th class="text-right py-2">Harga/ml</th><th class="text-center py-2">Tren</th><th class="text-left py-2 pl-3">Supplier</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="(h, i) in (sel?.riwayat || [])" :key="i">
                            <tr>
                                <td class="py-2" x-text="new Date(h.tgl).toLocaleDateString('id-ID')"></td>
                                <td class="py-2 text-right font-medium" x-text="'Rp ' + Number(h.harga).toLocaleString('id-ID',{maximumFractionDigits:2})"></td>
                                <td class="py-2 text-center text-xs">
                                    <template x-if="h.delta === null"><span class="text-gray-300">—</span></template>
                                    <template x-if="h.delta !== null">
                                        <span :class="h.delta > 0 ? 'text-red-600' : (h.delta < 0 ? 'text-emerald-600' : 'text-gray-400')"
                                              x-text="(h.delta > 0 ? '▲ +' : (h.delta < 0 ? '▼ ' : '')) + Number(h.delta).toFixed(1) + '%'"></span>
                                    </template>
                                </td>
                                <td class="py-2 pl-3 text-xs text-gray-600" x-text="h.supplier || '—'"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <p class="text-xs text-gray-400 mt-3" x-show="(sel?.riwayat || []).length < 2">Tren harga muncul setelah bibit ini dibeli lebih dari sekali.</p>
            </div>
        </div>
    </div>

</div>
</div>
<style>[x-cloak]{display:none!important}</style>
</body>
</html>
