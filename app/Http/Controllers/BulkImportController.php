<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessBulkImport;
use App\Models\BulkImport;
use App\Models\Zone;
use App\Services\BulkImportProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BulkImportController extends Controller
{
    private const ENTITY_TYPES = ['clients', 'staff', 'payments'];

    public function index()
    {
        $imports = BulkImport::with('importedBy')
            ->orderBy('imported_at', 'desc')
            ->paginate(20);

        $thisMonth = BulkImport::whereMonth('imported_at', now()->month)
            ->whereYear('imported_at', now()->year);

        $recentImports = collect($imports->items())->map(fn (BulkImport $import): array => [
            'id' => $import->id,
            'file_name' => $import->file_name,
            'entity_type' => $import->entity_type,
            'records_imported' => $import->records_imported,
            'status' => $import->status,
            'imported_at' => $import->imported_at,
            'imported_by_name' => $import->importedBy?->name ?? '—',
        ])->all();

        return Inertia::render('BulkImport/Index', [
            'imports' => $imports,
            'recentImports' => $recentImports,
            'entityTypes' => self::ENTITY_TYPES,
            'zones' => Zone::orderBy('name')->get(['id', 'name']),
            'stats' => [
                'total_imports' => (clone $thisMonth)->count(),
                'records_imported' => (clone $thisMonth)->sum('records_imported'),
                'success_rate' => $this->successRate(),
                'last_import' => BulkImport::latest('imported_at')->value('records_imported') ?? 0,
            ],
        ]);
    }

    public function preview(Request $request, BulkImportProcessor $processor): JsonResponse
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
            'entity_type' => 'required|in:'.implode(',', self::ENTITY_TYPES),
            'zone_id' => 'nullable|exists:zones,id',
        ]);

        // Store to disk first so the file keeps its extension; PhpSpreadsheet
        // detects the reader from the extension, which the raw temp upload path
        // (e.g. /tmp/phpXXXX) lacks.
        $path = $request->file('file')->store('bulk-imports/previews', 'local');
        $absolutePath = Storage::disk('local')->path($path);

        try {
            $preview = $processor->preview($absolutePath, $validated['entity_type'], 20, $validated['zone_id'] ?? null);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Could not read file: '.$e->getMessage()], 422);
        } finally {
            Storage::disk('local')->delete($path);
        }

        return response()->json($preview);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
            'entity_type' => 'required|in:'.implode(',', self::ENTITY_TYPES),
            'import_date' => 'nullable|date',
            'zone_id' => 'nullable|exists:zones,id',
        ]);

        $file = $request->file('file');
        $path = $file->store('bulk-imports', 'local');

        $bulkImport = BulkImport::create([
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'entity_type' => $validated['entity_type'],
            'status' => 'processing',
            'imported_by' => auth()->id(),
            'imported_at' => now(),
        ]);

        ProcessBulkImport::dispatch($bulkImport, $validated['import_date'] ?? null, $validated['zone_id'] ?? null);

        return back()->with('success', 'Import started. Refresh to see the result once processing completes.');
    }

    public function downloadTemplate(string $entityType)
    {
        abort_unless(in_array($entityType, self::ENTITY_TYPES, true), 404);

        $headers = match ($entityType) {
            'clients' => ['name', 'phone', 'email', 'zone_id', 'client_type_id', 'monthly_fee', 'address'],
            'staff' => ['name', 'phone', 'role', 'national_id', 'zone_id', 'base_salary', 'hire_date'],
            'payments' => ['control_number', 'receipt_number', 'amount', 'client_id', 'payment_method', 'payer_name', 'paid_at'],
            default => [],
        };

        $callback = function () use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fclose($file);
        };

        return response()->streamDownload($callback, "{$entityType}_template.csv", [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function rollback(BulkImport $bulkImport)
    {
        if ($bulkImport->status !== 'completed') {
            return back()->with('error', 'Only completed imports can be rolled back.');
        }

        $modelClass = BulkImportProcessor::ENTITY_MODELS[$bulkImport->entity_type] ?? null;

        if (! $modelClass) {
            return back()->with('error', 'Unknown entity type; cannot roll back.');
        }

        DB::transaction(function () use ($bulkImport, $modelClass) {
            $modelClass::whereIn('id', $bulkImport->imported_ids ?? [])->delete();
            $bulkImport->update(['status' => 'rolled_back']);
        });

        return back()->with('success', 'Import rolled back successfully.');
    }

    private function successRate(): float
    {
        $totals = BulkImport::selectRaw('SUM(success_count) as ok, SUM(failed_count) as bad')->first();
        $ok = (int) ($totals->ok ?? 0);
        $bad = (int) ($totals->bad ?? 0);

        if (($ok + $bad) === 0) {
            return 0.0;
        }

        return round(($ok / ($ok + $bad)) * 100, 1);
    }
}
