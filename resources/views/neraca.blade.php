<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hagos ERP - Neraca</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">

@php $rp = fn($n) => 'Rp ' . number_format($n, 0, ',', '.'); @endphp

<div class="min-h-screen p-6 max-w-5xl mx-auto">
    <header class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Neraca</h1>
        <p class="text-gray-500 mt-1">Posisi keuangan per {{ now()->translatedFormat('d F Y') }} · Aset = Kewajiban + Modal</p>
    </header>

    {{-- Ringkasan utama --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-blue-500">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Aset</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">{{ $rp($totalAset) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-red-500">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Kewajiban</p>
            <p class="text-2xl font-bold text-red-600 mt-1">{{ $rp($totalKewajiban) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 {{ $modalBersih >= 0 ? 'border-emerald-500' : 'border-red-500' }}">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Modal Bersih (Kekayaan)</p>
            <p class="text-2xl font-bold {{ $modalBersih >= 0 ? 'text-emerald-600' : 'text-red-600' }} mt-1">{{ $rp($modalBersih) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- ASET --}}
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-3 bg-blue-50 border-b border-blue-100"><h2 class="font-bold text-blue-800">ASET</h2></div>
            <div class="divide-y divide-gray-100">
                {{-- Kas --}}
                <div class="px-6 py-3">
                    <div class="flex justify-between font-semibold text-gray-800"><span>Kas & Bank</span><span>{{ $rp($kas) }}</span></div>
                    <div class="mt-1 space-y-0.5">
                        @foreach($akunKas as $a)
                            <div class="flex justify-between text-xs text-gray-500"><span class="pl-3">{{ $a->nama }}</span><span>{{ $rp($a->saldo) }}</span></div>
                        @endforeach
                    </div>
                </div>
                {{-- Piutang --}}
                <div class="px-6 py-3">
                    <div class="flex justify-between font-semibold text-gray-800"><span>Piutang</span><span>{{ $rp($piutang) }}</span></div>
                    <div class="mt-1 space-y-0.5">
                        <div class="flex justify-between text-xs text-gray-500"><span class="pl-3">Piutang reseller</span><span>{{ $rp($piutangReseller) }}</span></div>
                        <div class="flex justify-between text-xs text-gray-500"><span class="pl-3">Settlement MP belum cair (perkiraan)</span><span>{{ $rp($piutangMP) }}</span></div>
                    </div>
                </div>
                {{-- Persediaan --}}
                <div class="px-6 py-3">
                    <div class="flex justify-between font-semibold text-gray-800"><span>Persediaan</span><span>{{ $rp($persediaan) }}</span></div>
                    <div class="mt-1 space-y-0.5">
                        <div class="flex justify-between text-xs text-gray-500"><span class="pl-3">Stok bibit</span><span>{{ $rp($nilaiBibit) }}</span></div>
                        <div class="flex justify-between text-xs text-gray-500"><span class="pl-3">Stok komponen</span><span>{{ $rp($nilaiKomponen) }}</span></div>
                        <div class="flex justify-between text-xs text-gray-500"><span class="pl-3">Produk jadi (T11)</span><span>{{ $rp($nilaiT11) }}</span></div>
                    </div>
                </div>
                <div class="px-6 py-3 flex justify-between items-center bg-blue-50">
                    <span class="font-bold text-blue-800">TOTAL ASET</span>
                    <span class="font-bold text-blue-800 text-lg">{{ $rp($totalAset) }}</span>
                </div>
            </div>
        </div>

        {{-- KEWAJIBAN + EKUITAS --}}
        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-3 bg-red-50 border-b border-red-100"><h2 class="font-bold text-red-800">KEWAJIBAN</h2></div>
                <div class="divide-y divide-gray-100">
                    <div class="px-6 py-3 flex justify-between text-gray-800"><span>Sisa Utang & Cicilan</span><span class="font-semibold">{{ $rp($sisaUtang) }}</span></div>
                    <div class="px-6 py-3 flex justify-between items-center bg-red-50">
                        <span class="font-bold text-red-800">TOTAL KEWAJIBAN</span>
                        <span class="font-bold text-red-800 text-lg">{{ $rp($totalKewajiban) }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-3 bg-emerald-50 border-b border-emerald-100"><h2 class="font-bold text-emerald-800">MODAL (EKUITAS)</h2></div>
                <div class="divide-y divide-gray-100">
                    <div class="px-6 py-3 flex justify-between text-gray-800"><span>Modal disetor (bersih prive)</span><span class="font-semibold {{ $modalDisetor < 0 ? 'text-red-600' : '' }}">{{ $rp($modalDisetor) }}</span></div>
                    <div class="px-6 py-3 flex justify-between text-gray-800"><span>Laba terakumulasi</span><span class="font-semibold {{ $labaAkumulasi < 0 ? 'text-red-600' : 'text-emerald-700' }}">{{ $rp($labaAkumulasi) }}</span></div>
                    <div class="px-6 py-3 flex justify-between items-center bg-emerald-50">
                        <span class="font-bold text-emerald-800">TOTAL MODAL</span>
                        <span class="font-bold text-emerald-800 text-lg">{{ $rp($modalBersih) }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-gray-800 rounded-xl shadow-sm px-6 py-4 flex justify-between items-center">
                <span class="text-white font-semibold">Kewajiban + Modal</span>
                <span class="text-white font-bold text-lg">{{ $rp($totalKewajiban + $modalBersih) }}</span>
            </div>
        </div>
    </div>

    <p class="text-xs text-gray-400 mt-4">Catatan: "Laba terakumulasi" dihitung sebagai turunan (Modal Bersih − Modal disetor) — bukan dari pembukuan akrual penuh, jadi Neraca selalu seimbang. Modal masuk/keluar dicatat di menu <a href="{{ route('saldo.modal_form') }}" class="text-indigo-600 underline">Modal & Prive</a>.</p>
</div>
</div>
</body>
</html>
