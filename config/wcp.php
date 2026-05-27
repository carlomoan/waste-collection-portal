<?php

return [
    'grace_period_days' => env('WCP_GRACE_PERIOD_DAYS', 15),
    'penalty_rate' => env('WCP_PENALTY_RATE', 10),       // % of outstanding balance
    'currency' => 'TZS',
    'company' => [
        'name' => env('WCP_COMPANY_NAME', 'UWAMAMO'),
        'pos_id' => env('WCP_POS_ID', '170896-2024-00107'),
        'tin' => env('WCP_TIN', ''),
        'address' => env('WCP_ADDRESS', ''),
        'phone' => env('WCP_PHONE', ''),
    ],
    'invoice_prefix' => 'INV',
    'client_prefix' => 'WCP',
    'staff_prefix' => 'WCP-STF',
];