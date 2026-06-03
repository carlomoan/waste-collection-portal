<?php

namespace App\Http\Controllers;

use App\Models\BulkImport;
use App\Imports\ClientsImport;
use App\Imports\StaffImport;
use App\Imports\PaymentsImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class BulkImportController extends Controller
{
    public function index()
    {
        $imports = BulkImport::with('importedBy')
            ->orderBy('imported_at', 'desc')
            ->paginate(20);

        return Inertia::render('BulkImport/Index', [
            'imports' => $imports,
            'entityTypes' => ['clients', 'staff', 'payments', 'collection_sessions'],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'entity_type' => 'required|in:clients,staff,payments,collection_sessions',
            'import_date' => 'nullable|date',
        ]);

        $file = $request->file('file');
        $path = $file->store('bulk-imports', 'local');

        $bulkImport = BulkImport::create([
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'entity_type' => $request->entity_type,
            'status' => 'processing',
            'imported_by' => auth()->id(),
            'imported_at' => now(),
        ]);

        // Dispatch job for async processing
        ProcessBulkImport::dispatch($bulkImport, $request->import_date);

        return back()->with('success', 'Import started. You will be notified when completed.');
    }

    public function show(BulkImport $bulkImport)
    {
        $errors = json_decode($bulkImport->error_log, true) ?? [];
        return Inertia::render('BulkImport/Show', [
            'import' => $bulkImport,
            'errors' => $errors,
        ]);
    }

    public function downloadTemplate($entityType)
    {
        $templates = [
            'clients' => 'templates/clients_import.xlsx',
            'staff' => 'templates/staff_import.xlsx',
            'payments' => 'templates/payments_import.xlsx',
        ];

        $path = storage_path('app/'.$templates[$entityType]);
        if (!file_exists($path)) {
            // Generate dummy template dynamically
            return $this->generateTemplate($entityType);
        }

        return response()->download($path);
    }

    private function generateTemplate($entityType)
    {
        $headers = match ($entityType) {
            'clients' => ['name', 'phone', 'zone_id', 'client_number', 'monthly_fee'],
            'staff' => ['user_id', 'phone', 'role', 'base_salary', 'zone_id'],
            'payments' => ['control_number', 'amount', 'paid_at', 'client_id', 'payment_method'],
            default => [],
        };

        $callback = function () use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$entityType}_template.csv",
        ]);
    }

    public function rollback(BulkImport $bulkImport)
    {
        if ($bulkImport->status !== 'completed') {
            return back()->with('error', 'Only completed imports can be rolled back.');
        }

        DB::beginTransaction();
        try {
            $modelClass = $this->getModelClass($bulkImport->entity_type);
            $modelClass::whereIn('id', $bulkImport->imported_ids)->delete();
            $bulkImport->update(['status' => 'rolled_back']);
            DB::commit();
            return back()->with('success', 'Import rolled back successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    private function getModelClass($entityType)
    {
        return match ($entityType) {
            'clients' => \App\Models\Client::class,
            'staff' => \App\Models\Staff::class,
            'payments' => \App\Models\Payment::class,
            default => null,
        };
    }
}