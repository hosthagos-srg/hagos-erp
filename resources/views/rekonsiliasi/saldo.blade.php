<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Saldo Marketplace - Hagos ERP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">

@php $rp = fn($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); @endphp

<div class="p-4 sm:p-6 max-w-5xl mx-auto">
    <div class="mb-5">
        <h1 class="text-2xl font-bold text-gray-900">🔎 Cek Saldo Marketplace (otomatis)</h1>
        <p class="text-gray-600 mt-1 text-sm">Bandingkan saldo ERP dengan saldo asli di aplikasi marketplace. Kalau beda, sistem <b>menjelaskan sendiri</b> penyebabnya & bisa memperbaikinya.</p>
    </div>

    @if(session('success'))<div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="mb-4 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded">{{ session('error') }}</div>@endif

    {{-- Saldo ERP per akun MP + pembanding manual --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-5">
        @foreach($akuns as $a)
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="text-xs text-gray-500">{{ $a->nama }} — saldo di ERP</div>
            <div class="text-2xl font-bold text-gray-900 mt-1">{{ $rp($a->saldo) }}</div>
            <div class="mt-3">
                <label class="block text-xs text-gray-500 mb-1">Saldo asli di aplikasi (ketik untuk bandingkan)</label>
                <input type="number" step="1" data-erp="{{ (float) $a->saldo }}" oninput="cekSelisih(this)"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="mis. 5170264">
                <p class="mt-2 text-sm hidden" data-out></p>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Audit otomatis --}}
    <div class="bg-white rounded-lg border {{ $gaps->count() ? 'border-rose-300' : 'border-emerald-300' }} overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h2 class="font-bold text-gray-800 text-sm">Audit pencatatan settlement</h2>
                <p class="text-xs text-gray-500">Tiap pesanan cair: <b>net settlement</b> harus sama dengan <b>uang yang tercatat</b> di saldo MP.</p>
            </div>
            @if($gaps->count())
                <span class="text-xs bg-rose-100 text-rose-700 px-2 py-1 rounded font-medium">{{ $gaps->count() }} pesanan bolong · {{ $rp($totalGap) }}</span>
            @else
                <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-1 rounded font-medium">✓ Semua cocok</span>
            @endif
        </div>

        @if($gaps->count())
            <div class="px-5 py-3 bg-rose-50/60 text-xs text-rose-800">
                Selisih saldo kemungkinan besar berasal dari sini. Klik <b>Perbaiki Otomatis</b> untuk mencatat kekurangannya (aman: hanya menambah selisihnya, tidak menggandakan).
            </div>
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                    <tr>
                        <th class="px-4 py-2 text-left">Pesanan</th>
                        <th class="px-4 py-2 text-left">Channel · Tgl Cair</th>
                        <th class="px-4 py-2 text-right">Net Settlement</th>
                        <th class="px-4 py-2 text-right">Tercatat di Kas</th>
                        <th class="px-4 py-2 text-right">Selisih</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($gaps as $g)
                    <tr>
                        <td class="px-4 py-2 font-medium text-gray-800">{{ $g->external_order_id }}</td>
                        <td class="px-4 py-2 text-xs text-gray-500">{{ $g->channel }}<br>{{ $g->tgl_cair_saldo ? \Illuminate\Support\Carbon::parse($g->tgl_cair_saldo)->format('d/m/Y') : '—' }}</td>
                        <td class="px-4 py-2 text-right {{ $g->net_settlement < 0 ? 'text-rose-600' : '' }}">{{ $rp($g->net_settlement) }}</td>
                        <td class="px-4 py-2 text-right text-gray-500">{{ $rp($g->mutasi) }}</td>
                        <td class="px-4 py-2 text-right font-semibold text-rose-700">{{ $rp($g->gap) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-5 py-3 bg-gray-50 flex justify-end">
                <form method="POST" action="{{ route('rekonsiliasi.saldo_perbaiki') }}" onsubmit="return confirm('Catat selisih untuk {{ $gaps->count() }} pesanan? Aman & tidak menggandakan.')">
                    @csrf
                    <button class="px-4 py-2 text-sm rounded-md bg-rose-600 text-white hover:bg-rose-700 font-medium">🔧 Perbaiki Otomatis</button>
                </form>
            </div>
        @else
            <div class="px-5 py-8 text-center text-gray-500 text-sm">
                Semua pesanan cair sudah tercatat pas di saldo marketplace.<br>
                <span class="text-xs text-gray-400">Kalau saldo masih beda, penyebabnya di luar settlement (mis. penarikan yang belum dicatat, atau retur yang datang setelah penarikan).</span>
            </div>
        @endif
    </div>

    <p class="text-xs text-gray-500 mt-4">💡 Alur ideal: upload settlement → buka halaman ini → ketik saldo asli dari aplikasi marketplace → kalau ada selisih, cek tabel audit → Perbaiki Otomatis.</p>
</div>
</div>
<script>
function cekSelisih(el){
    var erp = parseFloat(el.getAttribute('data-erp') || 0);
    var real = parseFloat(el.value || '');
    var out = el.parentElement.querySelector('[data-out]');
    if (isNaN(real) || el.value === '') { out.classList.add('hidden'); return; }
    var d = erp - real;
    out.classList.remove('hidden');
    if (Math.abs(d) < 1) {
        out.className = 'mt-2 text-sm text-emerald-600 font-medium';
        out.textContent = '✓ Cocok — saldo ERP sama dengan aplikasi.';
    } else {
        out.className = 'mt-2 text-sm text-rose-600 font-medium';
        out.textContent = (d > 0 ? '⚠ ERP KELEBIHAN Rp ' : '⚠ ERP KURANG Rp ') + Math.abs(d).toLocaleString('id-ID') + ' — cek tabel audit di bawah.';
    }
}
</script>
</body>
</html>
