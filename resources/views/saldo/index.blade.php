<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saldo & Cashflow - Hagos ERP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Saldo & Cashflow</h1>
                <p class="text-gray-600 mt-1">Saldo tiap akun = saldo awal + masuk − keluar.</p>
            </div>
            <div class="flex flex-wrap items-center justify-end gap-2">
                <button type="button" title="Tarik Saldo (Withdrawal)" onclick="document.getElementById('withdrawalModal').classList.remove('hidden')" class="inline-flex items-center px-3 py-2 bg-blue-600 rounded-md font-semibold text-xs text-white uppercase tracking-wide hover:bg-blue-700 whitespace-nowrap">🏦 Tarik&nbsp;(WD)</button>
                <button type="button" title="Transfer Antar Akun" onclick="document.getElementById('transferModal').classList.remove('hidden')" class="inline-flex items-center px-3 py-2 bg-emerald-600 rounded-md font-semibold text-xs text-white uppercase tracking-wide hover:bg-emerald-700 whitespace-nowrap">🔄 Transfer</button>
                <button type="button" title="Opname Saldo Kas" onclick="document.getElementById('opnameKasModal').classList.remove('hidden')" class="inline-flex items-center px-3 py-2 bg-amber-600 rounded-md font-semibold text-xs text-white uppercase tracking-wide hover:bg-amber-700 whitespace-nowrap">🧮 Opname</button>
                <button type="button" title="Patungan Biaya Bersama Masuk" onclick="document.getElementById('patunganModal').classList.remove('hidden')" class="inline-flex items-center px-3 py-2 bg-teal-600 rounded-md font-semibold text-xs text-white uppercase tracking-wide hover:bg-teal-700 whitespace-nowrap">🤝 Patungan</button>
                <a href="{{ route('dashboard') }}" class="inline-flex items-center px-3 py-2 bg-gray-200 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-wide hover:bg-gray-300 whitespace-nowrap">&larr; Dashboard</a>
            </div>
        </div>

        @if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>@endif
        @if($errors->any())<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded text-sm">{{ $errors->first() }}</div>@endif

        <div class="mb-6 bg-gradient-to-r from-indigo-600 to-indigo-500 text-white rounded-lg px-6 py-5 shadow">
            <span class="text-sm opacity-90">Total Kas Tersedia (semua akun)</span>
            <div class="text-3xl font-bold">Rp {{ number_format($totalSaldo, 0, ',', '.') }}</div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
            @foreach($rows as $r)
            <a href="{{ route('saldo.index', ['akun'=>$r->nama_akun]) }}" class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow transition block">
                <div class="flex items-center justify-between">
                    <span class="font-semibold text-gray-900">{{ $r->nama_akun }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $r->tipe }}</span>
                </div>
                <div class="mt-2 text-xl font-bold {{ $r->saldo < 0 ? 'text-red-600' : 'text-gray-900' }}">Rp {{ number_format($r->saldo, 0, ',', '.') }}</div>
                <div class="mt-1 text-xs text-gray-400">
                    awal {{ number_format($r->saldo_awal, 0, ',', '.') }} ·
                    <span class="text-green-600">+{{ number_format($r->masuk, 0, ',', '.') }}</span> ·
                    <span class="text-red-500">−{{ number_format($r->keluar, 0, ',', '.') }}</span>
                </div>
            </a>
            @endforeach
        </div>

        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800">Buku Mutasi Kas {{ request('akun') ? '· '.request('akun') : '(60 terakhir)' }}</h3>
                @if(request('akun'))<a href="{{ route('saldo.index') }}" class="text-xs text-indigo-600 hover:underline">Lihat semua akun</a>@endif
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akun</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kategori</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Keterangan</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($mutasis as $m)
                        <tr>
                            <td class="px-4 py-2 whitespace-nowrap text-gray-500">{{ \Illuminate\Support\Carbon::parse($m->tanggal)->format('d/m/Y') }}</td>
                            <td class="px-4 py-2 text-gray-800">{{ $m->akun }}</td>
                            <td class="px-4 py-2 text-gray-600">{{ ucfirst(str_replace('_',' ',$m->kategori)) }}</td>
                            <td class="px-4 py-2 text-gray-500">{{ $m->keterangan }}</td>
                            <td class="px-4 py-2 text-right font-medium {{ $m->tipe === 'masuk' ? 'text-green-600' : 'text-red-600' }}">
                                {{ $m->tipe === 'masuk' ? '+' : '−' }} Rp {{ number_format($m->jumlah, 0, ',', '.') }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400 italic">Belum ada mutasi kas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Withdrawal (tarik saldo MP -> bank) -->
<div id="withdrawalModal" class="fixed z-50 inset-0 overflow-y-auto hidden" role="dialog" aria-modal="true">
  <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
    <div class="fixed inset-0 bg-gray-500 bg-opacity-40" onclick="document.getElementById('withdrawalModal').classList.add('hidden')"></div>
    <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
    <div class="relative z-10 inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
      <form method="POST" action="{{ route('saldo.withdrawal') }}">
        @csrf
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-1">Tarik Saldo (Withdrawal)</h3>
          <p class="text-sm text-gray-500 mb-4">Pindahkan saldo marketplace ke bank.</p>
          <div class="space-y-3">
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-sm font-medium text-gray-700">Dari (Saldo MP)</label>
                <select name="dari_akun" required class="mt-1 block w-full border-gray-300 rounded-md border px-3 py-2 text-sm bg-white">
                  <option value="">-- Pilih --</option>
                  @foreach($rows->where('tipe','Saldo MP') as $r)<option value="{{ $r->nama_akun }}">{{ $r->nama_akun }} (Rp {{ number_format($r->saldo,0,',','.') }})</option>@endforeach
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Ke (Bank)</label>
                <select name="ke_akun" required class="mt-1 block w-full border-gray-300 rounded-md border px-3 py-2 text-sm bg-white">
                  <option value="">-- Pilih --</option>
                  @foreach($rows->where('tipe','Bank') as $r)<option value="{{ $r->nama_akun }}">{{ $r->nama_akun }}</option>@endforeach
                </select>
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Jumlah (Rp)</label>
              <input type="number" name="jumlah" min="1" step="any" required class="mt-1 block w-full border-gray-300 rounded-md border px-3 py-2 text-sm">
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-sm font-medium text-gray-700">Tanggal</label>
                <input type="date" name="tanggal" value="{{ date('Y-m-d') }}" class="mt-1 block w-full border-gray-300 rounded-md border px-3 py-2 text-sm">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Catatan</label>
                <input type="text" name="catatan" class="mt-1 block w-full border-gray-300 rounded-md border px-3 py-2 text-sm">
              </div>
            </div>
          </div>
        </div>
        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
          <button type="submit" class="w-full inline-flex justify-center rounded-md px-4 py-2 bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 sm:ml-3 sm:w-auto">Tarik</button>
          <button type="button" onclick="document.getElementById('withdrawalModal').classList.add('hidden')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 px-4 py-2 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Transfer Antar Akun -->
<div id="transferModal" class="fixed z-50 inset-0 overflow-y-auto hidden" role="dialog" aria-modal="true">
  <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
    <div class="fixed inset-0 bg-gray-500 bg-opacity-40" onclick="document.getElementById('transferModal').classList.add('hidden')"></div>
    <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
    <div class="relative z-10 inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
      <form method="POST" action="{{ route('saldo.transfer') }}">
        @csrf
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Transfer Antar Akun</h3>
          <div class="space-y-3">
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-sm font-medium text-gray-700">Dari Akun</label>
                <select name="dari_akun" required class="mt-1 block w-full border-gray-300 rounded-md border px-3 py-2 text-sm bg-white">
                  <option value="">-- Pilih --</option>
                  @foreach($rows as $r)<option value="{{ $r->nama_akun }}">{{ $r->nama_akun }}</option>@endforeach
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Ke Akun</label>
                <select name="ke_akun" required class="mt-1 block w-full border-gray-300 rounded-md border px-3 py-2 text-sm bg-white">
                  <option value="">-- Pilih --</option>
                  @foreach($rows as $r)<option value="{{ $r->nama_akun }}">{{ $r->nama_akun }}</option>@endforeach
                </select>
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Jumlah Transfer (Rp)</label>
              <input type="number" name="jumlah" min="1" step="any" required class="mt-1 block w-full border-gray-300 rounded-md border px-3 py-2 text-sm">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Biaya Transfer (Rp) <span class="text-gray-400">— opsional</span></label>
              <input type="number" name="biaya_transfer" min="0" step="any" value="0" class="mt-1 block w-full border-gray-300 rounded-md border px-3 py-2 text-sm">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Potong biaya dari</label>
              <div class="flex gap-4 text-sm">
                <label class="flex items-center gap-1"><input type="radio" name="potong_biaya" value="pengirim" checked> Akun Pengirim</label>
                <label class="flex items-center gap-1"><input type="radio" name="potong_biaya" value="penerima"> Akun Penerima</label>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-sm font-medium text-gray-700">Tanggal</label>
                <input type="date" name="tanggal" value="{{ date('Y-m-d') }}" class="mt-1 block w-full border-gray-300 rounded-md border px-3 py-2 text-sm">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Catatan</label>
                <input type="text" name="catatan" class="mt-1 block w-full border-gray-300 rounded-md border px-3 py-2 text-sm">
              </div>
            </div>
          </div>
        </div>
        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
          <button type="submit" class="w-full inline-flex justify-center rounded-md px-4 py-2 bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 sm:ml-3 sm:w-auto">Transfer</button>
          <button type="button" onclick="document.getElementById('transferModal').classList.add('hidden')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 px-4 py-2 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Opname Kas -->
<div id="opnameKasModal" class="fixed z-50 inset-0 overflow-y-auto hidden" role="dialog" aria-modal="true">
  <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
    <div class="fixed inset-0 bg-gray-500 bg-opacity-40" onclick="document.getElementById('opnameKasModal').classList.add('hidden')"></div>
    <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
    <div class="relative z-10 inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
      <form method="POST" action="{{ route('saldo.opname_kas') }}">
        @csrf
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-1">🧮 Opname Saldo Kas</h3>
          <p class="text-sm text-gray-500 mb-4">Setel saldo akun ke jumlah riil (rekening/uang fisik). Selisihnya dicatat sebagai koreksi — bukan income/biaya, tidak masuk P&L.</p>
          <div class="space-y-3">
            <div>
              <label class="block text-sm font-medium text-gray-700">Akun</label>
              <select name="akun" id="opnameAkun" required onchange="opnameShowSistem()" class="mt-1 block w-full border-gray-300 rounded-md border px-3 py-2 text-sm bg-white">
                <option value="">-- Pilih Akun --</option>
                @foreach($rows as $r)<option value="{{ $r->nama_akun }}" data-saldo="{{ $r->saldo }}">{{ $r->nama_akun }}</option>@endforeach
              </select>
              <p id="opnameSistem" class="text-xs text-gray-500 mt-1"></p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Saldo Fisik Riil (Rp)</label>
              <input type="number" name="saldo_fisik" id="opnameFisik" step="1" required oninput="opnameHitung()" class="mt-1 block w-full border-gray-300 rounded-md border px-3 py-2 text-sm">
              <p id="opnameSelisih" class="text-xs mt-1"></p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Catatan (opsional)</label>
              <input type="text" name="catatan" placeholder="cth: selisih biaya admin bank" class="mt-1 block w-full border-gray-300 rounded-md border px-3 py-2 text-sm">
            </div>
          </div>
        </div>
        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
          <button type="submit" class="w-full inline-flex justify-center rounded-md px-4 py-2 bg-amber-600 text-white text-sm font-medium hover:bg-amber-700 sm:ml-3 sm:w-auto">Setel Saldo</button>
          <button type="button" onclick="document.getElementById('opnameKasModal').classList.add('hidden')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 px-4 py-2 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Patungan Masuk (mis. 420F) -->
<div id="patunganModal" class="fixed z-50 inset-0 overflow-y-auto hidden" role="dialog" aria-modal="true">
  <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
    <div class="fixed inset-0 bg-gray-500 bg-opacity-40" onclick="document.getElementById('patunganModal').classList.add('hidden')"></div>
    <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
    <div class="relative z-10 inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
      <form method="POST" action="{{ route('saldo.patungan') }}">
        @csrf
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-1">🤝 Patungan Biaya Bersama Masuk</h3>
          <p class="text-sm text-gray-500 mb-4">Kontribusi mitra (mis. 420F) untuk sewa/listrik/internet. Kas masuk, tapi <b>bukan pendapatan</b> — otomatis <b>mengurangi biaya operasional</b> di P&amp;L.</p>
          <div class="space-y-3">
            <div>
              <label class="block text-sm font-medium text-gray-700">Masuk ke Akun</label>
              <select name="akun" required class="mt-1 block w-full border-gray-300 rounded-md border px-3 py-2 text-sm bg-white">
                <option value="">-- Pilih Akun --</option>
                @foreach($rows as $r)<option value="{{ $r->nama_akun }}">{{ $r->nama_akun }}</option>@endforeach
              </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-sm font-medium text-gray-700">Dari</label>
                <input type="text" name="dari" value="420F" required class="mt-1 block w-full border-gray-300 rounded-md border px-3 py-2 text-sm">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Jumlah (Rp)</label>
                <input type="number" name="jumlah" min="1" step="1000" required class="mt-1 block w-full border-gray-300 rounded-md border px-3 py-2 text-sm" placeholder="cth: 500000">
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Untuk <span class="text-gray-400">(opsional)</span></label>
              <input type="text" name="untuk" class="mt-1 block w-full border-gray-300 rounded-md border px-3 py-2 text-sm" placeholder="cth: sewa/listrik/internet">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Tanggal</label>
              <input type="date" name="tanggal" value="{{ date('Y-m-d') }}" class="mt-1 block w-full border-gray-300 rounded-md border px-3 py-2 text-sm">
            </div>
          </div>
        </div>
        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
          <button type="submit" class="w-full inline-flex justify-center rounded-md px-4 py-2 bg-teal-600 text-white text-sm font-medium hover:bg-teal-700 sm:ml-3 sm:w-auto">Catat Patungan</button>
          <button type="button" onclick="document.getElementById('patunganModal').classList.add('hidden')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 px-4 py-2 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function opnameShowSistem() {
    const sel = document.getElementById('opnameAkun');
    const opt = sel.options[sel.selectedIndex];
    const saldo = opt ? parseFloat(opt.dataset.saldo || 0) : 0;
    document.getElementById('opnameSistem').textContent = opt && opt.value ? 'Saldo sistem: Rp ' + saldo.toLocaleString('id-ID') : '';
    opnameHitung();
}
function opnameHitung() {
    const sel = document.getElementById('opnameAkun');
    const opt = sel.options[sel.selectedIndex];
    const sistem = opt ? parseFloat(opt.dataset.saldo || 0) : 0;
    const fisik = parseFloat(document.getElementById('opnameFisik').value) || 0;
    const el = document.getElementById('opnameSelisih');
    if (!opt || !opt.value || !document.getElementById('opnameFisik').value) { el.textContent = ''; return; }
    const s = fisik - sistem;
    el.textContent = 'Selisih: ' + (s >= 0 ? '+' : '') + 'Rp ' + s.toLocaleString('id-ID') + (s > 0 ? ' (kas bertambah)' : (s < 0 ? ' (kas berkurang)' : ' (pas)'));
    el.className = 'text-xs mt-1 font-semibold ' + (s > 0 ? 'text-emerald-600' : (s < 0 ? 'text-red-600' : 'text-gray-400'));
}
</script>
</div>
</body>
</html>
