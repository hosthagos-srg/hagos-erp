<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hagos ERP - Modal & Prive</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">

<div class="min-h-screen p-6 max-w-4xl mx-auto">
    <header class="mb-6">
        <a href="{{ route('saldo.index') }}" class="text-sm text-gray-500 hover:underline">← Kembali ke Saldo</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Modal & Prive</h1>
        <p class="text-gray-500 text-sm">Setoran modal (uang pemilik masuk) & prive (ambil pemilik). Menambah/mengurangi kas, tapi <b>bukan</b> income/biaya — tidak masuk Laba Rugi.</p>
    </header>

    @if(session('success'))<div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="mb-4 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded"><ul class="list-disc list-inside text-sm">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
        {{-- Setoran Modal --}}
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h2 class="font-bold text-emerald-700 mb-3">➕ Setoran Modal</h2>
            <form method="POST" action="{{ route('saldo.modal_setor') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Masuk ke Akun *</label>
                    <select name="akun" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                        <option value="">-- Pilih --</option>
                        @foreach($rows->where('tipe', '!=', 'Piutang') as $r)<option value="{{ $r->nama_akun }}">{{ $r->nama_akun }}</option>@endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah (Rp) *</label>
                        <input type="number" name="jumlah" min="1" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                        <input type="date" name="tanggal" value="{{ date('Y-m-d') }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                    <input type="text" name="catatan" placeholder="cth: tambah modal dari tabungan" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                </div>
                <button type="submit" class="w-full bg-emerald-600 text-white py-2 rounded-md font-semibold hover:bg-emerald-700">Catat Setoran Modal</button>
            </form>
        </div>

        {{-- Prive --}}
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h2 class="font-bold text-red-700 mb-3">➖ Prive (Ambil Pemilik)</h2>
            <form method="POST" action="{{ route('saldo.modal_prive') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dari Akun *</label>
                    <select name="akun" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                        <option value="">-- Pilih --</option>
                        @foreach($rows->where('tipe', '!=', 'Piutang') as $r)<option value="{{ $r->nama_akun }}">{{ $r->nama_akun }} (Rp {{ number_format($r->saldo, 0, ',', '.') }})</option>@endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah (Rp) *</label>
                        <input type="number" name="jumlah" min="1" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                        <input type="date" name="tanggal" value="{{ date('Y-m-d') }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                    <input type="text" name="catatan" placeholder="cth: kebutuhan pribadi" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                </div>
                <button type="submit" class="w-full bg-red-600 text-white py-2 rounded-md font-semibold hover:bg-red-700">Catat Prive</button>
            </form>
        </div>
    </div>

    {{-- Riwayat --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100"><h2 class="font-bold text-gray-800 text-sm">Riwayat Modal & Prive</h2></div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50"><tr>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Tgl</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Jenis</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Akun</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Keterangan</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Jumlah</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($riwayat as $r)
                        <tr>
                            <td class="px-4 py-2 text-gray-600 whitespace-nowrap">{{ $r->tanggal->format('d/m/Y') }}</td>
                            <td class="px-4 py-2">
                                @if($r->kategori === 'modal')<span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded">Modal Masuk</span>
                                @else<span class="text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded">Prive</span>@endif
                            </td>
                            <td class="px-4 py-2 text-gray-500">{{ $r->akun }}</td>
                            <td class="px-4 py-2 text-gray-500">{{ $r->keterangan }}</td>
                            <td class="px-4 py-2 text-right font-semibold {{ $r->kategori === 'modal' ? 'text-emerald-600' : 'text-red-600' }} whitespace-nowrap">{{ $r->kategori === 'modal' ? '+' : '−' }}Rp {{ number_format($r->jumlah, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400 italic">Belum ada transaksi modal/prive.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
</body>
</html>
