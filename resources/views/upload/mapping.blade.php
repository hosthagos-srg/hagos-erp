<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapping SKU Marketplace - Hagos ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Pemetaan SKU Marketplace</h1>
                <p class="text-sm text-gray-600 mt-1">Sistem menemukan nama produk dari TikTok/Shopee yang tidak dikenali. Jodohkan dengan SKU Hagos Anda.</p>
            </div>
            <a href="{{ route('upload.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                Kembali
            </a>
        </div>

        <div class="bg-white shadow overflow-hidden sm:rounded-lg p-6">
            <form action="{{ route('mapping.store') }}" method="POST">
                @csrf
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Platform</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Produk di Marketplace</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Variasi/Ukuran</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pasangkan dengan SKU Hagos</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($unmappedSkus as $item)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item->platform }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $item->marketplace_nama }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item->marketplace_variasi }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <select name="mappings[{{ $item->id }}]" class="tom-select block w-full pl-3 pr-10 py-2 border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                        <option value="">-- Biarkan Menggantung --</option>
                                        <option value="SKIP" class="text-red-500 font-bold">❌ ABAIKAN / JANGAN IMPOR SELAMANYA</option>
                                        @foreach($products as $p)
                                            <option value="{{ $p->sku_id }}">{{ $p->nama_produk }} - {{ $p->ukuran_ml }}ml</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                    Hebat! Semua produk marketplace sudah terpetakan dengan sempurna.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if(count($unmappedSkus) > 0)
                <div class="pt-5 border-t border-gray-200 mt-6 flex justify-end">
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                        Simpan Pemetaan (Mapping)
                    </button>
                </div>
                @endif
            </form>
        </div>

    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll('.tom-select').forEach((el) => {
            new TomSelect(el, {
                create: false,
                sortField: { field: "text", direction: "asc" }
            });
        });
    });
</script>
</div>
</body>
</html>
