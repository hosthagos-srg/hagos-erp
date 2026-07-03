<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hagos ERP - Tambah Utang</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">
<div class="min-h-screen p-6 max-w-2xl mx-auto">
    <header class="mb-6">
        <a href="{{ route('utang.index') }}" class="text-sm text-gray-500 hover:underline">← Kembali ke Utang/Cicilan</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Tambah Utang Cicilan Baru</h1>
    </header>

    @if($errors->any())
        <div class="mb-4 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded">
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm p-6">
        <form method="POST" action="{{ route('utang.store') }}">
            @csrf

            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">Sumber Dana <span class="text-red-500">*</span></label>
                <select name="sumber_dana_id" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">-- Pilih Sumber Dana --</option>
                    @foreach($sumberDana as $sd)
                        <option value="{{ $sd->id }}" {{ old('sumber_dana_id') == $sd->id ? 'selected' : '' }}>
                            {{ $sd->nama }} (jatuh tempo tgl {{ $sd->jatuh_tempo_tgl }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi / Keterangan <span class="text-red-500">*</span></label>
                <input type="text" name="deskripsi" value="{{ old('deskripsi') }}" required
                    placeholder="Contoh: Belanja bibit Januari 2026"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div class="grid grid-cols-2 gap-4 mb-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Total Utang (Rp) <span class="text-red-500">*</span></label>
                    <input type="number" name="total_utang" value="{{ old('total_utang') }}" required min="1" step="1"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cicilan per Bulan (Rp) <span class="text-red-500">*</span></label>
                    <input type="number" name="cicilan_per_bulan" value="{{ old('cicilan_per_bulan') }}" required min="1" step="1"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah Bulan Cicilan <span class="text-red-500">*</span></label>
                    <input type="number" name="total_bulan" value="{{ old('total_bulan') }}" required min="1" max="360"
                        placeholder="Contoh: 12"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bulan Mulai Cicilan <span class="text-red-500">*</span></label>
                    <input type="month" name="bulan_mulai" value="{{ old('bulan_mulai') }}" required
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan (opsional)</label>
                <textarea name="catatan" rows="2" placeholder="Catatan tambahan..."
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('catatan') }}</textarea>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="flex-1 bg-red-600 text-white py-2 rounded-md font-semibold hover:bg-red-700">Simpan Utang</button>
                <a href="{{ route('utang.index') }}" class="flex-1 text-center bg-gray-200 text-gray-700 py-2 rounded-md font-semibold hover:bg-gray-300">Batal</a>
            </div>
        </form>
    </div>
</div>
</div>
</body>
</html>
