<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Produk Jadi - Hagos ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">

        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Stok Produk Jadi (T11)</h1>
                <p class="text-gray-600 mt-1">Botol jadi yang siap dijual (dari batal, retur, salah racik, produksi).</p>
            </div>
            <div class="space-x-2">
                <a href="{{ route('penjualan.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300">&larr; Kelola Pesanan</a>
                <button type="button" onclick="document.getElementById('tambahModal').classList.remove('hidden')" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">+ Tambah Stok</button>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
        @endif

        <div class="mb-4 bg-white shadow-sm border border-gray-200 rounded-lg px-5 py-4">
            <span class="text-sm text-gray-500">Total Nilai Inventory Produk Jadi</span>
            <div class="text-2xl font-bold text-gray-900">Rp {{ number_format($totalNilai, 0, ',', '.') }}</div>
        </div>

        {{-- Daftar produk yang punya stok jadi --}}
        <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-8">
            <div class="px-4 py-3 border-b border-gray-200"><h3 class="font-semibold text-gray-800">Produk dengan Stok Jadi</h3></div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produk</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Stok (pcs)</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">HPP / pcs (botol telanjang)</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Nilai</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($produks->where('stok_t11', '>', 0) as $p)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $p->nama_produk }}</div>
                                <div class="text-xs text-gray-500">{{ $p->sku_id }} ({{ $p->ukuran_ml }}ml)</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-semibold text-indigo-700">{{ $p->stok_t11 }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-700">Rp {{ number_format($p->hpp_t11, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900">Rp {{ number_format($p->stok_t11 * $p->hpp_t11, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <button type="button" onclick="openOpname('{{ $p->sku_id }}', '{{ addslashes($p->nama_produk) }} {{ $p->ukuran_ml }}ml', {{ (int) $p->stok_t11 }})" class="text-xs bg-amber-600 text-white px-3 py-1 rounded hover:bg-amber-700">Opname</button>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-6 py-8 text-center text-sm text-gray-400 italic">Belum ada stok produk jadi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Buku besar pergerakan --}}
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-3 border-b border-gray-200"><h3 class="font-semibold text-gray-800">Riwayat Pergerakan (50 terakhir)</h3></div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produk</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Tipe</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Qty</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sumber</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Catatan / Oleh</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($logs as $log)
                        <tr>
                            <td class="px-4 py-2 whitespace-nowrap text-gray-500">{{ \Illuminate\Support\Carbon::parse($log->tanggal)->format('d/m/Y') }}</td>
                            <td class="px-4 py-2 text-gray-800">{{ $log->sku_id }}</td>
                            <td class="px-4 py-2 text-center">
                                @php
                                    $tipeStyle = ['masuk' => ['bg-green-100 text-green-800', '+ masuk'], 'keluar' => ['bg-blue-100 text-blue-800', '− keluar'], 'rusak' => ['bg-red-100 text-red-700', '✕ rusak (rugi)']];
                                    [$cls, $lbl] = $tipeStyle[$log->tipe] ?? ['bg-gray-100 text-gray-700', $log->tipe];
                                @endphp
                                <span class="px-2 inline-flex text-xs font-semibold rounded-full {{ $cls }}">{{ $lbl }}</span>
                            </td>
                            <td class="px-4 py-2 text-center text-gray-800">{{ $log->qty }}</td>
                            <td class="px-4 py-2 text-gray-600">{{ ucfirst(str_replace('_',' ',$log->sumber)) }}</td>
                            <td class="px-4 py-2 text-gray-500">{{ $log->catatan }}{{ $log->dicatat_oleh ? ' · '.$log->dicatat_oleh : '' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400 italic">Belum ada pergerakan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Stok -->
<div id="tambahModal" class="fixed z-50 inset-0 overflow-y-auto hidden" role="dialog" aria-modal="true">
  <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
    <div class="fixed inset-0 bg-gray-500 bg-opacity-40" aria-hidden="true" onclick="document.getElementById('tambahModal').classList.add('hidden')"></div>
    <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
    <div class="relative z-10 inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
      <form method="POST" action="{{ route('stok_jadi.store') }}">
        @csrf
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Tambah Stok Produk Jadi</h3>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Produk</label>
              <select id="skuSelect" name="sku_id" required class="mt-1 block w-full border-gray-300 rounded-md border px-3 py-2 text-sm">
                <option value="">-- Pilih Produk --</option>
                @foreach($produks as $p)
                    <option value="{{ $p->sku_id }}">{{ $p->nama_produk }} - {{ $p->ukuran_ml }}ml</option>
                @endforeach
              </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Jumlah (pcs)</label>
                <input type="number" name="qty" min="1" value="1" required class="mt-1 block w-full border-gray-300 rounded-md border px-3 py-2 text-sm">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Diinput oleh</label>
                <select name="dicatat_oleh" class="mt-1 block w-full border-gray-300 rounded-md border px-3 py-2 text-sm bg-white">
                  <option value="">-- Admin --</option>
                  @foreach($admins as $a)<option value="{{ $a }}">{{ $a }}</option>@endforeach
                </select>
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Alasan</label>
              <select name="alasan" required class="mt-1 block w-full border-gray-300 rounded-md border px-3 py-2 text-sm bg-white">
                <option value="">-- Pilih Alasan --</option>
                @foreach($alasanList as $al)<option value="{{ $al }}">{{ $al }}</option>@endforeach
              </select>
              <p class="mt-1 text-xs text-gray-500">"Salah racik" & "Produksi batch" akan <b>memotong bibit + absolute + botol + sticker</b>. "Opname/Lainnya" hanya menambah jumlah.</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Catatan (opsional)</label>
              <input type="text" name="catatan" class="mt-1 block w-full border-gray-300 rounded-md border px-3 py-2 text-sm" placeholder="Cth: salah racik aroma X jadi Y">
            </div>
          </div>
        </div>
        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
          <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">Simpan</button>
          <button type="button" onclick="document.getElementById('tambahModal').classList.add('hidden')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Opname Stok Jadi -->
<div id="opnameModal" class="fixed z-50 inset-0 overflow-y-auto hidden" role="dialog" aria-modal="true">
  <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
    <div class="fixed inset-0 bg-gray-500 bg-opacity-40" onclick="document.getElementById('opnameModal').classList.add('hidden')"></div>
    <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
    <div class="relative z-10 inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
      <form method="POST" action="{{ route('stok_jadi.opname') }}">
        @csrf
        <input type="hidden" name="sku_id" id="opSku">
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-1">Opname Stok Produk Jadi</h3>
          <p class="text-sm text-gray-500 mb-3"><span id="opNama" class="font-semibold text-gray-800"></span></p>
          <div class="space-y-3">
            <div>
              <label class="block text-sm font-medium text-gray-700">Stok Sistem</label>
              <p id="opSistem" class="text-lg font-bold text-gray-700"></p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Stok Fisik Riil (pcs)</label>
              <input type="number" name="stok_fisik" id="opFisik" min="0" required oninput="opHitung()" class="mt-1 block w-full border-gray-300 rounded-md border px-3 py-2 text-sm">
              <p id="opSelisih" class="text-xs mt-1"></p>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-sm font-medium text-gray-700">Oleh</label>
                <select name="dicatat_oleh" class="mt-1 block w-full border-gray-300 rounded-md border px-3 py-2 text-sm bg-white">
                  <option value="">-- Admin --</option>
                  @foreach($admins as $a)<option value="{{ $a }}">{{ $a }}</option>@endforeach
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Catatan</label>
                <input type="text" name="catatan" class="mt-1 block w-full border-gray-300 rounded-md border px-3 py-2 text-sm" placeholder="cth: pecah/hilang">
              </div>
            </div>
          </div>
        </div>
        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
          <button type="submit" class="w-full inline-flex justify-center rounded-md px-4 py-2 bg-amber-600 text-white text-sm font-medium hover:bg-amber-700 sm:ml-3 sm:w-auto">Setel Stok</button>
          <button type="button" onclick="document.getElementById('opnameModal').classList.add('hidden')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 px-4 py-2 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        new TomSelect('#skuSelect', { create: false, sortField: { field: 'text', direction: 'asc' } });
    });
    let opSistemVal = 0;
    function openOpname(sku, nama, stok) {
        document.getElementById('opSku').value = sku;
        document.getElementById('opNama').textContent = nama + ' (' + sku + ')';
        document.getElementById('opSistem').textContent = stok + ' pcs';
        document.getElementById('opFisik').value = stok;
        opSistemVal = stok;
        opHitung();
        document.getElementById('opnameModal').classList.remove('hidden');
    }
    function opHitung() {
        const fisik = parseInt(document.getElementById('opFisik').value) || 0;
        const s = fisik - opSistemVal;
        const el = document.getElementById('opSelisih');
        el.textContent = 'Selisih: ' + (s >= 0 ? '+' : '') + s + ' pcs' + (s < 0 ? ' (berkurang/hilang)' : (s > 0 ? ' (bertambah)' : ' (pas)'));
        el.className = 'text-xs mt-1 font-semibold ' + (s > 0 ? 'text-emerald-600' : (s < 0 ? 'text-red-600' : 'text-gray-400'));
    }
</script>
</div>
</body>
</html>
