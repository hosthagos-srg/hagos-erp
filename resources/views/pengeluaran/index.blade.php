<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hagos ERP - Pengeluaran</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">

<div class="min-h-screen p-6">
    <header class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Pengeluaran</h1>
        <p class="text-gray-500 mt-1">Catat & pantau semua pengeluaran operasional.</p>
    </header>

    @if(session('success'))<div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="mb-4 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded"><ul class="list-disc list-inside text-sm">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- ── Form Input ── --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm p-5 sticky top-6">
                <h2 class="font-bold text-gray-800 mb-4">➖ Catat Pengeluaran</h2>
                <form method="POST" action="{{ route('pengeluaran.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Pengeluaran <span class="text-red-500">*</span></label>
                        <select name="kategori" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">-- Pilih Jenis --</option>
                            @foreach($kategoriList as $k)<option value="{{ $k }}">{{ $k }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah (Rp) <span class="text-red-500">*</span></label>
                        <input type="number" name="jumlah" min="1" step="1" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dari Akun <span class="text-red-500">*</span></label>
                        <select name="akun" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">-- Pilih Akun --</option>
                            @foreach($akuns as $a)<option value="{{ $a }}">{{ $a }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                        <input type="date" name="tanggal" value="{{ date('Y-m-d') }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dicatat Oleh</label>
                        <select name="oleh" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">-- Pilih --</option>
                            @foreach($admins as $adm)<option value="{{ $adm }}">{{ $adm }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                        <textarea name="keterangan" rows="2" placeholder="opsional..." class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>
                    <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded-md font-semibold hover:bg-indigo-700">Simpan Pengeluaran</button>
                </form>
            </div>
        </div>

        {{-- ── Riwayat ── --}}
        <div class="lg:col-span-2 space-y-5">
            {{-- Ringkasan per jenis --}}
            <div class="bg-white rounded-xl shadow-sm p-5">
                <div class="flex justify-between items-center mb-3">
                    <h2 class="font-bold text-gray-800">Ringkasan {{ ($dari || $sampai) ? 'Periode Filter' : 'Keseluruhan' }}</h2>
                    <span class="text-sm font-bold text-red-600">Total: Rp {{ number_format($totalSemua, 0, ',', '.') }}</span>
                </div>
                @if($perJenis->isEmpty())
                    <p class="text-sm text-gray-400">Belum ada pengeluaran.</p>
                @else
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                        @foreach($perJenis as $nama => $jml)
                            <div class="flex justify-between bg-gray-50 rounded-md px-3 py-1.5 text-sm">
                                <span class="text-gray-600 truncate">{{ $nama }}</span>
                                <span class="font-semibold text-gray-900 ml-2">Rp {{ number_format($jml, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Filter --}}
            <div class="bg-white rounded-xl shadow-sm p-4">
                <form method="GET" action="{{ route('pengeluaran.index') }}" class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Dari</label>
                        <input type="date" name="dari" value="{{ $dari }}" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Sampai</label>
                        <input type="date" name="sampai" value="{{ $sampai }}" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Jenis</label>
                        <select name="jenis" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm">
                            <option value="">-- Semua Jenis --</option>
                            @foreach($kategoriList as $k)<option value="{{ $k }}" {{ $jenis === $k ? 'selected' : '' }}>{{ $k }}</option>@endforeach
                        </select>
                    </div>
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-1.5 rounded-md text-sm font-semibold hover:bg-indigo-700">Filter</button>
                    @if($dari || $sampai || $jenis)
                        <a href="{{ route('pengeluaran.index') }}" class="px-4 py-1.5 rounded-md text-sm font-semibold bg-gray-100 text-gray-600 hover:bg-gray-200">Reset</a>
                    @endif
                </form>
            </div>

            {{-- Tabel riwayat --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 flex justify-between items-center">
                    <h2 class="font-bold text-gray-800 text-sm">Riwayat Pengeluaran</h2>
                    <span class="text-sm text-gray-500">Subtotal tampil: <strong class="text-red-600">Rp {{ number_format($totalFilter, 0, ',', '.') }}</strong></span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tgl</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Jenis</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Catatan</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Akun</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Oleh</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Jumlah</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @forelse($pengeluarans as $p)
                                @php
                                    $parts = explode('·', $p->keterangan, 2);
                                    $jns = trim($parts[0]);
                                    $cat = isset($parts[1]) ? trim($parts[1]) : '';
                                @endphp
                                <tr>
                                    <td class="px-4 py-2 text-gray-600 whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($p->tanggal)->format('d/m/Y') }}</td>
                                    <td class="px-4 py-2"><span class="text-xs bg-gray-100 text-gray-700 px-2 py-0.5 rounded">{{ $jns }}</span></td>
                                    <td class="px-4 py-2 text-gray-600">{{ $cat ?: '-' }}</td>
                                    <td class="px-4 py-2 text-gray-500">{{ $p->akun }}</td>
                                    <td class="px-4 py-2 text-gray-500">{{ $p->dicatat_oleh ?? '-' }}</td>
                                    <td class="px-4 py-2 text-right font-semibold text-red-600 whitespace-nowrap">Rp {{ number_format($p->jumlah, 0, ',', '.') }}</td>
                                    <td class="px-4 py-2 text-center">
                                        <form method="POST" action="{{ route('pengeluaran.destroy', $p->mutasi_id) }}" onsubmit="return confirm('Hapus pengeluaran ini? Saldo akun akan kembali.')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-xs text-red-500 hover:text-red-700">✕</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400 italic">Tidak ada pengeluaran pada filter ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3 border-t border-gray-100">{{ $pengeluarans->links() }}</div>
            </div>
        </div>
    </div>
</div>
</div>
</body>
</html>
