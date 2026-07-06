<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Pesanan Perlu Dicek - Hagos ERP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">

@php $tgl = fn($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d/m/Y') : '-'; @endphp

<div class="min-h-screen p-6 max-w-5xl mx-auto">
    <div class="mb-4">
        <a href="{{ route('penjualan.index') }}" class="text-indigo-600 hover:text-indigo-900 text-sm">&larr; Kembali ke Kelola Pesanan</a>
    </div>
    <header class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">🔎 Monitoring Pesanan Perlu Dicek</h1>
        <p class="text-gray-500 mt-1">Pesanan marketplace yang <b>belum cair</b> (terkirim / nyangkut / hilang / settlement telat). Isi <b>keterangan</b> hasil cek lalu klik "Sudah Dicek" — pesanan <b>tetap di sini sampai dana Cair</b>, tidak hilang. Angka "Perlu Dicek Sekarang" (jatuh tempo &gt;3 hari) hanya untuk notifikasi.</p>
    </header>

    @if(session('success'))<div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>@endif

    {{-- Ringkasan --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl ring-1 ring-indigo-200 shadow-sm p-4">
            <p class="text-xs text-gray-500">Total Dalam Monitoring</p>
            <p class="text-2xl font-bold text-indigo-600">{{ $allCount }}</p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-amber-200 shadow-sm p-4">
            <p class="text-xs text-gray-500">Perlu Dicek Sekarang <span class="text-gray-400">(notif)</span></p>
            <p class="text-2xl font-bold text-amber-600">{{ $dueCount }}</p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-500">Belum Pernah Dicek</p>
            <p class="text-2xl font-bold text-red-600">{{ $belumPernahDicek }}</p>
        </div>
        @foreach($perChannel as $ch => $jml)
        <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-500 truncate">{{ $ch }}</p>
            <p class="text-2xl font-bold text-gray-700">{{ $jml }}</p>
        </div>
        @endforeach
    </div>

    {{-- Filter channel --}}
    <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-4 mb-4">
        <form method="GET" action="{{ route('penjualan.perlu_cek') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Channel</label>
                <select name="channel" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm">
                    <option value="">-- Semua Marketplace --</option>
                    @foreach($channels as $c)<option value="{{ $c }}" {{ request('channel') === $c ? 'selected' : '' }}>{{ $c }}</option>@endforeach
                </select>
            </div>
            <button type="submit" class="bg-indigo-600 text-white px-4 py-1.5 rounded-md text-sm font-semibold hover:bg-indigo-700">Terapkan</button>
            @if(request('channel'))<a href="{{ route('penjualan.perlu_cek') }}" class="px-4 py-1.5 rounded-md text-sm text-gray-700 bg-gray-100 hover:bg-gray-200">Reset</a>@endif
        </form>
    </div>

    {{-- Daftar --}}
    <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50"><tr>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Order ID</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Channel</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Tgl Pesanan</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Umur</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Status Cek</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Keterangan &amp; Aksi</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($items as $c)
                        @php
                            $umur = (int) \Illuminate\Support\Carbon::parse($c->tgl_pesanan)->diffInDays(now());
                            // Jatuh tempo dicek = belum pernah dicek, atau cek terakhir >= 3 hari lalu.
                            $due = is_null($c->tgl_dicek) || \Illuminate\Support\Carbon::parse($c->tgl_dicek)->diffInDays(now()) >= 3;
                        @endphp
                        <tr class="{{ $due ? ($umur > 20 ? 'bg-red-50' : 'bg-amber-50/60') : '' }}">
                            <td class="px-4 py-2 align-top">
                                <a href="{{ route('penjualan.show', $c->internal_id) }}" class="font-medium text-indigo-600 hover:underline">{{ $c->external_order_id ?? ('INV-' . strtoupper(substr($c->internal_id, 0, 8))) }}</a>
                                @if($c->no_resi)<div class="text-xs text-gray-400">📦 {{ $c->no_resi }}</div>@endif
                            </td>
                            <td class="px-4 py-2 text-gray-600 align-top">{{ $c->channel }}</td>
                            <td class="px-4 py-2 text-gray-600 whitespace-nowrap align-top">{{ $tgl($c->tgl_pesanan) }}</td>
                            <td class="px-4 py-2 text-right whitespace-nowrap align-top {{ $umur > 20 ? 'text-red-600 font-semibold' : 'text-gray-700' }}">{{ $umur }} hari</td>
                            <td class="px-4 py-2 whitespace-nowrap align-top">
                                @if($c->jumlah_dicek > 0)
                                    <span class="text-xs text-amber-700">✓ dicek {{ $c->jumlah_dicek }}× · terakhir {{ $tgl($c->tgl_dicek) }}</span>
                                    @if($due)<div class="text-xs font-semibold text-amber-600">perlu cek ulang</div>@else<div class="text-xs text-emerald-600">masih terpantau</div>@endif
                                @else
                                    <span class="text-xs font-semibold text-red-600">Belum pernah dicek</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 align-top">
                                <form method="POST" action="{{ route('penjualan.update_status', $c->internal_id) }}" class="flex flex-col sm:flex-row gap-2 items-stretch">
                                    @csrf
                                    <input type="hidden" name="action" value="cek_pesanan">
                                    <input type="text" name="catatan_cek" value="{{ $c->catatan_cek }}" maxlength="255"
                                           placeholder="mis. paket stuck 3–6 Jul, masih menuju pembeli"
                                           class="flex-1 min-w-0 border border-gray-300 rounded-md px-2 py-1 text-xs w-full sm:w-64">
                                    <button type="submit" class="px-3 py-1 text-xs font-semibold rounded-md bg-amber-600 text-white hover:bg-amber-700 whitespace-nowrap">✓ Sudah Dicek</button>
                                </form>
                                @if($c->catatan_cek)<div class="text-xs text-gray-500 mt-1">📝 {{ $c->catatan_cek }}</div>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-emerald-600 italic">✓ Tidak ada pesanan dalam monitoring. Semua dana sudah cair!</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-xs text-gray-400 mt-4">Baris <b>kuning</b> = jatuh tempo dicek (belum dicek / cek terakhir &gt;3 hari). Baris <b>merah</b> = umur &gt;20 hari. Baris putih = sudah dicek & masih terpantau. Pesanan hanya hilang dari sini saat dana <b>Cair</b> — bukan saat dicek. Isi keterangan tiap kali cek biar jejaknya jelas.</p>
</div>
</div>
</body>
</html>
