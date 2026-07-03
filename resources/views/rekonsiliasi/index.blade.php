<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekonsiliasi Marketplace - Hagos ERP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">
    <div class="min-h-screen p-6">
        <header class="mb-6 flex items-start justify-between flex-wrap gap-3">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Rekonsiliasi Marketplace</h1>
                <p class="text-gray-600 mt-2 max-w-2xl">Cocokkan <b>net settlement versi sistem</b> dengan <b>dana riil</b> yang benar-benar masuk. Selisihnya = biaya siluman marketplace (iklan, denda, adjustment) yang belum tercatat.</p>
            </div>
            <form method="GET" class="flex items-end gap-2">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Periode</label>
                    <select name="bulan" onchange="this.form.submit()" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm">
                        @forelse($bulanTersedia as $b)
                            <option value="{{ $b }}" @selected($b===$bulan)>{{ $b }}</option>
                        @empty
                            <option value="{{ $bulan }}">{{ $bulan }}</option>
                        @endforelse
                    </select>
                </div>
            </form>
        </header>

        @if (session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 text-sm rounded-md px-4 py-2">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-md px-4 py-2">{{ session('error') }}</div>
        @endif

        @if($netPerChannel->isEmpty())
            <div class="bg-white rounded-xl shadow-sm p-10 text-center text-gray-400">Belum ada order marketplace yang cair di periode {{ $bulan }}.</div>
        @else
            <div class="space-y-4">
                @foreach($netPerChannel as $row)
                    @php $rek = $rekons[$row->channel] ?? null; @endphp
                    <div class="bg-white rounded-xl shadow-sm p-5">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h2 class="text-lg font-bold text-gray-900">{{ $row->channel }}</h2>
                                <p class="text-xs text-gray-500">{{ $row->jml }} order cair · periode {{ $bulan }}</p>
                            </div>
                            @if($rek && $rek->dibebankan)
                                <span class="text-xs font-semibold px-2 py-1 rounded bg-indigo-100 text-indigo-700">Selisih sudah dibebankan</span>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('rekonsiliasi.store') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
                            @csrf
                            <input type="hidden" name="channel" value="{{ $row->channel }}">
                            <input type="hidden" name="periode" value="{{ $bulan }}">

                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Net settlement (sistem)</label>
                                <div class="border border-gray-200 bg-gray-50 rounded-md px-3 py-2 text-sm font-bold text-gray-900">Rp {{ number_format($row->net, 0, ',', '.') }}</div>
                            </div>

                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Dana riil diterima</label>
                                <input type="number" step="0.01" name="saldo_riil" value="{{ $rek?->saldo_riil }}" required placeholder="cek saldo MP / mutasi bank"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Selisih tersimpan</label>
                                <div class="px-3 py-2 text-sm font-bold {{ $rek ? ($rek->selisih > 0 ? 'text-red-600' : ($rek->selisih < 0 ? 'text-green-700' : 'text-gray-500')) : 'text-gray-400' }}">
                                    {{ $rek ? 'Rp '.number_format($rek->selisih, 0, ',', '.') : '—' }}
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Akun kas (bila dibebankan)</label>
                                <select name="akun" class="w-full border border-gray-300 rounded-md px-2 py-2 text-sm">
                                    <option value="">—</option>
                                    @foreach($akuns as $a)
                                        <option value="{{ $a }}">{{ $a }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex flex-col gap-2">
                                <label class="flex items-center gap-2 text-xs text-gray-600">
                                    <input type="checkbox" name="bebankan" value="1" @disabled($rek && $rek->dibebankan)
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    Catat selisih sbg beban
                                </label>
                                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold py-2 rounded-md">Simpan</button>
                            </div>

                            <div class="md:col-span-5">
                                <input type="text" name="catatan" value="{{ $rek?->catatan }}" placeholder="Catatan (mis. rincian potongan: iklan, refund, dana ketahan)"
                                    class="w-full border border-gray-300 rounded-md px-3 py-1.5 text-sm">
                            </div>
                        </form>

                        @if($rek && $rek->selisih > 0 && !$rek->dibebankan)
                            <p class="text-xs text-amber-600 mt-2">⚠ Ada selisih Rp {{ number_format($rek->selisih, 0, ',', '.') }} yang belum dibebankan — laba masih terlihat lebih besar dari kenyataan.</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
</body>
</html>
