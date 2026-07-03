<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Belanja Bibit & Komponen - Hagos ERP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Belanja Bibit & Komponen</h1>
                <p class="text-gray-600 mt-1">Pembelian bibit/komponen. Stok & harga rata-rata naik saat status "Diterima".</p>
            </div>
            <div class="space-x-2">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300">&larr; Dashboard</a>
                <a href="{{ route('belanja.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">+ Belanja Baru</a>
            </div>
        </div>

        @if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>@endif

        {{-- Filter Jenis --}}
        <div class="mb-3 flex flex-wrap gap-2 text-sm items-center">
            <span class="text-xs font-semibold text-gray-400 uppercase mr-1">Jenis:</span>
            <a href="{{ route('belanja.index', ['status'=>request('status')]) }}" class="px-3 py-1 rounded-md {{ !request('jenis') ? 'bg-indigo-600 text-white' : 'bg-white border' }}">Semua</a>
            <a href="{{ route('belanja.index', ['jenis'=>'bibit','status'=>request('status')]) }}" class="px-3 py-1 rounded-md {{ request('jenis')==='bibit' ? 'bg-indigo-600 text-white' : 'bg-white border' }}">Bibit</a>
            <a href="{{ route('belanja.index', ['jenis'=>'komponen','status'=>request('status')]) }}" class="px-3 py-1 rounded-md {{ request('jenis')==='komponen' ? 'bg-indigo-600 text-white' : 'bg-white border' }}">Komponen</a>
        </div>

        {{-- Filter Status --}}
        @php
            $statusFilters = [
                ''           => ['Semua', 'bg-gray-700'],
                'Dipesan'    => ['🟡 Dipesan', 'bg-yellow-500'],
                'Dikirim'    => ['🚚 Di Jalan', 'bg-blue-600'],
                'Diterima'   => ['✓ Diterima', 'bg-green-600'],
                'Dibatalkan' => ['✕ Dibatalkan', 'bg-red-500'],
            ];
        @endphp
        <div class="mb-4 flex flex-wrap gap-2 text-sm items-center">
            <span class="text-xs font-semibold text-gray-400 uppercase mr-1">Status:</span>
            @foreach($statusFilters as $val => $meta)
                @php $aktif = request('status', '') === $val; $jml = $val === '' ? $statusCounts->sum() : ($statusCounts[$val] ?? 0); @endphp
                <a href="{{ route('belanja.index', ['jenis'=>request('jenis'), 'status'=>$val]) }}"
                    class="px-3 py-1 rounded-md flex items-center gap-1.5 {{ $aktif ? $meta[1].' text-white' : 'bg-white border text-gray-600' }}">
                    {{ $meta[0] }}
                    <span class="text-xs {{ $aktif ? 'bg-white/25' : 'bg-gray-100' }} px-1.5 rounded-full">{{ $jml }}</span>
                </a>
            @endforeach
        </div>

        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID / Tanggal</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Supplier</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Jenis</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Item</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Bayar</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($belanjas as $b)
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <button type="button" onclick="toggleDetail('{{ $b->belanja_id }}')" class="flex items-center gap-1.5 text-left group">
                                    <span id="chev-{{ $b->belanja_id }}" class="text-gray-400 group-hover:text-indigo-600 transition-transform">▶</span>
                                    <span>
                                        <span class="font-medium text-gray-900 group-hover:text-indigo-600">{{ $b->belanja_id }}</span>
                                        <span class="block text-xs text-gray-500">{{ \Illuminate\Support\Carbon::parse($b->tgl_belanja)->format('d/m/Y') }}</span>
                                    </span>
                                </button>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $b->supplier_toko ?? '-' }}<div class="text-xs text-gray-400">{{ $b->platform_beli }}</div>
                                @if($b->no_resi)<div class="text-xs text-blue-600 mt-0.5">📦 {{ $b->no_resi }}{{ $b->kurir ? ' · '.$b->kurir : '' }}</div>@endif
                            </td>
                            <td class="px-4 py-3 text-center"><span class="px-2 py-0.5 text-xs rounded-full {{ $b->jenis==='bibit' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">{{ ucfirst($b->jenis) }}</span></td>
                            <td class="px-4 py-3 text-center text-gray-600">{{ $b->details_count }}</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900">Rp {{ number_format($b->total_bayar, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-center">
                                @php $sc = ['Dipesan'=>'bg-yellow-100 text-yellow-800','Dikirim'=>'bg-blue-100 text-blue-800','Diterima'=>'bg-green-100 text-green-800','Dibatalkan'=>'bg-red-100 text-red-700'][$b->status_belanja] ?? 'bg-gray-100'; @endphp
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $sc }}">{{ $b->status_belanja }}</span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap space-x-2">
                                @if($b->stok_diterapkan)
                                    <span class="text-xs text-gray-400">stok masuk</span>
                                @elseif($b->status_belanja === 'Dibatalkan')
                                    <span class="text-xs text-gray-400">—</span>
                                @else
                                    <button type="button" onclick="openKirimModal('{{ $b->belanja_id }}', @js($b->no_resi), @js($b->kurir))" class="text-blue-600 hover:text-blue-900 font-medium">🚚 Kirim</button>
                                    <form method="POST" action="{{ route('belanja.update_status', $b->belanja_id) }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="status_belanja" value="Diterima">
                                        <button type="submit" class="text-green-600 hover:text-green-900 font-medium" onclick="return confirm('Tandai barang DITERIMA? Stok & harga rata-rata akan diperbarui.')">✓ Terima</button>
                                    </form>
                                    <form method="POST" action="{{ route('belanja.update_status', $b->belanja_id) }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="status_belanja" value="Dibatalkan">
                                        <button type="submit" class="text-red-500 hover:text-red-700" onclick="return confirm('Batalkan belanja ini?')">✕ Batal</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                        {{-- Baris detail item (expandable) --}}
                        <tr id="detail-{{ $b->belanja_id }}" class="hidden bg-gray-50">
                            <td colspan="7" class="px-6 py-3">
                                <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Item Belanja</p>
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="text-xs text-gray-400">
                                            <th class="text-left py-1 font-medium">Item</th>
                                            <th class="text-right py-1 font-medium">Qty</th>
                                            <th class="text-right py-1 font-medium">Harga Total</th>
                                            <th class="text-right py-1 font-medium">Harga Net/Unit</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($b->details as $d)
                                            @php $nama = $b->jenis === 'bibit' ? ($namaBibit[$d->item_id] ?? null) : ($namaKomponen[$d->item_id] ?? null); @endphp
                                            <tr class="border-t border-gray-200">
                                                <td class="py-1.5 text-gray-800">
                                                    {{ $d->item_id }}
                                                    @if($nama)<span class="text-gray-500">· {{ $nama }}</span>@endif
                                                    <span class="block text-xs text-gray-400">batch: {{ $d->batch_id }}</span>
                                                </td>
                                                <td class="py-1.5 text-right text-gray-700">{{ rtrim(rtrim(number_format($d->qty, 2, ',', '.'), '0'), ',') }} {{ $b->jenis === 'bibit' ? 'ml' : 'pcs' }}</td>
                                                <td class="py-1.5 text-right text-gray-700">Rp {{ number_format($d->harga_total_item, 0, ',', '.') }}</td>
                                                <td class="py-1.5 text-right text-gray-500">Rp {{ number_format($d->harga_net_per_unit, 2, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400 italic">Belum ada data belanja.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-200">{{ $belanjas->links() }}</div>
        </div>
    </div>
</div>

<!-- Modal Kirim (input no resi) -->
<div id="kirimModal" class="fixed z-50 inset-0 overflow-y-auto hidden" role="dialog" aria-modal="true">
  <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
    <div class="fixed inset-0 bg-gray-500 bg-opacity-40" onclick="document.getElementById('kirimModal').classList.add('hidden')"></div>
    <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
    <div class="relative z-10 inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
      <form id="kirimForm" method="POST" action="">
        @csrf
        <input type="hidden" name="status_belanja" value="Dikirim">
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-1">Tandai Dikirim</h3>
          <p class="text-sm text-gray-500 mb-4">Isi no resi supaya Jhodi/Adzim bisa lacak pengiriman dari supplier.</p>
          <div class="space-y-3">
            <div>
              <label class="block text-sm font-medium text-gray-700">No. Resi</label>
              <input type="text" name="no_resi" id="kirimResi" required class="mt-1 block w-full border-gray-300 rounded-md border px-3 py-2 text-sm" placeholder="Cth: SPXID0123456789">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Kurir <span class="text-gray-400">(opsional)</span></label>
              <input type="text" name="kurir" id="kirimKurir" class="mt-1 block w-full border-gray-300 rounded-md border px-3 py-2 text-sm" placeholder="Cth: J&T / SPX">
            </div>
          </div>
        </div>
        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
          <button type="submit" class="w-full inline-flex justify-center rounded-md px-4 py-2 bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 sm:ml-3 sm:w-auto">Simpan</button>
          <button type="button" onclick="document.getElementById('kirimModal').classList.add('hidden')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 px-4 py-2 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
    function openKirimModal(belanjaId, resi, kurir) {
        document.getElementById('kirimForm').action = '/belanja/' + belanjaId + '/status';
        document.getElementById('kirimResi').value = resi || '';
        document.getElementById('kirimKurir').value = kurir || '';
        document.getElementById('kirimModal').classList.remove('hidden');
    }
    function toggleDetail(belanjaId) {
        const row = document.getElementById('detail-' + belanjaId);
        const chev = document.getElementById('chev-' + belanjaId);
        const show = row.classList.contains('hidden');
        row.classList.toggle('hidden');
        chev.textContent = show ? '▼' : '▶';
        chev.classList.toggle('text-indigo-600', show);
    }
</script>
</div>
</body>
</html>
