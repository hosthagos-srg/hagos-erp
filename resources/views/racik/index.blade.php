<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Antrean Peracikan - Hagos ERP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Antrean Peracikan (Gudang)</h1>
        </div>

        @if (session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                <p>{{ session('success') }}</p>
            </div>
        @endif
        @if (session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                <p>{{ session('error') }}</p>
            </div>
        @endif

        <div class="bg-white shadow overflow-hidden sm:rounded-lg" x-data="racikApp()">
            @if(count($antrean) > 0)
            <div class="px-4 py-3 bg-indigo-50 border-b border-indigo-100 flex flex-wrap items-center gap-3">
                <label class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                    <input type="checkbox" id="select-all" onclick="toggleAll(this)" checked class="rounded border-gray-300 text-indigo-600">
                    Pilih Semua
                </label>
                <span class="text-xs text-gray-500"><span id="count-selected">{{ count($antrean) }}</span> dipilih</span>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-600">Set aksi (terpilih):</span>
                    <select onchange="setBulkAksi(this.value)" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm bg-white">
                        <option value="">-- pilih --</option>
                        <option value="racik">Racik Baru (Potong Stok)</option>
                        <option value="kirim_golive">Sudah Dikirim (Go-Live)</option>
                        <option value="t11">Ambil dari Retur (T11)</option>
                    </select>
                </div>
                <div class="ml-auto flex items-center gap-2">
                    <span class="text-sm text-gray-600">Set diracik oleh (semua terpilih):</span>
                    <select onchange="setBulkOleh(this.value)" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm bg-white">
                        <option value="">-- pilih --</option>
                        @foreach($admins as $a)<option value="{{ $a }}">{{ $a }}</option>@endforeach
                    </select>
                </div>
            </div>
            @endif
            <form id="racikForm" action="{{ route('racik.process') }}" method="POST" @submit.prevent="submitForm">
                @csrf
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-10">✓</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal & Channel</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU / Aroma</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi Racik</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Diracik Oleh</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($antrean as $item)
                        <tr>
                            <td class="px-4 py-4 text-center">
                                <input type="checkbox" class="row-check rounded border-gray-300 text-indigo-600" data-id="{{ $item->detail_id }}" checked onclick="updateCount()">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $item->tgl_pesanan }}</div>
                                <div class="text-sm text-gray-500">{{ $item->channel }}</div>
                                @if($item->external_order_id)
                                    <div class="text-xs text-blue-600 font-bold mt-1">ID: {{ $item->external_order_id }}</div>
                                @endif
                                @if($item->nama_pembeli)
                                    <div class="text-xs text-indigo-600 font-bold mt-1">{{ $item->nama_pembeli }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <select name="actions[{{ $item->detail_id }}][sku_id]" class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                    @foreach($produks as $p)
                                        <option value="{{ $p->sku_id }}" {{ $item->sku_id == $p->sku_id ? 'selected' : '' }}>
                                            {{ $p->nama_produk }} - {{ $p->ukuran_ml }}ml
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $item->qty }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <select name="actions[{{ $item->detail_id }}][aksi]" data-aksi class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                    <option value="racik" selected>Racik Baru (Potong Stok)</option>
                                    <option value="kirim_golive">Sudah Dikirim (Go-Live · tanpa potong stok)</option>
                                    <option value="t11">Ambil dari Retur (T11)</option>
                                    <option value="batal">Batalkan Pesanan</option>
                                    <option value="skip" hidden>(tidak diproses)</option>
                                </select>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <select name="actions[{{ $item->detail_id }}][diracik_oleh]" data-oleh class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                    @foreach($admins as $a)<option value="{{ $a }}">{{ $a }}</option>@endforeach
                                </select>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                Tidak ada antrean pesanan yang menunggu diracik.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                @if(count($antrean) > 0)
                <div class="px-4 py-3 bg-gray-50 text-right sm:px-6 flex justify-end items-center space-x-4">
                    <span x-show="loading" class="text-sm text-indigo-600 font-medium">Memeriksa stok...</span>
                    <button type="submit" :disabled="loading" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50">
                        Proses Racik Semua yang Terpilih
                    </button>
                </div>
                @endif
            </form>

            @if($antrean->hasPages())
            <div class="px-4 py-3 border-t border-gray-100 bg-white">
                <div class="text-xs text-gray-500 mb-2">Menampilkan {{ $antrean->firstItem() }}–{{ $antrean->lastItem() }} dari {{ $antrean->total() }} antrean · 50 per halaman</div>
                {{ $antrean->links() }}
            </div>
            @endif

            <!-- Modal Opname Dadakan (Vanilla JS) -->
            <div id="opnameModal" class="fixed z-50 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeOpnameModal()"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="relative z-10 inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        {{-- LANGKAH 1: Peringatan (batalkan / lanjutkan) --}}
                        <div id="opnameStep1">
                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <div class="sm:flex sm:items-start">
                                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                        <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                    </div>
                                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                        <h3 class="text-lg leading-6 font-medium text-gray-900">Stok Kurang / Kosong!</h3>
                                        <div class="mt-2 text-sm text-gray-500">
                                            <p>Bahan berikut tidak mencukupi. <b>Jika fisiknya benar-benar kosong, batalkan</b> (tidak jadi diracik). Jika fisik masih ada, lanjut untuk input stok riil.</p>
                                        </div>
                                        <div class="mt-4 space-y-2" id="deficitsWarn"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                <button type="button" onclick="opnameLanjut()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">
                                    Lanjutkan (Input Stok Riil)
                                </button>
                                <button type="button" id="btnProsesCukup" onclick="prosesYangCukup()" class="hidden mt-3 w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-emerald-600 text-base font-medium text-white hover:bg-emerald-700 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                    <span id="btnCukupLabel">Proses yang stoknya cukup</span>
                                </button>
                                <button type="button" onclick="closeOpnameModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                    Batalkan
                                </button>
                            </div>
                        </div>
                        {{-- LANGKAH 2: Input stok fisik riil --}}
                        <div id="opnameStep2" class="hidden">
                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <div class="text-center sm:text-left">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900">Input Stok Fisik Riil</h3>
                                    <p class="mt-2 text-sm text-gray-500">Ketik jumlah fisik yang benar-benar ada sekarang.</p>
                                    <div class="mt-4 space-y-4" id="deficitsList"></div>
                                </div>
                            </div>
                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                <button type="button" onclick="adjustAndContinueRacik()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">
                                    Update Stok & Lanjut
                                </button>
                                <button type="button" onclick="opnameKembali()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                    Kembali
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    function racikApp() {
        return {
            loading: false,
            
            submitForm() {
                // Baris yang TIDAK dicentang → aksi 'skip' (tidak diproses, tetap antre)
                let adaTerpilih = false;
                document.querySelectorAll('.row-check').forEach(cb => {
                    const row = cb.closest('tr');
                    const aksi = row.querySelector('[data-aksi]');
                    if (!cb.checked) { aksi.value = 'skip'; }
                    else { if (aksi.value === 'skip') aksi.value = 'racik'; adaTerpilih = true; }
                });
                if (!adaTerpilih) { alert('Belum ada produk yang dipilih untuk diracik.'); return; }

                this.loading = true;
                const form = document.getElementById('racikForm');
                const formData = new FormData(form);

                fetch('{{ route("racik.check_stock") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'deficit') {
                        window.currentDeficits = data.deficits.map(d => ({...d, real_stock: d.total_butuh}));
                        window.blockedIds = data.blocked || [];
                        renderDeficits();
                        configProsesCukup(data);
                        openOpnameModal();
                        this.loading = false;
                    } else if (data.status === 'ok') {
                        form.submit();
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert("Terjadi kesalahan saat memeriksa stok.");
                    this.loading = false;
                });
            }
        }
    }

    // ── Checklist & bulk ──
    function toggleAll(master) {
        document.querySelectorAll('.row-check').forEach(cb => cb.checked = master.checked);
        updateCount();
    }
    function updateCount() {
        const all = document.querySelectorAll('.row-check');
        const checked = document.querySelectorAll('.row-check:checked');
        const el = document.getElementById('count-selected');
        if (el) el.textContent = checked.length;
        const master = document.getElementById('select-all');
        if (master) {
            master.checked = checked.length === all.length && all.length > 0;
            master.indeterminate = checked.length > 0 && checked.length < all.length;
        }
    }
    function setBulkOleh(val) {
        if (!val) return;
        // Hanya untuk baris yang dicentang
        document.querySelectorAll('.row-check:checked').forEach(cb => {
            const sel = cb.closest('tr').querySelector('[data-oleh]');
            if (sel) sel.value = val;
        });
    }
    function setBulkAksi(val) {
        if (!val) return;
        document.querySelectorAll('.row-check:checked').forEach(cb => {
            const sel = cb.closest('tr').querySelector('[data-aksi]');
            if (sel) sel.value = val;
        });
    }
    // Tombol "Proses yang stoknya cukup" — tampil bila ada pesanan feasible & ada yang diblokir
    function configProsesCukup(data) {
        const btn = document.getElementById('btnProsesCukup');
        const label = document.getElementById('btnCukupLabel');
        const feasible = data.feasible_count || 0;
        const blocked = (data.blocked || []).length;
        if (feasible > 0 && blocked > 0) {
            if (label) label.textContent = `Proses ${feasible} yang cukup (lewati ${blocked})`;
            btn.classList.remove('hidden');
        } else {
            btn.classList.add('hidden');
        }
    }
    // Lewati pesanan yang kekurangan stok (uncheck), lalu proses sisanya.
    function prosesYangCukup() {
        (window.blockedIds || []).forEach(id => {
            const cb = document.querySelector(`.row-check[data-id="${id}"]`);
            if (cb) cb.checked = false;
        });
        updateCount();
        closeOpnameModal();
        document.getElementById('racikForm').requestSubmit();
    }

    // Vanilla JS
    window.currentDeficits = [];
    function openOpnameModal() {
        // selalu mulai dari Langkah 1 (peringatan)
        document.getElementById('opnameStep1').classList.remove('hidden');
        document.getElementById('opnameStep2').classList.add('hidden');
        document.getElementById('opnameModal').classList.remove('hidden');
    }
    function closeOpnameModal() {
        document.getElementById('opnameModal').classList.add('hidden');
    }
    function opnameLanjut() {
        document.getElementById('opnameStep1').classList.add('hidden');
        document.getElementById('opnameStep2').classList.remove('hidden');
    }
    function opnameKembali() {
        document.getElementById('opnameStep2').classList.add('hidden');
        document.getElementById('opnameStep1').classList.remove('hidden');
    }
    function unitOf(d) { return (d.type === 'komponen') ? 'pcs' : 'ml'; }
    function renderDeficits() {
        // Langkah 1 — ringkasan peringatan (read-only)
        document.getElementById('deficitsWarn').innerHTML = window.currentDeficits.map(d => `
            <div class="bg-red-50 px-3 py-2 rounded border border-red-100 text-sm">
                <span class="font-bold text-gray-900">${d.nama_bibit}</span>
                <span class="text-xs text-red-700"> — butuh ${d.total_butuh} ${unitOf(d)}, stok ${d.stok_sistem} ${unitOf(d)}</span>
            </div>`).join('');
        // Langkah 2 — input stok fisik
        const container = document.getElementById('deficitsList');
        container.innerHTML = '';
        window.currentDeficits.forEach((d, index) => {
            container.innerHTML += `
                <div class="bg-red-50 p-3 rounded border border-red-100 flex flex-col sm:flex-row items-center justify-between mb-2">
                    <div>
                        <p class="font-bold text-gray-900">${d.nama_bibit}</p>
                        <p class="text-xs text-red-700">Butuh: ${d.total_butuh} ${unitOf(d)} | Stok: ${d.stok_sistem} ${unitOf(d)}</p>
                    </div>
                    <div class="mt-2 sm:mt-0 flex items-center space-x-2">
                        <label class="text-xs font-medium text-gray-700">Fisik:</label>
                        <input type="number" step="0.1" value="${d.real_stock}" onchange="window.currentDeficits[${index}].real_stock = this.value" class="block w-24 shadow-sm sm:text-sm border-gray-300 rounded-md border px-2 py-1">
                    </div>
                </div>
            `;
        });
    }

    function adjustAndContinueRacik() {
        fetch('{{ route("racik.adjust_stock") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                adjustments: window.currentDeficits
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'ok') {
                document.getElementById('racikForm').submit();
            }
        })
        .catch(err => {
            console.error(err);
            alert("Gagal mengupdate stok riil.");
        });
    }
</script>
</div>
</body>
</html>
