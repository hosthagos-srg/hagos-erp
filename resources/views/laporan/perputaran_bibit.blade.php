<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perputaran & Stok Mati Bibit - Hagos ERP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">

@php
    $rp = fn($n) => 'Rp ' . number_format($n, 0, ',', '.');
    $ml = fn($n) => rtrim(rtrim(number_format($n, 2, ',', '.'), '0'), ',');
    $tgl = fn($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d/m/Y') : null;
    $badge = [
        'mati'       => ['Stok Mati', 'bg-red-100 text-red-700'],
        'lambat'     => ['Lambat', 'bg-amber-100 text-amber-700'],
        'perlu_beli' => ['Perlu Beli', 'bg-orange-100 text-orange-700'],
        'sehat'      => ['Sehat', 'bg-emerald-100 text-emerald-700'],
        'habis'      => ['Habis', 'bg-gray-200 text-gray-600'],
    ];
@endphp

<div class="min-h-screen p-6 max-w-6xl mx-auto">
    <header class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">🔄 Perputaran &amp; Stok Mati Bibit</h1>
            <p class="text-gray-500 mt-1">Bibit mana yang <b>mengendap</b> (tidak/lambat terpakai) = modal terkunci, dan mana yang menipis. Konsumsi dihitung dari pesanan diproses {{ $rentang }} bulan terakhir.</p>
        </div>
    </header>

    {{-- Filter rentang --}}
    <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('laporan.perputaran_bibit') }}" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Rentang Konsumsi</label>
                <select name="rentang" onchange="this.form.submit()" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm bg-white">
                    @foreach([1=>'1 bulan terakhir',3=>'3 bulan terakhir',6=>'6 bulan terakhir',12=>'12 bulan terakhir'] as $v => $lbl)
                        <option value="{{ $v }}" {{ $rentang === $v ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <span class="text-xs text-gray-400">Periode: {{ \Illuminate\Support\Carbon::parse($awal)->format('d/m/Y') }} – {{ \Illuminate\Support\Carbon::parse($akhir)->format('d/m/Y') }}</span>
        </form>
    </div>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-500">Nilai Total Persediaan Bibit</p>
            <p class="text-xl font-bold text-gray-900">{{ $rp($nilaiTotal) }}</p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-red-200 shadow-sm p-4">
            <p class="text-xs text-gray-500">💤 Modal Mengendap (Stok Mati)</p>
            <p class="text-xl font-bold text-red-600">{{ $rp($nilaiMati) }}</p>
            <p class="text-xs text-gray-400">{{ $jmlMati }} bibit tak terpakai</p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-amber-200 shadow-sm p-4">
            <p class="text-xs text-gray-500">Perputaran Lambat</p>
            <p class="text-xl font-bold text-amber-600">{{ $jmlLambat }} <span class="text-sm font-normal text-gray-400">bibit</span></p>
            <p class="text-xs text-gray-400">stok &gt; 6 bulan pemakaian</p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-500">Perlu Beli (menipis)</p>
            <p class="text-xl font-bold text-orange-600">{{ $jmlPerluBeli }} <span class="text-sm font-normal text-gray-400">bibit</span></p>
        </div>
    </div>

    {{-- Tabel --}}
    <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50"><tr>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Bibit</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Stok (ml)</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Nilai</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Terpakai ({{ $rentang }}bl)</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Sisa (bulan)</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Terakhir Dipakai</th>
                    <th class="px-4 py-2 text-center text-xs text-gray-500 uppercase">Status</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rows as $r)
                        @php [$lbl, $cls] = $badge[$r->status]; @endphp
                        <tr class="{{ $r->status === 'mati' ? 'bg-red-50' : '' }}">
                            <td class="px-4 py-2">
                                <div class="font-medium text-gray-800">{{ $r->nama }}</div>
                                <div class="text-xs text-gray-400">{{ $r->bibit_id }} · {{ $rp($r->harga) }}/ml</div>
                            </td>
                            <td class="px-4 py-2 text-right text-gray-700 whitespace-nowrap">{{ $ml($r->stok) }}</td>
                            <td class="px-4 py-2 text-right text-gray-700 whitespace-nowrap">{{ $rp($r->nilai) }}</td>
                            <td class="px-4 py-2 text-right text-gray-600 whitespace-nowrap">{{ $r->kons > 0 ? $ml($r->kons) . ' ml' : '—' }}</td>
                            <td class="px-4 py-2 text-right whitespace-nowrap {{ ($r->coverage !== null && $r->coverage > 6) ? 'text-amber-600 font-medium' : 'text-gray-600' }}">
                                @if($r->coverage === null) <span class="text-gray-400">tak terpakai</span>
                                @elseif($r->coverage > 99) &gt; 99 bln
                                @else {{ number_format($r->coverage, 1) }} bln @endif
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-gray-500 text-xs">{{ $tgl($r->last_used) ?? 'belum pernah' }}</td>
                            <td class="px-4 py-2 text-center"><span class="text-xs font-semibold px-2 py-0.5 rounded {{ $cls }}">{{ $lbl }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400 italic">Tidak ada data bibit.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-xs text-gray-400 mt-4"><b>Stok Mati</b> = ada stok tapi 0 pemakaian dalam {{ $rentang }} bulan (modal terkunci — pertimbangkan promo/bundling/stop beli). <b>Lambat</b> = stok cukup untuk &gt;6 bulan pemakaian. <b>Sisa (bulan)</b> = stok ÷ rata-rata pakai per bulan. Urut: stok mati bernilai terbesar di atas. Resep mix belum diperhitungkan (basis single-bibit).</p>
</div>
</div>
</body>
</html>
