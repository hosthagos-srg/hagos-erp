<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hagos ERP - Produk Gratis (Affiliate)</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">

<div class="min-h-screen p-6">
    <header class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Produk Gratis (Affiliate)</h1>
        <p class="text-gray-500 mt-1">Catat sampel/seeding ke affiliate TikTok & Shopee. Stok otomatis dipotong, HPP masuk Biaya Promo di P&L.</p>
    </header>

    @if(session('success'))<div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="mb-4 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded"><ul class="list-disc list-inside text-sm">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    {{-- Ringkasan --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-pink-500">
            <p class="text-xs text-gray-500">Total Produk Gratis</p>
            <p class="text-2xl font-bold text-pink-600">{{ $totalQty }} <span class="text-sm font-normal text-gray-400">pcs</span></p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-orange-500">
            <p class="text-xs text-gray-500">Total Biaya (HPP)</p>
            <p class="text-lg font-bold text-orange-600">Rp {{ number_format($totalHpp, 0, ',', '.') }}</p>
        </div>
        @foreach(['TikTok','Shopee'] as $pf)
            @php $d = $perPlatform[$pf] ?? ['qty'=>0,'hpp'=>0]; @endphp
            <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-gray-300">
                <p class="text-xs text-gray-500">{{ $pf }}</p>
                <p class="text-lg font-bold text-gray-700">{{ $d['qty'] }} pcs <span class="text-xs font-normal text-gray-400">· Rp {{ number_format($d['hpp'], 0, ',', '.') }}</span></p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Form --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm p-5 sticky top-6">
                <h2 class="font-bold text-gray-800 mb-3">🎁 Catat Produk Gratis</h2>
                <form method="POST" action="{{ route('sampel.store') }}" class="space-y-3">
                    @csrf
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal *</label>
                            <input type="date" name="tanggal" value="{{ date('Y-m-d') }}" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Platform *</label>
                            <select name="platform" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                @foreach($platformList as $pf)<option value="{{ $pf }}">{{ $pf }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama / Username Affiliate *</label>
                        <input type="text" name="nama_affiliate" required placeholder="cth: @beautybyrina" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Produk *</label>
                        <select name="sku_id" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option value="">-- Pilih Produk --</option>
                            @foreach($produks as $p)<option value="{{ $p->sku_id }}">{{ $p->nama_produk }} - {{ $p->ukuran_ml }}ml</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Qty *</label>
                        <input type="number" name="qty" min="1" value="1" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Dicatat Oleh</label>
                            <select name="dicatat_oleh" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                <option value="">-- Pilih --</option>
                                @foreach($admins as $adm)<option value="{{ $adm }}">{{ $adm }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                            <input type="text" name="catatan" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-pink-600 text-white py-2 rounded-md font-semibold hover:bg-pink-700">Catat Produk Gratis</button>
                </form>
            </div>
        </div>

        {{-- Riwayat --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
                <form method="GET" class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Dari</label>
                        <input type="date" name="dari" value="{{ request('dari') }}" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Sampai</label>
                        <input type="date" name="sampai" value="{{ request('sampai') }}" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Platform</label>
                        <select name="platform" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm">
                            <option value="">-- Semua --</option>
                            @foreach($platformList as $pf)<option value="{{ $pf }}" {{ request('platform') === $pf ? 'selected' : '' }}>{{ $pf }}</option>@endforeach
                        </select>
                    </div>
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-1.5 rounded-md text-sm font-semibold hover:bg-indigo-700">Filter</button>
                    @if(request('dari') || request('sampai') || request('platform'))
                        <a href="{{ route('sampel.index') }}" class="px-4 py-1.5 rounded-md text-sm font-semibold bg-gray-100 text-gray-600 hover:bg-gray-200">Reset</a>
                    @endif
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100"><h2 class="font-bold text-gray-800 text-sm">Riwayat Produk Gratis</h2></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50"><tr>
                            <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Tgl</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Platform</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Affiliate</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase">Produk</th>
                            <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">Qty</th>
                            <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase">HPP</th>
                            <th class="px-4 py-2 text-center text-xs text-gray-500 uppercase"></th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($riwayat as $r)
                                <tr>
                                    <td class="px-4 py-2 text-gray-600 whitespace-nowrap">{{ $r->tanggal->format('d/m/Y') }}</td>
                                    <td class="px-4 py-2"><span class="text-xs bg-gray-100 text-gray-700 px-2 py-0.5 rounded">{{ $r->platform }}</span></td>
                                    <td class="px-4 py-2 font-medium text-gray-800">{{ $r->nama_affiliate }}</td>
                                    <td class="px-4 py-2 text-gray-600">{{ $r->produk->nama_produk ?? $r->sku_id }} {{ optional($r->produk)->ukuran_ml }}ml</td>
                                    <td class="px-4 py-2 text-right font-bold text-pink-600">{{ $r->qty }}</td>
                                    <td class="px-4 py-2 text-right text-gray-500 whitespace-nowrap">Rp {{ number_format($r->total_hpp, 0, ',', '.') }}</td>
                                    <td class="px-4 py-2 text-center">
                                        <form method="POST" action="{{ route('sampel.destroy', $r->id) }}" onsubmit="return confirm('Hapus catatan ini? Stok ({{ $r->qty }} pcs) dikembalikan ke Stok Produk Jadi.')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-xs text-red-500 hover:text-red-700">✕</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400 italic">Belum ada produk gratis dicatat.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3 border-t border-gray-100">{{ $riwayat->links() }}</div>
            </div>
        </div>
    </div>
</div>
</div>
</body>
</html>
