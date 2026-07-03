<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hagos ERP - Utang & Cicilan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">

@include('partials.notifikasi_cicilan')

<div class="min-h-screen p-6">
    <header class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Utang & Cicilan</h1>
            <p class="text-gray-500 mt-1">Kelola semua cicilan berdasarkan sumber dana</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('utang.create') }}" class="inline-flex items-center px-4 py-2 bg-red-600 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">+ Tambah Utang</a>
            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gray-700 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-800">← Dashboard</a>
        </div>
    </header>

    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded">{{ session('success') }}</div>
    @endif

    {{-- UTANG AKTIF --}}
    <div class="mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Cicilan Aktif</h2>

        @if($utangAktif->isEmpty())
            <div class="bg-white rounded-xl shadow-sm p-6 text-center text-gray-500">Belum ada utang cicilan aktif.</div>
        @else
            <div class="space-y-4">
                @foreach($utangAktif as $utang)
                    @php
                        $today = \Carbon\Carbon::today();
                        $tagihanBelum = $utang->pembayaran->where('status', 'belum')->sortBy('periode');
                        $tagihanTerdekat = $tagihanBelum->first();
                        $sudahBayar = $utang->pembayaran->where('status', 'lunas')->sum('jumlah_bayar');
                        $sisaUtang = max(0, $utang->total_utang - $sudahBayar);
                        $progress = $utang->total_utang > 0 ? ($sudahBayar / $utang->total_utang) * 100 : 0;
                    @endphp
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-start">
                            <div>
                                <span class="text-xs font-semibold bg-blue-100 text-blue-700 px-2 py-0.5 rounded">{{ $utang->sumberDana->nama }}</span>
                                <h3 class="font-bold text-gray-900 mt-1">{{ $utang->deskripsi }}</h3>
                                @if($utang->catatan)
                                    <p class="text-xs text-gray-500 mt-0.5">{{ $utang->catatan }}</p>
                                @endif
                            </div>
                            <a href="{{ route('utang.show', $utang->id) }}" class="text-sm text-indigo-600 hover:underline">Lihat Detail →</a>
                        </div>
                        <div class="px-6 py-4">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                <div>
                                    <p class="text-xs text-gray-500">Total Utang</p>
                                    <p class="font-bold text-gray-900">Rp {{ number_format($utang->total_utang, 0, ',', '.') }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Sisa Utang</p>
                                    <p class="font-bold text-red-600">Rp {{ number_format($sisaUtang, 0, ',', '.') }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Cicilan/Bulan</p>
                                    <p class="font-bold text-gray-700">Rp {{ number_format($utang->cicilan_per_bulan, 0, ',', '.') }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Progress</p>
                                    <p class="font-bold text-emerald-600">{{ $utang->pembayaran->where('status','lunas')->count() }} / {{ $utang->total_bulan }} bulan</p>
                                </div>
                            </div>

                            {{-- Progress bar --}}
                            <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                                <div class="bg-emerald-500 h-2 rounded-full" style="width: {{ min(100, $progress) }}%"></div>
                            </div>

                            {{-- Tagihan belum bayar --}}
                            @if($tagihanBelum->isNotEmpty())
                                <div class="mt-2">
                                    <p class="text-xs font-semibold text-gray-600 mb-2">Tagihan Belum Dibayar:</p>
                                    <div class="space-y-2">
                                        @foreach($tagihanBelum->take(3) as $tagihan)
                                            @php
                                                $selisih = $today->diffInDays($tagihan->periode, false);
                                                $isAlert = $selisih <= 3;
                                                $isTerlambat = $selisih < 0;
                                            @endphp
                                            <div class="flex items-center justify-between {{ $isAlert ? ($isTerlambat ? 'bg-red-50 border border-red-200' : 'bg-yellow-50 border border-yellow-200') : 'bg-gray-50 border border-gray-200' }} rounded-lg px-3 py-2">
                                                <div class="flex items-center gap-2">
                                                    @if($isTerlambat)
                                                        <span class="text-xs font-bold bg-red-600 text-white px-2 py-0.5 rounded">TERLAMBAT {{ abs($selisih) }}h</span>
                                                    @elseif($selisih === 0)
                                                        <span class="text-xs font-bold bg-orange-500 text-white px-2 py-0.5 rounded">HARI INI</span>
                                                    @elseif($isAlert)
                                                        <span class="text-xs font-bold bg-yellow-500 text-white px-2 py-0.5 rounded">H-{{ $selisih }}</span>
                                                    @endif
                                                    <span class="text-sm text-gray-700">{{ $tagihan->periode->format('d M Y') }}</span>
                                                    <span class="text-sm font-semibold text-gray-900">Rp {{ number_format($tagihan->jumlah_tagihan, 0, ',', '.') }}</span>
                                                </div>
                                                <button onclick="openBayarModal({{ $tagihan->id }}, '{{ $tagihan->periode->format('d M Y') }}', {{ $tagihan->jumlah_tagihan }})"
                                                    class="text-xs bg-emerald-600 text-white px-3 py-1 rounded hover:bg-emerald-700">
                                                    Bayar
                                                </button>
                                            </div>
                                        @endforeach
                                        @if($tagihanBelum->count() > 3)
                                            <p class="text-xs text-gray-500 text-center">+ {{ $tagihanBelum->count() - 3 }} tagihan lainnya — <a href="{{ route('utang.show', $utang->id) }}" class="text-indigo-600 underline">Lihat semua</a></p>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <p class="text-sm text-emerald-600 font-semibold">✓ Semua cicilan sudah lunas</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- UTANG LUNAS --}}
    @if($utangLunas->isNotEmpty())
    <div>
        <h2 class="text-xl font-bold text-gray-500 mb-4">Cicilan Lunas</h2>
        <div class="space-y-2">
            @foreach($utangLunas as $utang)
            <div class="bg-gray-50 rounded-lg border border-gray-200 px-4 py-3 flex justify-between items-center">
                <div>
                    <span class="text-xs text-gray-400">{{ $utang->sumberDana->nama }}</span>
                    <p class="font-semibold text-gray-500">{{ $utang->deskripsi }}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-400">Rp {{ number_format($utang->total_utang, 0, ',', '.') }}</p>
                    <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded">LUNAS</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

@include('partials.modal_bayar_cicilan')

</div>
</body>
</html>
