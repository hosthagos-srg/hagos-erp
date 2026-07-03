<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hagos ERP - Daftar Karyawan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">

<div class="min-h-screen p-6">
    <header class="mb-6 flex flex-wrap justify-between items-center gap-3">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Daftar Karyawan</h1>
            <p class="text-gray-500 mt-1">{{ $karyawans->count() }} karyawan · total kasbon beredar <strong class="text-red-600">Rp {{ number_format($totalKasbon, 0, ',', '.') }}</strong></p>
        </div>
        <button onclick="document.getElementById('modal-tambah').classList.remove('hidden')" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm font-semibold hover:bg-indigo-700">+ Tambah Karyawan</button>
    </header>

    @if(session('success'))<div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="mb-4 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded"><ul class="list-disc list-inside text-sm">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Posisi</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Gaji Pokok</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">No HP</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Sisa Kasbon</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($karyawans as $k)
                        @php $sisa = max(0, (float) ($sisaKasbon[$k->id] ?? 0)); @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900"><a href="{{ route('karyawan.show', $k->id) }}" class="text-indigo-600 hover:underline">{{ $k->nama }}</a></td>
                            <td class="px-4 py-3 text-gray-600">{{ $k->posisi ?? '-' }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">Rp {{ number_format($k->gaji_pokok, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $k->no_hp ?? '-' }}</td>
                            <td class="px-4 py-3 text-center"><span class="text-xs px-2 py-0.5 rounded {{ $k->status === 'Aktif' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-500' }}">{{ $k->status }}</span></td>
                            <td class="px-4 py-3 text-right font-semibold {{ $sisa > 0 ? 'text-red-600' : 'text-gray-400' }}">{{ $sisa > 0 ? 'Rp ' . number_format($sisa, 0, ',', '.') : '—' }}</td>
                            <td class="px-4 py-3 text-center"><a href="{{ route('karyawan.show', $k->id) }}" class="text-xs text-indigo-600 hover:underline">Detail →</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400 italic">Belum ada karyawan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal Tambah --}}
<div id="modal-tambah" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg mx-4 p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Tambah Karyawan</h2>
        <form method="POST" action="{{ route('karyawan.store') }}">
            @csrf
            @include('karyawan._form')
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
