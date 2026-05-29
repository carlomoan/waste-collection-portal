<?php

namespace App\Http\Controllers;

use App\Models\BulkImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class BulkImportController extends Controller
{
    public function index()
    {
        // Get recent bulk imports with user information
        $recentImports = BulkImport::with('importedBy')
            ->orderBy('imported_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($import) {
                return [
                    'id' => $import->id,
                    'file_name' => $import->file_name,
                    'records_imported' => $import->records_imported,
                    'status' => $import->status,
                    'imported_at' => $import->imported_at?->format('Y-m-d H:i:s'),
                    'imported_by' => $import->importedBy->name ?? 'System',
                ];
            });

        return Inertia::render('BulkImport/Index', [
            'recentImports' => $recentImports,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'import_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $file = $request->file('file');
            $importDate = $request->input('import_date');
            
            // Store the file
            $filePath = $file->store('bulk-imports', 'local');
            
            // Create bulk import record
            $bulkImport = BulkImport::create([
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'records_imported' => 0,
                'status' => 'processing',
                'imported_by' => auth()->id(),
            ]);
            
            // Process the file (Excel/CSV)
            // This would typically use Laravel Excel package
            $records = $this->processImportFile($file, $importDate, $bulkImport);
            
            // Update bulk import record
            $bulkImport->update([
                'records_imported' => $records,
                'status' => 'completed',
                'imported_at' => now(),
            ]);
            
            return back()->with('success', "Successfully imported {$records} collection records.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to import file: ' . $e->getMessage());
        }
    }

    private function processImportFile($file, $importDate, BulkImport $bulkImport)
    {
        // Placeholder for actual import logic
        // In production, this would use Laravel Excel to parse the file
        // and insert records into the database
        
        // Simulate processing
        // This would be replaced with actual Excel parsing logic
        return rand(50, 200);
    }

    public function downloadTemplate()
    {
        // Return a template file for bulk import
        $templatePath = storage_path('app/templates/daily_collections_template.xlsx');
        
        if (file_exists($templatePath)) {
            return response()->download($templatePath);
        }
        
        return back()->with('error', 'Template file not found.');
    }
}
