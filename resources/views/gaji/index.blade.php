<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hagos ERP - Gaji</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">

@php
    $kJs = [];
    foreach ($karyawans as $k) { $kJs[$k->id] = ['pokok' => (float) $k->gaji_pokok, 'sisa' => max(0, (float) ($sisaKasbon[$k->id] ?? 0))]; }
@endphp

<div class="min-h-screen p-6">
    <header class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Gaji Karyawan</h1>
        <p class="text-gray-500 mt-1">Bayar gaji + opsi potong kasbon. Tercatat sebagai biaya di Laporan & Pengeluaran.</p>
    </header>

    @if(session('success'))<div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="mb-4 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded"><ul class="list-disc list-inside text-sm">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Form bayar gaji --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm p-5 sticky top-6">
                <h2 class="font-bold text-gray-800 mb-3">💸 Bayar Gaji</h2>
                <form method="POST" action="{{ route('gaji.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Karyawan *</label>
                        <select name="karyawan_id" id="g-karyawan" onchange="onKaryawan()" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option value="">-- Pilih --</option>
                            @foreach($karyawans as $k)<option value="{{ $k->id }}">{{ $k->nama }}</option>@endforeach
                        </select>
                        <p id="g-sisa" class="text-xs text-gray-400 mt-1"></p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Periode *</label>
                            <input type="text" name="periode" placeholder="cth: Juni 2026" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tgl Bayar *</label>
                            <input type="date" name="tanggal_bayar" value="{{ date('Y-m-d') }}" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Gaji Pokok (Rp) *</label>
                        <input type="number" name="gaji_pokok" id="g-pokok" min="0" required oninput="calc()" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tunjangan</label>
                            <input type="number" name="tunjangan" id="g-tunj" min="0" value="0" oninput="calc()" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Potongan Lain</label>
                            <input type="number" name="potongan_lain" id="g-lain" min="0" value="0" oninput="calc()" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Potong Kasbon</label>
                        <input type="number" name="potongan_kasbon" id="g-kasbon" min="0" value="0" oninput="calc()" class="w-full border border-amber-300 rounded-md px-3 py-2 text-sm bg-amber-50">
                        <p id="g-kasbon-warn" class="text-xs text-red-600 mt-1 hidden">Melebihi sisa kasbon!</p>
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
                            <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                            <input type="text" name="catatan" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-md p-3 text-sm">
                        <div class="flex justify-between"><span class="text-gray-500">Biaya gaji (P&L)</span><span id="g-biaya" class="font-semibold">Rp 0</span></div>
                        <div class="flex justify-between mt-1 pt-1 border-t border-gray-200"><span class="font-semibold text-gray-700">Diterima karyawan</span><span id="g-bersih" class="font-bold text-indigo-700 text-lg">Rp 0</span></div>
                    </div>

                    <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded-md font-semibold hover:bg-indigo-700">Bayar Gaji</button>
                </form>
            </div>
        </div>

        {{-- Riwayat gaji --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 flex justify-between items-center">
                    <h2 class="font-bold text-gray-800 text-sm">Riwayat Gaji</h2>
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
                            <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Periode</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Karyawan</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Tgl</th>
                            <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Pokok+Tunj</th>
                            <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Pot. Kasbon</th>
                            <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Diterima</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($riwayat as $g)
                                <tr>
                                    <td class="px-4 py-2 text-gray-700">{{ $g->periode }}</td>
                                    <td class="px-4 py-2 font-medium text-gray-800">{{ $g->karyawan->nama ?? '-' }}</td>
                                    <td class="px-4 py-2 text-gray-500 whitespace-nowrap">{{ $g->tanggal_bayar->format('d/m/Y') }}</td>
                                    <td class="px-4 py-2 text-right text-gray-600">Rp {{ number_format($g->gaji_pokok + $g->tunjangan, 0, ',', '.') }}</td>
                                    <td class="px-4 py-2 text-right text-amber-700">{{ $g->potongan_kasbon > 0 ? 'Rp ' . number_format($g->potongan_kasbon, 0, ',', '.') : '-' }}</td>
                                    <td class="px-4 py-2 text-right font-semibold text-gray-900">Rp {{ number_format($g->gaji_bersih, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400 italic">Belum ada gaji.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3 border-t border-gray-100">{{ $riwayat->links() }}</div>
            </div>
        </div>
    </div>
</div>

<script>
const KMAP = @json($kJs);
function rupiah(n){ return 'Rp ' + Number(n||0).toLocaleString('id-ID'); }
function onKaryawan() {
    const id = document.getElementById('g-karyawan').value;
    const info = KMAP[id];
    const el = document.getElementById('g-sisa');
    if (!info) { el.textContent=''; return; }
    if (info.pokok > 0 && !document.getElementById('g-pokok').value) document.getElementById('g-pokok').value = info.pokok;
    el.textContent = 'Sisa kasbon: ' + rupiah(info.sisa);
    el.className = 'text-xs mt-1 ' + (info.sisa > 0 ? 'text-red-600 font-semibold' : 'text-gray-400');
    document.getElementById('g-kasbon').max = info.sisa;
    calc();
}
function calc() {
    const pokok = parseFloat(document.getElementById('g-pokok').value)||0;
    const tunj = parseFloat(document.getElementById('g-tunj').value)||0;
    const lain = parseFloat(document.getElementById('g-lain').value)||0;
    const kasbon = parseFloat(document.getElementById('g-kasbon').value)||0;
    const id = document.getElementById('g-karyawan').value;
    const sisa = (KMAP[id]||{}).sisa || 0;
    const warn = document.getElementById('g-kasbon-warn');
    warn.classList.toggle('hidden', kasbon <= sisa);
    const biaya = pokok + tunj - lain;
    const bersih = biaya - kasbon;
    document.getElementById('g-biaya').textContent = rupiah(biaya);
    document.getElementById('g-bersih').textContent = rupiah(bersih);
}
</script>
</div>
</body>
</html>
