<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budgeting - Hagos ERP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">

@php
    $rp = fn($n) => 'Rp ' . number_format($n, 0, ',', '.');
    $rps = fn($n) => ($n < 0 ? '− Rp ' : 'Rp ') . number_format(abs($n), 0, ',', '.');
    $bulanID = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $tgl = fn($c) => $c->day . ' ' . mb_substr($bulanID[(int)$c->month], 0, 3);
@endphp

<div class="min-h-screen p-6 max-w-6xl mx-auto">
    <header class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">🎯 Budgeting Mingguan</h1>
            <p class="text-gray-500 mt-1">Jatah per minggu (Senin–Minggu). Sisa/over otomatis dibawa ke minggu berikutnya (amplop ketat), reset tiap awal bulan.</p>
        </div>
        {{-- Navigasi bulan --}}
        <div class="flex items-center gap-2">
            <a href="{{ route('budgeting.index', ['bulan' => $prevMonth]) }}" class="px-3 py-1.5 bg-white border border-gray-300 rounded-md text-sm hover:bg-gray-50">←</a>
            <span class="px-4 py-1.5 bg-white border border-gray-300 rounded-md text-sm font-semibold">{{ $bulanID[(int)$monthStart->month] }} {{ $monthStart->year }}</span>
            <a href="{{ route('budgeting.index', ['bulan' => $nextMonth]) }}" class="px-3 py-1.5 bg-white border border-gray-300 rounded-md text-sm hover:bg-gray-50">→</a>
        </div>
    </header>

    @if(session('success'))
        <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    {{-- Ringkasan minggu berjalan --}}
    <div class="mb-6">
        <h2 class="text-sm font-semibold text-gray-600 mb-2">
            {{ $isCurrentMonth ? 'Ringkasan Minggu Berjalan' : 'Ringkasan Minggu Terakhir (' . $bulanID[(int)$monthStart->month] . ')' }}
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-4">
                <p class="text-xs text-gray-500">Total Tersedia (jatah + sisa lalu)</p>
                <p class="text-2xl font-bold text-gray-900">{{ $rp($sumActiveTersedia) }}</p>
            </div>
            <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-4">
                <p class="text-xs text-gray-500">Total Terpakai</p>
                <p class="text-2xl font-bold text-indigo-600">{{ $rp($sumActiveTerpakai) }}</p>
            </div>
            <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-4 {{ $sumActiveSisa < 0 ? 'ring-red-200' : 'ring-emerald-200' }}">
                <p class="text-xs text-gray-500">Total Sisa</p>
                <p class="text-2xl font-bold {{ $sumActiveSisa < 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ $rps($sumActiveSisa) }}</p>
            </div>
            <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-4 {{ $countOver > 0 ? 'ring-red-200' : '' }}">
                <p class="text-xs text-gray-500">Kategori Over Budget</p>
                <p class="text-2xl font-bold {{ $countOver > 0 ? 'text-red-600' : 'text-gray-400' }}">{{ $countOver }}</p>
            </div>
        </div>
    </div>

    {{-- Kartu per kategori --}}
    @forelse($cards as $c)
        @php $f = $c['focus']; @endphp
        <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm mb-4 overflow-hidden">
            <div class="px-5 py-4 flex flex-wrap items-center justify-between gap-3 border-b border-gray-100">
                <div>
                    <h3 class="font-bold text-gray-900">{{ $c['kategori'] }}</h3>
                    <p class="text-xs text-gray-400">Jatah {{ $rp($c['jatah']) }} / minggu @if($c['catatan']) · {{ $c['catatan'] }} @endif</p>
                </div>
                @if($f)
                    <div class="text-right">
                        <span class="text-xs text-gray-500">Sisa minggu {{ $isCurrentMonth ? 'ini' : 'terakhir' }}:</span>
                        <span class="text-lg font-bold {{ $f['sisa'] < 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ $rps($f['sisa']) }}</span>
                        @if($f['over'])<span class="ml-1 text-xs font-semibold px-2 py-0.5 rounded bg-red-100 text-red-700">OVER</span>@endif
                    </div>
                @endif
            </div>

            {{-- Progress bar minggu fokus --}}
            @if($f && $f['tersedia'] > 0)
                @php $pct = min(100, round($f['terpakai'] / $f['tersedia'] * 100)); @endphp
                <div class="px-5 pt-3">
                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                        <span>Terpakai {{ $rp($f['terpakai']) }} dari {{ $rp($f['tersedia']) }}</span>
                        <span>{{ $pct }}%</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2.5">
                        <div class="h-2.5 rounded-full {{ $f['over'] ? 'bg-red-500' : ($pct >= 80 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            @endif

            {{-- Rincian per minggu --}}
            <div class="overflow-x-auto px-2 py-3">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-xs text-gray-500 uppercase text-left">
                            <th class="px-3 py-1.5">Minggu</th>
                            <th class="px-3 py-1.5 text-right">Jatah</th>
                            <th class="px-3 py-1.5 text-right">Sisa Bawaan</th>
                            <th class="px-3 py-1.5 text-right">Tersedia</th>
                            <th class="px-3 py-1.5 text-right">Terpakai</th>
                            <th class="px-3 py-1.5 text-right">Sisa</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($c['weeks'] as $w)
                            <tr class="{{ $w['isActive'] ? 'bg-indigo-50/60' : '' }}">
                                <td class="px-3 py-1.5 whitespace-nowrap">
                                    <span class="font-medium text-gray-700">M{{ $w['no'] }}</span>
                                    <span class="text-xs text-gray-400">{{ $tgl($w['start']) }}–{{ $tgl($w['end']) }}</span>
                                    @if($w['isActive'])<span class="ml-1 text-[10px] font-bold text-indigo-600">• kini</span>@endif
                                </td>
                                <td class="px-3 py-1.5 text-right text-gray-600 whitespace-nowrap">{{ $rp($w['jatah']) }}</td>
                                <td class="px-3 py-1.5 text-right whitespace-nowrap {{ $w['carryIn'] < 0 ? 'text-red-500' : 'text-gray-400' }}">{{ $w['carryIn'] == 0 ? '—' : $rps($w['carryIn']) }}</td>
                                <td class="px-3 py-1.5 text-right font-medium text-gray-800 whitespace-nowrap">{{ $rp($w['tersedia']) }}</td>
                                <td class="px-3 py-1.5 text-right text-indigo-600 whitespace-nowrap">{{ $rp($w['terpakai']) }}</td>
                                <td class="px-3 py-1.5 text-right font-semibold whitespace-nowrap {{ $w['sisa'] < 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ $rps($w['sisa']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Hapus anggaran --}}
            <div class="px-5 py-2 border-t border-gray-50 flex justify-end">
                <form method="POST" action="{{ route('budgeting.destroy', $c['id']) }}" onsubmit="return confirm('Hapus anggaran {{ $c['kategori'] }}?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-xs text-red-500 hover:text-red-700">Hapus anggaran</button>
                </form>
            </div>
        </div>
    @empty
        <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-8 text-center text-gray-400 mb-4">
            Belum ada anggaran. Tambahkan lewat form di bawah.
        </div>
    @endforelse

    {{-- Kategori yang ada pengeluaran tapi belum dianggarkan --}}
    @if($tanpaAnggaran->isNotEmpty())
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 mb-6">
            <p class="text-sm font-semibold text-amber-800 mb-2">⚠ Ada pengeluaran bulan ini tanpa anggaran</p>
            <div class="flex flex-wrap gap-2">
                @foreach($tanpaAnggaran as $kat => $tot)
                    <span class="text-xs bg-white border border-amber-200 rounded-full px-3 py-1 text-amber-700">{{ $kat }}: {{ $rp($tot) }}</span>
                @endforeach
            </div>
            <p class="text-xs text-amber-600 mt-2">Set jatahnya di form bawah agar ikut termonitor.</p>
        </div>
    @endif

    {{-- Form set / ubah anggaran --}}
    <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-6">
        <h2 class="font-bold text-gray-800 mb-1">Set / Ubah Anggaran Mingguan</h2>
        <p class="text-xs text-gray-400 mb-4">Pilih kategori pengeluaran dan tetapkan jatah per minggu. Kategori yang sudah ada akan diperbarui.</p>
        <form method="POST" action="{{ route('budgeting.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
            @csrf
            <div class="md:col-span-1">
                <label class="block text-xs font-medium text-gray-600 mb-1">Kategori</label>
                <select name="kategori" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white">
                    <option value="">-- Pilih --</option>
                    @foreach($kategoriList as $k)
                        <option value="{{ $k }}">{{ $k }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Jatah / Minggu (Rp)</label>
                <input type="number" name="jumlah_mingguan" min="0" step="any" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-right" placeholder="500000">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Catatan (opsional)</label>
                <input type="text" name="catatan" maxlength="255" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" placeholder="mis. makan + rokok tim">
            </div>
            <div>
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-semibold">Simpan Anggaran</button>
            </div>
        </form>
    </div>

    <p class="text-xs text-gray-400 mt-4">Realisasi otomatis dibaca dari data <b>Pengeluaran</b> (kategori + tanggal). Tidak perlu input ulang — cukup catat pengeluaran seperti biasa.</p>
</div>
</div>
</body>
</html>
