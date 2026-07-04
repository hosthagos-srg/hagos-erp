<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Belanja Baru - Hagos ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .ts-control { border-radius: 0.375rem; border-color: #d1d5db; min-height: 34px; }
    </style>
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">
<div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Belanja Baru</h1>
        <a href="{{ route('belanja.index') }}" class="text-indigo-600 hover:text-indigo-900 text-sm">&larr; Kembali</a>
    </div>

    @if(session('error'))<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded text-sm">{{ $errors->first() }}</div>@endif

    <form method="POST" action="{{ route('belanja.store') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Jenis Belanja</label>
                <select id="jenis" name="jenis" required class="mt-1 block w-full border-gray-300 rounded-md border px-3 py-2 text-sm bg-white">
                    <option value="bibit">Bibit</option>
                    <option value="komponen">Komponen</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Tanggal Belanja</label>
                <input type="date" name="tgl_belanja" value="{{ date('Y-m-d') }}" required class="mt-1 block w-full border-gray-300 rounded-md border px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">No. Pesanan / ID <span class="text-gray-400">(opsional)</span></label>
                <input type="text" name="belanja_id" placeholder="auto jika kosong" class="mt-1 block w-full border-gray-300 rounded-md border px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Supplier / Toko</label>
                <input type="text" name="supplier_toko" class="mt-1 block w-full border-gray-300 rounded-md border px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Platform</label>
                <select name="platform_beli" class="mt-1 block w-full border-gray-300 rounded-md border px-3 py-2 text-sm bg-white">
                    <option>Shopee</option><option>WA</option><option>Langsung</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Akun Bayar</label>
                <select name="akun_bayar" class="mt-1 block w-full border-gray-300 rounded-md border px-3 py-2 text-sm bg-white">
                    <option value="">-- Pilih Akun --</option>
                    @foreach($akuns as $a)<option value="{{ $a }}">{{ $a }}</option>@endforeach
                </select>
            </div>
        </div>

        {{-- Detail item --}}
        <div>
            <div class="flex items-center justify-between mb-2">
                <h3 class="font-semibold text-gray-800">Item Belanja</h3>
                <button type="button" onclick="addRow()" class="text-sm px-3 py-1 bg-indigo-50 text-indigo-700 rounded-md hover:bg-indigo-100">+ Tambah Item</button>
            </div>
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-xs text-gray-500 uppercase text-left">
                        <th class="py-1">Item</th>
                        <th class="py-1 w-28 text-right"><span class="qty-label">Qty (ml)</span></th>
                        <th class="py-1 w-36 text-right">Harga Total Item (Rp)</th>
                        <th class="py-1 w-8"></th>
                    </tr>
                </thead>
                <tbody id="itemRows"></tbody>
            </table>
        </div>

        {{-- Biaya & ringkasan --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-gray-200">
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <label class="text-sm text-gray-600">Voucher (Rp)</label>
                    <input type="number" name="voucher_nominal" id="voucher" value="0" min="0" step="any" class="w-40 border-gray-300 rounded-md border px-3 py-1.5 text-sm text-right calc">
                </div>
                <div class="flex items-center justify-between">
                    <label class="text-sm text-gray-600">Ongkir Net (Rp)</label>
                    <input type="number" name="ongkir_net" id="ongkir" value="0" step="any" class="w-40 border-gray-300 rounded-md border px-3 py-1.5 text-sm text-right calc">
                </div>
                <div class="flex items-center justify-between">
                    <label class="text-sm text-gray-600">Biaya Layanan (Rp)</label>
                    <input type="number" name="biaya_layanan" id="layanan" value="0" step="any" class="w-40 border-gray-300 rounded-md border px-3 py-1.5 text-sm text-right calc">
                </div>
                <div>
                    <label class="block text-sm text-gray-600">Status</label>
                    <select name="status_belanja" class="mt-1 block w-full border-gray-300 rounded-md border px-3 py-2 text-sm bg-white">
                        <option value="Dipesan">Dipesan (uang keluar, stok belum masuk)</option>
                        <option value="Dikirim">Dikirim</option>
                        <option value="Diterima">Diterima (stok + harga rata-rata langsung naik)</option>
                    </select>
                    <input type="text" name="no_resi" placeholder="No. resi (opsional)" class="mt-2 block w-full border-gray-300 rounded-md border px-3 py-2 text-sm">
                </div>
            </div>
            <div class="bg-gray-50 rounded-lg p-4 space-y-2 text-sm self-start">
                <div class="flex justify-between"><span class="text-gray-500">Subtotal Kotor</span><span id="sumSubtotal" class="font-medium">Rp 0</span></div>
                <div class="flex justify-between text-red-600"><span>− Voucher</span><span id="sumVoucher">Rp 0</span></div>
                <div class="flex justify-between"><span class="text-gray-500">+ Ongkir Net</span><span id="sumOngkir">Rp 0</span></div>
                <div class="flex justify-between"><span class="text-gray-500">+ Biaya Layanan</span><span id="sumLayanan">Rp 0</span></div>
                <div class="flex justify-between font-bold text-base border-t border-gray-200 pt-2"><span>Total Bayar</span><span id="sumTotal">Rp 0</span></div>
                <p class="text-xs text-gray-400 pt-1">Voucher/ongkir/biaya dialokasikan proporsional ke tiap item saat disimpan (harga net per ml/pcs).</p>
            </div>
        </div>

        <div class="pt-4 border-t border-gray-200 flex justify-end">
            <button type="submit" class="px-5 py-2 bg-indigo-600 text-white rounded-md font-medium text-sm hover:bg-indigo-700">Simpan Belanja</button>
        </div>
    </form>
</div>

<script>
    const BIBITS = @json($bibits);
    const KOMPONENS = @json($komponens);
    let rowIdx = 0;

    function itemOptions() {
        const jenis = document.getElementById('jenis').value;
        const list = jenis === 'bibit' ? BIBITS : KOMPONENS;
        const idKey = jenis === 'bibit' ? 'bibit_id' : 'komponen_id';
        const nameKey = jenis === 'bibit' ? 'nama_bibit' : 'nama_komponen';
        let html = '<option value="">-- Pilih --</option>';
        list.forEach(x => { html += `<option value="${x[idKey]}">${x[nameKey]}</option>`; });
        return html;
    }

    function itemOptionsArr() {
        const jenis = document.getElementById('jenis').value;
        const list = jenis === 'bibit' ? BIBITS : KOMPONENS;
        const idKey = jenis === 'bibit' ? 'bibit_id' : 'komponen_id';
        const nameKey = jenis === 'bibit' ? 'nama_bibit' : 'nama_komponen';
        return [{ value: '', text: '-- Pilih --' }]
            .concat(list.map(x => ({ value: String(x[idKey]), text: x[nameKey] })));
    }

    function addRow() {
        const i = rowIdx++;
        const tr = document.createElement('tr');
        tr.className = 'border-b border-gray-50';
        tr.innerHTML = `
            <td class="py-1 pr-2"><select name="items[${i}][item_id]" required class="block w-full border-gray-300 rounded-md border px-2 py-1.5 text-sm bg-white item-select">${itemOptions()}</select></td>
            <td class="py-1 pr-2"><input type="number" name="items[${i}][qty]" step="any" min="0.01" required class="block w-full border-gray-300 rounded-md border px-2 py-1.5 text-sm text-right"></td>
            <td class="py-1 pr-2"><input type="number" name="items[${i}][harga_total_item]" step="any" min="0" required class="block w-full border-gray-300 rounded-md border px-2 py-1.5 text-sm text-right calc"></td>
            <td class="py-1 text-center"><button type="button" onclick="this.closest('tr').remove(); recalc();" class="text-red-500 hover:text-red-700">✕</button></td>`;
        document.getElementById('itemRows').appendChild(tr);
        tr.querySelectorAll('.calc').forEach(el => el.addEventListener('input', recalc));
        const sel = tr.querySelector('.item-select');
        if (window.TomSelect && sel) {
            new TomSelect(sel, { create: false, sortField: { field: 'text', direction: 'asc' } });
        }
    }

    function rp(n){ return 'Rp ' + Math.round(n).toLocaleString('id-ID'); }

    function recalc() {
        let subtotal = 0;
        document.querySelectorAll('input[name$="[harga_total_item]"]').forEach(el => subtotal += parseFloat(el.value || 0));
        const voucher = parseFloat(document.getElementById('voucher').value || 0);
        const ongkir = parseFloat(document.getElementById('ongkir').value || 0);
        const layanan = parseFloat(document.getElementById('layanan').value || 0);
        const total = subtotal - voucher + ongkir + layanan;
        document.getElementById('sumSubtotal').textContent = rp(subtotal);
        document.getElementById('sumVoucher').textContent = rp(voucher);
        document.getElementById('sumOngkir').textContent = rp(ongkir);
        document.getElementById('sumLayanan').textContent = rp(layanan);
        document.getElementById('sumTotal').textContent = rp(total);
    }

    document.getElementById('jenis').addEventListener('change', function () {
        document.querySelector('.qty-label').textContent = this.value === 'bibit' ? 'Qty (ml)' : 'Qty (pcs)';
        // Bangun ulang tiap select bersih: hancurkan TomSelect lama → isi opsi jenis baru → init ulang.
        // Lebih andal daripada memutasi opsi (yang bisa bikin dropdown rusak/terbuka).
        document.querySelectorAll('.item-select').forEach(s => {
            if (s.tomselect) s.tomselect.destroy();
            s.innerHTML = itemOptions();
            if (window.TomSelect) {
                new TomSelect(s, { create: false, sortField: { field: 'text', direction: 'asc' } });
            }
        });
    });
    document.querySelectorAll('#voucher,#ongkir,#layanan').forEach(el => el.addEventListener('input', recalc));
    addRow(); // baris pertama
</script>
</div>
</body>
</html>
