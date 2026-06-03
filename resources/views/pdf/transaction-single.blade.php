<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Transaction #{{ $payment->id }}</title>
    <style>
        body { font-family: 'Figtree', sans-serif; margin: 30px; }
        .header { border-bottom: 2px solid #2d7a50; padding-bottom: 15px; margin-bottom: 20px; }
        .title { font-size: 20px; font-weight: bold; color: #2d7a50; }
        .meta { font-size: 12px; color: #666; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; margin-top: 20px; }
        th { background: #f8faf9; text-align: left; padding: 8px; border-bottom: 1px solid #ddd; }
        td { padding: 6px 8px; border-bottom: 1px solid #eee; }
        .label { font-weight: bold; width: 30%; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Waste Collection Portal</div>
        <div class="meta">Transaction Receipt — Generated: {{ now()->format('d M Y H:i') }}</div>
    </div>
    <table>
        <tr><td class="label">Control Number</td><td>{{ $payment->control_number }}</td></tr>
        <tr><td class="label">Payer Name</td><td>{{ $payment->payer_name ?? $payment->client->name }}</td></tr>
        <tr><td class="label">Client Number</td><td>{{ $payment->client->client_number ?? '—' }}</td></tr>
        <tr><td class="label">Amount (TZS)</td><td style="font-weight:bold; color:#2d7a50;">{{ number_format($payment->amount, 0) }}</td></tr>
        <tr><td class="label">Status</td><td>{{ strtoupper($payment->status) }}</td></tr>
        <tr><td class="label">Collector</td><td>{{ $payment->staff->user->name ?? '—' }}</td></tr>
        <tr><td class="label">Receipt Ref</td><td>{{ $payment->collectionSession->session_reference ?? '—' }}</td></tr>
        <tr><td class="label">Date & Time</td><td>{{ $payment->paid_at?->format('d M Y H:i:s') }}</td></tr>
    </table>
</body>
</html>