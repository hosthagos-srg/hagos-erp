<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hagos ERP - Master Produk</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">

<div class="min-h-screen p-6">
    <header class="mb-6 flex flex-wrap justify-between items-center gap-3">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Master Produk</h1>
            <p class="text-gray-500 mt-1">{{ $produks->total() }} produk · indikator kesiapan jual (resep + harga)</p>
        </div>
        <a href="{{ route('master_produk.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">+ Tambah Produk</a>
    </header>

    @if(session('success'))<div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="mb-4 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded">{{ session('error') }}</div>@endif

    <form method="GET" class="mb-4">
        <input type="text" name="cari" value="{{ request('cari') }}" placeholder="Cari SKU / nama / aroma..."
            class="w-full max-w-sm border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
    </form>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Produk</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aroma</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ukuran</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Bentuk</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Resep</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Channel Berharga</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($produks as $p)
                        @php $punyaResep = $adaResep->has($p->sku_id); $jmlHarga = $hargaCounts[$p->sku_id] ?? 0; @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                <a href="{{ route('master_produk.detail', $p->sku_aroma) }}" class="text-indigo-600 hover:underline">{{ $p->sku_id }}</a>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $p->nama_produk }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('master_produk.detail', $p->sku_aroma) }}" class="text-gray-500 hover:text-indigo-600 hover:underline">{{ $p->sku_aroma }}</a>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-600">{{ $p->ukuran_ml }}ml</td>
                            <td class="px-4 py-3 text-center"><span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{{ $p->bentuk ?? '-' }}</span></td>
                            <td class="px-4 py-3 text-center">
                                @if($punyaResep)
                                    <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded">✓ Ada</span>
                                @else
                                    <span class="text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded">✕ Belum</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($jmlHarga > 0)
                                    <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded font-semibold">{{ $jmlHarga }} channel</span>
                                @else
                                    <span class="text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded">belum ada harga</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400 italic">Tidak ada produk.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-100">{{ $produks->links() }}</div>
    </div>
</div>
</div>
</body>
</html>
