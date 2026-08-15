<?php

namespace App\Jobs;

use App\Models\BulkImport;
use App\Services\BulkImportProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessBulkImport implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 600;

    public function __construct(
        public BulkImport $bulkImport,
        public ?string $importDate = null,
        public ?int $zoneId = null,
    ) {}

    public function handle(BulkImportProcessor $processor): void
    {
        $absolutePath = Storage::disk('local')->path($this->bulkImport->file_path);

        try {
            $processor->process($this->bulkImport, $absolutePath, $this->importDate, $this->zoneId);
        } catch (\Throwable $e) {
            $this->bulkImport->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->bulkImport->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);
    }
}
