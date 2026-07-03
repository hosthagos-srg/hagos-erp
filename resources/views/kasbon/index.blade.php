<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hagos ERP - Kasbon</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">

@php
    $sisaJs = [];
    foreach ($karyawans as $k) { $sisaJs[$k->id] = max(0, (float) ($sisaKasbon[$k->id] ?? 0)); }
@endphp

<div class="min-h-screen p-6">
    <header class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Kasbon Karyawan</h1>
        <p class="text-gray-500 mt-1">Total kasbon beredar: <strong class="text-red-600">Rp {{ number_format($totalKasbonAktif, 0, ',', '.') }}</strong></p>
    </header>

    @if(session('success'))<div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="mb-4 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded"><ul class="list-disc list-inside text-sm">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">
        {{-- Beri Kasbon --}}
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h2 class="font-bold text-red-700 mb-3">➖ Beri Kasbon (karyawan pinjam)</h2>
            <form method="POST" action="{{ route('kasbon.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Karyawan *</label>
                    <select name="karyawan_id" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                        <option value="">-- Pilih --</option>
                        @foreach($karyawans as $k)<option value="{{ $k->id }}">{{ $k->nama }}</option>@endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah (Rp) *</label>
                        <input type="number" name="jumlah" min="1" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal *</label>
                        <input type="date" name="tanggal" value="{{ date('Y-m-d') }}" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dari Akun *</label>
                    <select name="akun" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                        <option value="">-- Pilih --</option>
                        @foreach($akuns as $a)<option value="{{ $a }}">{{ $a }}</option>@endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dicatat Oleh</label>
                        <select name="dicatat_oleh" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option value="">-- Pilih --</option>
                            @foreach($admins as $adm)<option value="{{ $adm }}">{{ $adm }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
                        <input type="text" name="keterangan" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    </div>
                </div>
                <button type="submit" class="w-full bg-red-600 text-white py-2 rounded-md font-semibold hover:bg-red-700">Catat Kasbon</button>
            </form>
        </div>

        {{-- Bayar Kasbon --}}
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h2 class="font-bold text-emerald-700 mb-3">➕ Pelunasan Kasbon (tunai)</h2>
            <form method="POST" action="{{ route('kasbon.bayar') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Karyawan *</label>
                    <select name="karyawan_id" id="bayar-karyawan" onchange="showSisa()" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                        <option value="">-- Pilih --</option>
                        @foreach($karyawans as $k)<option value="{{ $k->id }}">{{ $k->nama }}</option>@endforeach
                    </select>
                    <p id="sisa-info" class="text-xs text-gray-400 mt-1"></p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah (Rp) *</label>
                        <input type="number" name="jumlah" id="bayar-jumlah" min="1" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal *</label>
                        <input type="date" name="tanggal" value="{{ date('Y-m-d') }}" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Masuk ke Akun *</label>
                    <select name="akun" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                        <option value="">-- Pilih --</option>
                        @foreach($akuns as $a)<option value="{{ $a }}">{{ $a }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dicatat Oleh</label>
                    <select name="dicatat_oleh" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                        <option value="">-- Pilih --</option>
                        @foreach($admins as $adm)<option value="{{ $adm }}">{{ $adm }}</option>@endforeach
                    </select>
                </div>
                <button type="submit" class="w-full bg-emerald-600 text-white py-2 rounded-md font-semibold hover:bg-emerald-700">Catat Pelunasan</button>
            </form>
        </div>
    </div>

    {{-- Riwayat --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex justify-between items-center">
            <h2 class="font-bold text-gray-800 text-sm">Riwayat Kasbon</h2>
            <form method="GET" class="flex items-center gap-2">
                <select name="karyawan_id" onchange="this.form.submit()" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm">
                    <option value="">-- Semua Karyawan --</option>
                    @foreach($karyawans as $k)<option value="{{ $k->id }}" {{ request('karyawan_id') == $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>@endforeach
                </select>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50"><tr>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Tgl</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Karyawan</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Tipe</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Akun</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Keterangan</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Jumlah</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($riwayat as $r)
                        <tr>
                            <td class="px-4 py-2 text-gray-600 whitespace-nowrap">{{ $r->tanggal->format('d/m/Y') }}</td>
                            <td class="px-4 py-2 font-medium text-gray-800">{{ $r->karyawan->nama ?? '-' }}</td>
                            <td class="px-4 py-2">
                                @if($r->tipe === 'kasbon')<span class="text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded">Ambil</span>
                                @else<span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded">Bayar · {{ $r->metode }}</span>@endif
                            </td>
                            <td class="px-4 py-2 text-gray-500">{{ $r->akun ?? '-' }}</td>
                            <td class="px-4 py-2 text-gray-500">{{ $r->keterangan ?? '-' }}</td>
                            <td class="px-4 py-2 text-right font-semibold {{ $r->tipe === 'kasbon' ? 'text-red-600' : 'text-emerald-600' }}">{{ $r->tipe === 'kasbon' ? '+' : '−' }}Rp {{ number_format($r->jumlah, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400 italic">Belum ada kasbon.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-100">{{ $riwayat->links() }}</div>
    </div>
</div>

<script>
const SISA = @json($sisaJs);
function showSisa() {
    const id = document.getElementById('bayar-karyawan').value;
    const el = document.getElementById('sisa-info');
    const j = document.getElementById('bayar-jumlah');
    if (!id) { el.textContent = ''; j.removeAttribute('max'); return; }
    const sisa = SISA[id] || 0;
    el.textContent = 'Sisa kasbon: Rp ' + Number(sisa).toLocaleString('id-ID');
    el.className = 'text-xs mt-1 ' + (sisa > 0 ? 'text-red-600 font-semibold' : 'text-emerald-600');
    j.max = sisa;
}
</script>
</div>
</body>
</html>
