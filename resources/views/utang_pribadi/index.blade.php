<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utang Pribadi - Hagos ERP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">

@php
    $rp = fn($n) => 'Rp ' . number_format($n, 0, ',', '.');
    $bulanID = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $tgl = fn($c) => $c ? $c->day . ' ' . $bulanID[(int)$c->month] . ' ' . $c->year : '-';
@endphp

<div class="min-h-screen p-6 max-w-6xl mx-auto">
    <header class="mb-6 flex justify-between items-start gap-3 flex-wrap">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">💸 Utang Pribadi</h1>
            <p class="text-gray-500 mt-1">Pinjaman uang tunai dari orang (non-dagang). Kas otomatis bertambah saat meminjam, dan berkurang saat membayar. Tidak masuk laba/rugi.</p>
        </div>
        <a href="{{ route('utang.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 flex-shrink-0">← Utang & Cicilan</a>
    </header>

    @if(session('success'))
        <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ $errors->first() }}</div>
    @endif

    {{-- Ringkasan --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-500">Total Utang (aktif)</p>
            <p class="text-2xl font-bold text-gray-900">{{ $rp($totalPinjam) }}</p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-amber-200 shadow-sm p-4">
            <p class="text-xs text-gray-500">Sisa Belum Dibayar</p>
            <p class="text-2xl font-bold text-amber-600">{{ $rp($totalSisa) }}</p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-500">Sudah Dibayar</p>
            <p class="text-2xl font-bold text-emerald-600">{{ $rp($totalBayar) }}</p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-500">Jumlah Pemberi Pinjaman</p>
            <p class="text-2xl font-bold text-gray-700">{{ $jmlPemberi }}</p>
        </div>
    </div>

    {{-- Form utang baru --}}
    <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-6 mb-6">
        <h2 class="font-bold text-gray-800 mb-1">Catat Utang Baru</h2>
        <p class="text-xs text-gray-400 mb-4">Uang akan langsung masuk ke akun tujuan yang dipilih.</p>
        <form method="POST" action="{{ route('utang_pribadi.store') }}" class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
            @csrf
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Nama Pemberi Pinjaman</label>
                <input type="text" name="nama" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" placeholder="mis. Om Budi">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Hubungan</label>
                <input type="text" name="hubungan" list="hubunganList" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" placeholder="Keluarga">
                <datalist id="hubunganList">
                    <option value="Keluarga"><option value="Kerabat"><option value="Teman"><option value="Investor">
                </datalist>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Jumlah (Rp)</label>
                <input type="number" name="jumlah_pinjaman" min="1" step="any" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-right" placeholder="1000000">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Tanggal Pinjam</label>
                <input type="date" name="tgl_pinjam" value="{{ date('Y-m-d') }}" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Akun Tujuan (uang masuk)</label>
                <select name="akun_tujuan" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white">
                    <option value="">-- Pilih Akun --</option>
                    @foreach($akuns as $a)<option value="{{ $a }}">{{ $a }}</option>@endforeach
                </select>
            </div>
            <div class="md:col-span-5">
                <label class="block text-xs font-medium text-gray-600 mb-1">Catatan (opsional)</label>
                <input type="text" name="catatan" maxlength="255" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" placeholder="mis. modal usaha, janji balik lebaran">
            </div>
            <div>
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-semibold">Simpan & Tambah Kas</button>
            </div>
        </form>
    </div>

    {{-- Daftar utang aktif --}}
    <h2 class="text-sm font-semibold text-gray-600 mb-2">Utang Aktif ({{ $aktif->count() }})</h2>
    @forelse($aktif as $u)
        @php $pct = $u->jumlah_pinjaman > 0 ? min(100, round($u->total_bayar / $u->jumlah_pinjaman * 100)) : 0; @endphp
        <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm mb-4 overflow-hidden">
            <div class="px-5 py-4 flex flex-wrap items-start justify-between gap-3 border-b border-gray-100">
                <div>
                    <h3 class="font-bold text-gray-900">{{ $u->nama }}
                        @if($u->hubungan)<span class="text-xs font-normal text-gray-400">· {{ $u->hubungan }}</span>@endif
                    </h3>
                    <p class="text-xs text-gray-400">Dipinjam {{ $tgl($u->tgl_pinjam) }} · masuk ke {{ $u->akun_tujuan }} @if($u->catatan) · {{ $u->catatan }} @endif</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-500">Sisa belum dibayar</p>
                    <p class="text-xl font-bold text-amber-600">{{ $rp($u->sisa) }}</p>
                    <p class="text-xs text-gray-400">dari {{ $rp($u->jumlah_pinjaman) }}</p>
                </div>
            </div>

            {{-- Progress --}}
            <div class="px-5 pt-3">
                <div class="flex justify-between text-xs text-gray-500 mb-1">
                    <span>Sudah dibayar {{ $rp($u->total_bayar) }}</span><span>{{ $pct }}%</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2.5">
                    <div class="h-2.5 rounded-full bg-emerald-500" style="width: {{ $pct }}%"></div>
                </div>
            </div>

            {{-- Riwayat pembayaran --}}
            @if($u->bayar->isNotEmpty())
                <div class="px-5 py-3">
                    <table class="min-w-full text-sm">
                        <thead><tr class="text-xs text-gray-400 uppercase text-left">
                            <th class="py-1">Tanggal</th><th class="py-1 text-right">Nominal</th><th class="py-1">Dari Akun</th><th class="py-1">Catatan</th><th class="py-1"></th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($u->bayar->sortBy('tgl_bayar') as $b)
                                <tr>
                                    <td class="py-1.5 text-gray-600">{{ $tgl($b->tgl_bayar) }}</td>
                                    <td class="py-1.5 text-right text-red-600 font-medium">{{ $rp($b->jumlah) }}</td>
                                    <td class="py-1.5 text-gray-500">{{ $b->akun_sumber }}</td>
                                    <td class="py-1.5 text-gray-400 text-xs">{{ $b->catatan }}</td>
                                    <td class="py-1.5 text-right">
                                        <form method="POST" action="{{ route('utang_pribadi.hapus_bayar', $b->id) }}" onsubmit="return confirm('Hapus pembayaran ini & kembalikan kas?')">
                                            @csrf @method('DELETE')
                                            <button class="text-xs text-red-400 hover:text-red-600">hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Form pembayaran --}}
            <div class="px-5 py-3 bg-gray-50 border-t border-gray-100">
                <form method="POST" action="{{ route('utang_pribadi.bayar', $u->id) }}" class="grid grid-cols-1 md:grid-cols-5 gap-2 items-end">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Nominal Bayar</label>
                        <input type="number" name="jumlah" min="1" step="any" max="{{ $u->sisa }}" required class="w-full border border-gray-300 rounded-md px-3 py-1.5 text-sm text-right" placeholder="{{ (int)$u->sisa }}">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Tanggal</label>
                        <input type="date" name="tgl_bayar" value="{{ date('Y-m-d') }}" required class="w-full border border-gray-300 rounded-md px-3 py-1.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Keluar dari Akun</label>
                        <select name="akun_sumber" required class="w-full border border-gray-300 rounded-md px-3 py-1.5 text-sm bg-white">
                            <option value="">-- Pilih --</option>
                            @foreach($akuns as $a)<option value="{{ $a }}" @if($a === $u->akun_tujuan) selected @endif>{{ $a }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Catatan</label>
                        <input type="text" name="catatan" maxlength="255" class="w-full border border-gray-300 rounded-md px-3 py-1.5 text-sm" placeholder="opsional">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-md text-sm font-semibold">Catat Bayar</button>
                        <button type="submit" form="destroy-utang-{{ $u->id }}" onclick="return confirm('Hapus SELURUH utang {{ $u->nama }} & batalkan semua mutasi kasnya?')" class="px-2 py-1.5 text-red-500 hover:text-red-700 text-sm" title="Hapus utang">🗑</button>
                    </div>
                </form>
                <form id="destroy-utang-{{ $u->id }}" method="POST" action="{{ route('utang_pribadi.destroy', $u->id) }}" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            </div>
        </div>
    @empty
        <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-8 text-center text-gray-400 mb-6">
            Belum ada utang aktif. Catat pinjaman lewat form di atas.
        </div>
    @endforelse

    {{-- Utang lunas --}}
    @if($lunas->isNotEmpty())
        <h2 class="text-sm font-semibold text-gray-600 mb-2 mt-6">Utang Lunas ({{ $lunas->count() }})</h2>
        <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-400 uppercase text-left">
                    <th class="px-4 py-2">Nama</th><th class="px-4 py-2">Dipinjam</th><th class="px-4 py-2 text-right">Jumlah</th><th class="px-4 py-2 text-right"></th>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($lunas as $u)
                        <tr>
                            <td class="px-4 py-2 text-gray-800">{{ $u->nama }} @if($u->hubungan)<span class="text-xs text-gray-400">· {{ $u->hubungan }}</span>@endif</td>
                            <td class="px-4 py-2 text-gray-500">{{ $tgl($u->tgl_pinjam) }}</td>
                            <td class="px-4 py-2 text-right text-gray-700">{{ $rp($u->jumlah_pinjaman) }}</td>
                            <td class="px-4 py-2 text-right">
                                <span class="text-xs font-semibold px-2 py-0.5 rounded bg-emerald-100 text-emerald-700">LUNAS</span>
                                <form method="POST" action="{{ route('utang_pribadi.destroy', $u->id) }}" class="inline" onsubmit="return confirm('Hapus utang lunas ini & batalkan mutasi kasnya?')">
                                    @csrf @method('DELETE')
                                    <button class="ml-2 text-xs text-red-400 hover:text-red-600">hapus</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</div>
</div>
</body>
</html>
