<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produksi Internal - Hagos ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .ts-control { border-radius: 0.375rem; border-color: #d1d5db; padding-top: 0.5rem; padding-bottom: 0.5rem; }
    </style>
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">
    <div class="min-h-screen p-6 max-w-5xl mx-auto" x-data="produksiApp()">
        <header class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Produksi Internal (Gudang)</h1>
                <p class="text-gray-600 mt-2">Racik Tester Massal dan Campur Absolute</p>
            </div>
            <div class="flex items-center gap-4">
                <a href="{{ route('produksi.riwayat') }}" class="text-indigo-600 hover:text-indigo-900 font-medium">📜 Riwayat Produksi</a>
                <a href="{{ route('dashboard') }}" class="text-indigo-600 hover:text-indigo-900 font-medium">&larr; Dashboard</a>
            </div>
        </header>

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

        <!-- Tabs Navigation -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button @click="tab = 'tester'" :class="tab === 'tester' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    📦 Produksi Tester Per-Sesi
                </button>
                <button @click="tab = 'absolute'" :class="tab === 'absolute' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    🧪 Campur Absolute
                </button>
            </nav>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">
            
            <!-- Tab Racik Tester -->
            <div x-show="tab === 'tester'" style="display: none;">
                <div class="mb-6 text-sm text-gray-600 bg-gray-50 p-4 rounded-lg flex justify-between items-center border">
                    <div>
                        <p class="font-bold text-gray-800">Informasi Gudang "Botol Tester Jadi"</p>
                        <p>Total Tersedia: <span class="font-semibold text-indigo-600">{{ $tstr->stok ?? 0 }} pcs</span></p>
                    </div>
                    <div class="text-right">
                        <p>HPP Rata-Rata Saat Ini:</p>
                        <p class="font-bold text-gray-800">Rp {{ number_format((float)($tstr->harga_satuan ?? 0), 0, ',', '.') }} / pcs</p>
                    </div>
                </div>

                <form id="testerForm" action="{{ route('produksi.tester.store') }}" method="POST" @submit.prevent="submitTesterForm">
                    @csrf
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tanggal Racik</label>
                            <input type="date" name="tgl_racik" value="{{ now()->toDateString() }}" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md border px-3 py-2" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Diracik Oleh (Petugas)</label>
                            <input type="text" name="diracik_oleh" placeholder="Cth: Adzim" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md border px-3 py-2" required>
                        </div>
                    </div>

                    <div class="border-t pt-6 mb-4">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Daftar Aroma yang Diracik</h3>
                        
                        <template x-for="(aroma, index) in aromas" :key="aroma.id">
                            <div class="flex items-center space-x-4 mb-4 bg-gray-50 p-3 rounded-md border">
                                <div class="flex-grow">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Aroma Tester</label>
                                    <select :name="`aromas[${index}][bibit_id]`" x-init="$nextTick(() => { if (window.TomSelect && !$el.tomselect) new TomSelect($el, { create: false, sortField: { field: 'text', direction: 'asc' } }); })" class="block w-full pl-3 pr-10 py-2 border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md border bg-white" required>
                                        <option value="">-- Pilih Aroma --</option>
                                        @foreach($bibits as $bibit)
                                            <option value="{{ $bibit->bibit_id }}">Tester {{ $bibit->nama_bibit }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="w-32">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Jml Botol</label>
                                    <input type="number" :name="`aromas[${index}][qty_botol]`" min="1" x-model="aroma.qty" class="focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md border px-3 py-2" required>
                                </div>
                                <div class="w-10 flex items-end justify-center pt-5">
                                    <button type="button" @click="aromas = aromas.filter(a => a.id !== aroma.id)" x-show="aromas.length > 1" class="text-red-500 hover:text-red-700 font-bold text-xl">&times;</button>
                                </div>
                            </div>
                        </template>

                        <button type="button" @click="aromas.push({ id: Date.now(), bibit_id: '', qty: 1 })" class="mt-2 inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            + Tambah Aroma Lain
                        </button>
                    </div>

                    <div class="pt-5 border-t border-gray-200 flex justify-end items-center space-x-4">
                        <span x-show="loading" class="text-sm text-indigo-600 font-medium">Memeriksa stok...</span>
                        <button type="submit" :disabled="loading" class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50">
                            Proses Produksi Tester
                        </button>
                    </div>
                </form>

                <!-- Modal Opname Dadakan Tester (Vanilla JS) -->
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
                                                <p>Bahan berikut tidak mencukupi. <b>Jika fisiknya benar-benar kosong, batalkan</b> (tidak jadi produksi). Jika fisik masih ada, lanjut untuk input stok riil.</p>
                                            </div>
                                            <div class="mt-4 space-y-2" id="deficitsWarn"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                    <button type="button" onclick="opnameLanjut()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">
                                        Lanjutkan (Input Stok Riil)
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
                                    <button type="button" onclick="adjustAndContinueTester()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">
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

            <!-- Tab Racik Absolute -->
            <div x-show="tab === 'absolute'" style="display: none;">
                <div class="mb-6 text-sm text-gray-600 bg-blue-50 p-4 rounded-lg flex justify-between items-center border border-blue-100">
                    <div>
                        <p class="font-bold text-blue-800">Informasi Gudang "Absolute Campuran"</p>
                        <p>Total Tersedia: <span class="font-semibold text-blue-600">{{ $absc->stok ?? 0 }} ml</span></p>
                    </div>
                    <div class="text-right">
                        <p>HPP Rata-Rata Saat Ini:</p>
                        <p class="font-bold text-blue-800">Rp {{ number_format((float)str_replace(',', '.', $absc->harga_satuan ?? 0), 2, ',', '.') }} / ml</p>
                    </div>
                </div>

                <form action="{{ route('produksi.absolute.store') }}" method="POST" class="space-y-6 max-w-xl mx-auto">
                    @csrf
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tanggal Racik</label>
                            <input type="date" name="tgl_racik" value="{{ now()->toDateString() }}" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md border px-3 py-2" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Diracik Oleh (Petugas)</label>
                            <input type="text" name="diracik_oleh" placeholder="Cth: Adzim" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md border px-3 py-2" required>
                        </div>
                    </div>

                    <div class="border-t pt-6 mb-4">
                        <div>
                            <label for="ml_murni" class="block text-sm font-medium text-gray-700">Absolute Murni (ML)</label>
                            <input type="number" step="0.1" name="ml_murni" id="ml_murni" min="0" value="700" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md border px-3 py-2" required>
                            <p class="mt-1 text-xs text-gray-500">Stok sisa: {{ $absm->stok ?? 0 }} ml</p>
                        </div>

                        <div class="mt-4">
                            <label for="ml_denat" class="block text-sm font-medium text-gray-700">Aqua Denat / Fixative (ML)</label>
                            <input type="number" step="0.1" name="ml_denat" id="ml_denat" min="0" value="50" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md border px-3 py-2" required>
                            <p class="mt-1 text-xs text-gray-500">Stok sisa: {{ $aqua->stok ?? 0 }} ml</p>
                        </div>
                    </div>

                    <div class="bg-blue-100 p-3 rounded-md text-center">
                        <p class="text-sm text-blue-800">Akan menghasilkan: <strong id="total_hasil" class="text-lg">750</strong> ml Absolute Campuran.</p>
                    </div>

                    <div class="pt-5 flex justify-center">
                        <button type="submit" class="inline-flex justify-center py-3 px-8 border border-transparent shadow-sm text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 w-full">
                            Proses Campur Absolute
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
    
    <div x-show="false" x-data="{ tab: 'tester', aromas: [{ id: Date.now(), bibit_id: '', qty: 1 }], loading: false, showModal: false, deficits: [], submitTesterForm() { this.loading = true; const form = document.getElementById('testerForm'); const formData = new FormData(form); fetch('{{ route('produksi.tester.check_stock') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }, body: formData }).then(res => res.json()).then(data => { if (data.status === 'deficit') { this.deficits = data.deficits.map(d => ({...d, real_stock: d.total_butuh})); this.showModal = true; } else if (data.status === 'ok') { form.submit(); } }).catch(err => { console.error(err); alert('Terjadi kesalahan saat memeriksa stok.'); this.loading = false; }); }, adjustAndContinueTester() { fetch('{{ route('produksi.tester.adjust_stock') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify({ adjustments: this.deficits }) }).then(res => res.json()).then(data => { if (data.status === 'ok') { document.getElementById('testerForm').submit(); } }).catch(err => { console.error(err); alert('Gagal mengupdate stok riil.'); }); } }"></div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('produksiApp', () => ({
                tab: 'tester', 
                aromas: [{ id: Date.now(), bibit_id: '', qty: 1 }],
                loading: false,
                
                submitTesterForm() {
                    this.loading = true;
                    const form = document.getElementById('testerForm');
                    const formData = new FormData(form);
                    
                    fetch('{{ route("produksi.tester.check_stock") }}', {
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
                            renderDeficits();
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
            }));
        });

        // Vanilla JS for Modal to ensure it shows perfectly
        window.currentDeficits = [];
        function openOpnameModal() {
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
            document.getElementById('deficitsWarn').innerHTML = window.currentDeficits.map(d => `
                <div class="bg-red-50 px-3 py-2 rounded border border-red-100 text-sm">
                    <span class="font-bold text-gray-900">${d.nama_bibit}</span>
                    <span class="text-xs text-red-700"> — butuh ${d.total_butuh} ${unitOf(d)}, stok ${d.stok_sistem} ${unitOf(d)}</span>
                </div>`).join('');
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

        function adjustAndContinueTester() {
            fetch('{{ route("produksi.tester.adjust_stock") }}', {
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
                    document.getElementById('testerForm').submit();
                }
            })
            .catch(err => {
                console.error(err);
                alert("Gagal mengupdate stok riil.");
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const murniInput = document.getElementById('ml_murni');
            const denatInput = document.getElementById('ml_denat');
            const totalDisplay = document.getElementById('total_hasil');

            function updateTotal() {
                const murni = parseFloat(murniInput.value) || 0;
                const denat = parseFloat(denatInput.value) || 0;
                totalDisplay.textContent = murni + denat;
            }

            murniInput.addEventListener('input', updateTotal);
            denatInput.addEventListener('input', updateTotal);
        });
    </script>
</div>
</body>
</html>
