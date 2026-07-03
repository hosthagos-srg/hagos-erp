<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Marketplace - Hagos ERP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Upload Data Marketplace</h1>
        </div>

        @if (session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded" role="alert">
                <p>{{ session('success') }}</p>
            </div>
        @endif
        @if (session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded" role="alert">
                <p>{{ session('error') }}</p>
            </div>
        @endif

        @php
            $unmappedCount = \App\Models\MarketplaceSku::whereNull('sku_id')->count();
        @endphp

        @if($unmappedCount > 0)
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 shadow-sm rounded-r-md">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-medium text-yellow-800">Tindakan Diperlukan: Ada {{ $unmappedCount }} Produk Belum Dikenali!</h3>
                    <p class="mt-1 text-sm text-yellow-700">Beberapa pesanan gagal diimpor karena nama produk di marketplace belum dipetakan ke SKU Hagos Anda.</p>
                </div>
                <a href="{{ route('mapping.index') }}" class="ml-4 inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-yellow-600 hover:bg-yellow-700">
                    Mulai Mapping SKU
                </a>
            </div>
        </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Upload Pesanan -->
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h2 class="text-xl font-semibold mb-4 border-b pb-2">1. Upload Laporan Pesanan (Order Report)</h2>
                <p class="text-sm text-gray-600 mb-4">Upload file CSV/Excel Pesanan dari Seller Center untuk memasukkan data ke Antrean Racik. Status otomatis: <span class="font-bold text-yellow-600">Belum Cair</span>.</p>
                
                <form action="{{ route('upload.pesanan') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Platform</label>
                        <select name="platform" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md border" required>
                            <option value="Shopee">Shopee</option>
                            <option value="TikTok">TikTok</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">File Pesanan (.csv / .xlsx)</label>
                        <input type="file" name="file_pesanan" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border rounded p-1" required>
                    </div>
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none">
                        Upload & Proses Pesanan
                    </button>
                </form>
            </div>

            <!-- Upload Settlement -->
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h2 class="text-xl font-semibold mb-4 border-b pb-2">2. Upload Laporan Pencairan (Settlement)</h2>
                <p class="text-sm text-gray-600 mb-4">Upload file Settlement/Income untuk mencocokkan uang masuk. Pesanan yang cocok akan berubah status menjadi: <span class="font-bold text-green-600">Cair</span>.</p>
                
                <form action="{{ route('upload.settlement') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Platform</label>
                        <select name="platform" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md border" required>
                            <option value="Shopee">Shopee</option>
                            <option value="TikTok">TikTok</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">File Settlement (.csv / .xlsx)</label>
                        <input type="file" name="file_settlement" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100 border rounded p-1" required>
                    </div>
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none">
                        Upload & Proses Pencairan
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>
</div>
</body>
</html>
