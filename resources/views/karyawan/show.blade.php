<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hagos ERP - {{ $karyawan->nama }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">

<div class="min-h-screen p-6 max-w-5xl mx-auto">
    <header class="mb-6">
        <a href="{{ route('karyawan.index') }}" class="text-sm text-gray-500 hover:underline">← Kembali ke Daftar Karyawan</a>
        <div class="flex justify-between items-end mt-2">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $karyawan->nama }}</h1>
                <p class="text-gray-500">{{ $karyawan->posisi ?? '-' }} · {{ $karyawan->status }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-400 uppercase">Sisa Kasbon (utang ke Hagos)</p>
                <p class="text-2xl font-bold {{ $sisaKasbon > 0 ? 'text-red-600' : 'text-emerald-600' }}">Rp {{ number_format($sisaKasbon, 0, ',', '.') }}</p>
            </div>
        </div>
    </header>

    @if(session('success'))<div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="mb-4 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded"><ul class="list-disc list-inside text-sm">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Edit data --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h2 class="font-bold text-gray-800 mb-3">Data Karyawan</h2>
                <form method="POST" action="{{ route('karyawan.update', $karyawan->id) }}">
                    @csrf
                    @include('karyawan._form', ['k' => $karyawan])
                    <button type="submit" class="w-full mt-3 bg-emerald-600 text-white py-2 rounded-md font-semibold hover:bg-emerald-700">Simpan Perubahan</button>
                </form>
            </div>
        </div>

        {{-- Riwayat --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Kasbon --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 flex justify-between items-center">
                    <h2 class="font-bold text-gray-800 text-sm">Riwayat Kasbon</h2>
                    <a href="{{ route('kasbon.index', ['karyawan_id' => $karyawan->id]) }}" class="text-xs text-indigo-600 hover:underline">+ Kelola Kasbon</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50"><tr>
                            <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Tgl</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Tipe</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Keterangan</th>
                            <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Jumlah</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($kasbon as $kb)
                                <tr>
                                    <td class="px-4 py-2 text-gray-600 whitespace-nowrap">{{ $kb->tanggal->format('d/m/Y') }}</td>
                                    <td class="px-4 py-2">
                                        @if($kb->tipe === 'kasbon')
                                            <span class="text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded">Ambil</span>
                                        @else
                                            <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded">Bayar · {{ $kb->metode }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-gray-500">{{ $kb->keterangan ?? '-' }}</td>
                                    <td class="px-4 py-2 text-right font-semibold {{ $kb->tipe === 'kasbon' ? 'text-red-600' : 'text-emerald-600' }}">
                                        {{ $kb->tipe === 'kasbon' ? '+' : '−' }}Rp {{ number_format($kb->jumlah, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-4 text-center text-gray-400 text-sm">Belum ada kasbon.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Gaji --}}
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 flex justify-between items-center">
                    <h2 class="font-bold text-gray-800 text-sm">Riwayat Gaji</h2>
                    <a href="{{ route('gaji.index', ['karyawan_id' => $karyawan->id]) }}" class="text-xs text-indigo-600 hover:underline">+ Bayar Gaji</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50"><tr>
                            <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Periode</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Tgl Bayar</th>
                            <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Pokok+Tunj</th>
                            <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Potong Kasbon</th>
                            <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Diterima</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($gaji as $g)
                                <tr>
                                    <td class="px-4 py-2 text-gray-700">{{ $g->periode }}</td>
                                    <td class="px-4 py-2 text-gray-500 whitespace-nowrap">{{ $g->tanggal_bayar->format('d/m/Y') }}</td>
                                    <td class="px-4 py-2 text-right text-gray-600">Rp {{ number_format($g->gaji_pokok + $g->tunjangan, 0, ',', '.') }}</td>
                                    <td class="px-4 py-2 text-right text-amber-700">{{ $g->potongan_kasbon > 0 ? 'Rp ' . number_format($g->potongan_kasbon, 0, ',', '.') : '-' }}</td>
                                    <td class="px-4 py-2 text-right font-semibold text-gray-900">Rp {{ number_format($g->gaji_bersih, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-4 text-center text-gray-400 text-sm">Belum ada gaji.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
</body>
</html>
