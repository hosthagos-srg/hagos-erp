<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hagos ERP - Stok Bahan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">

@include('partials.notifikasi_cicilan')

<div class="min-h-screen p-6">
    <header class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Stok Bahan</h1>
        <p class="text-gray-500 mt-1">Bibit & Komponen — pantau stok dan koreksi/opname</p>
    </header>

    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded">
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    {{-- Kartu ringkasan --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-indigo-500">
            <p class="text-xs text-gray-500">Jenis Bibit</p>
            <p class="text-2xl font-bold text-indigo-600">{{ $bibits->count() }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 {{ $bibitWarning > 0 ? 'border-red-500' : 'border-emerald-500' }}">
            <p class="text-xs text-gray-500">Bibit Perlu Beli</p>
            <p class="text-2xl font-bold {{ $bibitWarning > 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ $bibitWarning }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-teal-500">
            <p class="text-xs text-gray-500">Stok Tester Jadi</p>
            <p class="text-2xl font-bold text-teal-600">{{ rtrim(rtrim(number_format($stokTesterJadi, 2, ',', '.'), '0'), ',') }} <span class="text-sm font-normal text-gray-400">pcs</span></p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-amber-500">
            <p class="text-xs text-gray-500">Nilai Stok Bibit</p>
            <p class="text-lg font-bold text-amber-600">Rp {{ number_format($nilaiBibit, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-purple-500">
            <p class="text-xs text-gray-500">Nilai Stok Komponen</p>
            <p class="text-lg font-bold text-purple-600">Rp {{ number_format($nilaiKomponen, 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Tab --}}
    <div class="flex gap-1 mb-4 border-b border-gray-200">
        <button onclick="switchTab('bibit')" id="tab-btn-bibit"
            class="tab-btn px-5 py-2.5 text-sm font-semibold border-b-2 border-indigo-600 text-indigo-700">
            🌿 Bibit ({{ $bibits->count() }})
        </button>
        <button onclick="switchTab('komponen')" id="tab-btn-komponen"
            class="tab-btn px-5 py-2.5 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700">
            📦 Komponen ({{ $komponens->count() }})
            @if($komponenWarning > 0)<span class="ml-1 text-xs bg-red-100 text-red-700 px-1.5 rounded-full">{{ $komponenWarning }} perlu beli</span>@endif
        </button>
        <button onclick="switchTab('riwayat')" id="tab-btn-riwayat"
            class="tab-btn px-5 py-2.5 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700">
            📋 Riwayat Koreksi
        </button>
    </div>

    {{-- TAB BIBIT --}}
    <div id="tab-bibit" class="tab-content">
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th onclick="sortBibit('sku')" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer select-none hover:text-indigo-600">
                                SKU / Aroma <span id="arrow-sku" class="text-gray-300"></span>
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Bibit</th>
                            <th onclick="sortBibit('stok')" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase cursor-pointer select-none hover:text-indigo-600">
                                Stok (ml) <span id="arrow-stok" class="text-gray-300"></span>
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Threshold</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Harga/ml</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Nilai Stok</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="bibit-tbody" class="bg-white divide-y divide-gray-100">
                        @forelse($bibits as $b)
                            @php
                                $low = (float) $b->stok_ml <= (float) $b->threshold_ml;
                                $nilai = (float) $b->stok_ml * (float) $b->harga_per_ml;
                            @endphp
                            <tr class="{{ $low ? 'bg-red-50' : '' }}" data-sku="{{ strtolower($b->sku_aroma ?? $b->bibit_id) }}" data-stok="{{ (float) $b->stok_ml }}">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $b->sku_aroma ?? $b->bibit_id }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $b->nama_bibit }}</td>
                                <td class="px-4 py-3 text-sm text-right font-bold {{ $low ? 'text-red-600' : 'text-gray-900' }}">{{ rtrim(rtrim(number_format($b->stok_ml, 2, ',', '.'), '0'), ',') }}</td>
                                <td class="px-4 py-3 text-sm text-right text-gray-400">{{ rtrim(rtrim(number_format($b->threshold_ml, 2, ',', '.'), '0'), ',') }}</td>
                                <td class="px-4 py-3 text-sm text-right text-gray-500">Rp {{ number_format($b->harga_per_ml, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-sm text-right text-gray-700">Rp {{ number_format($nilai, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if($low)
                                        <span class="text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded font-semibold">PERLU BELI</span>
                                    @else
                                        <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded">Aman</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <button type="button"
                                        data-id="{{ $b->bibit_id }}"
                                        data-aroma="{{ $b->sku_aroma }}"
                                        data-nama="{{ $b->nama_bibit }}"
                                        data-merek="{{ $b->merek_bibit }}"
                                        data-asli="{{ $b->nama_asli }}"
                                        data-harga="{{ $b->harga_per_ml }}"
                                        data-threshold="{{ $b->threshold_ml }}"
                                        data-status="{{ $b->status }}"
                                        onclick="openEditBibit(this)"
                                        class="text-xs bg-indigo-600 text-white px-3 py-1 rounded hover:bg-indigo-700">Edit</button>
                                    <button onclick="openKoreksi('bibit', '{{ $b->bibit_id }}', '{{ addslashes($b->nama_bibit) }}', {{ (float) $b->stok_ml }}, 'ml')"
                                        class="text-xs bg-gray-700 text-white px-3 py-1 rounded hover:bg-gray-900">Koreksi</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-6 text-center text-gray-400 text-sm">Belum ada data bibit.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TAB KOMPONEN --}}
    <div id="tab-komponen" class="tab-content hidden">
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Komponen</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Stok</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Threshold</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Satuan</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Harga Satuan</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Nilai Stok</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($komponens as $k)
                            @php
                                $nilai = (float) $k->stok * (float) $k->harga_satuan;
                                $low = (float) $k->threshold > 0 && (float) $k->stok <= (float) $k->threshold;
                            @endphp
                            <tr class="{{ $low ? 'bg-red-50' : '' }}">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $k->komponen_id }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $k->nama_komponen }}</td>
                                <td class="px-4 py-3 text-sm text-right font-bold {{ $low ? 'text-red-600' : 'text-gray-900' }}">{{ rtrim(rtrim(number_format($k->stok, 2, ',', '.'), '0'), ',') }}</td>
                                <td class="px-4 py-3 text-sm text-right text-gray-400">{{ (float) $k->threshold > 0 ? rtrim(rtrim(number_format($k->threshold, 2, ',', '.'), '0'), ',') : '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $k->satuan }}</td>
                                <td class="px-4 py-3 text-sm text-right text-gray-500">Rp {{ number_format($k->harga_satuan, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-sm text-right text-gray-700">Rp {{ number_format($nilai, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if((float) $k->threshold <= 0)
                                        <span class="text-xs text-gray-300">—</span>
                                    @elseif($low)
                                        <span class="text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded font-semibold">PERLU BELI</span>
                                    @else
                                        <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded">Aman</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <button type="button"
                                        data-id="{{ $k->komponen_id }}" data-nama="{{ $k->nama_komponen }}"
                                        data-harga="{{ $k->harga_satuan }}" data-satuan="{{ $k->satuan }}" data-threshold="{{ $k->threshold }}"
                                        onclick="openEditKomponen(this)"
                                        class="text-xs bg-indigo-600 text-white px-3 py-1 rounded hover:bg-indigo-700">Edit</button>
                                    <button onclick="openKoreksi('komponen', '{{ $k->komponen_id }}', '{{ addslashes($k->nama_komponen) }}', {{ (float) $k->stok }}, '{{ addslashes($k->satuan) }}')"
                                        class="text-xs bg-gray-700 text-white px-3 py-1 rounded hover:bg-gray-900">Koreksi</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-4 py-6 text-center text-gray-400 text-sm">Belum ada data komponen.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TAB RIWAYAT --}}
    <div id="tab-riwayat" class="tab-content hidden">
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Jenis</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Stok Sistem</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Stok Fisik</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Selisih</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Alasan</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Oleh</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($riwayat as $r)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $r->tanggal->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $r->nama_item ?? $r->item_id }}</td>
                                <td class="px-4 py-3 text-center"><span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{{ $r->item_type }}</span></td>
                                <td class="px-4 py-3 text-sm text-right text-gray-500">{{ rtrim(rtrim(number_format($r->stok_sistem, 2, ',', '.'), '0'), ',') }}</td>
                                <td class="px-4 py-3 text-sm text-right text-gray-900">{{ rtrim(rtrim(number_format($r->stok_fisik, 2, ',', '.'), '0'), ',') }}</td>
                                <td class="px-4 py-3 text-sm text-right font-semibold {{ $r->selisih < 0 ? 'text-red-600' : ($r->selisih > 0 ? 'text-emerald-600' : 'text-gray-400') }}">
                                    {{ $r->selisih > 0 ? '+' : '' }}{{ rtrim(rtrim(number_format($r->selisih, 2, ',', '.'), '0'), ',') }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $r->alasan }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $r->dicatat_oleh ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-6 text-center text-gray-400 text-sm">Belum ada riwayat koreksi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>

{{-- Modal Koreksi --}}
<div id="modal-koreksi" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-1">Koreksi / Opname Stok</h2>
        <p class="text-sm text-gray-500 mb-4"><span id="k-nama" class="font-semibold text-gray-800"></span></p>

        <form method="POST" action="{{ route('stok.koreksi') }}">
            @csrf
            <input type="hidden" name="item_type" id="k-type">
            <input type="hidden" name="item_id" id="k-id">

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Stok Sistem Saat Ini</label>
                <p id="k-sistem" class="text-lg font-bold text-gray-700"></p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Stok Fisik Riil <span class="text-red-500">*</span></label>
                <div class="flex items-center gap-2">
                    <input type="number" step="0.01" min="0" name="stok_fisik" id="k-fisik" required
                        class="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <span id="k-satuan" class="text-sm text-gray-500"></span>
                </div>
                <p id="k-selisih" class="text-xs mt-1 text-gray-400"></p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Alasan <span class="text-red-500">*</span></label>
                <select name="alasan" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @foreach($alasanList as $a)
                        <option value="{{ $a }}">{{ $a }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Dicatat Oleh</label>
                <select name="dicatat_oleh" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">-- Pilih --</option>
                    @foreach($admins as $adm)
                        <option value="{{ $adm }}">{{ $adm }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="flex-1 bg-indigo-600 text-white py-2 rounded-md font-semibold hover:bg-indigo-700">Simpan Koreksi</button>
                <button type="button" onclick="closeKoreksi()" class="flex-1 bg-gray-200 text-gray-700 py-2 rounded-md font-semibold hover:bg-gray-300">Batal</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Edit Bibit --}}
<div id="modal-edit-bibit" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg mx-4 p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-1">Edit Detail Bibit</h2>
        <p class="text-sm text-gray-500 mb-4"><span id="eb-judul" class="font-semibold text-gray-800"></span></p>

        <form method="POST" id="form-edit-bibit" action="">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Bibit (versi kita) <span class="text-red-500">*</span></label>
                    <input type="text" name="nama_bibit" id="eb-nama" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Merek Bibit</label>
                    <input type="text" name="merek_bibit" id="eb-merek" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Asli (di merek)</label>
                    <input type="text" name="nama_asli" id="eb-asli" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Harga per ml (Rp) <span class="text-red-500">*</span></label>
                    <input type="number" name="harga_per_ml" id="eb-harga" step="0.01" min="0" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Threshold (ml)</label>
                    <input type="number" name="threshold_ml" id="eb-threshold" step="0.01" min="0" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <p class="text-xs text-gray-400 mt-1">Batas stok minimum → muncul peringatan "Perlu Beli".</p>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="eb-status" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="Aktif">Aktif</option>
                        <option value="Nonaktif">Nonaktif</option>
                    </select>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-3">Stok bibit tidak diubah di sini — pakai tombol <b>Koreksi</b> untuk opname stok.</p>
            <div class="flex gap-3 mt-4">
                <button type="submit" class="flex-1 bg-indigo-600 text-white py-2 rounded-md font-semibold hover:bg-indigo-700">Simpan Perubahan</button>
                <button type="button" onclick="document.getElementById('modal-edit-bibit').classList.add('hidden')" class="flex-1 bg-gray-200 text-gray-700 py-2 rounded-md font-semibold hover:bg-gray-300">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditBibit(btn) {
    const d = btn.dataset;
    document.getElementById('eb-judul').textContent = (d.aroma || d.id) + ' · ' + d.nama;
    document.getElementById('eb-nama').value = d.nama || '';
    document.getElementById('eb-merek').value = d.merek || '';
    document.getElementById('eb-asli').value = d.asli || '';
    document.getElementById('eb-harga').value = d.harga || '';
    document.getElementById('eb-threshold').value = d.threshold || '';
    document.getElementById('eb-status').value = (d.status === 'Nonaktif') ? 'Nonaktif' : 'Aktif';
    document.getElementById('form-edit-bibit').action = '/stok/bibit/' + d.id + '/update';
    document.getElementById('modal-edit-bibit').classList.remove('hidden');
}
</script>

{{-- Modal Edit Komponen --}}
<div id="modal-edit-komponen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg mx-4 p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-1">Edit Detail Komponen</h2>
        <p class="text-sm text-gray-500 mb-4"><span id="ek-judul" class="font-semibold text-gray-800"></span></p>
        <form method="POST" id="form-edit-komponen" action="">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Komponen <span class="text-red-500">*</span></label>
                    <input type="text" name="nama_komponen" id="ek-nama" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Harga Satuan (Rp) <span class="text-red-500">*</span></label>
                    <input type="number" name="harga_satuan" id="ek-harga" step="0.01" min="0" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Satuan</label>
                    <input type="text" name="satuan" id="ek-satuan" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Threshold (batas minimum)</label>
                    <input type="number" name="threshold" id="ek-threshold" step="0.01" min="0" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <p class="text-xs text-gray-400 mt-1">Stok ≤ threshold → status "Perlu Beli" + notifikasi. Isi 0 untuk tidak dipantau.</p>
                </div>
            </div>
            <div class="flex gap-3 mt-4">
                <button type="submit" class="flex-1 bg-indigo-600 text-white py-2 rounded-md font-semibold hover:bg-indigo-700">Simpan Perubahan</button>
                <button type="button" onclick="document.getElementById('modal-edit-komponen').classList.add('hidden')" class="flex-1 bg-gray-200 text-gray-700 py-2 rounded-md font-semibold hover:bg-gray-300">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditKomponen(btn) {
    const d = btn.dataset;
    document.getElementById('ek-judul').textContent = d.id + ' · ' + d.nama;
    document.getElementById('ek-nama').value = d.nama || '';
    document.getElementById('ek-harga').value = d.harga || '';
    document.getElementById('ek-satuan').value = d.satuan || '';
    document.getElementById('ek-threshold').value = (parseFloat(d.threshold) > 0) ? d.threshold : '';
    document.getElementById('form-edit-komponen').action = '/stok/komponen/' + d.id + '/update';
    document.getElementById('modal-edit-komponen').classList.remove('hidden');
}
</script>

<script>
function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.getElementById('tab-' + tab).classList.remove('hidden');
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('border-indigo-600', 'text-indigo-700');
        btn.classList.add('border-transparent', 'text-gray-500');
    });
    const active = document.getElementById('tab-btn-' + tab);
    active.classList.add('border-indigo-600', 'text-indigo-700');
    active.classList.remove('border-transparent', 'text-gray-500');
}

// Sortir tabel bibit (client-side)
let sortState = { col: null, dir: 'asc' };
function sortBibit(col) {
    // toggle arah jika kolom sama, kalau beda mulai asc
    if (sortState.col === col) {
        sortState.dir = sortState.dir === 'asc' ? 'desc' : 'asc';
    } else {
        sortState.col = col;
        sortState.dir = 'asc';
    }
    const tbody = document.getElementById('bibit-tbody');
    const rows = Array.from(tbody.querySelectorAll('tr')).filter(r => r.hasAttribute('data-sku'));
    rows.sort((a, b) => {
        let va, vb;
        if (col === 'stok') {
            va = parseFloat(a.dataset.stok) || 0;
            vb = parseFloat(b.dataset.stok) || 0;
        } else {
            va = a.dataset.sku;
            vb = b.dataset.sku;
        }
        let cmp = (col === 'stok') ? (va - vb) : va.localeCompare(vb);
        return sortState.dir === 'asc' ? cmp : -cmp;
    });
    rows.forEach(r => tbody.appendChild(r));
    // update indikator panah
    const arrows = { sku: document.getElementById('arrow-sku'), stok: document.getElementById('arrow-stok') };
    arrows.sku.textContent = ''; arrows.sku.className = 'text-gray-300';
    arrows.stok.textContent = ''; arrows.stok.className = 'text-gray-300';
    const active = arrows[col];
    active.textContent = sortState.dir === 'asc' ? '▲' : '▼';
    active.className = 'text-indigo-600';
}

let kSistemVal = 0;
function openKoreksi(type, id, nama, stokSistem, satuan) {
    document.getElementById('k-type').value = type;
    document.getElementById('k-id').value = id;
    document.getElementById('k-nama').textContent = nama + ' (' + id + ')';
    document.getElementById('k-sistem').textContent = stokSistem.toLocaleString('id-ID') + ' ' + satuan;
    document.getElementById('k-satuan').textContent = satuan;
    document.getElementById('k-fisik').value = stokSistem;
    document.getElementById('k-selisih').textContent = '';
    kSistemVal = stokSistem;
    document.getElementById('modal-koreksi').classList.remove('hidden');
}
function closeKoreksi() {
    document.getElementById('modal-koreksi').classList.add('hidden');
}
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('k-fisik').addEventListener('input', function () {
        const fisik = parseFloat(this.value) || 0;
        const selisih = fisik - kSistemVal;
        const el = document.getElementById('k-selisih');
        if (selisih === 0) { el.textContent = 'Tidak ada selisih'; el.className = 'text-xs mt-1 text-gray-400'; }
        else if (selisih < 0) { el.textContent = 'Selisih: ' + selisih.toLocaleString('id-ID') + ' (berkurang)'; el.className = 'text-xs mt-1 text-red-600 font-semibold'; }
        else { el.textContent = 'Selisih: +' + selisih.toLocaleString('id-ID') + ' (bertambah)'; el.className = 'text-xs mt-1 text-emerald-600 font-semibold'; }
    });
});
</script>
</body>
</html>
