<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hagos ERP - Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="bg-slate-50 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">

@include('partials.notifikasi_cicilan')

    <div class="min-h-screen p-6">
        <header class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">Dashboard</h1>
                <p class="text-sm text-gray-500 mt-1">Ringkasan keuangan &amp; operasional HAGOS</p>
            </div>
            <div class="text-right">
                <p class="text-[11px] uppercase tracking-wide text-gray-400">Hari ini</p>
                <p class="text-sm font-semibold text-gray-700">{{ \Carbon\Carbon::now()->format('d M Y') }}</p>
            </div>
        </header>

        {{-- ─── STRIP OMZET ─────────────────────────────────── --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
            <a href="{{ route('penjualan.index') }}" class="group bg-white rounded-2xl p-5 ring-1 ring-gray-200/70 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                <div class="flex items-center justify-between">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-blue-50 text-blue-600 text-lg">💵</span>
                    @include('partials._delta', ['pct' => $finansial['omzet_hari_pct']])
                </div>
                <p class="text-2xl font-bold text-gray-900 mt-4">Rp {{ number_format($finansial['omzet_hari'], 0, ',', '.') }}</p>
                <p class="text-xs text-gray-500 font-medium mt-1">Omzet Hari Ini <span class="text-gray-400 font-normal">· vs kemarin</span></p>
            </a>
            <a href="{{ route('penjualan.index') }}" class="group bg-white rounded-2xl p-5 ring-1 ring-gray-200/70 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                <div class="flex items-center justify-between">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 text-lg">📅</span>
                    @include('partials._delta', ['pct' => $finansial['omzet_minggu_pct']])
                </div>
                <p class="text-2xl font-bold text-gray-900 mt-4">Rp {{ number_format($finansial['omzet_minggu'], 0, ',', '.') }}</p>
                <p class="text-xs text-gray-500 font-medium mt-1">Omzet Minggu Ini <span class="text-gray-400 font-normal">· vs minggu lalu</span></p>
            </a>
            <a href="{{ route('laporan.pl') }}" class="group bg-white rounded-2xl p-5 ring-1 ring-gray-200/70 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                <div class="flex items-center justify-between">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-violet-50 text-violet-600 text-lg">🗓️</span>
                    @include('partials._delta', ['pct' => $finansial['omzet_bulan_pct']])
                </div>
                <p class="text-2xl font-bold text-gray-900 mt-4">Rp {{ number_format($finansial['omzet_bulan'], 0, ',', '.') }}</p>
                <p class="text-xs text-gray-500 font-medium mt-1">Omzet Bulan Ini <span class="text-gray-400 font-normal">· vs bulan lalu</span></p>
            </a>
        </div>

        {{-- ─── ALERT FINANSIAL ─────────────────────────────── --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
            <a href="{{ route('penjualan.index') }}" class="group bg-white rounded-2xl p-5 ring-1 ring-amber-200/60 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-amber-50 text-amber-600 text-lg">⏳</span>
                    <div>
                        <p class="text-2xl font-bold text-amber-600">Rp {{ number_format($finansial['nyangkut_nilai'], 0, ',', '.') }}</p>
                        <p class="text-xs text-gray-500 font-medium">Settlement Belum Cair <span class="text-gray-400 font-normal">· {{ $finansial['nyangkut_jml'] }} pesanan</span></p>
                    </div>
                </div>
            </a>
            <a href="{{ route('utang.index') }}" class="group bg-white rounded-2xl p-5 ring-1 ring-red-200/60 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-red-50 text-red-600 text-lg">💳</span>
                    <div>
                        <p class="text-2xl font-bold text-red-600">Rp {{ number_format($finansial['total_hutang'], 0, ',', '.') }}</p>
                        <p class="text-xs text-gray-500 font-medium">Total Hutang
                            <span class="text-gray-400 font-normal">· cicilan {{ number_format($finansial['sisa_utang'], 0, ',', '.') }} + pribadi {{ number_format($finansial['sisa_utang_pribadi'], 0, ',', '.') }}</span>
                        </p>
                        @if($finansial['tagihan_terdekat'])
                            @php $td = $finansial['tagihan_terdekat']; @endphp
                            <p class="text-xs text-gray-400 font-normal">tagihan terdekat {{ \Carbon\Carbon::parse($td->periode)->format('d M') }} Rp {{ number_format($td->jumlah_tagihan, 0, ',', '.') }}</p>
                        @endif
                    </div>
                </div>
            </a>
        </div>

        {{-- ─── RINGKASAN PESANAN ───────────────────────────── --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <a href="{{ route('penjualan.index') }}" class="bg-white rounded-2xl p-6 ring-1 ring-gray-200/70 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Pesanan Minggu Ini</h2>
                <p class="text-4xl font-bold text-indigo-600 mt-2 tracking-tight">{{ $totalPesanan }}</p>
                <p class="text-xs text-gray-400 mt-1">@include('partials._delta', ['pct' => $totalPesananPct]) vs minggu lalu</p>
            </a>
            <a href="{{ route('penjualan.index') }}" class="bg-white rounded-2xl p-6 ring-1 ring-gray-200/70 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Pesanan Bulan Ini</h2>
                <p class="text-4xl font-bold text-emerald-600 mt-2 tracking-tight">{{ $totalPesananBln }}</p>
                <p class="text-xs text-gray-400 mt-1">@include('partials._delta', ['pct' => $totalPesananBlnPct]) vs bulan lalu</p>
            </a>
            <a href="{{ route('penjualan.index') }}" class="bg-white rounded-2xl p-6 ring-1 ring-gray-200/70 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Melewati Jatuh Tempo</h2>
                <p class="text-4xl font-bold text-red-600 mt-2 tracking-tight">{{ $totalLewatTempo }}</p>
                <p class="text-xs text-gray-400 mt-1">piutang &gt;7h / settlement &gt;12h</p>
            </a>
            <a href="{{ route('penjualan.index') }}" class="bg-white rounded-2xl p-6 ring-1 ring-gray-200/70 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Belum Cair</h2>
                <p class="text-4xl font-bold text-amber-500 mt-2 tracking-tight">{{ $totalBelumCair }}</p>
                <p class="text-xs text-gray-400 mt-1">uang belum masuk</p>
            </a>
        </div>

        {{-- ─── GRAFIK PENJUALAN ──────────────────────────────── --}}
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-200/70 overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap justify-between items-center gap-3">
                <div>
                    <h2 class="text-lg font-bold text-gray-800">Grafik Penjualan</h2>
                    <p id="grafik-subtitle" class="text-xs text-gray-400 mt-0.5"></p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    {{-- Tombol filter cepat --}}
                    <div class="flex rounded-lg border border-gray-200 overflow-hidden text-xs font-semibold">
                        <button onclick="setFilter('hari_ini')" data-filter="hari_ini"
                            class="filter-btn px-3 py-1.5 bg-gray-50 text-gray-600 hover:bg-indigo-50 hover:text-indigo-700 transition-colors">Hari Ini</button>
                        <button onclick="setFilter('minggu_ini')" data-filter="minggu_ini"
                            class="filter-btn px-3 py-1.5 bg-gray-50 text-gray-600 hover:bg-indigo-50 hover:text-indigo-700 border-l border-gray-200 transition-colors">Minggu Ini</button>
                        <button onclick="setFilter('bulan_ini')" data-filter="bulan_ini"
                            class="filter-btn px-3 py-1.5 bg-indigo-600 text-white border-l border-gray-200">Bulan Ini</button>
                        <button onclick="setFilter('bulan_lalu')" data-filter="bulan_lalu"
                            class="filter-btn px-3 py-1.5 bg-gray-50 text-gray-600 hover:bg-indigo-50 hover:text-indigo-700 border-l border-gray-200 transition-colors">Bulan Lalu</button>
                        <button onclick="toggleCustom()" data-filter="custom"
                            class="filter-btn px-3 py-1.5 bg-gray-50 text-gray-600 hover:bg-indigo-50 hover:text-indigo-700 border-l border-gray-200 transition-colors">Custom</button>
                    </div>
                    {{-- Input custom range --}}
                    <div id="custom-range" class="hidden flex items-center gap-1.5">
                        <input type="date" id="custom-dari" class="border border-gray-300 rounded px-2 py-1 text-xs">
                        <span class="text-gray-400 text-xs">–</span>
                        <input type="date" id="custom-sampai" class="border border-gray-300 rounded px-2 py-1 text-xs">
                        <button onclick="applyCustom()" class="px-3 py-1 bg-indigo-600 text-white rounded text-xs font-semibold hover:bg-indigo-700">Tampilkan</button>
                    </div>
                </div>
            </div>

            {{-- Kartu ringkasan periode --}}
            <div class="grid grid-cols-2 gap-0 border-b border-gray-100">
                <div class="px-6 py-3 border-r border-gray-100">
                    <p class="text-xs text-gray-400">Total Pesanan</p>
                    <p id="ringkasan-pesanan" class="text-2xl font-bold text-indigo-600">—</p>
                </div>
                <div class="px-6 py-3">
                    <p class="text-xs text-gray-400">Net Omset</p>
                    <p id="ringkasan-omset" class="text-2xl font-bold text-emerald-600">—</p>
                </div>
            </div>

            <div class="p-4 relative">
                <div id="grafik-loading" class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-80 z-10 hidden">
                    <div class="text-sm text-gray-400">Memuat data...</div>
                </div>
                <canvas id="grafikPenjualan" height="90"></canvas>
            </div>
        </div>

        {{-- ─── PRODUK TERBANYAK + RINGKASAN CHANNEL ─────── --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

            {{-- Produk Terjual Terbanyak --}}
            <div class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-200/70 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center gap-2">
                    <h2 class="text-base font-bold text-gray-800">🏆 Produk Terjual Terbanyak</h2>
                    <input type="month" id="produk-bulan" class="border border-gray-300 rounded-md px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div id="produk-konklusi" class="hidden px-6 py-2.5 text-xs border-b border-gray-50"></div>
                <div id="produk-loading" class="px-6 py-4 text-sm text-gray-400">Memuat...</div>
                <div id="produk-list" class="hidden divide-y divide-gray-100"></div>
            </div>

            {{-- Ringkasan per Channel --}}
            <div class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-200/70 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center gap-2">
                    <div>
                        <h2 class="text-base font-bold text-gray-800">📊 Ringkasan per Channel</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Net omset · non-batal</p>
                    </div>
                    <input type="month" id="channel-bulan" class="border border-gray-300 rounded-md px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div id="channel-konklusi" class="hidden px-6 py-2.5 text-xs border-b border-gray-50"></div>
                <div id="channel-loading" class="px-6 py-4 text-sm text-gray-400">Memuat...</div>
                <div id="channel-list" class="hidden divide-y divide-gray-100"></div>
                <div id="channel-footer" class="hidden px-6 py-3 bg-gray-50 border-t border-gray-100 flex justify-between items-center">
                    <span class="text-xs font-semibold text-gray-600">Total Net Omset</span>
                    <span id="channel-total" class="text-sm font-bold text-gray-900"></span>
                </div>
            </div>
        </div>

        {{-- ─── EXPENSE BREAKDOWN + RECENT ACTIVITY ──────── --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

            {{-- Breakdown Pengeluaran --}}
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm ring-1 ring-gray-200/70 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap justify-between items-center gap-3">
                    <h2 class="text-base font-bold text-gray-800">💸 Breakdown Pengeluaran</h2>
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="text-right">
                            <p id="exp-total" class="text-xl font-bold text-gray-900">—</p>
                            <p id="exp-total-label" class="text-xs text-gray-400">Total</p>
                        </div>
                        <select id="exp-periode" onchange="onExpPeriodeChange()" class="border border-gray-300 rounded-md px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="minggu">Minggu Ini</option>
                            <option value="minggu_lalu">Minggu Lalu</option>
                            <option value="bulan">Bulan Ini</option>
                            <option value="bulan_lalu">Bulan Lalu</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>
                </div>
                <div id="exp-custom" class="hidden px-6 py-2 border-b border-gray-50 flex items-center gap-2 text-xs">
                    <input type="date" id="exp-dari" class="border border-gray-300 rounded px-2 py-1 text-xs">
                    <span class="text-gray-400">–</span>
                    <input type="date" id="exp-sampai" class="border border-gray-300 rounded px-2 py-1 text-xs">
                    <button onclick="loadExpense()" class="px-3 py-1 bg-indigo-600 text-white rounded text-xs font-semibold hover:bg-indigo-700">Tampilkan</button>
                </div>
                <div id="exp-konklusi" class="hidden px-6 py-2.5 text-xs border-b border-gray-50"></div>
                <div class="p-4">
                    <canvas id="expenseChart" height="100"></canvas>
                    <div class="flex items-center justify-center gap-4 mt-3 text-xs">
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm inline-block" style="background:#a78bfa"></span>Belanja</span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm inline-block" style="background:#60a5fa"></span>Gaji</span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm inline-block" style="background:#86efac"></span>Operasional</span>
                    </div>
                </div>
            </div>

            {{-- Recent Activity --}}
            <div class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-200/70 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="text-base font-bold text-gray-800">🕑 Aktivitas Terbaru</h2>
                </div>
                <div class="divide-y divide-gray-50 max-h-[420px] overflow-y-auto">
                    @forelse($recentActivity as $a)
                        <div class="flex items-start gap-3 px-5 py-3">
                            <span class="flex-shrink-0 w-8 h-8 rounded-full {{ $a['color'] }} text-white flex items-center justify-center text-sm">{{ $a['icon'] }}</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-800">{{ $a['title'] }}</p>
                                <p class="text-xs text-gray-500 truncate">{{ $a['detail'] }}</p>
                            </div>
                            <span class="text-xs text-gray-400 flex-shrink-0 whitespace-nowrap">{{ $a['waktu'] }}</span>
                        </div>
                    @empty
                        <div class="px-5 py-8 text-center text-sm text-gray-400">Belum ada aktivitas.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Peringatan Stok Bibit --}}
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-200/70 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-red-50">
                <h2 class="text-lg font-bold text-red-700">Peringatan Stok Bibit (Perlu Beli)</h2>
            </div>
            <div class="p-0">
                @if($bibitWarnings->isEmpty())
                    <div class="p-6 text-center text-gray-500">Stok semua bibit aman.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU / ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Bibit</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sisa Stok (ml)</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Threshold (ml)</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($bibitWarnings as $bibit)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $bibit->sku_aroma ?? $bibit->bibit_id }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $bibit->nama_bibit }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-red-600">{{ $bibit->stok_ml }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $bibit->threshold_ml }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

    </div>

<script>
const GRAFIK_URL = '{{ route("dashboard.grafik") }}';
const STATS_URL  = '{{ route("dashboard.stats") }}';
let chart = null;
let activeFilter = 'bulan_ini';

function formatRupiah(n) {
    if (n >= 1_000_000) return 'Rp ' + (n / 1_000_000).toFixed(1).replace('.0','') + ' Jt';
    if (n >= 1_000) return 'Rp ' + (n / 1_000).toFixed(0) + ' Rb';
    return 'Rp ' + n.toLocaleString('id-ID');
}

function formatRupiahFull(n) {
    return 'Rp ' + Number(n).toLocaleString('id-ID');
}

async function loadGrafik(params = {}) {
    document.getElementById('grafik-loading').classList.remove('hidden');
    const qs = new URLSearchParams({ filter: activeFilter, ...params }).toString();
    try {
        const res = await fetch(GRAFIK_URL + '?' + qs);
        const data = await res.json();
        renderGrafik(data);
    } catch(e) {
        console.error(e);
    } finally {
        document.getElementById('grafik-loading').classList.add('hidden');
    }
}

function renderGrafik(data) {
    // Ringkasan
    document.getElementById('ringkasan-pesanan').textContent = data.ringkasan.total_pesanan;
    document.getElementById('ringkasan-omset').textContent = formatRupiahFull(data.ringkasan.total_omset);

    // Subtitle
    const subtitles = {
        hari_ini: 'Per jam — hari ini',
        minggu_ini: 'Per hari — minggu ini',
        bulan_ini: 'Per hari — bulan ini',
        custom: 'Rentang kustom',
    };
    document.getElementById('grafik-subtitle').textContent = subtitles[activeFilter] || '';

    const ctx = document.getElementById('grafikPenjualan').getContext('2d');
    if (chart) chart.destroy();

    chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [
                {
                    label: 'Pesanan',
                    data: data.pesanan,
                    backgroundColor: 'rgba(99, 102, 241, 0.15)',
                    borderColor: 'rgba(99, 102, 241, 0.8)',
                    borderWidth: 2,
                    borderRadius: 4,
                    yAxisID: 'yPesanan',
                    order: 2,
                },
                {
                    label: 'Net Omset',
                    data: data.omset,
                    type: 'line',
                    borderColor: 'rgba(16, 185, 129, 0.9)',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2.5,
                    pointRadius: data.labels.length > 20 ? 0 : 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.3,
                    yAxisID: 'yOmset',
                    order: 1,
                },
            ],
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top', labels: { usePointStyle: true, padding: 16, font: { size: 12 } } },
                tooltip: {
                    callbacks: {
                        label: ctx => {
                            if (ctx.dataset.label === 'Net Omset')
                                return ' ' + ctx.dataset.label + ': ' + formatRupiahFull(ctx.raw);
                            return ' ' + ctx.dataset.label + ': ' + ctx.raw + ' pesanan';
                        }
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 11 }, maxRotation: 45 } },
                yPesanan: {
                    type: 'linear', position: 'left',
                    beginAtZero: true,
                    ticks: { precision: 0, font: { size: 11 } },
                    grid: { color: 'rgba(0,0,0,0.05)' },
                    title: { display: true, text: 'Pesanan', font: { size: 11 }, color: '#6366f1' },
                },
                yOmset: {
                    type: 'linear', position: 'right',
                    beginAtZero: true,
                    grid: { drawOnChartArea: false },
                    ticks: { font: { size: 11 }, callback: v => formatRupiah(v) },
                    title: { display: true, text: 'Omset', font: { size: 11 }, color: '#10b981' },
                },
            },
        },
    });
}

function setFilter(f) {
    activeFilter = f;
    // Update tombol aktif
    document.querySelectorAll('.filter-btn').forEach(btn => {
        const isActive = btn.dataset.filter === f;
        btn.classList.toggle('bg-indigo-600', isActive);
        btn.classList.toggle('text-white', isActive);
        btn.classList.toggle('bg-gray-50', !isActive);
        btn.classList.toggle('text-gray-600', !isActive);
    });
    // Sembunyikan custom range kecuali filter custom
    if (f !== 'custom') {
        document.getElementById('custom-range').classList.add('hidden');
        loadGrafik();
    }
}

function toggleCustom() {
    activeFilter = 'custom';
    document.querySelectorAll('.filter-btn').forEach(btn => {
        const isActive = btn.dataset.filter === 'custom';
        btn.classList.toggle('bg-indigo-600', isActive);
        btn.classList.toggle('text-white', isActive);
        btn.classList.toggle('bg-gray-50', !isActive);
        btn.classList.toggle('text-gray-600', !isActive);
    });
    document.getElementById('custom-range').classList.remove('hidden');
    // Set default date
    const today = new Date().toISOString().slice(0,10);
    const sebulanLalu = new Date(Date.now() - 29*86400000).toISOString().slice(0,10);
    if (!document.getElementById('custom-dari').value) document.getElementById('custom-dari').value = sebulanLalu;
    if (!document.getElementById('custom-sampai').value) document.getElementById('custom-sampai').value = today;
}

function applyCustom() {
    const dari = document.getElementById('custom-dari').value;
    const sampai = document.getElementById('custom-sampai').value;
    if (!dari || !sampai) return;
    loadGrafik({ dari, sampai });
}

// ── Helper perbandingan ──
function pctBadge(pct) {
    if (pct === null || pct === undefined) return '<span class="text-gray-400">baru</span>';
    const up = pct >= 0;
    return `<span class="${up ? 'text-emerald-600' : 'text-red-600'} font-semibold">${up ? '▲' : '▼'} ${Math.abs(pct)}%</span>`;
}
function renderKonklusi(elId, pct, prevLabel, naikWord, turunWord) {
    const el = document.getElementById(elId);
    const base = 'px-6 py-2.5 text-xs border-b border-gray-50 ';
    if (pct === null || pct === undefined) {
        el.className = base + 'text-gray-500';
        el.innerHTML = `Belum ada data periode pembanding (${prevLabel}).`;
        return;
    }
    const up = pct >= 0;
    el.className = base + (up ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700');
    el.innerHTML = `<b>${up ? '▲' : '▼'} ${up ? naikWord : turunWord} ${Math.abs(pct)}%</b> vs ${prevLabel}`;
}

async function fetchStats(bulan) {
    const res = await fetch(STATS_URL + '?bulan=' + bulan);
    return res.json();
}
// Produk & Channel kini INDEPENDEN — masing-masing punya pemilih bulan sendiri.
async function loadProduk(bulan) {
    bulan = bulan || document.getElementById('produk-bulan').value || new Date().toISOString().slice(0, 7);
    document.getElementById('produk-loading').classList.remove('hidden');
    document.getElementById('produk-list').classList.add('hidden');
    try { renderProduk(await fetchStats(bulan)); } catch(e) { console.error(e); }
}
async function loadChannel(bulan) {
    bulan = bulan || document.getElementById('channel-bulan').value || new Date().toISOString().slice(0, 7);
    document.getElementById('channel-loading').classList.remove('hidden');
    document.getElementById('channel-list').classList.add('hidden');
    document.getElementById('channel-footer').classList.add('hidden');
    try { renderChannel(await fetchStats(bulan)); } catch(e) { console.error(e); }
}

function renderProduk(data) {
    const produk = data.produk_terbanyak || [];
    document.getElementById('produk-loading').classList.add('hidden');
    renderKonklusi('produk-konklusi', data.produk_total_pct, data.prev_label, 'Total penjualan naik', 'Total penjualan turun');
    const el = document.getElementById('produk-list');
    if (!produk.length) {
        el.innerHTML = '<div class="px-6 py-4 text-sm text-gray-400">Belum ada data penjualan di ' + data.bulan_label + '.</div>';
        el.classList.remove('hidden');
        return;
    }
    const maxQty = produk[0].total_qty;
    el.innerHTML = produk.map((p, i) => `
        <div class="px-5 py-3">
            <div class="flex items-center justify-between mb-1">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="text-xs font-bold w-5 text-gray-400 flex-shrink-0">${i+1}</span>
                    <span class="text-sm font-medium text-gray-800 truncate">${p.nama_produk} <span class="text-gray-400 font-normal">${p.ukuran_ml}ml</span></span>
                </div>
                <span class="text-sm font-bold text-indigo-700 flex-shrink-0 ml-2">${p.total_qty} pcs</span>
            </div>
            <div class="flex items-center gap-2 ml-7">
                <div class="flex-1 bg-gray-100 rounded-full h-1.5">
                    <div class="bg-indigo-400 h-1.5 rounded-full" style="width:${(p.total_qty/maxQty*100).toFixed(0)}%"></div>
                </div>
                <span class="text-xs text-gray-400 flex-shrink-0">Rp ${Number(p.total_omset).toLocaleString('id-ID')}</span>
                <span class="text-xs flex-shrink-0 w-14 text-right" title="vs ${data.prev_label}">${pctBadge(p.pct)}</span>
            </div>
        </div>
    `).join('');
    el.classList.remove('hidden');
}

const CHANNEL_META = {
    TikTok:   { badge: 'bg-gray-900 text-white', bar: '#111827' },
    Shopee:   { badge: 'bg-orange-500 text-white', bar: '#f97316' },
    Offline:  { badge: 'bg-blue-600 text-white', bar: '#2563eb' },
    Website:  { badge: 'bg-violet-600 text-white', bar: '#7c3aed' },
    Reseller: { badge: 'bg-emerald-600 text-white', bar: '#059669' },
};

function renderChannel(data) {
    const channels = data.channel || [];
    const totalNet = data.total_net_omset || 0;
    document.getElementById('channel-loading').classList.add('hidden');
    renderKonklusi('channel-konklusi', data.total_net_pct, data.prev_label, 'Net omset naik', 'Net omset turun');
    const el = document.getElementById('channel-list');
    const footer = document.getElementById('channel-footer');
    if (!channels.length) {
        el.innerHTML = '<div class="px-6 py-4 text-sm text-gray-400">Belum ada data penjualan di ' + data.bulan_label + '.</div>';
        el.classList.remove('hidden');
        footer.classList.add('hidden');
        return;
    }
    el.innerHTML = channels.map(c => {
        const share = totalNet > 0 ? (c.net_omset / totalNet * 100).toFixed(1) : 0;
        const meta = CHANNEL_META[c.channel] || { badge: 'bg-gray-500 text-white', bar: '#6b7280' };
        return `
        <div class="px-5 py-3">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold px-2 py-0.5 rounded ${meta.badge}">${c.channel}</span>
                    <span class="text-xs text-gray-500">${c.jml} pesanan</span>
                </div>
                <div class="text-right">
                    <p class="text-sm font-bold text-gray-900">Rp ${Number(c.net_omset).toLocaleString('id-ID')}</p>
                    <p class="text-xs text-gray-400">${share}% porsi · <span title="vs ${data.prev_label}">${pctBadge(c.pct)}</span></p>
                </div>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-2">
                <div class="h-2 rounded-full" style="width:${share}%; background-color:${meta.bar}; opacity:0.75"></div>
            </div>
        </div>`;
    }).join('');
    document.getElementById('channel-total').textContent = 'Rp ' + Number(totalNet).toLocaleString('id-ID');
    el.classList.remove('hidden');
    footer.classList.remove('hidden');
}

// ── Expense Breakdown chart ──
const EXPENSE_URL = '{{ route("dashboard.expense_breakdown") }}';
let expChart = null;
const EXP_LABEL = { minggu:'Minggu Ini', minggu_lalu:'Minggu Lalu', bulan:'Bulan Ini', bulan_lalu:'Bulan Lalu', custom:'Custom' };
function onExpPeriodeChange() {
    const periode = document.getElementById('exp-periode').value;
    const custom = document.getElementById('exp-custom');
    if (periode === 'custom') {
        custom.classList.remove('hidden');
        const today = new Date().toISOString().slice(0,10);
        const lalu = new Date(Date.now() - 13*86400000).toISOString().slice(0,10);
        if (!document.getElementById('exp-dari').value) document.getElementById('exp-dari').value = lalu;
        if (!document.getElementById('exp-sampai').value) document.getElementById('exp-sampai').value = today;
    } else {
        custom.classList.add('hidden');
        loadExpense();
    }
}
async function loadExpense() {
    const periode = document.getElementById('exp-periode').value;
    let url = EXPENSE_URL + '?periode=' + periode;
    if (periode === 'custom') {
        const dari = document.getElementById('exp-dari').value, sampai = document.getElementById('exp-sampai').value;
        if (!dari || !sampai) return;
        url += '&dari=' + dari + '&sampai=' + sampai;
    }
    try {
        const res = await fetch(url);
        const data = await res.json();
        document.getElementById('exp-total').textContent = formatRupiahFull(data.total);
        document.getElementById('exp-total-label').textContent = 'Total ' + (EXP_LABEL[periode] || '');
        // Kesimpulan: pengeluaran NAIK = merah (jelek), TURUN = hijau (bagus) — kebalikan dari omset
        const ke = document.getElementById('exp-konklusi');
        if (data.total_pct === null || data.total_pct === undefined) {
            ke.className = 'px-6 py-2.5 text-xs border-b border-gray-50 text-gray-500';
            ke.innerHTML = `Belum ada data pembanding (${data.prev_label}).`;
        } else {
            const naik = data.total_pct >= 0;
            const c = data.kategori;
            const detail = ['Belanja','Gaji','Operasional'].filter(k => c[k].pct !== null).map(k => `${k} ${pctBadge(c[k].pct)}`).join(' · ');
            ke.className = 'px-6 py-2.5 text-xs border-b border-gray-50 ' + (naik ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700');
            ke.innerHTML = `<b>${naik ? '▲' : '▼'} Pengeluaran ${naik ? 'naik' : 'turun'} ${Math.abs(data.total_pct)}%</b> vs ${data.prev_label}` + (detail ? ` <span class="text-gray-500">· ${detail}</span>` : '');
        }
        const ctx = document.getElementById('expenseChart').getContext('2d');
        if (expChart) expChart.destroy();
        expChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [
                    { label: 'Belanja', data: data.series.Belanja, backgroundColor: '#a78bfa', borderRadius: 4, stack: 's' },
                    { label: 'Gaji', data: data.series.Gaji, backgroundColor: '#60a5fa', borderRadius: 4, stack: 's' },
                    { label: 'Operasional', data: data.series.Operasional, backgroundColor: '#86efac', borderRadius: 4, stack: 's' },
                ],
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: c => ' ' + c.dataset.label + ': ' + formatRupiahFull(c.raw) } },
                },
                scales: {
                    x: { stacked: true, grid: { display: false }, ticks: { font: { size: 11 } } },
                    y: { stacked: true, beginAtZero: true, ticks: { font: { size: 11 }, callback: v => formatRupiah(v) }, grid: { color: 'rgba(0,0,0,0.05)' } },
                },
            },
        });
    } catch(e) { console.error(e); }
}

// Load default saat halaman siap
document.addEventListener('DOMContentLoaded', () => {
    const thisMonth = new Date().toISOString().slice(0, 7);
    document.getElementById('produk-bulan').value = thisMonth;
    document.getElementById('channel-bulan').value = thisMonth;
    document.getElementById('produk-bulan').addEventListener('change', e => loadProduk(e.target.value));
    document.getElementById('channel-bulan').addEventListener('change', e => loadChannel(e.target.value));
    loadGrafik();
    loadProduk();
    loadChannel();
    loadExpense();
});
</script>

</div>
</body>
</html>
