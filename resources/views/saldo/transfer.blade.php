<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hagos ERP - Transfer Antar Akun</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">

<div class="min-h-screen p-6 max-w-2xl mx-auto">
    <header class="mb-6">
        <a href="{{ route('saldo.index') }}" class="text-sm text-gray-500 hover:underline">← Kembali ke Saldo</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">🔄 Transfer Antar Akun</h1>
        <p class="text-gray-500 text-sm">Pindahkan dana antar akun kas, dengan opsi biaya transfer.</p>
    </header>

    @if(session('success'))<div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="mb-4 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="mb-4 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded"><ul class="list-disc list-inside text-sm">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <div class="bg-white rounded-xl shadow-sm p-6">
        <form method="POST" action="{{ route('saldo.transfer') }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dari Akun <span class="text-red-500">*</span></label>
                    <select name="dari_akun" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="">-- Pilih --</option>
                        @foreach($rows as $r)<option value="{{ $r->nama_akun }}">{{ $r->nama_akun }} (Rp {{ number_format($r->saldo, 0, ',', '.') }})</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ke Akun <span class="text-red-500">*</span></label>
                    <select name="ke_akun" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="">-- Pilih --</option>
                        @foreach($rows as $r)<option value="{{ $r->nama_akun }}">{{ $r->nama_akun }} (Rp {{ number_format($r->saldo, 0, ',', '.') }})</option>@endforeach
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah (Rp) <span class="text-red-500">*</span></label>
                    <input type="number" name="jumlah" min="1" step="1" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                    <input type="date" name="tanggal" value="{{ date('Y-m-d') }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Biaya Transfer (Rp)</label>
                    <input type="number" name="biaya_transfer" min="0" step="any" value="0" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Biaya dipotong dari</label>
                    <div class="flex gap-4 mt-2 text-sm">
                        <label class="flex items-center gap-1.5"><input type="radio" name="potong_biaya" value="pengirim" checked> Akun Pengirim</label>
                        <label class="flex items-center gap-1.5"><input type="radio" name="potong_biaya" value="penerima"> Akun Penerima</label>
                    </div>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                <input type="text" name="catatan" placeholder="opsional..." class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 bg-emerald-600 text-white py-2 rounded-md font-semibold hover:bg-emerald-700">Proses Transfer</button>
                <a href="{{ route('saldo.index') }}" class="flex-1 text-center bg-gray-200 text-gray-700 py-2 rounded-md font-semibold hover:bg-gray-300">Batal</a>
            </div>
        </form>
    </div>

    {{-- Riwayat Transfer --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden mt-6">
        <div class="px-5 py-3 border-b border-gray-100"><h2 class="font-bold text-gray-800 text-sm">Riwayat Transfer</h2></div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50"><tr>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Tgl</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Dari</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Ke</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Jumlah</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Biaya</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($riwayat as $r)
                        <tr>
                            <td class="px-4 py-2 text-gray-600 whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($r->tanggal)->format('d/m/Y') }}</td>
                            <td class="px-4 py-2 text-gray-700">{{ $r->dari }}</td>
                            <td class="px-4 py-2 text-gray-700">{{ $r->ke }}</td>
                            <td class="px-4 py-2 text-right font-semibold text-emerald-700 whitespace-nowrap">Rp {{ number_format($r->jumlah, 0, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right text-gray-500 whitespace-nowrap">{{ $r->biaya > 0 ? 'Rp ' . number_format($r->biaya, 0, ',', '.') : '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400 italic">Belum ada transfer.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
</body>
</html>
