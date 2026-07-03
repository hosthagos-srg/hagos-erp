<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 10px; color: #222; margin: 0; }
        h1 { font-size: 16px; margin: 0 0 2px 0; }
        .periode { font-size: 10px; color: #666; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        th { background: #f3f4f6; text-align: left; padding: 5px 6px; font-size: 9px; text-transform: uppercase; border-bottom: 2px solid #d1d5db; }
        td { padding: 4px 6px; border-bottom: 1px solid #eee; font-size: 9px; }
        .num { text-align: right; white-space: nowrap; }
        .neg { color: #b91c1c; }
        .summary { margin-top: 8px; }
        .summary td { border: 0; padding: 2px 6px; }
        .summary .label { color: #555; }
        .summary .val { text-align: right; font-weight: bold; }
        .footer { margin-top: 16px; font-size: 8px; color: #999; text-align: right; }
    </style>
</head>
<body>
    @php
        $fmt = function ($v, $type) {
            if ($type === 'rupiah') return 'Rp ' . number_format((float) $v, 0, ',', '.');
            if ($type === 'int') return number_format((float) $v, 0, ',', '.');
            return $v;
        };
    @endphp

    <h1>{{ $report['title'] }}</h1>
    <div class="periode">Periode: {{ $report['periode_label'] }} &middot; {{ count($report['rows']) }} baris &middot; Hagos ERP</div>

    <table>
        <thead>
            <tr>
                @foreach($report['columns'] as $c)
                    <th class="{{ in_array($c['type'], ['rupiah','int']) ? 'num' : '' }}">{{ $c['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($report['rows'] as $row)
                <tr>
                    @foreach($report['columns'] as $i => $c)
                        @php $isNum = in_array($c['type'], ['rupiah','int']); $neg = ($c['type']==='rupiah' && (float)($row[$i]??0) < 0); @endphp
                        <td class="{{ $isNum ? 'num' : '' }} {{ $neg ? 'neg' : '' }}">{{ $fmt($row[$i] ?? '', $c['type']) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($report['columns']) }}" style="text-align:center; color:#999; padding:16px;">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if(!empty($report['summary']))
        <strong style="font-size:10px;">RINGKASAN</strong>
        <table class="summary">
            @foreach($report['summary'] as $s)
                <tr>
                    <td class="label">{{ $s['label'] }}</td>
                    <td class="val {{ (($s['type']??'')==='rupiah' && (float)$s['value'] < 0) ? 'neg' : '' }}">{{ $fmt($s['value'], $s['type'] ?? 'text') }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <div class="footer">Dicetak {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
