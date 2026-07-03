<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Penjualan - Hagos ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">
    <div class="min-h-screen p-6 max-w-4xl mx-auto">
        <header class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Input Penjualan</h1>
                <p class="text-gray-600 mt-2">Catat transaksi penjualan manual</p>
            </div>
            <a href="{{ route('dashboard') }}" class="text-indigo-600 hover:text-indigo-900 font-medium">&larr; Kembali ke Dashboard</a>
        </header>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">
            @if(session('success'))
                <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold">Sukses!</strong>
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif
            @if(session('error'))
                <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold">Error!</strong>
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <form action="{{ route('penjualan.store') }}" method="POST" class="space-y-6" onsubmit="return lockSubmit(this)">
                @csrf
                
                {{-- Item produk (bisa lebih dari 1 untuk 1 pembeli) --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700">Produk yang Dibeli</label>
                        <button type="button" onclick="addItemRow()" class="text-sm px-3 py-1 bg-indigo-50 text-indigo-700 rounded-md hover:bg-indigo-100">+ Tambah Produk</button>
                    </div>
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-xs text-gray-500 uppercase text-left">
                                <th class="py-1">Produk (SKU)</th>
                                <th class="py-1 w-24 text-right">Qty</th>
                                <th class="py-1 w-32 text-right">Harga</th>
                                <th class="py-1 w-32 text-right">Subtotal</th>
                                <th class="py-1 w-8"></th>
                            </tr>
                        </thead>
                        <tbody id="itemRows"></tbody>
                    </table>
                    <p class="mt-1 text-xs text-gray-500">Tambah beberapa produk untuk 1 pembeli tanpa input berulang. Harga mengikuti channel yang dipilih.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="channel" class="block text-sm font-medium text-gray-700">Channel Penjualan</label>
                        <select id="channel" name="channel" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md shadow-sm border bg-white" required>
                            <option value="">-- Pilih Channel --</option>
                            @foreach($channelOptions as $ch)
                                <option value="{{ $ch }}">{{ $ch }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="metode_pengiriman" class="block text-sm font-medium text-gray-700">Metode Pengiriman</label>
                        <select id="metode_pengiriman" name="metode_pengiriman" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md shadow-sm border bg-white">
                            <option value="Ambil Langsung">Ambil Langsung (tanpa biaya packing/Lapis 2)</option>
                            <option value="Dikirim">Dikirim Ekspedisi (kena biaya packing/Lapis 2)</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Menentukan apakah biaya fulfillment (gaji packing + shrink + bahan packing) masuk HPP. Otomatis menyesuaikan channel, bisa diubah.</p>
                    </div>

                    <div>
                        <label for="diterima_oleh" class="block text-sm font-medium text-gray-700">Diterima / Diracik oleh (Admin)</label>
                        <select id="diterima_oleh" name="diterima_oleh" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md shadow-sm border bg-white">
                            <option value="">-- Pilih Admin --</option>
                            @foreach($admins as $admin)
                                <option value="{{ $admin }}">{{ $admin }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Untuk pesanan non-marketplace (diracik langsung), tercatat sebagai peracik.</p>
                    </div>

                    <div>
                        <label for="status_pembayaran" class="block text-sm font-medium text-gray-700">Status Pembayaran</label>
                        <select id="status_pembayaran" name="status_pembayaran" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md shadow-sm border bg-white">
                            <option value="Lunas">Lunas</option>
                            <option value="Piutang">Belum Bayar / Piutang</option>
                        </select>
                    </div>

                    <div>
                        <label for="akun_pembayaran" class="block text-sm font-medium text-gray-700">Akun Pembayaran (Jika Lunas)</label>
                        <select id="akun_pembayaran" name="akun_pembayaran" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md shadow-sm border bg-white">
                            <option value="">-- Pilih Akun --</option>
                            @foreach($akuns as $a)<option value="{{ $a }}">{{ $a }}</option>@endforeach
                        </select>
                    </div>

                    <div>
                        <label for="diskon_manual" class="block text-sm font-medium text-gray-700">Diskon (Rp)</label>
                        <input type="number" name="diskon_manual" id="diskon_manual" min="0" step="500" value="0" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md border px-3 py-2" placeholder="Cth: 10000">
                        <p class="mt-1 text-xs text-gray-500">Potongan harga langsung (mis. diskon langganan). Net diterima = Omset − Diskon.</p>
                    </div>

                    <div>
                        <label for="nama_pembeli" class="block text-sm font-medium text-gray-700">Nama Pemesan (Offline/WA/Reseller/Website)</label>
                        <input type="text" name="nama_pembeli" id="nama_pembeli" value="{{ old('nama_pembeli') }}" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md border px-3 py-2" placeholder="Cth: Budi">
                    </div>

                    <div>
                        <label for="no_hp_pembeli" class="block text-sm font-medium text-gray-700">No. HP Pemesan <span class="text-gray-400 font-normal">(untuk CRM)</span></label>
                        <input type="tel" name="no_hp_pembeli" id="no_hp_pembeli" value="{{ old('no_hp_pembeli') }}" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md border px-3 py-2" placeholder="Cth: 0812xxxxxxx">
                        <p class="mt-1 text-xs text-gray-500">Pesanan non-marketplace otomatis tersimpan ke daftar Pelanggan (CRM).</p>
                    </div>

                    <div>
                        <label for="external_order_id" class="block text-sm font-medium text-gray-700">ID Pesanan (Khusus Marketplace)</label>
                        <input type="text" name="external_order_id" id="external_order_id" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md border px-3 py-2" placeholder="Cth: 240624XXXX">
                    </div>

                    <div>
                        <label for="ekstra_tester" class="block text-sm font-medium text-gray-700">Ekstra Tester (Opsional)</label>
                        <input type="number" name="ekstra_tester" id="ekstra_tester" min="0" value="0" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md border px-3 py-2" placeholder="Cth: 1">
                        <p class="mt-1 text-xs text-gray-500">Isi jika Anda memberi tester tambahan (di luar jatah resep).</p>
                    </div>
                </div>

                {{-- Ringkasan Harga (live) --}}
                <div id="ringkasan-harga" class="mt-6 bg-indigo-50 border border-indigo-100 rounded-lg p-4">
                    <h3 class="text-sm font-bold text-indigo-800 mb-2">Ringkasan Pesanan</h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-x-6 gap-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-gray-600">Total Item</span><span id="rh-items" class="font-semibold text-gray-900">0</span></div>
                        <div class="flex justify-between"><span class="text-gray-600">Subtotal</span><span id="rh-subtotal" class="font-semibold text-gray-900">Rp 0</span></div>
                        <div class="flex justify-between"><span class="text-gray-600">Diskon</span><span id="rh-diskon" class="font-semibold text-red-600">− Rp 0</span></div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-indigo-200 flex justify-between items-center">
                        <span class="font-bold text-indigo-800">Net Diterima</span>
                        <span id="rh-net" class="text-xl font-bold text-indigo-700">Rp 0</span>
                    </div>
                </div>

                <div class="pt-5 border-t border-gray-200 mt-6 flex justify-end">
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Simpan Penjualan
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script>
        // Cegah double-submit (double-click): kunci tombol setelah submit valid.
        let _submitting = false;
        function lockSubmit(form) {
            if (_submitting) return false;
            _submitting = true;
            const b = form.querySelector('button[type=submit]');
            if (b) { b.disabled = true; b.textContent = 'Menyimpan...'; }
            return true;
        }

        const PRODUKS = @json($produks->map(fn($p) => ['id' => $p->sku_id, 'nama' => $p->nama_produk . ' - ' . $p->ukuran_ml . 'ml'])->values());
        const HARGA = @json($hargaMap); // {sku_id: {channel: harga}}
        let itemIdx = 0;
        const rupiah = n => 'Rp ' + Number(n || 0).toLocaleString('id-ID');

        function skuOptions() {
            let h = '<option value="">-- Pilih Produk --</option>';
            PRODUKS.forEach(p => h += `<option value="${p.id}">${p.nama}</option>`);
            return h;
        }
        function currentChannel() { return document.getElementById('channel').value; }
        function hargaOf(sku) { const ch = currentChannel(); return (HARGA[sku] && HARGA[sku][ch]) ? Number(HARGA[sku][ch]) : 0; }

        function addItemRow() {
            const i = itemIdx++;
            const tr = document.createElement('tr');
            tr.className = 'border-b border-gray-50';
            tr.innerHTML = `
                <td class="py-1 pr-2"><select name="items[${i}][sku_id]" required class="block w-full border-gray-300 rounded-md border px-2 py-1.5 text-sm bg-white item-sku">${skuOptions()}</select></td>
                <td class="py-1 pr-2"><input type="number" name="items[${i}][qty]" min="1" value="1" required class="block w-full border-gray-300 rounded-md border px-2 py-1.5 text-sm text-right item-qty"></td>
                <td class="py-1 pr-2 text-right text-gray-600 item-harga">—</td>
                <td class="py-1 pr-2 text-right font-medium text-gray-800 item-sub">—</td>
                <td class="py-1 text-center"><button type="button" onclick="this.closest('tr').remove(); recalc();" class="text-red-500 hover:text-red-700">✕</button></td>`;
            document.getElementById('itemRows').appendChild(tr);
            const sel = tr.querySelector('.item-sku');
            new TomSelect(sel, { create: false, sortField: { field: 'text', direction: 'asc' } });
            sel.addEventListener('change', recalc);
            tr.querySelector('.item-qty').addEventListener('input', recalc);
            recalc();
        }

        function recalc() {
            let total = 0, items = 0;
            document.querySelectorAll('#itemRows tr').forEach(tr => {
                const sku = tr.querySelector('.item-sku').value;
                const qty = parseInt(tr.querySelector('.item-qty').value || 0) || 0;
                const harga = hargaOf(sku);
                const hCell = tr.querySelector('.item-harga'), sCell = tr.querySelector('.item-sub');
                if (!sku) { hCell.textContent = '—'; sCell.textContent = '—'; }
                else if (harga <= 0) { hCell.innerHTML = '<span class="text-red-600">belum diset</span>'; sCell.textContent = '—'; }
                else { hCell.textContent = rupiah(harga); sCell.textContent = rupiah(harga * qty); total += harga * qty; items += qty; }
            });
            const diskon = parseFloat(document.getElementById('diskon_manual').value || 0) || 0;
            document.getElementById('rh-items').textContent = items;
            document.getElementById('rh-subtotal').textContent = rupiah(total);
            document.getElementById('rh-diskon').textContent = '− ' + rupiah(diskon);
            document.getElementById('rh-net').textContent = rupiah(Math.max(0, total - diskon));
        }

        document.addEventListener('DOMContentLoaded', function() {
            const channelEl = document.getElementById('channel');
            const metodeEl = document.getElementById('metode_pengiriman');
            channelEl.addEventListener('change', function() {
                const c = (channelEl.value || '').toLowerCase();
                metodeEl.value = (c.includes('marketplace') || c.includes('wa')) ? 'Dikirim' : 'Ambil Langsung';
                recalc();
            });
            document.getElementById('diskon_manual').addEventListener('input', recalc);
            addItemRow(); // baris pertama
        });
    </script>
</div>
</body>
</html>
