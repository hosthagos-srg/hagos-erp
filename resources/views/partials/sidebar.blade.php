@php
    // Struktur menu: 'single' = link langsung, 'group' = bisa dibuka (klik → muncul anak menu)
    $navItems = [
        ['type' => 'single', 'route' => 'dashboard', 'pattern' => 'dashboard', 'label' => 'Dashboard', 'icon' => '🏠'],
        ['type' => 'group', 'label' => 'Penjualan', 'icon' => '🧾', 'children' => [
            ['route' => 'penjualan.index', 'pattern' => 'penjualan.*', 'label' => 'Kelola Pesanan'],
            ['route' => 'pelanggan.index', 'pattern' => 'pelanggan.*', 'label' => 'Pelanggan / Reseller'],
            ['route' => 'sampel.index',    'pattern' => 'sampel.*',    'label' => 'Produk Gratis (Affiliate)'],
            ['route' => 'upload.index',    'pattern' => 'upload.*',    'label' => 'Upload Marketplace'],
            ['route' => 'mapping.index',   'pattern' => 'mapping.*',   'label' => 'Mapping SKU'],
        ]],
        ['type' => 'group', 'label' => 'Produksi', 'icon' => '🏭', 'children' => [
            ['route' => 'racik.index',     'pattern' => 'racik.index', 'label' => 'Gudang Racik'],
            ['route' => 'racik.riwayat',   'pattern' => 'racik.riwayat', 'label' => 'Riwayat Racik'],
            ['route' => 'produksi.index',  'pattern' => 'produksi.index', 'label' => 'Produksi Internal'],
            ['route' => 'produksi.riwayat', 'pattern' => 'produksi.riwayat', 'label' => 'Riwayat Produksi (Tester/Absolute)'],
        ]],
        ['type' => 'group', 'label' => 'Produk & Stok', 'icon' => '📦', 'children' => [
            ['route' => 'master_produk.index', 'pattern' => 'master_produk.*', 'label' => 'Master Produk'],
            ['route' => 'resep_mix.index',  'pattern' => 'resep_mix.*',  'label' => 'Resep Mix'],
            ['route' => 'margin.index',    'pattern' => 'margin.*',    'label' => 'Margin Watchdog'],
            ['route' => 'stok.index',      'pattern' => 'stok.index',  'label' => 'Stok Bahan'],
            ['route' => 'stok_jadi.index', 'pattern' => 'stok_jadi.*', 'label' => 'Stok Produk Jadi'],
        ]],
        ['type' => 'group', 'label' => 'Pembelian', 'icon' => '🛒', 'children' => [
            ['route' => 'belanja.index',   'pattern' => 'belanja.*',   'label' => 'Belanja Bibit & Komponen'],
            ['route' => 'utang.index',     'pattern' => 'utang.*',     'label' => 'Utang & Cicilan'],
        ]],
        ['type' => 'group', 'label' => 'Pengeluaran', 'icon' => '💸', 'children' => [
            ['route' => 'pengeluaran.index', 'pattern' => 'pengeluaran.*', 'label' => 'Pengeluaran'],
            ['route' => 'budgeting.index',   'pattern' => 'budgeting.*',   'label' => 'Budgeting'],
        ]],
        ['type' => 'group', 'label' => 'Keuangan', 'icon' => '💰', 'children' => [
            ['route' => 'saldo.index',          'pattern' => 'saldo.index',          'label' => 'Saldo & Cashflow'],
            ['route' => 'penerimaan.index',     'pattern' => 'penerimaan.*',         'label' => 'Penerimaan Uang'],
            ['route' => 'piutang.index',        'pattern' => 'piutang.index',        'label' => 'Piutang & Aging'],
            ['route' => 'piutang_pribadi.index', 'pattern' => 'piutang_pribadi.*',   'label' => 'Piutang Pribadi (Pinjaman)'],
            ['route' => 'utang_pribadi.index',   'pattern' => 'utang_pribadi.*',     'label' => 'Utang Pribadi (Hutang)'],
            ['route' => 'proyeksi_kas.index',   'pattern' => 'proyeksi_kas.*',       'label' => 'Proyeksi Arus Kas'],
            ['route' => 'saldo.modal_form',     'pattern' => 'saldo.modal_form',     'label' => 'Modal & Prive'],
            ['route' => 'saldo.withdrawal_form', 'pattern' => 'saldo.withdrawal_form', 'label' => 'Tarik Dana (WD)'],
            ['route' => 'saldo.transfer_form',  'pattern' => 'saldo.transfer_form',  'label' => 'Transfer Antar Akun'],
            ['route' => 'rekonsiliasi.index',   'pattern' => 'rekonsiliasi.*',       'label' => 'Rekonsiliasi Marketplace'],
        ]],
        ['type' => 'group', 'label' => 'Karyawan', 'icon' => '👷', 'children' => [
            ['route' => 'karyawan.index', 'pattern' => 'karyawan.*', 'label' => 'Daftar Karyawan'],
            ['route' => 'kasbon.index',   'pattern' => 'kasbon.*',   'label' => 'Kasbon'],
            ['route' => 'gaji.index',     'pattern' => 'gaji.*',     'label' => 'Gaji'],
        ]],
        ['type' => 'group', 'label' => 'Laporan', 'icon' => '📊', 'children' => [
            ['route' => 'laporan.pl',      'pattern' => 'laporan.pl',  'label' => 'Laporan P&L'],
            ['route' => 'laporan.laba_produk', 'pattern' => 'laporan.laba_produk', 'label' => 'Laba per Aroma/Produk'],
            ['route' => 'laporan.bibit',   'pattern' => 'laporan.bibit', 'label' => 'Bibit Terpakai'],
            ['route' => 'laporan.perputaran_bibit', 'pattern' => 'laporan.perputaran_bibit', 'label' => 'Perputaran & Stok Mati'],
            ['route' => 'pelanggan.kinerja', 'pattern' => 'pelanggan.kinerja', 'label' => 'Kinerja Pelanggan/Reseller'],
            ['route' => 'laporan.retur',   'pattern' => 'laporan.retur', 'label' => 'Retur / Pembatalan'],
            ['route' => 'laporan.pajak',   'pattern' => 'laporan.pajak', 'label' => 'Pajak PPh Final 0,5%'],
            ['route' => 'neraca.index',    'pattern' => 'neraca.*',    'label' => 'Neraca'],
            ['route' => 'biaya_marketplace.index', 'pattern' => 'biaya_marketplace.*', 'label' => 'Biaya Marketplace'],
            ['route' => 'laporan.export',  'pattern' => 'laporan.export', 'label' => 'Export Laporan'],
        ]],
        ['type' => 'group', 'label' => 'Pengaturan', 'icon' => '⚙️', 'children' => [
            ['route' => 'users.index', 'pattern' => 'users.*', 'label' => 'Kelola User'],
            ['route' => 'audit.index', 'pattern' => 'audit.*', 'label' => 'Log Aktivitas'],
            ['route' => 'pengaturan.index', 'pattern' => 'pengaturan.*', 'label' => 'Tutup Buku'],
        ]],
    ];
