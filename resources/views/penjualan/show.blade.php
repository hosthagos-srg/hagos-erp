<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan - Hagos ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>.ts-control { border-radius: 0.375rem; border-color: #d1d5db; min-height: 34px; }</style>
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">
<div class="max-w-4xl mx-auto py-8 sm:px-6 lg:px-8">
    
    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('penjualan.index') }}" class="text-indigo-600 hover:text-indigo-900 flex items-center">
            <svg class="h-5 w-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali ke Kelola Pesanan
        </a>
    </div>

    @if(session('success'))<div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ $errors->first() }}</div>@endif

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Informasi Pesanan
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">
                Detail lengkap pesanan #{{ $header->internal_id }}
            </p>
        </div>
        
        <div class="px-4 py-5 sm:p-0">
            <dl class="sm:divide-y sm:divide-gray-200">
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Order ID Marketplace</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $header->external_order_id ?? '-' }}</dd>
                </div>
                @if($header->no_resi)
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">No. Resi Ekspedisi</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 font-medium">📦 {{ $header->no_resi }}</dd>
                </div>
                @endif
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Channel</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $header->channel }}</dd>
                </div>
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Metode Pengiriman</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        {{ $header->metode_pengiriman ?? ($cls['is_shipped'] ? 'Dikirim' : 'Ambil Langsung') }}
                        <span class="text-xs text-gray-400">{{ $cls['is_shipped'] ? '(termasuk Lapis 2 fulfillment)' : '(tanpa Lapis 2)' }}</span>
                    </dd>
                </div>
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Nama Pembeli</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $header->nama_pembeli ?? '-' }}</dd>
                </div>
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Status Pesanan</dt>
                    <dd class="mt-1 text-sm sm:mt-0 sm:col-span-2">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            {{ $header->status_pesanan === 'Batal' || $header->status_pesanan === 'Retur' ? 'bg-red-100 text-red-800' : '' }}
                            {{ $header->status_pesanan === 'Menunggu' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $header->status_pesanan === 'Selesai Racik' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $header->status_pesanan === 'Dikirim' ? 'bg-purple-100 text-purple-800' : '' }}
                            {{ $header->status_pesanan === 'Selesai' ? 'bg-green-100 text-green-800' : '' }}">
                            {{ $header->status_pesanan }}
                        </span>
                        @if($header->alasan_batal)
                            <span class="ml-2 text-sm text-red-600 font-medium">({{ $header->alasan_batal }})</span>
                        @endif
                    </dd>
                </div>
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Status Pencairan</dt>
                    <dd class="mt-1 text-sm sm:mt-0 sm:col-span-2">
                        @php
                            if(in_array($header->status_pembayaran, ['Cair','Sudah Cair','Lunas'])) $payColor='bg-green-100 text-green-800';
                            elseif(in_array($header->status_pembayaran, ['Belum Dibayar','Piutang'])) $payColor='bg-red-100 text-red-800';
                            else $payColor='bg-yellow-100 text-yellow-800';
                        @endphp
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $payColor }}">
                            {{ $header->status_pembayaran }}
                        </span>
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Items Section -->
    <div class="mt-8 bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Produk yang Dipesan
            </h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU / Produk</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Harga Jual</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">HPP (Modal)</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Laba Kotor</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($details as $d)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $d->nama_produk }}</div>
                            <div class="text-sm text-gray-500">{{ $d->sku_id }} ({{ $d->ukuran_ml }}ml)</div>
                            @if($d->sku_id_asli)
                                <div class="mt-1 inline-flex items-center text-xs text-amber-700 bg-amber-50 rounded px-1.5 py-0.5" title="Aroma diganti saat racik">
                                    ↔ Tukar aroma dari: {{ $asliMap[$d->sku_id_asli] ?? $d->sku_id_asli }}
                                </div>
                            @endif
                            @if($d->resep_blend)
                                <div class="mt-1 inline-flex items-center text-xs text-purple-700 bg-purple-50 rounded px-1.5 py-0.5" title="Komposisi mix custom">
                                    🧪 Mix custom ({{ count(json_decode($d->resep_blend, true) ?: []) }} bibit)
                                </div>
                            @endif
                            @if($d->dari_bundle)
                                <div class="mt-1 inline-flex items-center text-xs text-amber-700 bg-amber-50 rounded px-1.5 py-0.5" title="Baris hasil pecah bundling">
                                    📦 dari bundling
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                            {{ $d->qty }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500">
                            Rp {{ number_format($d->harga_satuan, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm {{ is_null($d->hpp_satuan) ? 'text-gray-400 italic' : 'text-red-600' }}">
                            {{ is_null($d->hpp_satuan) ? 'Belum diracik' : 'Rp ' . number_format($d->hpp_satuan, 0, ',', '.') }}
                        </td>
                        @php $lk = $labaKotor[$d->detail_id] ?? null; @endphp
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium {{ is_null($lk) ? 'text-gray-400 italic' : ($lk >= 0 ? 'text-green-600' : 'text-red-600') }}">
                            {{ is_null($lk) ? '-' : 'Rp ' . number_format($lk, 0, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if($header->status_pesanan === 'Menunggu')
    <!-- Aksi Pra-Racik: Pecah Bundle & Mix Custom -->
    <div class="mt-6 bg-white shadow-sm border border-gray-200 rounded-lg p-4">
        <h3 class="text-sm font-semibold text-gray-700 mb-1">🛠️ Aksi Pra-Racik (Bundle & Mix Custom)</h3>
        <p class="text-xs text-gray-400 mb-3">Pesanan marketplace: <b>Pecah Bundle</b> (1 baris → beberapa aroma, harga dibagi otomatis) atau <b>Mix Custom</b> (racikan &gt;1 bibit pilihan pelanggan). Setelah diatur, racik seperti biasa di Gudang Racik.</p>
        <div class="space-y-2">
            @foreach($details as $d)
                @if(is_null($d->hpp_satuan))
                <div class="flex flex-wrap items-center justify-between gap-2 border border-gray-100 rounded-md px-3 py-2">
                    <div class="text-sm text-gray-700">{{ $d->nama_produk }}
                        <span class="text-gray-400">{{ $d->sku_id }} · {{ $d->qty }}pcs · Rp {{ number_format($d->harga_satuan, 0, ',', '.') }}/pcs = Rp {{ number_format($d->subtotal, 0, ',', '.') }}</span>
                        @if($d->resep_blend)<span class="ml-1 text-xs text-purple-700">· 🧪 mix custom aktif</span>@endif
                    </div>
                    <div class="flex gap-2">
                        <button type="button" onclick="openAroma('{{ route('pesanan.change_aroma', $d->detail_id) }}', '{{ $d->sku_id }}')" class="text-xs px-2.5 py-1 bg-sky-50 text-sky-700 rounded hover:bg-sky-100">Ubah Aroma</button>
                        <button type="button" onclick="openBundle('{{ route('pesanan.split_bundle', $d->detail_id) }}')" class="text-xs px-2.5 py-1 bg-amber-50 text-amber-700 rounded hover:bg-amber-100">Pecah Bundle</button>
                        <button type="button" onclick="openMix('{{ route('pesanan.set_mix', $d->detail_id) }}')" class="text-xs px-2.5 py-1 bg-purple-50 text-purple-700 rounded hover:bg-purple-100">Mix Custom</button>
                        @if($d->resep_blend)
                        <form method="POST" action="{{ route('pesanan.clear_mix', $d->detail_id) }}" onsubmit="return confirm('Hapus komposisi mix custom dari baris ini?')">@csrf @method('DELETE')<button type="submit" class="text-xs px-2 py-1 text-red-500 hover:text-red-700">✕ mix</button></form>
                        @endif
                    </div>
                </div>
                @endif
            @endforeach
        </div>
    </div>
    @endif

    <!-- Rincian HPP per Produk -->
    <div class="mt-6 bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden">
        <button type="button" onclick="toggleSection('hppBody', this)" class="w-full px-4 py-2.5 flex items-center justify-between text-left hover:bg-gray-50">
            <span class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                <svg data-caret class="h-3.5 w-3.5 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path></svg>
                Rincian Modal (HPP) per Produk
            </span>
            <span class="px-2 py-0.5 text-[11px] font-medium rounded-full bg-indigo-50 text-indigo-700">
                {{ $cls['tipe'] }} · {{ $cls['is_shipped'] ? 'Dikirim (Lapis 1+2)' : 'Ambil langsung (Lapis 1)' }}
            </span>
        </button>
        <div id="hppBody" class="px-4 pb-4 pt-1 space-y-4 border-t border-gray-100">
            @foreach($details as $d)
                @php $bd = $breakdowns[$d->detail_id] ?? null; @endphp
                @if($bd)
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="font-medium text-gray-900 mb-3">{{ $d->nama_produk }} <span class="text-gray-500 text-sm">({{ $d->ukuran_ml }}ml × {{ $d->qty }} pcs)</span></div>

                    <div class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-1">Lapis 1 — Modal Produk (per pcs)</div>
                    <table class="w-full text-sm mb-3">
                        @foreach($bd['lapis1'] as $row)
                        <tr class="border-b border-gray-50">
                            <td class="py-1 text-gray-600">
                                {{ $row['label'] }}
                                @isset($row['ml'])<span class="text-gray-400 text-xs">({{ rtrim(rtrim(number_format($row['ml'],2,',','.'),'0'),',') }}ml × Rp {{ number_format($row['harga_ml'],0,',','.') }}/ml)</span>@endisset
                            </td>
                            <td class="py-1 text-right text-gray-800">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                        <tr class="font-semibold">
                            <td class="py-1 text-gray-700">Subtotal Lapis 1 / pcs</td>
                            <td class="py-1 text-right">Rp {{ number_format($bd['lapis1_per_unit'], 0, ',', '.') }}</td>
                        </tr>
                    </table>

                    <div class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-1">Tester (per pesanan)</div>
                    <table class="w-full text-sm mb-3">
                        <tr>
                            <td class="py-1 text-gray-600">
                                @if($bd['tester']['jml'] > 0)
                                    {{ rtrim(rtrim(number_format($bd['tester']['jml'],2,',','.'),'0'),',') }} botol × Rp {{ number_format($bd['tester']['harga'],0,',','.') }}
                                @else
                                    <span class="text-gray-400 italic">Tanpa tester untuk channel ini</span>
                                @endif
                            </td>
                            <td class="py-1 text-right text-gray-800">Rp {{ number_format($bd['tester']['total'], 0, ',', '.') }}</td>
                        </tr>
                    </table>

                    <div class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-1">Lapis 2 — Fulfillment (pesanan dikirim)</div>
                    <table class="w-full text-sm mb-3">
                        @if($bd['is_shipped'])
                            <tr class="border-b border-gray-50"><td class="py-1 text-gray-600">Gaji packing ({{ $d->qty }} pcs × Rp {{ number_format($bd['lapis2']['gaji'],0,',','.') }})</td><td class="py-1 text-right text-gray-800">Rp {{ number_format($bd['lapis2']['gaji']*$d->qty, 0, ',', '.') }}</td></tr>
                            <tr class="border-b border-gray-50"><td class="py-1 text-gray-600">Shrink ({{ $d->qty }} pcs × Rp {{ number_format($bd['lapis2']['shrink'],0,',','.') }})</td><td class="py-1 text-right text-gray-800">Rp {{ number_format($bd['lapis2']['shrink']*$d->qty, 0, ',', '.') }}</td></tr>
                            <tr><td class="py-1 text-gray-600">Bahan packing (per pesanan)</td><td class="py-1 text-right text-gray-800">Rp {{ number_format($bd['lapis2']['bahan_packing'], 0, ',', '.') }}</td></tr>
                        @else
                            <tr><td class="py-1 text-gray-400 italic">Tidak ada — pesanan diambil langsung (offline/reseller/refill)</td><td></td></tr>
                        @endif
                    </table>

                    <div class="flex justify-between items-center bg-red-50 rounded px-3 py-2 mt-2">
                        <span class="font-bold text-gray-900">Total HPP ({{ $d->qty }} pcs)</span>
                        <span class="font-bold text-red-700">Rp {{ number_format($bd['hpp_total'], 0, ',', '.') }} <span class="text-xs font-normal text-gray-500">(Rp {{ number_format($bd['hpp_per_unit'],0,',','.') }}/pcs)</span></span>
                    </div>
                </div>
                @endif
            @endforeach
        </div>
    </div>

    <!-- Keuangan Section -->
    @php
        $totalHpp = 0;
        foreach($details as $d) { $totalHpp += ($d->hpp_satuan * $d->qty); }

        $butuhSettlement = $cls['butuh_settlement']; // hanya marketplace
        $sudahCair = $header->status_pembayaran === 'Cair';

        if ($butuhSettlement) {
            $net = (float) $header->net_settlement;
            $bisaHitungLaba = $sudahCair;
            // Omset marketplace = subtotal settlement (basis riil penjual); fallback ke GMV impor
            $omset = $header->gross_settlement ? (float) $header->gross_settlement : (float) $header->gmv_kotor;
        } else {
            // Channel langsung (offline/WA/reseller/refill): uang diterima saat itu juga
            $net = ((float) $header->net_settlement) > 0
                ? (float) $header->net_settlement
                : ((float) $header->gmv_kotor - (float) ($header->diskon_manual ?? 0));
            $bisaHitungLaba = true;
            $omset = (float) $header->gmv_kotor;
        }
        $potongan = $omset - $net;
        $labaBersih = $net - $totalHpp;
    @endphp

    <div class="mt-8 bg-white shadow overflow-hidden sm:rounded-lg mb-8">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Rincian Keuangan</h3>
        </div>
        <div class="px-4 py-5 sm:p-0">
            <dl class="sm:divide-y sm:divide-gray-200">
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">{{ ($butuhSettlement && $header->gross_settlement) ? 'Omset (Subtotal Settlement)' : 'Omset (GMV Kotor)' }}</dt>
                    <dd class="mt-1 text-sm font-medium text-gray-900 sm:mt-0 sm:col-span-2">Rp {{ number_format($omset, 0, ',', '.') }}</dd>
                </div>

                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 bg-gray-50">
                    <dt class="text-sm font-medium text-gray-500">
                        {{ $butuhSettlement ? 'Uang Cair ke Saldo (Net Settlement)' : 'Uang Diterima' }}
                    </dt>
                    <dd class="mt-1 text-sm font-medium sm:mt-0 sm:col-span-2">
                        @if($bisaHitungLaba)
                            <span class="text-green-600">Rp {{ number_format($net, 0, ',', '.') }}</span>
                            @unless($butuhSettlement)<span class="ml-2 text-xs text-gray-500">(tunai/transfer langsung)</span>@endunless
                        @else
                            <span class="text-yellow-600 italic">Belum Cair / Menunggu Settlement</span>
                        @endif
                    </dd>
                </div>

                @if($bisaHitungLaba && abs($potongan) > 0)
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">
                        {{ $butuhSettlement ? 'Biaya Admin / Potongan Platform' : 'Diskon Manual' }}
                        @if($butuhSettlement && !empty($header->potongan_detail))
                            <button type="button" onclick="toggleSection('potonganDetail', this)" class="ml-1 text-xs text-indigo-600 hover:underline">(lihat rincian)</button>
                        @endif
                    </dt>
                    <dd class="mt-1 text-sm font-medium sm:mt-0 sm:col-span-2">
                        <span class="text-red-600">- Rp {{ number_format($potongan, 0, ',', '.') }}</span>
                        @if($butuhSettlement && !empty($header->potongan_detail))
                            <div id="potonganDetail" class="hidden mt-2 bg-gray-50 rounded-md p-3 text-sm">
                                <div class="text-xs uppercase tracking-wide text-gray-400 font-semibold mb-1">Rincian biaya dari settlement</div>
                                <table class="w-full">
                                    @foreach($header->potongan_detail as $nama => $jml)
                                    <tr class="border-b border-gray-100">
                                        <td class="py-1 text-gray-600">{{ $nama }}</td>
                                        <td class="py-1 text-right {{ $jml < 0 ? 'text-red-600' : 'text-green-600' }}">
                                            {{ $jml < 0 ? '-' : '+' }} Rp {{ number_format(abs($jml), 0, ',', '.') }}
                                        </td>
                                    </tr>
                                    @endforeach
                                    <tr class="font-semibold">
                                        <td class="py-1 text-gray-700">Total Potongan</td>
                                        <td class="py-1 text-right text-red-700">- Rp {{ number_format($potongan, 0, ',', '.') }}</td>
                                    </tr>
                                </table>
                            </div>
                        @endif
                    </dd>
                </div>
                @endif

                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Total HPP (Modal Racikan)</dt>
                    <dd class="mt-1 text-sm font-medium text-gray-900 sm:mt-0 sm:col-span-2">Rp {{ number_format($totalHpp, 0, ',', '.') }}</dd>
                </div>

                @if($bisaHitungLaba)
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 bg-green-50 border-t-2 border-green-200">
                    <dt class="text-base font-bold text-gray-900">Laba Bersih (Profit)</dt>
                    <dd class="mt-1 text-base font-bold {{ $labaBersih >= 0 ? 'text-green-700' : 'text-red-600' }} sm:mt-0 sm:col-span-2">
                        Rp {{ number_format($labaBersih, 0, ',', '.') }}
                    </dd>
                </div>
                @else
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 bg-gray-50">
                    <dt class="text-sm font-medium text-gray-900">Laba Bersih (Profit)</dt>
                    <dd class="mt-1 text-sm text-gray-500 italic sm:mt-0 sm:col-span-2">Belum bisa dihitung (Uang dari Marketplace belum cair)</dd>
                </div>
                @endif

                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Tanggal Cair</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $header->tgl_cair_saldo ?? '-' }}</dd>
                </div>
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Masuk ke Akun</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $header->akun_masuk ?? '-' }}</dd>
                </div>
            </dl>
        </div>
    </div>
</div>
<script>
    function toggleSection(id, btn) {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.toggle('hidden');
        const caret = btn ? btn.querySelector('[data-caret]') : null;
        if (caret) caret.classList.toggle('-rotate-90');
    }
</script>

@if($header->status_pesanan === 'Menunggu')
{{-- ===== Modal Pecah Bundle ===== --}}
<div id="bundleModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-gray-500/75" onclick="closeModals()"></div>
    <div class="relative z-10 max-w-lg mx-auto mt-20 bg-white rounded-lg shadow-xl">
        <form id="bundleForm" method="POST">
            @csrf
            <div class="p-5">
                <h3 class="text-lg font-medium text-gray-900">Pecah Bundle jadi Aroma</h3>
                <p class="text-xs text-gray-500 mt-1">Pilih aroma tiap botol + jumlahnya. Harga bundle dibagi proporsional ke tiap baris (total omset tetap).</p>
                <table class="min-w-full text-sm mt-4"><tbody id="bundleRows"></tbody></table>
                <button type="button" onclick="addBundleRow()" class="mt-2 text-xs px-3 py-1 bg-indigo-50 text-indigo-700 rounded hover:bg-indigo-100">+ Tambah aroma</button>
            </div>
            <div class="bg-gray-50 px-5 py-3 flex flex-row-reverse gap-2 rounded-b-lg">
                <button type="submit" class="px-4 py-2 bg-amber-600 text-white text-sm rounded-md hover:bg-amber-700">Pecah Bundle</button>
                <button type="button" onclick="closeModals()" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm rounded-md">Batal</button>
            </div>
        </form>
    </div>
</div>

{{-- ===== Modal Mix Custom ===== --}}
<div id="mixModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-gray-500/75" onclick="closeModals()"></div>
    <div class="relative z-10 max-w-lg mx-auto mt-20 bg-white rounded-lg shadow-xl">
        <form id="mixForm" method="POST">
            @csrf
            <div class="p-5">
                <h3 class="text-lg font-medium text-gray-900">Komposisi Mix Custom</h3>
                <p class="text-xs text-gray-500 mt-1">Racikan &gt;1 bibit dalam 1 botol (min 2). ml total ideal ≈ ml bibit resep normal ukuran ini. Absolute &amp; tester ikut resep SKU.</p>
                <table class="min-w-full text-sm mt-4"><tbody id="mixRows"></tbody></table>
                <button type="button" onclick="addMixRow()" class="mt-2 text-xs px-3 py-1 bg-indigo-50 text-indigo-700 rounded hover:bg-indigo-100">+ Tambah bibit</button>
            </div>
            <div class="bg-gray-50 px-5 py-3 flex flex-row-reverse gap-2 rounded-b-lg">
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white text-sm rounded-md hover:bg-purple-700">Simpan Mix</button>
                <button type="button" onclick="closeModals()" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm rounded-md">Batal</button>
            </div>
        </form>
    </div>
</div>

{{-- ===== Modal Ubah Aroma (perbaiki salah pilih aroma) ===== --}}
<div id="aromaModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-gray-500/75" onclick="closeModals()"></div>
    <div class="relative z-10 max-w-lg mx-auto mt-24 bg-white rounded-lg shadow-xl">
        <form id="aromaForm" method="POST">
            @csrf
            <div class="p-5">
                <h3 class="text-lg font-medium text-gray-900">Ubah Aroma Baris</h3>
                <p class="text-xs text-gray-500 mt-1">Ganti aroma/SKU baris ini (mis. salah pilih saat pecah bundle). <b>Harga baris tidak berubah.</b></p>
                <select name="sku_id" id="aromaSelect" required class="mt-4 block w-full border-gray-300 rounded-md border px-2 py-2 text-sm bg-white">
                    <option value="">-- Pilih Aroma --</option>
                    @foreach($allProduks as $p)<option value="{{ $p->sku_id }}">{{ $p->sku_id }} — {{ $p->nama_produk }} ({{ $p->ukuran_ml }}ml)</option>@endforeach
                </select>
            </div>
            <div class="bg-gray-50 px-5 py-3 flex flex-row-reverse gap-2 rounded-b-lg">
                <button type="submit" class="px-4 py-2 bg-sky-600 text-white text-sm rounded-md hover:bg-sky-700">Simpan Aroma</button>
                <button type="button" onclick="closeModals()" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm rounded-md">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
    const PRODUKS = @json($allProduks->map(fn($p) => ['id' => $p->sku_id, 'nama' => $p->sku_id . ' — ' . $p->nama_produk . ' (' . $p->ukuran_ml . 'ml)']));
    const BIBITS  = @json($allBibits->map(fn($b) => ['id' => $b->bibit_id, 'nama' => $b->nama_bibit]));
    let bIdx = 0, mIdx = 0, aromaTom = null;

    function opts(list) { let h = '<option value="">-- Pilih --</option>'; list.forEach(x => h += `<option value="${x.id}">${x.nama}</option>`); return h; }

    function addBundleRow() {
        const i = bIdx++;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="py-1 pr-2"><select name="lines[${i}][sku_id]" required class="block w-full border-gray-300 rounded-md border px-2 py-1.5 text-sm bg-white js-sku">${opts(PRODUKS)}</select></td>
            <td class="py-1 pr-2 w-20"><input type="number" name="lines[${i}][qty]" min="1" value="1" required class="block w-full border-gray-300 rounded-md border px-2 py-1.5 text-sm text-right"></td>
            <td class="py-1 text-center w-8"><button type="button" onclick="this.closest('tr').remove()" class="text-red-500 hover:text-red-700">✕</button></td>`;
        document.getElementById('bundleRows').appendChild(tr);
        new TomSelect(tr.querySelector('.js-sku'), { create: false, sortField: { field: 'text', direction: 'asc' } });
    }

    function addMixRow() {
        const i = mIdx++;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="py-1 pr-2"><select name="bibit_id[${i}]" required class="block w-full border-gray-300 rounded-md border px-2 py-1.5 text-sm bg-white js-bibit">${opts(BIBITS)}</select></td>
            <td class="py-1 pr-2 w-24"><input type="number" name="ml[${i}]" step="any" min="0.0001" placeholder="ml" required class="block w-full border-gray-300 rounded-md border px-2 py-1.5 text-sm text-right"></td>
            <td class="py-1 text-center w-8"><button type="button" onclick="this.closest('tr').remove()" class="text-red-500 hover:text-red-700">✕</button></td>`;
        document.getElementById('mixRows').appendChild(tr);
        new TomSelect(tr.querySelector('.js-bibit'), { create: false, sortField: { field: 'text', direction: 'asc' } });
    }

    function openBundle(url) { document.getElementById('bundleForm').action = url; document.getElementById('bundleRows').innerHTML = ''; bIdx = 0; addBundleRow(); addBundleRow(); document.getElementById('bundleModal').classList.remove('hidden'); }
    function openMix(url) { document.getElementById('mixForm').action = url; document.getElementById('mixRows').innerHTML = ''; mIdx = 0; addMixRow(); addMixRow(); document.getElementById('mixModal').classList.remove('hidden'); }
    function openAroma(url, currentSku) { document.getElementById('aromaForm').action = url; if (aromaTom) aromaTom.setValue(currentSku || ''); document.getElementById('aromaModal').classList.remove('hidden'); }
    function closeModals() { document.getElementById('bundleModal').classList.add('hidden'); document.getElementById('mixModal').classList.add('hidden'); document.getElementById('aromaModal').classList.add('hidden'); }

    document.addEventListener('DOMContentLoaded', () => {
        aromaTom = new TomSelect('#aromaSelect', { create: false, sortField: { field: 'text', direction: 'asc' } });
    });
</script>
@endif

</div>
</body>
</html>
