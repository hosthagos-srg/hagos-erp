@php $pct = $pct ?? null; @endphp
@if($pct !== null)
    @php $naik = $pct >= 0; @endphp
    <span class="inline-flex items-center gap-0.5 text-xs font-semibold {{ $naik ? 'text-emerald-600' : 'text-red-600' }}">
        <span>{!! $naik ? '&#9650;' : '&#9660;' !!}</span>{{ number_format(abs($pct), 1) }}%
    </span>
@else
    <span class="text-xs text-gray-300">—</span>
@endif
