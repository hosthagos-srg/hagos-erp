<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hagos ERP - Pelanggan / Reseller</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">

@php $rp = fn($n) => 'Rp ' . number_format($n, 0, ',', '.'); @endphp

<div class="min-h-screen p-6">
    <header class="mb-6 flex flex-wrap justify-between items-center gap-3">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Pelanggan / Reseller</h1>
            <p class="text-gray-500 mt-1">{{ $pelanggans->count() }} pelanggan · total piutang <strong class="text-red-600">{{ $rp($totalPiutang) }}</strong></p>
        </div>
        <button onclick="document.getElementById('modal-tambah').classList.remove('hidden')" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm font-semibold hover:bg-indigo-700">+ Tambah Pelanggan</button>
    </header>

    @if(session('success'))<div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="mb-4 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded"><ul class="list-disc list-inside text-sm">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    {{-- Saran impor --}}
    @if($saranImpor->isNotEmpty())
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-5">
            <p class="text-sm font-semibold text-amber-800 mb-2">💡 {{ $saranImpor->count() }} nama pembeli dari pesanan belum terdaftar — impor cepat:</p>
            <div class="flex flex-wrap gap-2">
                @foreach($saranImpor->take(20) as $nama)
                    <form method="POST" action="{{ route('pelanggan.import') }}" class="inline">
                        @csrf <input type="hidden" name="nama" value="{{ $nama }}">
                        <button type="submit" class="text-xs bg-white border border-amber-300 text-amber-800 px-2.5 py-1 rounded hover:bg-amber-100">+ {{ $nama }}</button>
                    </form>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Filter --}}
    <form method="GET" class="flex flex-wrap gap-3 mb-4">
        <input type="text" name="cari" value="{{ request('cari') }}" placeholder="Cari nama / HP / kota..." class="border border-gray-300 rounded-md px-3 py-2 text-sm w-64">
        <select name="tipe" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
            <option value="">-- Semua Tipe --</option>
            @foreach($tipeList as $t)<option value="{{ $t }}" {{ request('tipe') === $t ? 'selected' : '' }}>{{ $t }}</option>@endforeach
        </select>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm font-semibold hover:bg-indigo-700">Cari</button>
    </form>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50"><tr>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Nama</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Tipe</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">No HP</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Order</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Total Belanja</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Piutang</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Terakhir</th>
                    <th class="px-4 py-2 text-center text-xs text-gray-500 uppercase"></th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($pelanggans as $p)
                        @php $s = $stats[$p->nama] ?? null; @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 font-medium text-gray-900"><a href="{{ route('pelanggan.show', $p->id) }}" class="text-indigo-600 hover:underline">{{ $p->nama }}</a></td>
                            <td class="px-4 py-2"><span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{{ $p->tipe ?? '-' }}</span></td>
                            <td class="px-4 py-2 text-gray-500">{{ $p->no_hp ?? '-' }}</td>
                            <td class="px-4 py-2 text-right text-gray-600">{{ $s->orders ?? 0 }}</td>
                            <td class="px-4 py-2 text-right text-gray-800">{{ $rp($s->total ?? 0) }}</td>
                            <td class="px-4 py-2 text-right font-semibold {{ ($s->piutang ?? 0) > 0 ? 'text-red-600' : 'text-gray-400' }}">{{ ($s->piutang ?? 0) > 0 ? $rp($s->piutang) : '—' }}</td>
                            <td class="px-4 py-2 text-gray-500">{{ $s && $s->last_order ? \Illuminate\Support\Carbon::parse($s->last_order)->format('d/m/Y') : '-' }}</td>
                            <td class="px-4 py-2 text-center whitespace-nowrap">
                                <a href="{{ route('pelanggan.show', $p->id) }}" class="text-xs text-indigo-600 hover:underline">Detail</a>
                                <form method="POST" action="{{ route('pelanggan.destroy', $p->id) }}" onsubmit="return confirm('Hapus {{ $p->nama }}?')" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs text-red-500 hover:text-red-700 ml-2">🗑</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400 italic">Belum ada pelanggan. Impor dari pembeli yang ada di atas, atau tambah manual.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal Tambah --}}
<div id="modal-tambah" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg mx-4 p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Tambah Pelanggan</h2>
        <form method="POST" action="{{ route('pelanggan.store') }}">
            @csrf
            @include('pelanggan._form')
            <div class="flex gap-3 mt-4">
                <button type="submit" class="flex-1 bg-indigo-600 text-white py-2 rounded-md font-semibold hover:bg-indigo-700">Simpan</button>
                <button type="button" onclick="document.getElementById('modal-tambah').classList.add('hidden')" class="flex-1 bg-gray-200 text-gray-700 py-2 rounded-md font-semibold hover:bg-gray-300">Batal</button>
            </div>
        </form>
    </div>
</div>
</div>
</body>
</html>
