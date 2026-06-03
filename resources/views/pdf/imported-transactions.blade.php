<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Imported Transactions' }}</title>
    <style>
        body {
            font-family: 'Figtree', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 30px;
            color: #1a2e24;
        }
        .header {
            border-bottom: 2px solid #2d7a50;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .title {
            font-size: 20px;
            font-weight: bold;
            color: #2d7a50;
        }
        .meta {
            font-size: 12px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-top: 20px;
        }
        th {
            background-color: #f8faf9;
            text-align: left;
            padding: 8px;
            border-bottom: 1px solid #ddd;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #7a9489;
        }
        td {
            padding: 6px 8px;
            border-bottom: 1px solid #eee;
        }
        .amount {
            text-align: right;
            font-weight: 600;
            color: #2d7a50;
        }
        .status {
            font-weight: bold;
        }
        .paid {
            color: #2d7a50;
        }
        .reversed {
            color: #c0392b;
        }
        .footer {
            margin-top: 30px;
            font-size: 11px;
            color: #888;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Waste Collection Portal</div>
        <div class="meta">{{ $title ?? 'Imported Transactions' }} — Generated: {{ now()->format('d M Y H:i') }}</div>
    </div>

    @if($payments->isEmpty())
        <p>No transactions to display.</p>
    @else
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
                    <td class="status {{ strtolower($p->status) }}">{{ strtoupper($p->status) }}</td>
                    <td>{{ $p->paid_at?->format('d M Y H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        Waste Collection Portal — Automatic report
    </div>
</body>
</html>