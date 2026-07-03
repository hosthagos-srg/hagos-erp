<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hagos ERP - Detail {{ $aroma }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">

<div class="min-h-screen p-6 max-w-5xl mx-auto">
    <header class="mb-6">
        <a href="{{ route('master_produk.index') }}" class="text-sm text-gray-500 hover:underline">← Kembali ke Master Produk</a>
        <div class="flex justify-between items-end mt-2">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $bibit->nama_bibit ?? $aroma }}</h1>
                <p class="text-gray-500">Aroma <strong>{{ $aroma }}</strong> · {{ $produks->count() }} varian</p>
            </div>
            <a href="{{ route('master_produk.create') }}" class="text-sm bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">+ Tambah Varian/Produk</a>
        </div>
    </header>

    @if(session('success'))<div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="mb-4 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded"><ul class="list-disc list-inside text-sm">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    {{-- ── INFO BIBIT / AROMA ── --}}
    @if($bibit)
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <h2 class="font-bold text-gray-800 mb-4">🌿 Data Aroma / Bibit</h2>
        <form method="POST" action="{{ route('master_produk.update_bibit', $aroma) }}">
            @csrf
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">SKU Aroma</label>
                    <input type="text" value="{{ $bibit->sku_aroma }}" disabled class="w-full bg-gray-100 border border-gray-200 rounded-md px-3 py-2 text-sm text-gray-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Nama Bibit (versi kita) *</label>
                    <input type="text" name="nama_bibit" value="{{ $bibit->nama_bibit }}" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Harga per ml (Rp) *</label>
                    <input type="number" name="harga_per_ml" value="{{ $bibit->harga_per_ml }}" step="0.01" min="0" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Merek Bibit</label>
                    <input type="text" name="merek_bibit" value="{{ $bibit->merek_bibit }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Nama Asli (di merek)</label>
                    <input type="text" name="nama_asli" value="{{ $bibit->nama_asli }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Threshold (ml)</label>
                    <input type="number" name="threshold_ml" value="{{ $bibit->threshold_ml }}" step="0.01" min="0" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                </div>
            </div>
            <div class="flex justify-between items-center mt-4">
                <p class="text-xs text-gray-400">Stok bibit ({{ rtrim(rtrim(number_format((float)$bibit->stok_ml,2,',','.'),'0'),',') }} ml) dikelola di menu <a href="{{ route('stok.index') }}" class="text-indigo-600 underline">Stok Bahan</a>.</p>
                <button type="submit" class="bg-emerald-600 text-white px-4 py-2 rounded-md text-sm font-semibold hover:bg-emerald-700">Simpan Aroma</button>
            </div>
        </form>
    </div>
    @endif

    {{-- ── VARIAN ── --}}
    @forelse($produks as $p)
        @php $resep = $reseps[$p->sku_id] ?? null; $harga = $hargaRows[$p->sku_id] ?? collect(); $hppRef = $hppPerSku[$p->sku_id] ?? 0; @endphp
        <div class="bg-white rounded-xl shadow-sm mb-6 overflow-hidden">
            <div class="px-6 py-3 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-bold text-gray-800">{{ $p->sku_id }} <span class="text-gray-400 font-normal">· {{ $p->ukuran_ml }}ml</span></h3>
                <span class="text-xs text-gray-500">HPP ref (Reguler): <strong class="text-orange-600">Rp {{ number_format($hppRef,0,',','.') }}</strong></span>
            </div>

            <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Detail + Resep --}}
                <form method="POST" action="{{ route('master_produk.update_produk', $p->sku_id) }}">
                    @csrf
                    <p class="text-xs font-semibold text-gray-500 uppercase mb-3">Detail & Resep</p>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Nama Produk *</label>
                            <input type="text" name="nama_produk" value="{{ $p->nama_produk }}" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Bentuk</label>
                                <input type="text" name="bentuk" value="{{ $p->bentuk }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Kategori</label>
                                <input type="text" name="kategori" value="{{ $p->kategori }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                                <input type="text" name="status" value="{{ $p->status }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                            </div>
                        </div>
                        <div class="grid grid-cols-4 gap-2">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Konsentrasi</label>
                                <input type="text" name="konsentrasi" value="{{ $resep->konsentrasi ?? '' }}" class="w-full border border-gray-300 rounded-md px-2 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">ml Bibit *</label>
                                <input type="number" name="ml_bibit_utama" value="{{ $resep->ml_bibit_utama ?? '' }}" step="0.01" min="0" required class="w-full border border-gray-300 rounded-md px-2 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">ml Absolute *</label>
                                <input type="number" name="ml_absolute" value="{{ $resep->ml_absolute ?? '' }}" step="0.01" min="0" required class="w-full border border-gray-300 rounded-md px-2 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Jml Tester *</label>
                                <input type="number" name="jml_tester" value="{{ $resep->jml_tester ?? '0' }}" step="0.01" min="0" required class="w-full border border-gray-300 rounded-md px-2 py-2 text-sm">
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="mt-4 bg-emerald-600 text-white px-4 py-2 rounded-md text-sm font-semibold hover:bg-emerald-700">Simpan Detail & Resep</button>
                </form>

                {{-- Harga --}}
                <form method="POST" action="{{ route('master_produk.update_harga', $p->sku_id) }}">
                    @csrf
                    <p class="text-xs font-semibold text-gray-500 uppercase mb-3">Harga Jual per Channel</p>
                    <div class="space-y-1.5">
                        @foreach($channels as $ch)
                            @php $row = $harga[$ch] ?? null; $hj = $row ? (float)$row->harga_jual : 0; $margin = $hj > 0 ? $hj - $hppRef : null; @endphp
                            <div class="flex items-center gap-2">
                                <span class="w-36 text-sm text-gray-700">{{ $ch }}</span>
                                <span class="text-xs text-gray-400">Rp</span>
                                <input type="number" name="harga[{{ $ch }}]" value="{{ $hj > 0 ? (int)$hj : '' }}" min="0" step="1" placeholder="kosong = tidak dijual"
                                    class="w-32 border border-gray-300 rounded-md px-2 py-1 text-sm">
                                @if($margin !== null)
                                    <span class="text-xs font-semibold {{ $margin >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $margin >= 0 ? '+' : '' }}Rp {{ number_format($margin,0,',','.') }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <p class="text-xs text-gray-400 mt-2">Kosongkan untuk hapus harga channel tsb. Margin vs HPP Reguler.</p>
                    <button type="submit" class="mt-3 bg-emerald-600 text-white px-4 py-2 rounded-md text-sm font-semibold hover:bg-emerald-700">Simpan Harga</button>
                </form>
            </div>
        </div>
    @empty
        <div class="bg-white rounded-xl shadow-sm p-6 text-center text-gray-400">Belum ada varian produk untuk aroma ini.</div>
    @endforelse
</div>
</div>
</body>
</html>
