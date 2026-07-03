<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hagos ERP - {{ $pelanggan->nama }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">

@php $rp = fn($n) => 'Rp ' . number_format($n, 0, ',', '.'); @endphp

<div class="min-h-screen p-6 max-w-5xl mx-auto">
    <header class="mb-6">
        <a href="{{ route('pelanggan.index') }}" class="text-sm text-gray-500 hover:underline">← Kembali ke Pelanggan</a>
        <div class="flex justify-between items-end mt-2">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $pelanggan->nama }}</h1>
                <p class="text-gray-500">{{ $pelanggan->tipe ?? '-' }} · {{ $pelanggan->no_hp ?? 'tanpa HP' }} · {{ $pelanggan->status }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-400 uppercase">Total Belanja</p>
                <p class="text-2xl font-bold text-gray-900">{{ $rp($totalBelanja) }}</p>
                @if($piutang > 0)<p class="text-sm text-red-600 font-semibold">Piutang: {{ $rp($piutang) }}</p>@endif
            </div>
        </div>
    </header>

    @if(session('success'))<div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="mb-4 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded"><ul class="list-disc list-inside text-sm">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Edit data --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h2 class="font-bold text-gray-800 mb-3">Data Pelanggan</h2>
                <form method="POST" action="{{ route('pelanggan.update', $pelanggan->id) }}">
                    @csrf
                    @include('pelanggan._form', ['p' => $pelanggan])
                    <button type="submit" class="w-full mt-3 bg-emerald-600 text-white py-2 rounded-md font-semibold hover:bg-emerald-700">Simpan Perubahan</button>
                </form>
                <form method="POST" action="{{ route('pelanggan.destroy', $pelanggan->id) }}" onsubmit="return confirm('Hapus pelanggan {{ $pelanggan->nama }}? Riwayat pesanannya tetap aman, hanya data pelanggan yang dihapus.')" class="mt-2">
                    @csrf @method('DELETE')
                    <button type="submit" class="w-full text-red-600 text-sm font-semibold py-2 rounded-md border border-red-200 hover:bg-red-50">🗑 Hapus Pelanggan</button>
                </form>
            </div>
        </div>

        {{-- Riwayat order --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100"><h2 class="font-bold text-gray-800 text-sm">Riwayat Order ({{ $orders->count() }})</h2></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50"><tr>
                            <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Tgl</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Order</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Channel</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Produk</th>
                            <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Nilai</th>
                            <th class="px-4 py-2 text-center text-xs text-gray-500 uppercase">Bayar</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($orders as $o)
                                @php $net = (float)$o->gmv_kotor - (float)($o->diskon_manual ?? 0); @endphp
                                <tr class="{{ $o->status_pesanan === 'Batal' ? 'opacity-50' : '' }}">
                                    <td class="px-4 py-2 text-gray-600 whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($o->tgl_pesanan)->format('d/m/Y') }}</td>
                                    <td class="px-4 py-2"><a href="{{ route('penjualan.show', $o->internal_id) }}" class="text-indigo-600 hover:underline">{{ $o->external_order_id ?: ('INV-'.strtoupper(substr($o->internal_id,0,8))) }}</a></td>
                                    <td class="px-4 py-2 text-gray-500">{{ $o->channel }}</td>
                                    <td class="px-4 py-2 text-gray-600 text-xs">{{ $o->produk ?? '-' }}</td>
                                    <td class="px-4 py-2 text-right font-medium text-gray-900 whitespace-nowrap">{{ $rp($net) }}</td>
                                    <td class="px-4 py-2 text-center">
                                        @php $c = in_array($o->status_pembayaran,['Cair','Lunas']) ? 'bg-green-100 text-green-700' : ($o->status_pembayaran==='Piutang' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700'); @endphp
                                        <span class="text-xs px-2 py-0.5 rounded {{ $c }}">{{ $o->status_pembayaran ?: '-' }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400 italic">Belum ada order atas nama ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
</body>
</html>
