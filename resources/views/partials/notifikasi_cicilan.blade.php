@php
    $notifCicilan = \App\Http\Controllers\UtangController::getNotifikasi();
@endphp

@if(count($notifCicilan) > 0)
<div id="notif-cicilan" class="bg-red-600 text-white px-6 py-3 shadow-lg">
    <div class="max-w-7xl mx-auto">
        <div class="flex items-start gap-3">
            <span class="text-2xl flex-shrink-0">🔔</span>
            <div class="flex-1">
                <p class="font-bold text-lg mb-1">Peringatan Cicilan Jatuh Tempo!</p>
                <ul class="space-y-1">
                    @foreach($notifCicilan as $notif)
                        @php
                            $periode = \Carbon\Carbon::parse($notif['periode']);
                            $today = \Carbon\Carbon::today();
                            $selisihHari = $today->diffInDays($periode, false);
                            $label = $selisihHari < 0
                                ? 'TERLAMBAT ' . abs($selisihHari) . ' hari'
                                : ($selisihHari === 0 ? 'HARI INI' : 'H-' . $selisihHari);
                        @endphp
                        <li class="text-sm">
                            <span class="font-bold bg-white text-red-700 px-2 py-0.5 rounded mr-2">{{ $label }}</span>
                            <strong>{{ $notif['utang_cicilan']['sumber_dana']['nama'] }}</strong>
                            — {{ $notif['utang_cicilan']['deskripsi'] }}
                            — Rp {{ number_format($notif['jumlah_tagihan'], 0, ',', '.') }}
                            — Jatuh tempo: {{ $periode->format('d/m/Y') }}
                        </li>
                    @endforeach
                </ul>
                <p class="text-xs mt-2 text-red-200">
                    <a href="{{ route('utang.index') }}" class="underline font-semibold">→ Buka halaman Utang/Cicilan untuk bayar</a>
                </p>
            </div>
        </div>
    </div>
</div>

{{-- Auto-refresh setiap 6 jam jika masih ada notifikasi belum dibayar --}}
<script>
    setTimeout(function() { location.reload(); }, 6 * 60 * 60 * 1000);
</script>
@endif
