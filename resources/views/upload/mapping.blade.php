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

        <div class="flex justify-between items-start mb-4 gap-3 flex-wrap">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Pemetaan SKU Marketplace</h1>
                <p class="text-sm text-gray-600 mt-1">Nama produk marketplace yang <b>belum dikenali</b> SKU-nya. Jodohkan dengan SKU Hagos, lalu upload ulang file pesanan.</p>
                @if($danglingCount > 0)
                    <p class="text-xs mt-1 text-red-700 font-semibold">⚠️ {{ $danglingCount }} peta salah (dipetakan ke SKU yang tak ada) — cek di "Kelola Semua Peta".</p>
                @endif
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('mapping.kelola') }}" class="inline-flex items-center px-4 py-2 border border-indigo-300 shadow-sm text-sm font-medium rounded-md text-indigo-700 bg-indigo-50 hover:bg-indigo-100">📋 Kelola Semua Peta ({{ $mappedCount }})</a>
                @if(count($unmappedSkus) > 0)
                <form action="{{ route('mapping.destroy_dangling') }}" method="POST" class="inline"
                      onsubmit="return confirm('Hapus SEMUA {{ count($unmappedSkus) }} baris yang belum dipetakan? (Muncul lagi kalau file diupload ulang. Untuk produk lama tak dijual lagi, pakai ❌ ABAIKAN.)');">
                    @csrf @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 shadow-sm text-sm font-medium rounded-md text-white bg-red-600">🗑 Hapus Belum Dipetakan</button>
                </form>
                @endif
                <a href="{{ route('upload.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Kembali</a>
            </div>
        </div>

        <div class="bg-white shadow overflow-hidden sm:rounded-lg p-6">
            <form action="{{ route('mapping.store') }}" method="POST">
                @csrf
                <input type="hidden" name="back" value="mapping.index">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Platform</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Produk di Marketplace</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Variasi/Ukuran</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pasangkan dengan SKU Hagos</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Hapus</th>
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
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <button type="submit" form="del-{{ $item->id }}" onclick="return confirm('Hapus baris peta ini secara permanen?');" class="text-gray-400" title="Hapus baris ini">🗑</button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                    Hebat! Semua produk marketplace sudah terpetakan. (Lihat & kelola di "Kelola Semua Peta".)
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if(count($unmappedSkus) > 0)
                <div class="pt-5 border-t border-gray-200 mt-6 flex justify-end">
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                        Simpan Pemetaan
                    </button>
                </div>
                @endif
            </form>

            @foreach($unmappedSkus as $item)
            <form id="del-{{ $item->id }}" action="{{ route('mapping.destroy', $item->id) }}" method="POST" class="hidden">
                @csrf @method('DELETE')
            </form>
            @endforeach
        </div>

    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll('.tom-select').forEach((el) => {
            new TomSelect(el, { create: false, sortField: { field: "text", direction: "asc" } });
        });
    });
</script>
</div>
</body>
</html>
