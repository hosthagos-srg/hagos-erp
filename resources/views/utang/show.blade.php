<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hagos ERP - Detail Cicilan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">

@include('partials.notifikasi_cicilan')

<div class="min-h-screen p-6">
    <header class="mb-6">
        <a href="{{ route('utang.index') }}" class="text-sm text-gray-500 hover:underline">← Kembali ke Utang/Cicilan</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">{{ $utang->deskripsi }}</h1>
        <p class="text-gray-500">{{ $utang->sumberDana->nama }} · {{ $utang->total_bulan }} bulan · mulai {{ \Carbon\Carbon::parse($utang->bulan_mulai)->format('M Y') }}</p>
    </header>

    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded">{{ session('success') }}</div>
    @endif

    @php
        $sudahBayar = $utang->pembayaran->where('status', 'lunas')->sum('jumlah_bayar');
        $sisaUtang = max(0, $utang->total_utang - $sudahBayar);
        $progress = $utang->total_utang > 0 ? ($sudahBayar / $utang->total_utang) * 100 : 0;
        $today = \Carbon\Carbon::today();
    @endphp

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm p-4">
            <p class="text-xs text-gray-500">Total Utang</p>
            <p class="text-xl font-bold text-gray-900">Rp {{ number_format($utang->total_utang, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4">
            <p class="text-xs text-gray-500">Sudah Dibayar</p>
            <p class="text-xl font-bold text-emerald-600">Rp {{ number_format($sudahBayar, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4">
            <p class="text-xs text-gray-500">Sisa Utang</p>
            <p class="text-xl font-bold text-red-600">Rp {{ number_format($sisaUtang, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4">
            <p class="text-xs text-gray-500">Status</p>
            <span class="inline-block px-2 py-1 rounded text-sm font-bold {{ $utang->status === 'lunas' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700' }}">
                {{ strtoupper($utang->status) }}
            </span>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm mb-2 px-4 py-2">
        <div class="flex justify-between text-xs text-gray-500 mb-1">
            <span>Progress Pelunasan</span>
            <span>{{ number_format($progress, 1) }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3">
            <div class="bg-emerald-500 h-3 rounded-full" style="width: {{ min(100, $progress) }}%"></div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-bold text-gray-800">Riwayat Cicilan ({{ $utang->pembayaran->count() }} periode)</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jatuh Tempo</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tagihan</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Dibayar</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Biaya Tambahan</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tgl Bayar</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($utang->pembayaran as $i => $tagihan)
                        @php
                            $selisih = $today->diffInDays($tagihan->periode, false);
                            $isAlert = $tagihan->status === 'belum' && $selisih <= 3;
                            $isTerlambat = $tagihan->status === 'belum' && $selisih < 0;
                        @endphp
                        <tr class="{{ $isTerlambat ? 'bg-red-50' : ($isAlert ? 'bg-yellow-50' : '') }}">
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $i + 1 }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                {{ $tagihan->periode->format('d M Y') }}
                                @if($isTerlambat)
                                    <span class="ml-1 text-xs bg-red-600 text-white px-1 rounded">TERLAMBAT</span>
                                @elseif($isAlert)
                                    <span class="ml-1 text-xs bg-yellow-500 text-white px-1 rounded">H-{{ $selisih }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-right text-gray-900">Rp {{ number_format($tagihan->jumlah_tagihan, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-sm text-right {{ $tagihan->status === 'lunas' ? 'text-emerald-600 font-semibold' : 'text-gray-400' }}">
                                {{ $tagihan->status === 'lunas' ? 'Rp ' . number_format($tagihan->jumlah_bayar, 0, ',', '.') : '-' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-right text-amber-700">
                                @if($tagihan->status === 'lunas' && $tagihan->biaya_tambahan > 0)
                                    Rp {{ number_format($tagihan->biaya_tambahan, 0, ',', '.') }}
                                    @if($tagihan->keterangan_biaya)
                                        <br><span class="text-xs text-gray-400">{{ $tagihan->keterangan_biaya }}</span>
                                    @endif
                                @else
                                    <span class="text-gray-300">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">
                                {{ $tagihan->tgl_bayar ? $tagihan->tgl_bayar->format('d/m/Y') : '-' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-xs px-2 py-0.5 rounded font-semibold {{ $tagihan->status === 'lunas' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                    {{ strtoupper($tagihan->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($tagihan->status === 'belum')
                                    <button onclick="openBayarModal({{ $tagihan->id }}, '{{ $tagihan->periode->format('d M Y') }}', {{ $tagihan->jumlah_tagihan }})"
                                        class="text-xs bg-emerald-600 text-white px-3 py-1 rounded hover:bg-emerald-700">
                                        Bayar
                                    </button>
                                @else
                                    <span class="text-xs text-gray-400">✓</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('partials.modal_bayar_cicilan')
</div>
</body>
</html>
