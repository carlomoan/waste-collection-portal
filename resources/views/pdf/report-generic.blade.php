<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $reportTitle }}</title>
    <style>
        body { font-family: 'Figtree', DejaVu Sans, sans-serif; margin: 24px; color: #1a2e24; }
        h1 { color: #2d7a50; font-size: 20px; margin: 0 0 4px; }
        h2 { font-size: 14px; margin: 22px 0 8px; color: #1a2e24; border-bottom: 2px solid #4caf76; padding-bottom: 4px; }
        .meta { font-size: 11px; color: #666; margin-bottom: 18px; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .summary td { padding: 5px 8px; font-size: 12px; border-bottom: 1px solid #eee; }
        .summary td:first-child { color: #7a9489; text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px; width: 40%; }
        .summary td:last-child { text-align: right; font-weight: 700; }
        table.data { width: 100%; border-collapse: collapse; font-size: 11px; }
        table.data th { background-color: #f0faf3; text-transform: uppercase; letter-spacing: 0.4px; color: #4a6357; padding: 6px 8px; border-bottom: 1px solid #a8ddb8; text-align: left; }
        table.data td { padding: 5px 8px; border-bottom: 1px solid #eee; }
        table.data tr:nth-child(even) td { background: #fafcfa; }
        .num { text-align: right; font-variant-numeric: tabular-nums; }
        .footer { margin-top: 26px; font-size: 10px; color: #999; border-top: 1px solid #ddd; padding-top: 8px; }
    </style>
</head>
<body>
    <h1>Waste Collection Portal — {{ $reportTitle }}</h1>
    <div class="meta">
        Period: {{ $period }} &nbsp;|&nbsp; Generated: {{ now()->format('d M Y, H:i') }}
    </div>

    @if(!empty($summary))
    <table class="summary">
        @foreach($summary as $key => $value)
        <tr>
            <td>{{ ucwords(str_replace('_', ' ', $key)) }}</td>
            <td>{{ is_numeric($value) ? number_format((float) $value, 2) : $value }}</td>
        </tr>
        @endforeach
    </table>
    @endif

    @foreach($sections as $section)
        <h2>{{ $section['title'] }}</h2>
        @if(count($section['rows']))
        <table class="data">
            <thead>
                <tr>
                    @foreach($section['headers'] as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($section['rows'] as $row)
                <tr>
                    @foreach(array_values($row) as $i => $cell)
                        <td class="{{ is_numeric($cell) && !is_string($cell) ? 'num' : '' }}">
                            {{ is_numeric($cell) && !is_string($cell) ? number_format((float) $cell, 2) : $cell }}
                        </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
            <p style="font-size:12px;color:#888;">No data for this period.</p>
        @endif
    @endforeach

    <div class="footer">
        Waste Collection Portal — automated report. Total rows: {{ collect($sections)->sum(fn ($s) => count($s['rows'])) }}
    </div>
</body>
</html>
