<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pesanan - Hagos ERP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        .animate-shake {
            animation: shake 0.3s ease-in-out;
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Kelola Semua Pesanan</h1>
            <div class="space-x-2">
                <a href="{{ route('upload.index') }}" class="inline-flex items-center px-4 py-2 bg-sky-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-sky-700 active:bg-sky-900 focus:outline-none focus:border-sky-900 focus:ring ring-sky-300 disabled:opacity-25 transition ease-in-out duration-150">
                    ⬆ Upload Pesanan Marketplace
                </a>
                <a href="{{ route('racik.index') }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">
                    📦 Ke Gudang Racik
                </a>
                <a href="{{ route('stok_jadi.index') }}" class="inline-flex items-center px-4 py-2 bg-teal-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-teal-700 active:bg-teal-900 focus:outline-none focus:border-teal-900 focus:ring ring-teal-300 disabled:opacity-25 transition ease-in-out duration-150">
                    🏷️ Stok Produk Jadi
                </a>
                <a href="{{ route('penjualan.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                    + Input Manual Baru
                </a>
            </div>
        </div>

        @if($perluCekCount > 0)
        <div class="bg-amber-50 border-l-4 border-amber-500 p-4 mb-6 shadow-sm rounded-r-md">
            <div class="flex items-start">
                <svg class="h-5 w-5 text-amber-500 flex-shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                <div class="ml-3 w-full">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-sm font-bold text-amber-800">{{ $perluCekCount }} pesanan belum cair — perlu dicek di Seller Center</h3>
                        <a href="{{ route('penjualan.perlu_cek') }}" class="flex-shrink-0 text-xs font-semibold text-amber-800 bg-amber-100 hover:bg-amber-200 px-3 py-1 rounded-md">Buka Monitoring →</a>
                    </div>
                    <p class="text-xs text-amber-700 mb-2">Cek status (terkirim / nyangkut / hilang / settlement telat), lalu klik "Sudah Dicek". Menampilkan {{ min(5, $perluCekCount) }} teratas (tertua).</p>
                    <div class="bg-white rounded-md border border-amber-200 divide-y divide-amber-100">
                        @foreach($perluCek as $c)
                        @php $umur = \Illuminate\Support\Carbon::parse($c->tgl_pesanan)->diffInDays(now()); @endphp
                        <div class="flex items-center justify-between px-3 py-2 text-sm">
                            <div>
                                <a href="{{ route('penjualan.show', $c->internal_id) }}" class="font-medium text-indigo-600 hover:underline">{{ $c->external_order_id ?? $c->internal_id }}</a>
                                <span class="text-gray-500">· {{ $c->channel }} · {{ \Illuminate\Support\Carbon::parse($c->tgl_pesanan)->format('d/m/Y') }} ({{ $umur }} hari)</span>
                                @if($c->jumlah_dicek > 0)
                                    <span class="ml-1 text-xs text-amber-700">· sudah dicek {{ $c->jumlah_dicek }}× (terakhir {{ \Illuminate\Support\Carbon::parse($c->tgl_dicek)->format('d/m/Y') }})</span>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('penjualan.update_status', $c->internal_id) }}" class="flex-shrink-0">
                                @csrf
                                <input type="hidden" name="action" value="cek_pesanan">
                                <button type="submit" class="px-3 py-1 text-xs font-semibold rounded-md bg-amber-600 text-white hover:bg-amber-700">✓ Sudah Dicek</button>
                            </form>
                        </div>
                        @endforeach
                    </div>
                    @if($perluCekCount > 5)
                    <div class="mt-2 text-center">
                        <a href="{{ route('penjualan.perlu_cek') }}" class="inline-block text-xs font-semibold text-amber-800 hover:underline">+ {{ $perluCekCount - 5 }} pesanan lainnya perlu dicek → buka halaman Monitoring</a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        @if($barangBalikCount > 0)
        <div class="bg-blue-50 border-l-4 border-blue-500 p-3 mb-6 shadow-sm rounded-r-md flex items-center justify-between gap-2">
            <p class="text-sm text-blue-800">↩️ <b>{{ $barangBalikCount }}</b> pesanan batal menunggu barang fisik kembali — konfirmasi "Barang Diterima" saat barang sampai.</p>
            <a href="{{ route('penjualan.perlu_barang_balik') }}" class="flex-shrink-0 text-xs font-semibold text-blue-800 bg-blue-100 hover:bg-blue-200 px-3 py-1 rounded-md">Buka Monitoring →</a>
        </div>
        @endif

        <!-- Filter & Search Section -->
        <div class="bg-white shadow sm:rounded-lg p-3 mb-6">
            <form action="{{ route('penjualan.index') }}" method="GET" class="flex flex-wrap items-center gap-3">
                
                <div class="w-full md:w-72">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="🔍 Cari Order ID, No. Resi, atau Nama Pembeli..." class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm py-1.5">
                </div>

                <div class="w-full sm:w-auto flex items-center space-x-2">
                    <span class="text-xs text-gray-500 font-medium">Tgl:</span>
                    <input type="date" name="start_date" value="{{ request('start_date') }}" class="border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm py-1.5" title="Mulai Tanggal">
                    <span class="text-gray-400">-</span>
                    <input type="date" name="end_date" value="{{ request('end_date') }}" class="border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm py-1.5" title="Sampai Tanggal">
                </div>

                <div class="w-full sm:w-auto">
                    <select name="channel" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm py-1.5">
                        <option value="">-- Semua Channel --</option>
                        @foreach($channels as $c)
                            <option value="{{ $c }}" {{ request('channel') == $c ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="w-full sm:w-auto flex space-x-2">
                    <button type="submit" class="flex-1 sm:flex-none justify-center py-1.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none">
                        Filter
                    </button>
                    @if(request()->anyFilled(['search', 'start_date', 'end_date', 'channel']))
                    <a href="{{ route('penjualan.index') }}" class="flex-1 sm:flex-none justify-center py-1.5 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 focus:outline-none text-center">
                        Reset
                    </a>
                    @endif
                </div>
                
            </form>
        </div>

        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tgl Pesanan</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-64">Produk Dipesan</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Channel</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total/GMV</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status Racik</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status Dana</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Net Settlement</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($pesanans as $p)
                        @php
                            $isWarning = ($p->status_pembayaran == 'Belum Cair' && $p->created_at < now()->subDays(14) && !in_array($p->status_pesanan, ['Batal', 'Retur']));
                        @endphp
                        <tr class="{{ $isWarning ? 'bg-red-50' : '' }}">
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">{{ $p->tgl_pesanan }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-indigo-600">
                                <a href="{{ route('penjualan.show', $p->internal_id) }}" class="hover:underline">
                                    {{ $p->external_order_id ?? ('INV-' . strtoupper(substr($p->internal_id, 0, 8))) }}
                                </a>
                                @if($p->no_resi)
                                    <div class="mt-0.5 text-xs font-normal text-gray-500" title="Nomor resi ekspedisi">📦 {{ $p->no_resi }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-900 max-w-[220px]">
                                <ul class="list-disc list-inside">
                                    @foreach($p->details as $d)
                                        <li>{{ $d->qty }}x {{ $d->produk->nama_produk ?? 'Unknown' }} - {{ $d->produk->ukuran_ml ?? '?' }}ml</li>
                                    @endforeach
                                </ul>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    @if($p->details->contains('dari_bundle', true))
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-amber-100 text-amber-800">
                                            📦 Bundling
                                        </span>
                                    @endif
                                    @if($p->details->contains(fn($d) => !empty($d->resep_blend)))
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-sky-100 text-sky-800">
                                            🧬 Mix Custom
                                        </span>
                                    @endif
                                    @if($p->ekstra_tester > 0)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-purple-100 text-purple-800">
                                            🎁 +{{ $p->ekstra_tester }} Tester
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ $p->channel }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">Rp {{ number_format($p->gmv_kotor, 0, ',', '.') }}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $p->status_pesanan == 'Selesai Racik' ? 'bg-green-100 text-green-800' : 
                                      ($p->status_pesanan == 'Batal' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                    {{ $p->status_pesanan }}
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                @php
                                    // Hijau = uang sudah diterima/cair/lunas; kuning = masih menunggu; merah = belum bayar/piutang
                                    if(in_array($p->status_pembayaran, ['Cair', 'Sudah Cair', 'Lunas'])) $paymentColor = 'bg-green-100 text-green-800';
                                    elseif(in_array($p->status_pembayaran, ['Belum Dibayar', 'Piutang'])) $paymentColor = 'bg-red-100 text-red-800';
                                    else $paymentColor = 'bg-yellow-100 text-yellow-800'; // Belum Cair / Menunggu
                                @endphp
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $paymentColor }}">
                                    {{ $p->status_pembayaran ?: 'Belum Cair' }}
                                </span>
                                @if(!in_array($p->status_pembayaran, ['Cair', 'Sudah Cair', 'Lunas']) && $p->status_pesanan !== 'Batal' && $p->jumlah_dicek > 0)
                                    <div class="mt-1">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-100 text-amber-700" title="Terakhir dicek {{ $p->tgl_dicek ? \Illuminate\Support\Carbon::parse($p->tgl_dicek)->format('d/m/Y') : '-' }}">
                                            ✓ dicek {{ $p->jumlah_dicek }}×
                                        </span>
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                {{ $p->net_settlement > 0 ? 'Rp ' . number_format($p->net_settlement, 0, ',', '.') : '—' }}
                            </td>
                            <td class="px-4 py-4 text-sm font-medium">
                                <div class="flex flex-col items-end gap-1">
                                    @if($p->status_pesanan !== 'Batal')
                                        <button type="button" class="text-red-600 hover:text-red-900 whitespace-nowrap" onclick="openBatalModal('{{ $p->internal_id }}', '{{ $p->status_pesanan }}')">Batal</button>
                                    @endif
                                    @if($p->status_pembayaran === 'Piutang' && $p->status_pesanan !== 'Batal')
                                        <button type="button" class="text-green-700 hover:text-green-900 whitespace-nowrap" onclick="openBayarModal('{{ $p->internal_id }}')">💵 Terima Bayar</button>
                                    @endif
                                    @if($p->status_pesanan === 'Batal' && $p->perlu_barang_balik)
                                        @if(is_null($p->tgl_retur_diterima))
                                            <button type="button" class="text-green-600 hover:text-green-900 whitespace-nowrap" onclick="openTerimaBarangModal('{{ $p->internal_id }}')">✓ Barang Diterima</button>
                                        @else
                                            <span class="text-green-600 text-xs whitespace-nowrap" title="Barang sudah masuk T11">✓ Diterima {{ \Illuminate\Support\Carbon::parse($p->tgl_retur_diterima)->format('d/m/Y') }}</span>
                                        @endif
                                    @endif
                                    @if($p->status_pesanan === 'Menunggu')
                                        @php $fd = $p->details->first(); @endphp
                                        @if($fd)
                                            <button type="button" class="text-amber-600 hover:text-amber-900 whitespace-nowrap" onclick="openTukarAromaModal('{{ $p->internal_id }}', '{{ $fd->detail_id }}', '{{ ($fd->produk->nama_produk ?? $fd->sku_id) }} {{ optional($fd->produk)->ukuran_ml }}ml')">↔ Tukar Aroma</button>
                                        @endif
                                        <button type="button" class="text-purple-600 hover:text-purple-900 whitespace-nowrap" onclick="openTesterModal('{{ $p->internal_id }}', '{{ $p->ekstra_tester ?? 0 }}')">🎁 Tester</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $pesanans->links() }}
            </div>
        </div>

    </div>

    <!-- Tukar Aroma Modal (hanya untuk pesanan Menunggu / bibit kosong) -->
    <div id="tukarAromaModal" class="fixed z-50 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-transparent transition-opacity" aria-hidden="true" onclick="shakeModal('tukarAromaModalBox')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div id="tukarAromaModalBox" class="relative z-10 inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
          <form id="tukarAromaForm" method="POST" action="">
            @csrf
            <input type="hidden" name="action" value="tukar_aroma">
            <input type="hidden" name="detail_id" id="tukarDetailId" value="">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
              <div class="sm:flex sm:items-start">
                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-amber-100 sm:mx-0 sm:h-10 sm:w-10">
                  <svg class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m4 6H4m0 0l4 4m-4-4l4-4" /></svg>
                </div>
                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                  <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Tukar Aroma (Bibit Kosong)</h3>
                  <div class="mt-2">
                    <p class="text-sm text-gray-500 mb-3">
                      Untuk pesanan yang bibitnya kosong & pembeli setuju ganti aroma. Harga tetap (tidak berubah); HPP nanti ikut aroma baru saat diracik.
                    </p>
                    <p class="text-sm mb-3">Aroma sekarang: <b id="tukarAromaSekarang" class="text-gray-800"></b></p>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ganti ke aroma:</label>
                    <select name="sku_id_baru" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm rounded-md" required>
                        <option value="">-- Pilih Aroma Pengganti --</option>
                        @foreach($produks as $prod)
                            <option value="{{ $prod->sku_id }}">{{ $prod->nama_produk }} - {{ $prod->ukuran_ml }}ml</option>
                        @endforeach
                    </select>
                  </div>
                </div>
              </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
              <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-amber-600 text-base font-medium text-white hover:bg-amber-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">Konfirmasi Tukar</button>
              <button type="button" onclick="closeTukarAromaModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Kembali</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Batal Modal -->
    <div id="batalModal" class="fixed z-50 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-transparent transition-opacity" aria-hidden="true" onclick="shakeModal('batalModalBox')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div id="batalModalBox" class="relative z-10 inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
          <form id="batalForm" method="POST" action="">
            @csrf
            <input type="hidden" name="action" value="batal">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
              <div class="sm:flex sm:items-start">
                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                  <svg class="h-6 w-6 text-red-600" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                  </svg>
                </div>
                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                  <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Batalkan Pesanan</h3>
                  <div class="mt-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Alasan Pembatalan:</label>
                    <select name="alasan_batal" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-red-500 focus:border-red-500 sm:text-sm rounded-md" required>
                        <option value="">-- Pilih Alasan --</option>
                        @foreach($alasanBatal as $alasan)
                            <option value="{{ $alasan }}">{{ $alasan }}</option>
                        @endforeach
                    </select>

                    {{-- Keadaan barang: opsi ditampilkan sesuai status pesanan (lihat JS) --}}
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Keadaan barang:</label>
                        <div class="space-y-2">
                            <label id="kondisiBelumRacik" class="flex items-start gap-2 text-sm hidden">
                                <input type="radio" name="kondisi_barang" value="belum_racik" class="mt-0.5">
                                <span><b>Produk belum diracik</b> — stok tidak terpengaruh (mis. salah input / pembeli tolak tukar aroma).</span>
                            </label>
                            <label id="kondisiGudang" class="flex items-start gap-2 text-sm hidden">
                                <input type="radio" name="kondisi_barang" value="gudang" class="mt-0.5">
                                <span><b>Sudah diracik, ada di gudang</b> — botol langsung masuk Stok Jadi (T11).</span>
                            </label>
                            <label id="kondisiMenunggu" class="flex items-start gap-2 text-sm hidden">
                                <input type="radio" name="kondisi_barang" value="menunggu" class="mt-0.5">
                                <span><b>Sudah dikirim, menunggu barang balik</b> (COD ditolak / alamat tak ketemu / dll) — belum masuk stok; nanti klik "Barang Diterima" saat barang sampai.</span>
                            </label>
                        </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
              <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">Konfirmasi Batal</button>
              <button type="button" onclick="closeBatalModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Kembali</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <!-- Modal Ekstra Tester -->
    <div id="testerModal" class="fixed z-50 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-transparent transition-opacity" aria-hidden="true" onclick="shakeModal('testerModalBox')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div id="testerModalBox" class="relative z-10 inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
          <form id="testerForm" method="POST" action="">
            @csrf
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
              <div class="sm:flex sm:items-start">
                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-purple-100 sm:mx-0 sm:h-10 sm:w-10">
                  <span class="text-2xl">🎁</span>
                </div>
                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                  <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Ubah Bonus Tester</h3>
                  <div class="mt-2">
                    <p class="text-sm text-gray-500 mb-4">
                      Tentukan berapa jumlah <b>Extra Bonus Tester</b> untuk pesanan ini. Stok botol tester dan bibit akan otomatis dipotong saat pesanan diracik.
                    </p>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah Extra Tester (Pcs):</label>
                    <input type="number" name="ekstra_tester" id="testerInput" min="0" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm rounded-md" required>
                  </div>
                </div>
              </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
              <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-purple-600 text-base font-medium text-white hover:bg-purple-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">Simpan</button>
              <button type="button" onclick="closeTesterModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Batal</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal Konfirmasi Barang Diterima (cek kondisi: layak jual / rusak) -->
    <div id="terimaBarangModal" class="fixed z-50 inset-0 overflow-y-auto hidden" role="dialog" aria-modal="true">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-transparent transition-opacity" aria-hidden="true" onclick="shakeModal('terimaBarangModalBox')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div id="terimaBarangModalBox" class="relative z-10 inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
          <form id="terimaBarangForm" method="POST" action="">
            @csrf
            <input type="hidden" name="action" value="terima_barang">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
              <div class="sm:flex sm:items-start">
                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                  <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                </div>
                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                  <h3 class="text-lg leading-6 font-medium text-gray-900">Konfirmasi Barang Diterima</h3>
                  <div class="mt-2">
                    <p class="text-sm text-gray-500 mb-3">Cek kondisi barang yang kembali, lalu pilih:</p>
                    <div class="space-y-2">
                        <label class="flex items-start gap-2 text-sm">
                            <input type="radio" name="kondisi_terima" value="layak" class="mt-0.5" checked>
                            <span><b>Layak jual</b> — botol masuk Stok Produk Jadi (T11), bisa dijual lagi.</span>
                        </label>
                        <label class="flex items-start gap-2 text-sm">
                            <input type="radio" name="kondisi_terima" value="rusak" class="mt-0.5">
                            <span><b>Rusak / tidak layak jual</b> — TIDAK masuk stok; dicatat sebagai kerugian (modal hangus).</span>
                        </label>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
              <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 sm:ml-3 sm:w-auto sm:text-sm">Konfirmasi</button>
              <button type="button" onclick="document.getElementById('terimaBarangModal').classList.add('hidden')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Batal</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal Terima Pembayaran (Piutang -> Cair) -->
    <div id="bayarModal" class="fixed z-50 inset-0 overflow-y-auto hidden" role="dialog" aria-modal="true">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-transparent" onclick="shakeModal('bayarModalBox')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div id="bayarModalBox" class="relative z-10 inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
          <form id="bayarForm" method="POST" action="">
            @csrf
            <input type="hidden" name="action" value="terima_pembayaran">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
              <h3 class="text-lg font-medium text-gray-900 mb-1">Terima Pembayaran Piutang</h3>
              <p class="text-sm text-gray-500 mb-4">Pesanan jadi Cair & uang masuk ke akun yang dipilih.</p>
              <label class="block text-sm font-medium text-gray-700">Uang masuk ke akun</label>
              <select name="akun" required class="mt-1 block w-full border-gray-300 rounded-md border px-3 py-2 text-sm bg-white">
                <option value="">-- Pilih Akun --</option>
                @foreach($akuns as $a)<option value="{{ $a }}">{{ $a }}</option>@endforeach
              </select>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
              <button type="submit" class="w-full inline-flex justify-center rounded-md px-4 py-2 bg-green-600 text-white text-sm font-medium hover:bg-green-700 sm:ml-3 sm:w-auto">Terima</button>
              <button type="button" onclick="document.getElementById('bayarModal').classList.add('hidden')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 px-4 py-2 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto">Batal</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <script>
        function openBayarModal(internalId) {
            document.getElementById('bayarForm').action = '/penjualan/' + internalId + '/status';
            document.getElementById('bayarModal').classList.remove('hidden');
        }

        function shakeModal(boxId) {
            const box = document.getElementById(boxId);
            box.classList.remove('animate-shake');
            // Trigger reflow
            void box.offsetWidth;
            box.classList.add('animate-shake');
        }

        function openTukarAromaModal(internalId, detailId, aromaSekarang) {
            document.getElementById('tukarAromaForm').action = '/penjualan/' + internalId + '/status';
            document.getElementById('tukarDetailId').value = detailId;
            document.getElementById('tukarAromaSekarang').textContent = aromaSekarang;
            document.getElementById('tukarAromaModal').classList.remove('hidden');
        }

        function closeTukarAromaModal() {
            document.getElementById('tukarAromaModal').classList.add('hidden');
        }

        function openBatalModal(internalId, status) {
            document.getElementById('batalForm').action = '/penjualan/' + internalId + '/status';
            const sudahDiracik = ['Selesai Racik', 'Dikirim', 'Selesai'].includes(status);

            // Tampilkan opsi keadaan barang sesuai status pesanan
            document.getElementById('kondisiBelumRacik').classList.toggle('hidden', sudahDiracik);
            document.getElementById('kondisiGudang').classList.toggle('hidden', !sudahDiracik);
            document.getElementById('kondisiMenunggu').classList.toggle('hidden', !sudahDiracik);

            // Default sesuai keadaan
            const val = sudahDiracik ? 'gudang' : 'belum_racik';
            const r = document.querySelector('#batalForm input[name="kondisi_barang"][value="' + val + '"]');
            if (r) r.checked = true;

            document.getElementById('batalModal').classList.remove('hidden');
        }

        function closeBatalModal() {
            document.getElementById('batalModal').classList.add('hidden');
        }

        function openTerimaBarangModal(internalId) {
            const f = document.getElementById('terimaBarangForm');
            f.action = '/penjualan/' + internalId + '/status';
            const layak = f.querySelector('input[name="kondisi_terima"][value="layak"]');
            if (layak) layak.checked = true;
            document.getElementById('terimaBarangModal').classList.remove('hidden');
        }

        function openTesterModal(internalId, currentExtra) {
            try {
                document.getElementById('testerForm').action = '/penjualan/' + internalId + '/extra-tester';
                document.getElementById('testerInput').value = currentExtra || 0;
                const modal = document.getElementById('testerModal');
                modal.classList.remove('hidden');
                modal.style.display = 'block';
            } catch (error) {
                alert("Sistem mendeteksi error saat membuka pop-up: " + error.message);
                console.error(error);
            }
        }

        function closeTesterModal() {
            const modal = document.getElementById('testerModal');
            modal.classList.add('hidden');
            modal.style.display = 'none';
        }
    </script>
</div>
</body>
</html>
