<?php

namespace Tests\Feature;

use App\Jobs\ProcessBulkImport;
use App\Models\BulkImport;
use App\Models\Client;
use App\Models\ClientType;
use App\Models\User;
use App\Models\Zone;
use App\Services\BulkImportProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BulkImportTest extends TestCase
{
    use RefreshDatabase;

    private function actingUser(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    private function csvFile(string $name, string $contents): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'imp').'.csv';
        file_put_contents($path, $contents);

        return new UploadedFile($path, $name, 'text/csv', null, true);
    }

    public function test_store_dispatches_processing_job(): void
    {
        Storage::fake('local');
        Bus::fake();
        $this->actingUser();

        $file = $this->csvFile('clients.csv', "name,phone,zone_id,client_type_id\nJohn,255700000001,1,1\n");

        $this->post('/bulk-import', [
            'file' => $file,
            'entity_type' => 'clients',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('bulk_imports', [
            'entity_type' => 'clients',
            'status' => 'processing',
        ]);
        Bus::assertDispatched(ProcessBulkImport::class);
    }

    public function test_store_rejects_invalid_entity_type(): void
    {
        Storage::fake('local');
        $this->actingUser();

        $file = $this->csvFile('clients.csv', "name,phone\nJohn,255700000001\n");

        $this->post('/bulk-import', [
            'file' => $file,
            'entity_type' => 'unicorns',
        ])->assertSessionHasErrors('entity_type');
    }

    public function test_job_imports_valid_rows_and_skips_invalid(): void
    {
        Storage::fake('local');
        $user = $this->actingUser();
        $zone = Zone::create(['name' => 'Zone A', 'code' => 'ZA']);
        $type = ClientType::create(['name' => 'Residential', 'category' => 'residential', 'default_monthly_fee' => 5000]);

        // Two valid clients, one invalid (missing phone).
        $csv = "name,phone,zone_id,client_type_id,monthly_fee\n".
            "Alice,255700000001,{$zone->id},{$type->id},5000\n".
            "Bob,255700000002,{$zone->id},{$type->id},6000\n".
            "NoPhone,,{$zone->id},{$type->id},7000\n";
        $path = 'bulk-imports/clients.csv';
        Storage::disk('local')->put($path, $csv);

        $import = BulkImport::create([
            'file_name' => 'clients.csv',
            'file_path' => $path,
            'entity_type' => 'clients',
            'status' => 'processing',
            'imported_by' => $user->id,
            'imported_at' => now(),
        ]);

        (new ProcessBulkImport($import))->handle(app(BulkImportProcessor::class));

        $import->refresh();
        $this->assertSame('completed', $import->status);
        $this->assertSame(2, $import->success_count);
        $this->assertSame(1, $import->failed_count);
        $this->assertSame(2, Client::whereIn('name', ['Alice', 'Bob'])->count());
        $this->assertCount(2, $import->imported_ids);
    }

    public function test_preview_returns_validation_summary_without_persisting(): void
    {
        $this->actingUser();

        $file = $this->csvFile('clients.csv', "name,phone,zone_id,client_type_id\nAlice,255700000001,1,1\nNoPhone,,1,1\n");

        $this->postJson('/bulk-import/preview', [
            'file' => $file,
            'entity_type' => 'clients',
        ])->assertOk()
            ->assertJsonStructure(['columns', 'rows', 'valid', 'invalid', 'errors'])
            ->assertJsonPath('valid', 1)
            ->assertJsonPath('invalid', 1);

        $this->assertSame(0, Client::count());
    }

    public function test_template_download_returns_csv_for_each_entity(): void
    {
        $this->actingUser();

        foreach (['clients', 'staff', 'payments'] as $entity) {
            $response = $this->get("/bulk-import/template/{$entity}");
            $response->assertOk();
            $this->assertStringContainsString("{$entity}_template.csv", $response->headers->get('content-disposition'));
        }
    }

    public function test_template_download_rejects_unknown_entity(): void
    {
        $this->actingUser();

        $this->get('/bulk-import/template/unicorns')->assertNotFound();
    }
}
