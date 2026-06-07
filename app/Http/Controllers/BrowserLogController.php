<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BrowserLogController extends Controller
{
    /**
     * Receive client-side browser logs/errors and persist them to a dedicated log file.
     */
    public function store(Request $request): JsonResponse
    {
        $payload = $request->all();

        $logs = $payload['logs'] ?? [$payload];

        $channel = Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/browser.log'),
        ]);

        foreach ($logs as $entry) {
            $level = strtolower((string) ($entry['level'] ?? 'info'));
            $level = in_array($level, ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'], true)
                ? $level
                : 'info';

            $channel->log($level, (string) ($entry['message'] ?? 'browser log'), [
                'url' => $entry['url'] ?? $request->headers->get('referer'),
                'user_id' => optional($request->user())->id,
                'user_agent' => $request->userAgent(),
                'context' => $entry['context'] ?? $entry['data'] ?? null,
                'stack' => $entry['stack'] ?? null,
            ]);
        }

        return response()->json(['status' => 'ok'], 200);
    }
}
