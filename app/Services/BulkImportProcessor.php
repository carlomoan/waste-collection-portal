<?php

namespace App\Services;

use App\Imports\GenericSheetImport;
use App\Models\BulkImport;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Parses and persists bulk-import spreadsheets (Excel/CSV).
 *
 * The same parsing + validation logic backs both the preview endpoint
 * (no persistence) and the queued import job (persists in a transaction).
 */
class BulkImportProcessor
{
    private const CHUNK_SIZE = 500;

    /** Supported entity types and their model classes. */
    public const ENTITY_MODELS = [
        'clients' => Client::class,
        'staff' => Staff::class,
        'payments' => Payment::class,
    ];

    /**
     * Parse the file and validate rows WITHOUT persisting anything.
     *
     * @return array{columns: array<int, string>, rows: array<int, array<string, mixed>>, valid: int, invalid: int, errors: array<int, array{row: int, message: string}>}
     */
    public function preview(string $absolutePath, string $entityType, int $sampleSize = 20, ?int $defaultZoneId = null): array
    {
        $rows = $this->readRows($absolutePath);
        $errors = [];
        $valid = 0;

        foreach ($rows as $index => $row) {
            $row = $this->applyImportDefaults($entityType, $row, $defaultZoneId);
            $validator = $this->validatorFor($entityType, $row);
            if ($validator->fails()) {
                $errors[] = ['row' => $index + 2, 'message' => $validator->errors()->first()];
            } else {
                $valid++;
            }
        }

        return [
            'columns' => $rows->isNotEmpty() ? array_keys($rows->first()) : [],
            'rows' => $rows->take($sampleSize)->values()->all(),
            'valid' => $valid,
            'invalid' => count($errors),
            'errors' => array_slice($errors, 0, 50),
        ];
    }

    /**
     * Parse, validate and persist the file. Valid rows are inserted inside a
     * single database transaction (chunked); invalid rows are skipped and
     * recorded in the import's error log.
     */
    public function process(BulkImport $import, string $absolutePath, ?string $importDate = null, ?int $defaultZoneId = null): void
    {
        $entityType = $import->entity_type;
        $rows = $this->readRows($absolutePath);

        $importedIds = [];
        $errors = [];
        $successCount = 0;

        DB::transaction(function () use ($rows, $entityType, $importDate, &$importedIds, &$errors, &$successCount) {
            foreach ($rows->chunk(self::CHUNK_SIZE) as $chunk) {
                foreach ($chunk as $index => $row) {
                    $row = $this->applyImportDefaults($entityType, $row, $defaultZoneId);
                    $validator = $this->validatorFor($entityType, $row);

                    if ($validator->fails()) {
                        $errors[] = ['row' => $index + 2, 'message' => $validator->errors()->first()];

                        continue;
                    }

                    try {
                        $model = $this->createRecord($entityType, $validator->validated(), $importDate);
                        $importedIds[] = $model->getKey();
                        $successCount++;
                    } catch (\Throwable $e) {
                        $errors[] = ['row' => $index + 2, 'message' => $e->getMessage()];
                    }
                }
            }
        });

        $import->update([
            'status' => 'completed',
            'total_rows' => $rows->count(),
            'records_imported' => $successCount,
            'success_count' => $successCount,
            'failed_count' => count($errors),
            'imported_ids' => $importedIds,
            'error_log' => array_slice($errors, 0, 200),
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function readRows(string $absolutePath): Collection
    {
        $import = new GenericSheetImport;
        Excel::import($import, $absolutePath);

        return collect($import->rows)
            ->map(fn ($row) => collect($row)
                ->reject(fn ($v, $k) => $k === null || $k === '')
                // Spreadsheet cells come back as ints/floats; normalise to
                // trimmed strings (empty -> null) so validation rules behave
                // predictably regardless of the source column formatting.
                ->map(fn ($v) => ($v === null || $v === '') ? null : trim((string) $v))
                ->all())
            ->reject(fn ($row) => count(array_filter($row, fn ($v) => $v !== null && $v !== '')) === 0)
            ->values();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function validatorFor(string $entityType, array $row): \Illuminate\Contracts\Validation\Validator
    {
        $rules = match ($entityType) {
            'clients' => [
                'name' => 'required|string|max:255',
                'phone' => 'required|string|max:50',
                'zone_id' => 'required|integer',
                'client_type_id' => 'required|integer',
                'email' => 'nullable|email',
                'monthly_fee' => 'nullable|numeric',
                'address' => 'nullable|string',
                'status' => 'nullable|string',
            ],
            'staff' => [
                'phone' => 'required|string|max:50',
                'role' => 'required|string|max:50',
                'name' => 'required_without:user_id|nullable|string|max:255',
                'user_id' => 'nullable|integer',
                'national_id' => 'nullable|string',
                'zone_id' => 'nullable|integer',
                'base_salary' => 'nullable|numeric',
                'hire_date' => 'nullable|date',
            ],
            'payments' => [
                'amount' => 'required|numeric|min:0',
                'control_number' => 'nullable|string',
                'receipt_number' => 'nullable|string',
                'client_id' => 'nullable|integer',
                'payment_method' => 'nullable|string',
                'payer_name' => 'nullable|string',
                'paid_at' => 'nullable|date',
                'status' => 'nullable|string',
            ],
            default => [],
        };

        return Validator::make($row, $rules);
    }

    private function applyImportDefaults(string $entityType, array $row, ?int $defaultZoneId): array
    {
        if (in_array($entityType, ['clients', 'staff'], true) && empty($row['zone_id']) && $defaultZoneId) {
            $row['zone_id'] = $defaultZoneId;
        }

        return $row;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createRecord(string $entityType, array $data, ?string $importDate): Model
    {
        return match ($entityType) {
            'clients' => Client::create(array_merge([
                'status' => 'active',
            ], $data)),
            'staff' => $this->createStaffRecord($data, $importDate),
            'payments' => Payment::create(array_merge([
                'status' => 'paid',
                'payment_method' => 'cash',
                'paid_at' => $importDate ?? now()->toDateString(),
            ], $data)),
            default => throw new \InvalidArgumentException("Unsupported entity type: {$entityType}"),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createStaffRecord(array $data, ?string $importDate): Staff
    {
        if (empty($data['user_id'])) {
            $name = trim((string) ($data['name'] ?? 'Imported Staff'));
            $email = str($name)->slug()->append('@import.wcp')->toString();

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'phone' => $data['phone'] ?? null,
                    'password' => bcrypt(str()->random(16)),
                    'is_active' => true,
                ]
            );

            $role = Role::where('name', $data['role'])->first();
            if ($role) {
                $user->roles()->syncWithoutDetaching([$role->id]);
            }

            $data['user_id'] = $user->id;
        }

        unset($data['name']);

        return Staff::create(array_merge([
            'is_active' => true,
            'hire_date' => $importDate ?? now()->toDateString(),
        ], $data));
    }
}
