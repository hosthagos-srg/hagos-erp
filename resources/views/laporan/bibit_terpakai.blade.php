<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Bibit Terpakai - Hagos ERP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">
    <div class="min-h-screen p-6">
        <header class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">Laporan Bibit Terpakai</h1>
                <p class="text-sm text-gray-500 mt-1">Pemakaian bibit (aroma) dari pesanan yang sudah diproses · {{ $periode->translatedFormat('F Y') }}</p>
            </div>
            <form method="GET" class="flex items-end gap-2">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Periode</label>
                    <select name="bulan" onchange="this.form.submit()" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm">
                        @forelse($bulanTersedia as $b)
                            <option value="{{ $b }}" @selected($b===$bulan)>{{ $b }}</option>
                        @empty
                            <option value="{{ $bulan }}">{{ $bulan }}</option>
                        @endforelse
                    </select>
                </div>
                <input type="hidden" name="sort" value="{{ $sort }}">
            </form>
        </header>

        {{-- ─── KARTU RINGKASAN ─────────────────────────────── --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
            <div class="bg-white rounded-2xl p-5 ring-1 ring-gray-200/70 shadow-sm">
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">🧪 Total Bibit Terpakai</p>
                <p class="text-2xl font-bold text-indigo-600 mt-2">{{ number_format($totalMl, 1, ',', '.') }} <span class="text-base font-semibold text-gray-400">ml</span></p>
                <p class="text-xs text-gray-400 mt-1">{{ $jumlahAroma }} aroma berbeda</p>
            </div>
            <div class="bg-white rounded-2xl p-5 ring-1 ring-gray-200/70 shadow-sm">
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">📦 Total Botol Terjual</p>
                <p class="text-2xl font-bold text-emerald-600 mt-2">{{ number_format($totalQty, 0, ',', '.') }} <span class="text-base font-semibold text-gray-400">pcs</span></p>
                <p class="text-xs text-gray-400 mt-1">30ml: {{ $qty30 }} · 50ml: {{ $qty50 }}</p>
            </div>
            <div class="bg-white rounded-2xl p-5 ring-1 ring-gray-200/70 shadow-sm">
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">🍶 30ml Terjual</p>
                <p class="text-2xl font-bold text-blue-600 mt-2">{{ number_format($qty30, 0, ',', '.') }} <span class="text-base font-semibold text-gray-400">pcs</span></p>
                <p class="text-xs text-gray-400 mt-1">{{ $totalQty > 0 ? round($qty30/$totalQty*100) : 0 }}% dari total</p>
            </div>
            <div class="bg-white rounded-2xl p-5 ring-1 ring-gray-200/70 shadow-sm">
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">🍾 50ml Terjual</p>
                <p class="text-2xl font-bold text-violet-600 mt-2">{{ number_format($qty50, 0, ',', '.') }} <span class="text-base font-semibold text-gray-400">pcs</span></p>
                <p class="text-xs text-gray-400 mt-1">{{ $totalQty > 0 ? round($qty50/$totalQty*100) : 0 }}% dari total</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
            <div class="bg-gradient-to-br from-amber-50 to-white rounded-2xl p-5 ring-1 ring-amber-200/60 shadow-sm">
                <p class="text-xs text-amber-700 font-medium uppercase tracking-wide">🏆 Aroma Keluar Terbanyak</p>
                @if($aromaTop)
                    <p class="text-xl font-bold text-gray-900 mt-2">{{ $aromaTop->nama_bibit }}</p>
                    <p class="text-sm text-gray-500 mt-0.5">{{ number_format($aromaTop->total_ml, 1, ',', '.') }} ml · {{ $aromaTop->total_qty }} botol</p>
                @else
                    <p class="text-xl font-bold text-gray-400 mt-2">—</p>
                @endif
            </div>
            <div class="bg-white rounded-2xl p-5 ring-1 ring-gray-200/70 shadow-sm">
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">💰 Nilai Bibit Terpakai</p>
                <p class="text-xl font-bold text-rose-600 mt-2">Rp {{ number_format($nilaiBibit, 0, ',', '.') }}</p>
                <p class="text-xs text-gray-400 mt-1">estimasi biaya bibit (ml × harga/ml)</p>
            </div>
        </div>

        {{-- ─── TABEL ───────────────────────────────────────── --}}
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-200/70 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap justify-between items-center gap-3">
                <h2 class="text-base font-bold text-gray-800">Rincian per Aroma</h2>
                <form method="GET" class="flex items-center gap-2">
                    <input type="hidden" name="bulan" value="{{ $bulan }}">
                    <label class="text-xs text-gray-500">Urutkan:</label>
                    <select name="sort" onchange="this.form.submit()" class="border border-gray-300 rounded-md px-2 py-1 text-xs">
                        <option value="ml" @selected($sort==='ml')>Bibit terpakai (terbanyak)</option>
                        <option value="qty" @selected($sort==='qty')>Botol terjual (terbanyak)</option>
                        <option value="nilai" @selected($sort==='nilai')>Nilai Rp (tertinggi)</option>
                        <option value="nama" @selected($sort==='nama')>Nama aroma (A-Z)</option>
                    </select>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                        <tr>
                            <th class="text-left px-4 py-3 w-10">#</th>
                            <th class="text-left px-4 py-3">Aroma (Bibit)</th>
                            <th class="text-right px-4 py-3">Bibit Terpakai</th>
                            <th class="text-right px-4 py-3">Botol</th>
                            <th class="text-right px-4 py-3">30ml</th>
                            <th class="text-right px-4 py-3">50ml</th>
                            <th class="text-right px-4 py-3">Nilai (Rp)</th>
                            <th class="text-right px-4 py-3 w-28">Porsi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @php $maxMl = $rows->max('total_ml') ?: 1; @endphp
                        @forelse($rows as $i => $r)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-400 font-bold">{{ $i + 1 }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $r->nama_bibit }}</td>
                                <td class="px-4 py-3 text-right font-bold text-indigo-700">{{ number_format($r->total_ml, 1, ',', '.') }} ml</td>
                                <td class="px-4 py-3 text-right text-gray-700">{{ $r->total_qty }}</td>
                                <td class="px-4 py-3 text-right text-gray-500">{{ $r->qty_30 }}</td>
                                <td class="px-4 py-3 text-right text-gray-500">{{ $r->qty_50 }}</td>
                                <td class="px-4 py-3 text-right text-gray-600">Rp {{ number_format($r->nilai, 0, ',', '.') }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2 justify-end">
                                        <div class="w-16 bg-gray-100 rounded-full h-1.5">
                                            <div class="bg-indigo-400 h-1.5 rounded-full" style="width:{{ round($r->total_ml/$maxMl*100) }}%"></div>
                                        </div>
                                        <span class="text-xs text-gray-400 w-10 text-right">{{ $totalMl > 0 ? round($r->total_ml/$totalMl*100) : 0 }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-10 text-center text-gray-400">Belum ada pemakaian bibit di periode {{ $bulan }} (belum ada pesanan diproses).</td></tr>
                        @endforelse
                    </tbody>
                    @if($rows->count())
                    <tfoot class="bg-gray-50 font-bold text-gray-800">
                        <tr>
                            <td class="px-4 py-3" colspan="2">TOTAL</td>
                            <td class="px-4 py-3 text-right text-indigo-700">{{ number_format($totalMl, 1, ',', '.') }} ml</td>
                            <td class="px-4 py-3 text-right">{{ $totalQty }}</td>
                            <td class="px-4 py-3 text-right">{{ $qty30 }}</td>
                            <td class="px-4 py-3 text-right">{{ $qty50 }}</td>
                            <td class="px-4 py-3 text-right">Rp {{ number_format($nilaiBibit, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">100%</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>
