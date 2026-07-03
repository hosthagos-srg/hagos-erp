<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Barang Kembali - Hagos ERP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">

@php $tgl = fn($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d/m/Y') : '-'; @endphp

<div class="min-h-screen p-6 max-w-5xl mx-auto">
    <div class="mb-4">
        <a href="{{ route('penjualan.index') }}" class="text-indigo-600 hover:text-indigo-900 text-sm">&larr; Kembali ke Kelola Pesanan</a>
    </div>
    <header class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">↩️ Monitoring Barang Kembali</h1>
        <p class="text-gray-500 mt-1">Pesanan yang sudah <b>dibatalkan</b> tetapi barang fisiknya <b>masih dalam perjalanan balik</b> (di pembeli/ekspedisi). Saat barang sampai, konfirmasi kondisinya: <b>Layak jual</b> → masuk Stok Jadi (T11); <b>Rusak</b> → tidak masuk stok (dicatat sebagai kerugian).</p>
    </header>

    @if(session('success'))<div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>@endif

    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl ring-1 ring-blue-200 shadow-sm p-4">
            <p class="text-xs text-gray-500">Total Menunggu Barang Kembali</p>
            <p class="text-2xl font-bold text-blue-600">{{ $total }}</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50"><tr>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Order ID</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Channel</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Tgl Pesanan</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Umur</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Alasan Batal</th>
                    <th class="px-4 py-2 text-center text-xs text-gray-500 uppercase">Konfirmasi Barang Diterima</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($items as $c)
                        @php $umur = (int) \Illuminate\Support\Carbon::parse($c->tgl_pesanan)->diffInDays(now()); @endphp
                        <tr class="{{ $umur > 30 ? 'bg-red-50' : '' }}">
                            <td class="px-4 py-2">
                                <a href="{{ route('penjualan.show', $c->internal_id) }}" class="font-medium text-indigo-600 hover:underline">{{ $c->external_order_id ?? ('INV-' . strtoupper(substr($c->internal_id, 0, 8))) }}</a>
                                @if($c->no_resi)<div class="text-xs text-gray-400">📦 {{ $c->no_resi }}</div>@endif
                            </td>
                            <td class="px-4 py-2 text-gray-600">{{ $c->channel }}</td>
                            <td class="px-4 py-2 text-gray-600 whitespace-nowrap">{{ $tgl($c->tgl_pesanan) }}</td>
                            <td class="px-4 py-2 text-right whitespace-nowrap {{ $umur > 30 ? 'text-red-600 font-semibold' : 'text-gray-700' }}">{{ $umur }} hari</td>
                            <td class="px-4 py-2 text-gray-600">{{ $c->alasan_batal ?? '-' }}</td>
                            <td class="px-4 py-2">
                                <div class="flex gap-2 justify-center">
                                    <form method="POST" action="{{ route('penjualan.update_status', $c->internal_id) }}" onsubmit="return confirm('Barang diterima dalam kondisi LAYAK JUAL? Akan masuk Stok Jadi (T11).')">
                                        @csrf
                                        <input type="hidden" name="action" value="terima_barang">
                                        <input type="hidden" name="kondisi_terima" value="layak">
                                        <button type="submit" class="px-3 py-1 text-xs font-semibold rounded-md bg-emerald-600 text-white hover:bg-emerald-700">✓ Layak (masuk stok)</button>
                                    </form>
                                    <form method="POST" action="{{ route('penjualan.update_status', $c->internal_id) }}" onsubmit="return confirm('Barang diterima dalam kondisi RUSAK? TIDAK masuk stok, dicatat sebagai kerugian.')">
                                        @csrf
                                        <input type="hidden" name="action" value="terima_barang">
                                        <input type="hidden" name="kondisi_terima" value="rusak">
                                        <button type="submit" class="px-3 py-1 text-xs font-semibold rounded-md bg-red-100 text-red-700 hover:bg-red-200">Rusak (kerugian)</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-emerald-600 italic">✓ Tidak ada pesanan yang menunggu barang kembali.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-xs text-gray-400 mt-4">Baris merah = umur &gt; 30 hari (barang mungkin tidak akan kembali — pertimbangkan tandai sesuai kondisi). Setelah dikonfirmasi, pesanan hilang dari sini (tgl_retur_diterima terisi).</p>
</div>
</div>
</body>
</html>
