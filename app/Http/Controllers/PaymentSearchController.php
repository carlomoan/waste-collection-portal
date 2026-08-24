<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentSearchController extends Controller
{
    /**
     * Search payments by payer name, receipt number or control number.
     * Used by the merge/reassignment tool.
     */
    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->get('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json(['results' => []]);
        }

        $results = Payment::query()
            ->with('client:id,client_number,name')
            ->where(fn ($q) => $q
                ->where('payer_name', 'ilike', "%{$term}%")
                ->orWhere('receipt_number', 'like', "%{$term}%")
                ->orWhere('control_number', 'like', "%{$term}%"))
            ->orderByDesc('paid_at')
            ->limit(50)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'control_number' => $p->control_number,
                'receipt_number' => $p->receipt_number,
                'payer_name' => $p->payer_name,
                'amount' => (float) $p->amount,
                'paid_at' => $p->paid_at?->toDateTimeString(),
                'client_id' => $p->client_id,
                'client_name' => $p->client?->name ?? '—',
                'client_number' => $p->client?->client_number ?? '',
            ]);

        return response()->json(['results' => $results]);
    }
}
