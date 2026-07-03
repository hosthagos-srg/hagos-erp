<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hagos ERP - Export Laporan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">

@php
    $fmt = function ($v, $type) {
        if ($type === 'rupiah') return 'Rp ' . number_format((float) $v, 0, ',', '.');
        if ($type === 'int') return number_format((float) $v, 0, ',', '.');
        return $v;
    };
    $params = ['jenis' => $jenis, 'dari' => $dari, 'sampai' => $sampai];
@endphp

<div class="min-h-screen p-6">
    <header class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Pusat Laporan & Export</h1>
        <p class="text-gray-500 mt-1">Pilih jenis & periode → lihat preview → export Excel / PDF.</p>
    </header>

    {{-- Filter --}}
    <div class="bg-white rounded-xl shadow-sm p-4 mb-5">
        <form method="GET" action="{{ route('laporan.export') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Jenis Laporan</label>
                <select name="jenis" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @foreach($jenisList as $k => $label)
                        <option value="{{ $k }}" {{ $jenis === $k ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Dari</label>
                <input type="date" name="dari" value="{{ $dari }}" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Sampai</label>
                <input type="date" name="sampai" value="{{ $sampai }}" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <button type="submit" class="bg-indigo-600 text-white px-5 py-2 rounded-md text-sm font-semibold hover:bg-indigo-700">Tampilkan</button>

            <div class="ml-auto flex gap-2">
                <a href="{{ route('laporan.export.excel', $params) }}" class="bg-emerald-600 text-white px-4 py-2 rounded-md text-sm font-semibold hover:bg-emerald-700">⬇ Excel</a>
                <a href="{{ route('laporan.export.pdf', $params) }}" class="bg-red-600 text-white px-4 py-2 rounded-md text-sm font-semibold hover:bg-red-700">⬇ PDF</a>
            </div>
        </form>
    </div>

    {{-- Preview --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-bold text-gray-800">{{ $report['title'] }}</h2>
            <p class="text-xs text-gray-500">Periode: {{ $report['periode_label'] }} · {{ count($report['rows']) }} baris</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        @foreach($report['columns'] as $c)
                            <th class="px-4 py-2 text-{{ in_array($c['type'], ['rupiah','int']) ? 'right' : 'left' }} text-xs font-medium text-gray-500 uppercase whitespace-nowrap">{{ $c['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($report['rows'] as $row)
                        <tr>
                            @foreach($report['columns'] as $i => $c)
                                @php $isNum = in_array($c['type'], ['rupiah','int']); @endphp
                                <td class="px-4 py-2 text-{{ $isNum ? 'right' : 'left' }} {{ $isNum ? 'whitespace-nowrap' : '' }} {{ ($c['type']==='rupiah' && (float)($row[$i]??0) < 0) ? 'text-red-600' : 'text-gray-700' }}">
                                    {{ $fmt($row[$i] ?? '', $c['type']) }}
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($report['columns']) }}" class="px-4 py-8 text-center text-gray-400 italic">Tidak ada data pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(!empty($report['summary']))
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                <h3 class="text-xs font-bold text-gray-500 uppercase mb-2">Ringkasan</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-x-8 gap-y-1 max-w-2xl">
                    @foreach($report['summary'] as $s)
                        <div class="flex justify-between border-b border-gray-100 py-1">
                            <span class="text-sm text-gray-600">{{ $s['label'] }}</span>
                            <span class="text-sm font-semibold {{ (($s['type']??'')==='rupiah' && (float)$s['value'] < 0) ? 'text-red-600' : 'text-gray-900' }}">{{ $fmt($s['value'], $s['type'] ?? 'text') }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
</div>
</body>
</html>
