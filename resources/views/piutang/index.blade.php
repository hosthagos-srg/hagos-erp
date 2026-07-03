<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hagos ERP - Piutang & Aging</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">

@php $rp = fn($n) => 'Rp ' . number_format($n, 0, ',', '.'); @endphp

<div class="min-h-screen p-6">
    <header class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Piutang &amp; Aging</h1>
        <p class="text-gray-500 mt-1">Uang yang belum masuk: piutang reseller (jatuh tempo 7 hari) + settlement marketplace belum cair (12 hari).</p>
    </header>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-blue-500">
            <p class="text-xs text-gray-500">Total Piutang</p>
            <p class="text-xl font-bold text-blue-600">{{ $rp($totalPiutang) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-indigo-500">
            <p class="text-xs text-gray-500">Piutang Reseller</p>
            <p class="text-xl font-bold text-indigo-600">{{ $rp($totalReseller) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-amber-500">
            <p class="text-xs text-gray-500">Settlement MP Belum Cair</p>
            <p class="text-xl font-bold text-amber-600">{{ $rp($totalMP) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-red-500">
            <p class="text-xs text-gray-500">Lewat Jatuh Tempo</p>
            <p class="text-xl font-bold text-red-600">{{ $rp($totalLewat) }}</p>
            <p class="text-xs text-gray-400">{{ $jmlLewat }} pesanan</p>
        </div>
    </div>

    {{-- Filter tipe --}}
    <div class="mb-4 flex flex-wrap gap-2 text-sm items-center">
        <span class="text-xs font-semibold text-gray-400 uppercase mr-1">Tipe:</span>
        @php $tipes = ['' => 'Semua', 'reseller' => 'Reseller', 'marketplace' => 'Marketplace']; @endphp
        @foreach($tipes as $val => $lbl)
            <a href="{{ route('piutang.index', $val ? ['tipe' => $val] : []) }}"
                class="px-3 py-1 rounded-md {{ (request('tipe', '') === $val) ? 'bg-indigo-600 text-white' : 'bg-white border text-gray-600' }}">{{ $lbl }}</a>
        @endforeach
    </div>

    {{-- Matriks Aging per penghutang --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
        <div class="px-5 py-3 border-b border-gray-100"><h2 class="font-bold text-gray-800 text-sm">Aging per Penghutang (umur sejak tgl pesanan)</h2></div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50"><tr>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Penghutang</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">0–7 hari</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">8–14</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">15–30</th>
                    <th class="px-4 py-2 text-right text-xs text-red-500 uppercase">&gt;30</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Total</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($matrix as $m)
                        <tr>
                            <td class="px-4 py-2 text-gray-800">{{ $m['grup'] }} <span class="text-xs text-gray-400">({{ $m['tipe'] }})</span></td>
                            <td class="px-4 py-2 text-right text-gray-600">{{ $m['0-7'] > 0 ? $rp($m['0-7']) : '-' }}</td>
                            <td class="px-4 py-2 text-right text-gray-600">{{ $m['8-14'] > 0 ? $rp($m['8-14']) : '-' }}</td>
                            <td class="px-4 py-2 text-right text-amber-700">{{ $m['15-30'] > 0 ? $rp($m['15-30']) : '-' }}</td>
                            <td class="px-4 py-2 text-right text-red-600 font-semibold">{{ $m['>30'] > 0 ? $rp($m['>30']) : '-' }}</td>
                            <td class="px-4 py-2 text-right font-bold text-gray-900">{{ $rp($m['total']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-emerald-600 italic">✓ Tidak ada piutang. Semua sudah cair/lunas.</td></tr>
                    @endforelse
                </tbody>
                @if($matrix->isNotEmpty())
                <tfoot class="bg-gray-50 font-semibold">
                    <tr>
                        <td class="px-4 py-2 text-gray-700">TOTAL</td>
                        <td class="px-4 py-2 text-right text-gray-700">{{ $rp($bucketTotal['0-7']) }}</td>
                        <td class="px-4 py-2 text-right text-gray-700">{{ $rp($bucketTotal['8-14']) }}</td>
                        <td class="px-4 py-2 text-right text-amber-700">{{ $rp($bucketTotal['15-30']) }}</td>
                        <td class="px-4 py-2 text-right text-red-600">{{ $rp($bucketTotal['>30']) }}</td>
                        <td class="px-4 py-2 text-right text-gray-900">{{ $rp($totalPiutang) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- Detail (tertua dulu) --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100"><h2 class="font-bold text-gray-800 text-sm">Detail Pesanan Belum Lunas (tertua dulu)</h2></div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50"><tr>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Umur</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Tgl Pesan</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Order</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Pembeli / Channel</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Nilai</th>
                    <th class="px-4 py-2 text-center text-xs text-gray-500 uppercase">Status</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($detail as $d)
                        <tr class="{{ $d->jatuh_tempo ? 'bg-red-50' : '' }}">
                            <td class="px-4 py-2 font-semibold {{ $d->jatuh_tempo ? 'text-red-600' : 'text-gray-700' }}">{{ $d->umur }} hari</td>
                            <td class="px-4 py-2 text-gray-500 whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($d->tgl)->format('d/m/Y') }}</td>
                            <td class="px-4 py-2"><a href="{{ route('penjualan.show', $d->internal_id) }}" class="text-indigo-600 hover:underline">{{ $d->label }}</a></td>
                            <td class="px-4 py-2 text-gray-600">{{ $d->tipe === 'Reseller' ? $d->pembeli : $d->channel }} <span class="text-xs text-gray-400">{{ $d->tipe === 'Reseller' ? '· '.$d->channel : '' }}</span></td>
                            <td class="px-4 py-2 text-right font-semibold text-gray-900 whitespace-nowrap">{{ $rp($d->nilai) }}</td>
                            <td class="px-4 py-2 text-center">
                                @if($d->jatuh_tempo)
                                    <span class="text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded font-semibold">Lewat tempo ({{ $d->tempo_label }}h)</span>
                                @else
                                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">Belum tempo</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400 italic">Tidak ada piutang.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-xs text-gray-400 mt-4">Piutang reseller lunas lewat tombol "💵 Terima Bayar" di Kelola Pesanan. Settlement marketplace otomatis hilang dari sini saat status jadi Cair.</p>
</div>
</div>
</body>
</html>
