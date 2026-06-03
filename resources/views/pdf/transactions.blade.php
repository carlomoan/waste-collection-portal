<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Transactions Report</title>
    <style>
        body { font-family: 'Figtree', sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th { background-color: #f8faf9; text-transform: uppercase; letter-spacing: 0.5px; color: #7a9489; padding: 8px; border-bottom: 1px solid #ddd; }
        td { padding: 6px 8px; border-bottom: 1px solid #eee; }
        .amount { text-align: right; font-weight: 600; color: #2d7a50; }
        .status { font-weight: bold; }
        .paid { color: #2d7a50; }
        .reversed { color: #c0392b; }
        h1 { color: #2d7a50; font-size: 18px; margin-bottom: 10px; }
        .meta { font-size: 11px; color: #666; margin-bottom: 15px; }
    </style>
</head>
<body>
    <h1>Waste Collection Portal – Transactions</h1>
    <div class="meta">
        Generated: {{ now()->format('d M Y, H:i') }} |
        Filters: {{ !empty($filters['month']) ? 'Month: ' . $filters['month'] : 'All months' }}
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Control Number</th>
                <th>Payer / Client</th>
                <th>Amount (TZS)</th>
                <th>Collector</th>
                <th>Receipt</th>
                <th>Status</th>
                <th>Date & Time</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $index => $p)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $p->control_number }}</td>
                <td>
                    {{ $p->payer_name ?? $p->client->name ?? '—' }}<br>
                    <small>{{ $p->client->client_number ?? '' }}</small>
                </td>
                <td class="amount">{{ number_format($p->amount, 0) }}</td>
                <td>{{ $p->staff->user->name ?? '—' }}</td>
                <td>{{ $p->collectionSession->session_reference ?? '—' }}</td>
                <td class="status {{ strtolower($p->status) }}">{{ $p->status }}</td>
                <td>{{ $p->paid_at?->format('d M Y H:i') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>