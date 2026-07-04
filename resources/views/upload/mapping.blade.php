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
                <p class="text-sm text-gray-600 mt-1">Semua nama produk marketplace & pasangan SKU Hagos-nya. Jodohkan, ubah, hapus, atau reset bila ada salah petakan.</p>
                <p class="text-xs mt-1">
                    <span class="text-amber-700 font-semibold">{{ $unmappedCount }} belum dipetakan</span>
                    @if($danglingCount > 0) · <span class="text-red-700 font-semibold">{{ $danglingCount }} menggantung ke SKU tak ada</span>@endif
                </p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <form action="{{ route('mapping.reset_all') }}" method="POST" class="inline"
                      onsubmit="return confirm('RESET semua peta? Semua pasangan SKU (termasuk yang ABAIKAN) akan dikosongkan sehingga perlu dipetakan ulang. Baris tidak dihapus. Lanjutkan?');">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 shadow-sm text-sm font-medium rounded-md text-white bg-amber-600 hover:bg-amber-700">♻️ Reset Semua Peta</button>
                </form>
                @if($unmappedCount > 0)
                <form action="{{ route('mapping.destroy_dangling') }}" method="POST" class="inline"
                      onsubmit="return confirm('Hapus SEMUA {{ $unmappedCount }} baris yang belum dipetakan? (Muncul lagi kalau file diupload ulang. Untuk produk lama tak dijual lagi, pakai ❌ ABAIKAN.)');">
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
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Platform</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Produk Marketplace</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Variasi</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pasangkan dengan SKU Hagos</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Hapus</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($rows as $item)
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $item->platform }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $item->marketplace_nama }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $item->marketplace_variasi }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs">
                                    @if($item->status_map === 'kosong')
                                        <span class="inline-flex px-2 py-0.5 rounded-md font-medium bg-amber-100 text-amber-800">⚠️ Belum dipetakan</span>
                                    @elseif($item->status_map === 'ok')
                                        <span class="inline-flex px-2 py-0.5 rounded-md font-medium bg-green-100 text-green-800">✓ {{ $item->nama_produk }}</span>
                                    @elseif($item->status_map === 'skip')
                                        <span class="inline-flex px-2 py-0.5 rounded-md font-medium bg-gray-100 text-gray-800">❌ Diabaikan</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded-md font-medium bg-red-100 text-red-800">⚠️ SKU '{{ $item->sku_id }}' tak ada</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                    <select name="mappings[{{ $item->id }}]" class="tom-select block w-full pl-3 pr-10 py-2 border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                        <option value="">-- Biarkan Menggantung --</option>
                                        <option value="SKIP" @selected($item->sku_id === 'SKIP')>❌ ABAIKAN / JANGAN IMPOR SELAMANYA</option>
                                        @foreach($products as $p)
                                            <option value="{{ $p->sku_id }}" @selected($item->sku_id === $p->sku_id)>{{ $p->nama_produk }} - {{ $p->ukuran_ml }}ml</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center">
                                    <button type="submit" form="del-{{ $item->id }}" onclick="return confirm('Hapus baris peta ini secara permanen?');" class="text-gray-400" title="Hapus baris ini">🗑</button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-4 py-4 text-sm text-gray-500 text-center">Belum ada data peta SKU. Muncul otomatis setelah upload file pesanan marketplace.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if(count($rows) > 0)
                <div class="pt-5 border-t border-gray-200 mt-6 flex justify-end">
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                        Simpan Perubahan Peta
                    </button>
                </div>
                @endif
            </form>

            {{-- Form hapus per-baris, terpisah dari form pemetaan (dipicu tombol via atribut form=) --}}
            @foreach($rows as $item)
            <form id="del-{{ $item->id }}" action="{{ route('mapping.destroy', $item->id) }}" method="POST" class="hidden">
                @csrf
                @method('DELETE')
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
