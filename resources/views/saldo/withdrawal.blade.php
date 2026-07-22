<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hagos ERP - Tarik Dana (WD)</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">

<div class="min-h-screen p-6 max-w-2xl mx-auto">
    <header class="mb-6">
        <a href="{{ route('saldo.index') }}" class="text-sm text-gray-500 hover:underline">← Kembali ke Saldo</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">🏦 Tarik Dana (Withdrawal)</h1>
        <p class="text-gray-500 text-sm">Pindahkan saldo marketplace ke rekening bank.</p>
    </header>

    @if(session('success'))<div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="mb-4 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="mb-4 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded"><ul class="list-disc list-inside text-sm">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <div class="bg-white rounded-xl shadow-sm p-6">
        <form method="POST" action="{{ route('saldo.withdrawal') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Dari (Saldo Marketplace) <span class="text-red-500">*</span></label>
                <select name="dari_akun" id="wdDari" onchange="wdCheck()" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Pilih Akun --</option>
                    @foreach($rows->where('tipe', 'Saldo MP') as $r)
                        <option value="{{ $r->nama_akun }}" data-saldo="{{ (float) $r->saldo }}">{{ $r->nama_akun }} (Rp {{ number_format($r->saldo, 0, ',', '.') }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ke (Bank) <span class="text-red-500">*</span></label>
                <select name="ke_akun" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Pilih Akun --</option>
                    @foreach($rows->where('tipe', 'Bank') as $r)
                        <option value="{{ $r->nama_akun }}">{{ $r->nama_akun }} (Rp {{ number_format($r->saldo, 0, ',', '.') }})</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah (Rp) <span class="text-red-500">*</span></label>
                    <input type="number" name="jumlah" id="wdJumlah" min="1" step="1" oninput="wdCheck()" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                    <input type="date" name="tanggal" value="{{ date('Y-m-d') }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div id="wdWarn" class="hidden bg-amber-50 border border-amber-300 text-amber-800 text-xs rounded-md px-3 py-2"></div>
            <p class="text-xs text-gray-500 -mt-2">💡 Hindari "kuras ke Rp 0". Sisakan sedikit buffer atau tarik yang sudah pasti cair — retur yang datang setelah penarikan bisa bikin saldo tak sinkron.</p>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                <input type="text" name="catatan" placeholder="opsional..." class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 bg-blue-600 text-white py-2 rounded-md font-semibold hover:bg-blue-700">Proses Tarik Dana</button>
                <a href="{{ route('saldo.index') }}" class="flex-1 text-center bg-gray-200 text-gray-700 py-2 rounded-md font-semibold hover:bg-gray-300">Batal</a>
            </div>
        </form>
    </div>

    {{-- Riwayat WD --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden mt-6">
        <div class="px-5 py-3 border-b border-gray-100"><h2 class="font-bold text-gray-800 text-sm">Riwayat Tarik Dana</h2></div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50"><tr>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Tgl</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Dari</th>
                    <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Ke</th>
                    <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Jumlah</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($riwayat as $r)
                        <tr>
                            <td class="px-4 py-2 text-gray-600 whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($r->tanggal)->format('d/m/Y') }}</td>
                            <td class="px-4 py-2 text-gray-700">{{ $r->dari }}</td>
                            <td class="px-4 py-2 text-gray-700">{{ $r->ke }}</td>
                            <td class="px-4 py-2 text-right font-semibold text-blue-700 whitespace-nowrap">Rp {{ number_format($r->jumlah, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400 italic">Belum ada penarikan dana.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
<script>
function wdCheck(){
    var sel = document.getElementById('wdDari');
    var opt = sel.options[sel.selectedIndex];
    var saldo = opt ? parseFloat(opt.getAttribute('data-saldo') || 0) : 0;
    var jml = parseFloat(document.getElementById('wdJumlah').value || 0);
    var w = document.getElementById('wdWarn');
    if (!opt || !opt.value || !jml) { w.classList.add('hidden'); return; }
    var sisa = saldo - jml;
    if (sisa < 0) {
        w.textContent = '⚠ Jumlah melebihi saldo (Rp ' + saldo.toLocaleString('id-ID') + '). Saldo akan MINUS ' + Math.abs(sisa).toLocaleString('id-ID') + '. Cek lagi.';
        w.classList.remove('hidden');
    } else if (sisa === 0) {
        w.textContent = '⚠ Ini menguras saldo ke Rp 0. Kalau ada retur menyusul, saldo bisa jadi tak sinkron. Pertimbangkan sisakan buffer.';
        w.classList.remove('hidden');
    } else {
        w.classList.add('hidden');
    }
}
</script>
</body>
</html>
