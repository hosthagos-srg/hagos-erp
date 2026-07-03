<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resep Mix - Hagos ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>.ts-control { border-radius: 0.375rem; border-color: #d1d5db; min-height: 38px; }</style>
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">

@php $rp = fn($n) => 'Rp ' . number_format($n, 0, ',', '.'); @endphp

<div class="min-h-screen p-6 max-w-5xl mx-auto">
    <header class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">🧬 Resep Mix</h1>
        <p class="text-gray-500 mt-1">Racikan 1 botol dari <b>lebih dari 1 bibit</b> (mis. "Skndls x Dunsblue"). Saat SKU punya resep mix, HPP & potong stok otomatis mengikuti komposisi ini.</p>
    </header>

    @if(session('success'))<div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ $errors->first() }}</div>@endif

    {{-- Form tambah / ubah resep mix --}}
    <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-6 mb-6">
        <h2 class="font-bold text-gray-800 mb-1">Buat / Ubah Resep Mix</h2>
        <p class="text-xs text-gray-400 mb-4">Pilih SKU produk (buat dulu di Master Produk bila belum ada), lalu tentukan bibit-bibit penyusunnya + ml masing-masing. Absolute & tester ikut resep induk SKU.</p>

        <form method="POST" action="{{ route('resep_mix.store') }}" id="mixForm">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">SKU Produk (mix)</label>
                    <select name="sku_id" id="sku_id" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white">
                        <option value="">-- Pilih SKU --</option>
                        @foreach($produks as $p)
                            <option value="{{ $p->sku_id }}">{{ $p->sku_id }} — {{ $p->nama_produk }} ({{ $p->ukuran_ml }}ml)</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mb-2 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-700">Komposisi Bibit</h3>
                <button type="button" onclick="addRow()" class="text-sm px-3 py-1 bg-indigo-50 text-indigo-700 rounded-md hover:bg-indigo-100">+ Tambah Bibit</button>
            </div>
            <table class="min-w-full text-sm mb-3">
                <thead><tr class="text-xs text-gray-500 uppercase text-left">
                    <th class="py-1">Bibit</th><th class="py-1 w-32 text-right">ml</th><th class="py-1 w-8"></th>
                </tr></thead>
                <tbody id="bibitRows"></tbody>
            </table>

            <div class="flex flex-wrap items-center gap-4 pt-3 border-t border-gray-100">
                <button type="button" onclick="previewHpp()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200">Cek HPP</button>
                <div id="previewBox" class="text-sm text-gray-600 hidden">
                    Total bibit: <b id="pvMl">0</b> ml · HPP/pcs (basis Offline): <b id="pvHpp" class="text-indigo-700">—</b>
                </div>
                <div class="flex-grow"></div>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md text-sm font-semibold">Simpan Resep Mix</button>
            </div>
        </form>
    </div>

    {{-- Daftar resep mix --}}
    <h2 class="text-sm font-semibold text-gray-600 mb-2">Resep Mix Tersimpan ({{ count($mixes) }})</h2>
    @forelse($mixes as $m)
        <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm mb-3 p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="font-bold text-gray-900">{{ $m['nama'] }} <span class="text-xs font-normal text-gray-400">{{ $m['ukuran'] }}ml · {{ $m['sku'] }}</span></h3>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach($m['items'] as $it)
                            <span class="text-xs bg-indigo-50 text-indigo-700 rounded-full px-3 py-1">{{ $it['nama'] }}: {{ rtrim(rtrim(number_format($it['ml'],4,',','.'),'0'),',') }}ml</span>
                        @endforeach
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-500">Total {{ rtrim(rtrim(number_format($m['total_ml'],4,',','.'),'0'),',') }}ml · HPP/pcs</p>
                    <p class="text-lg font-bold text-gray-800">{{ $rp($m['hpp']) }}</p>
                    <div class="flex gap-3 justify-end mt-1">
                        <button type="button" class="text-xs text-indigo-600 hover:underline" onclick='loadMix(@json($m))'>Ubah</button>
                        <form method="POST" action="{{ route('resep_mix.destroy', $m['sku']) }}" onsubmit="return confirm('Hapus resep mix {{ $m['sku'] }}? Kembali ke bibit tunggal.')">
                            @csrf @method('DELETE')
                            <button class="text-xs text-red-500 hover:text-red-700">Hapus</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="bg-white rounded-2xl ring-1 ring-gray-100 shadow-sm p-8 text-center text-gray-400">Belum ada resep mix. Buat lewat form di atas.</div>
    @endforelse
</div>
</div>

<script>
    const BIBITS = @json($bibits->map(fn($b) => ['id' => $b->bibit_id, 'nama' => $b->nama_bibit])->values());
    let rowIdx = 0;
    let skuTom = null;

    function bibitOptions() {
        let html = '<option value="">-- Pilih Bibit --</option>';
        BIBITS.forEach(b => { html += `<option value="${b.id}">${b.nama}</option>`; });
        return html;
    }

    function addRow(bibitId = '', ml = '') {
        const i = rowIdx++;
        const tr = document.createElement('tr');
        tr.className = 'border-b border-gray-50';
        tr.innerHTML = `
            <td class="py-1 pr-2"><select name="bibit_id[${i}]" required class="block w-full border-gray-300 rounded-md border px-2 py-1.5 text-sm bg-white bibit-select">${bibitOptions()}</select></td>
            <td class="py-1 pr-2"><input type="number" name="ml[${i}]" step="any" min="0.0001" required value="${ml}" class="block w-full border-gray-300 rounded-md border px-2 py-1.5 text-sm text-right"></td>
            <td class="py-1 text-center"><button type="button" onclick="this.closest('tr').remove()" class="text-red-500 hover:text-red-700">✕</button></td>`;
        document.getElementById('bibitRows').appendChild(tr);
        const sel = tr.querySelector('.bibit-select');
        const ts = new TomSelect(sel, { create: false, sortField: { field: 'text', direction: 'asc' } });
        if (bibitId) ts.setValue(String(bibitId));
    }

    function loadMix(m) {
        // isi form dengan resep mix terpilih untuk diubah
        document.getElementById('bibitRows').innerHTML = '';
        rowIdx = 0;
        if (skuTom) skuTom.setValue(m.sku);
        m.items.forEach(it => addRow(it.bibit_id, it.ml));
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function previewHpp() {
        const fd = new FormData(document.getElementById('mixForm'));
        fetch('{{ route('resep_mix.preview') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }, body: fd })
            .then(r => r.json()).then(d => {
                const box = document.getElementById('previewBox');
                box.classList.remove('hidden');
                if (!d.ok) { document.getElementById('pvHpp').textContent = d.msg || 'gagal'; document.getElementById('pvMl').textContent = '0'; return; }
                document.getElementById('pvMl').textContent = (Math.round(d.total_ml * 10000) / 10000).toString();
                document.getElementById('pvHpp').textContent = 'Rp ' + Math.round(d.hpp_per_unit).toLocaleString('id-ID');
            }).catch(() => {});
    }

    document.addEventListener('DOMContentLoaded', () => {
        skuTom = new TomSelect('#sku_id', { create: false, sortField: { field: 'text', direction: 'asc' } });
        addRow(); addRow(); // mulai dengan 2 baris
    });
</script>
</body>
</html>
