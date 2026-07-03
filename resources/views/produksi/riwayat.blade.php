<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Produksi - Hagos ERP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">

@php
    $tgl = fn($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d/m/Y') : '-';
    $unitLabel = $tipe === 'Absolute' ? 'ml dihasilkan' : 'botol tester';
    $fmtUnit = fn($n) => rtrim(rtrim(number_format($n, 2, ',', '.'), '0'), ',');
@endphp

<div class="min-h-screen p-6 max-w-5xl mx-auto">
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-3xl font-bold text-gray-900">🧪 Riwayat Produksi Internal</h1>
        <a href="{{ route('produksi.index') }}" class="text-indigo-600 hover:text-indigo-900 text-sm">&larr; Ke Produksi Internal</a>
    </div>

    {{-- Tab terpisah: Tester | Absolute --}}
    <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex space-x-6">
            <a href="{{ route('produksi.riwayat', ['tab' => 'tester']) }}"
               class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm {{ $tab === 'tester' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                📦 Riwayat Racik Tester
            </a>
            <a href="{{ route('produksi.riwayat', ['tab' => 'absolute']) }}"
               class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm {{ $tab === 'absolute' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                🧴 Riwayat Campur Absolute
            </a>
        </nav>
    </div>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-4">
        <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-500">Jumlah Sesi Produksi</p>
            <p class="text-2xl font-bold text-gray-900">{{ $totalSesi }}</p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-indigo-100 shadow-sm p-4">
            <p class="text-xs text-gray-500">Total {{ $tipe === 'Absolute' ? 'ml Dihasilkan' : 'Botol Tester' }}</p>
            <p class="text-2xl font-bold text-indigo-600">{{ $fmtUnit($totalUnit) }} <span class="text-sm font-normal text-gray-400">{{ $tipe === 'Absolute' ? 'ml' : 'botol' }}</span></p>
        </div>
    </div>

    {{-- Filter --}}
    <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-4 mb-4">
        <form method="GET" action="{{ route('produksi.riwayat') }}" class="flex flex-wrap items-end gap-3">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Dari</label>
                <input type="date" name="dari" value="{{ request('dari') }}" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Sampai</label>
                <input type="date" name="sampai" value="{{ request('sampai') }}" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Diracik Oleh</label>
                <select name="diracik_oleh" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm bg-white">
                    <option value="">-- Semua --</option>
                    @foreach($admins as $a)<option value="{{ $a }}" {{ request('diracik_oleh') === $a ? 'selected' : '' }}>{{ $a }}</option>@endforeach
                </select>
            </div>
            <button type="submit" class="bg-indigo-600 text-white px-4 py-1.5 rounded-md text-sm font-semibold hover:bg-indigo-700">Terapkan</button>
            @if(request()->anyFilled(['dari', 'sampai', 'diracik_oleh']))
                <a href="{{ route('produksi.riwayat', ['tab' => $tab]) }}" class="px-4 py-1.5 rounded-md text-sm text-gray-700 bg-gray-100 hover:bg-gray-200">Reset</a>
            @endif
        </form>
    </div>

    {{-- Tabel riwayat --}}
    <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50"><tr>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Tgl Racik</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Diracik Oleh</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Ringkasan</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Rincian</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($items as $r)
                        <tr class="align-top">
                            <td class="px-4 py-2 text-gray-600 whitespace-nowrap">{{ $tgl($r->tgl_racik) }}</td>
                            <td class="px-4 py-2 text-gray-800 whitespace-nowrap">{{ $r->diracik_oleh }}</td>
                            <td class="px-4 py-2 text-gray-800">{{ $r->ringkasan ?? '—' }}</td>
                            <td class="px-4 py-2 text-gray-600">
                                @php $d = $r->detail_text ?? []; @endphp
                                @if($tipe === 'Tester')
                                    <div class="flex flex-wrap gap-1">
                                        @foreach((array) $d as $row)
                                            <span class="text-xs bg-indigo-50 text-indigo-700 rounded-full px-2 py-0.5">{{ $row['nama_bibit'] ?? ($row['bibit_id'] ?? '?') }} ×{{ $row['qty'] ?? 0 }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-gray-600">
                                        Murni {{ $fmtUnit($d['ml_murni_digunakan'] ?? 0) }}ml + Denat {{ $fmtUnit($d['ml_denat_digunakan'] ?? 0) }}ml → <b>{{ $fmtUnit($d['ml_absolute_dihasilkan'] ?? 0) }}ml</b>
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400 italic">Belum ada riwayat {{ $tipe === 'Absolute' ? 'campur absolute' : 'racik tester' }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $items->links() }}</div>
        @endif
    </div>
</div>
</div>
</body>
</html>