@endphp

{{-- Flash error global (mis. periode terkunci) — muncul di semua halaman, auto-hilang --}}
@if(session('error'))
<div id="flash-error-global" class="fixed top-4 right-4 z-[60] max-w-sm bg-red-600 text-white text-sm px-4 py-3 rounded-lg shadow-lg flex items-start gap-3 animate-[fadeIn_0.2s_ease-out]">
    <span class="flex-1">{{ session('error') }}</span>
    <button onclick="this.parentElement.remove()" class="font-bold leading-none text-lg">&times;</button>
</div>
<script>setTimeout(() => document.getElementById('flash-error-global')?.remove(), 6000);</script>
@endif

{{-- Tombol hamburger (mobile) --}}
<button onclick="document.getElementById('app-sidebar').classList.toggle('-translate-x-full')"
    class="lg:hidden fixed top-4 left-4 z-50 bg-gray-900 text-white p-2 rounded-md shadow-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
</button>

{{-- Sidebar --}}
<aside id="app-sidebar"
    class="fixed top-0 left-0 z-40 w-60 h-screen bg-gray-900 text-gray-300 flex flex-col transition-transform -translate-x-full lg:translate-x-0">

    @php $notif = app(\App\Services\NotifikasiService::class)->get(); @endphp
    <div class="px-5 py-5 border-b border-gray-800 flex items-start justify-between">
        <a href="{{ route('dashboard') }}" class="block">
            <h1 class="text-lg font-bold text-white">Hagos ERP</h1>
            <p class="text-xs text-gray-500 mt-0.5">Sistem Keuangan & Operasional</p>
        </a>
        {{-- Lonceng notifikasi --}}
        <div class="relative flex-shrink-0">
            <button type="button" onclick="toggleNotif(event)" class="relative p-1.5 rounded-md hover:bg-gray-800 text-gray-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                @if($notif['total'] > 0)
                    <span class="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[10px] font-bold rounded-full min-w-[16px] h-4 px-1 flex items-center justify-center">{{ $notif['total'] > 99 ? '99+' : $notif['total'] }}</span>
                @endif
            </button>

            {{-- Dropdown --}}
            <div id="notif-dropdown" class="hidden absolute left-0 top-full mt-2 w-80 max-h-[70vh] overflow-y-auto bg-white rounded-lg shadow-2xl z-50 text-gray-800">
                <div class="px-4 py-3 border-b border-gray-100 flex justify-between items-center sticky top-0 bg-white">
                    <h3 class="font-bold text-sm text-gray-900">Notifikasi</h3>
                    <span class="text-xs text-gray-400">{{ $notif['total'] }} item</span>
                </div>
                @if(empty($notif['groups']))
                    <div class="px-4 py-8 text-center text-sm text-gray-400">✓ Tidak ada notifikasi. Semua aman!</div>
                @else
                    @foreach($notif['groups'] as $g)
                        @php $cmap = ['red'=>'text-red-700 bg-red-50','amber'=>'text-amber-700 bg-amber-50','orange'=>'text-orange-700 bg-orange-50']; @endphp
                        <div class="border-b border-gray-100 last:border-0">
                            <a href="{{ $g['url'] }}" class="flex items-center justify-between px-4 py-2 {{ $cmap[$g['color']] ?? 'bg-gray-50' }} hover:opacity-80">
                                <span class="text-xs font-bold uppercase tracking-wide">{{ $g['icon'] }} {{ $g['label'] }}</span>
                                <span class="text-xs font-semibold">{{ $g['count'] }}</span>
                            </a>
                            <ul class="divide-y divide-gray-50">
                                @foreach(array_slice($g['items'], 0, 5) as $it)
                                    <li class="px-4 py-2 text-xs {{ ($it['urgent'] ?? false) ? 'text-gray-800 font-medium' : 'text-gray-600' }}">{{ $it['text'] }}</li>
                                @endforeach
                                @if(count($g['items']) > 5)
                                    <li class="px-4 py-2 text-xs text-indigo-600">
                                        <a href="{{ $g['url'] }}" class="hover:underline">+ {{ count($g['items']) - 5 }} lainnya →</a>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    <style>summary::-webkit-details-marker{display:none}</style>

    <div class="px-4 pt-3 space-y-2">
        <a href="{{ route('penjualan.create') }}"
            class="flex items-center justify-center gap-2 w-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold py-2 rounded-md transition-colors">
            + Input Penjualan
        </a>
        <a href="{{ route('pengeluaran.index') }}"
            class="flex items-center justify-center gap-2 w-full bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold py-2 rounded-md transition-colors">
            + Input Pengeluaran
        </a>
    </div>

    <nav class="flex-1 overflow-y-auto py-3">
        @foreach($navItems as $item)
            @if(($item['type'] ?? 'single') === 'single')
                @php $active = request()->routeIs($item['pattern']); @endphp
                <a href="{{ route($item['route']) }}"
                    class="flex items-center gap-3 px-5 py-2.5 text-sm transition-colors
                        {{ $active
                            ? 'bg-indigo-600 text-white font-semibold border-l-4 border-indigo-300'
                            : 'text-gray-400 hover:bg-gray-800 hover:text-white border-l-4 border-transparent' }}">
                    <span class="text-base">{{ $item['icon'] }}</span>
                    <span>{{ $item['label'] }}</span>
                </a>
            @else
                @php $groupActive = collect($item['children'])->contains(fn($c) => request()->routeIs($c['pattern'])); @endphp
                <details class="group" {{ $groupActive ? 'open' : '' }}>
                    <summary class="flex items-center justify-between gap-3 px-5 py-2.5 text-sm cursor-pointer list-none transition-colors border-l-4
                        {{ $groupActive ? 'text-white border-indigo-300 bg-gray-800/40' : 'text-gray-400 border-transparent hover:bg-gray-800 hover:text-white' }}">
                        <span class="flex items-center gap-3"><span class="text-base">{{ $item['icon'] }}</span><span class="font-medium">{{ $item['label'] }}</span></span>
                        <svg class="w-4 h-4 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </summary>
                    <div class="bg-gray-950/30">
                        @foreach($item['children'] as $c)
                            @php $cActive = request()->routeIs($c['pattern']); @endphp
                            <a href="{{ route($c['route']) }}"
                                class="flex items-center gap-2 pl-12 pr-5 py-2 text-sm transition-colors border-l-4
                                    {{ $cActive
                                        ? 'bg-indigo-600 text-white font-semibold border-indigo-300'
                                        : 'text-gray-400 hover:bg-gray-800 hover:text-white border-transparent' }}">
                                {{ $c['label'] }}
                            </a>
                        @endforeach
                    </div>
                </details>
            @endif
        @endforeach
    </nav>

    {{-- Footer: user aktif & logout --}}
    @auth
    <div class="border-t border-gray-800 px-4 py-3 flex items-center justify-between gap-2">
        <div class="min-w-0">
            <p class="text-sm font-semibold text-white truncate">{{ auth()->user()->name }}</p>
            <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</p>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="flex-shrink-0">
            @csrf
            <button type="submit" title="Keluar"
                class="flex items-center gap-1 text-xs text-gray-400 hover:text-white hover:bg-gray-800 px-2 py-1.5 rounded-md transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                Keluar
            </button>
        </form>
    </div>
    @endauth

</aside>

{{-- Overlay (mobile) --}}
<div onclick="document.getElementById('app-sidebar').classList.add('-translate-x-full')"
    class="lg:hidden fixed inset-0 bg-black bg-opacity-30 z-30 hidden" id="sidebar-overlay"></div>

<script>
function toggleNotif(e) {
    e.stopPropagation();
    document.getElementById('notif-dropdown').classList.toggle('hidden');
}
// Tutup dropdown saat klik di luar
document.addEventListener('click', function (e) {
    const dd = document.getElementById('notif-dropdown');
    if (dd && !dd.classList.contains('hidden') && !dd.contains(e.target)) {
        dd.classList.add('hidden');
    }
});
</script>
