<?php

namespace Tests\Feature\Api;

use App\Contracts\SheetReader;
use App\Enums\ColumnType;
use App\Enums\ImportRunStatus;
use App\Jobs\SyncSheetsJob;
use App\Models\SheetSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Support\FakeSheetReader;
use Tests\TestCase;

class SheetConfigurationApiTest extends TestCase
{
    use RefreshDatabase;

    private FakeSheetReader $reader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reader = new FakeSheetReader;
        $this->app->instance(SheetReader::class, $this->reader);
    }

    public function test_connecting_a_sheet_auto_provisions_config_and_queues_sync(): void
    {
        Queue::fake();

        $this->reader->setTitle('example-spreadsheet-id', 'Sales workbook');
        $this->reader->put('example-spreadsheet-id', 'Sales', [
            [
                'date' => '2026-01-02',
                'product_name' => 'Pencil',
                'price' => '1.50',
                'amount' => '2',
            ],
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/sources', [
            'spreadsheet_id' => 'https://docs.google.com/spreadsheets/d/example-spreadsheet-id/edit',
        ])->assertCreated();

        $this->assertSame('Sales workbook', $response->json('data.name'));
        $this->assertSame('example-spreadsheet-id', $response->json('data.spreadsheet_id'));
        $this->assertNotEmpty($response->json('data.tabs'));
        $this->assertSame('Sales', $response->json('data.tabs.0.name'));
        $this->assertSame('sales', $response->json('data.tabs.0.mappings.0.destination_table'));
        $this->assertNotNull($response->json('meta.import_run_id'));

        Queue::assertPushed(SyncSheetsJob::class);

        $this->assertDatabaseHas('column_mappings', [
            'source_column' => 'product_name',
            'destination_column' => 'product_name',
        ]);
    }

    public function test_advanced_mapping_still_rejects_users_table(): void
    {
        $this->reader->setTitle('sheet-advanced', 'Advanced');
        $this->reader->put('sheet-advanced', 'Sales', [
            ['date' => '2026-01-02', 'product_name' => 'Pencil'],
        ]);

        $user = User::factory()->create();

        $sourceId = $this->actingAs($user)->postJson('/api/sources', [
            'spreadsheet_id' => 'sheet-advanced',
        ])->assertCreated()->json('data.id');

        $tabId = $this->actingAs($user)->postJson("/api/sources/{$sourceId}/tabs", [
            'name' => 'Extra',
        ])->assertCreated()->json('data.id');

        $this->actingAs($user)->postJson("/api/tabs/{$tabId}/mappings", [
            'destination_table' => 'users',
            'unique_columns' => ['email'],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['destination_table']);
    }

    public function test_advanced_rename_column_mapping_is_supported(): void
    {
        $this->reader->setTitle('sheet-rename', 'Rename');
        $this->reader->put('sheet-rename', 'Sales', [
            ['product_name' => 'Pencil'],
        ]);

        $user = User::factory()->create();

        $sourceId = $this->actingAs($user)->postJson('/api/sources', [
            'spreadsheet_id' => 'sheet-rename',
        ])->assertCreated()->json('data.id');

        $tabId = SheetSource::query()->findOrFail($sourceId)->tabs()->firstOrFail()->id;

        $mappingId = $this->actingAs($user)->postJson("/api/tabs/{$tabId}/mappings", [
            'destination_table' => 'catalog',
            'unique_columns' => ['name'],
        ])->assertCreated()->json('data.id');

        $this->actingAs($user)->postJson("/api/mappings/{$mappingId}/columns", [
            'source_column' => 'product_name',
            'destination_column' => 'name',
            'data_type' => ColumnType::String->value,
        ])->assertCreated()
            ->assertJsonPath('data.destination_column', 'name');
    }

    public function test_sync_endpoint_queues_a_run_and_returns_accepted(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        SheetSource::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/sync')
            ->assertStatus(202)
            ->assertJsonPath('data.status', ImportRunStatus::Pending->value);

        Queue::assertPushed(SyncSheetsJob::class, function (SyncSheetsJob $job) use ($response): bool {
            return $job->importRunId === $response->json('data.id');
        });
    }

    public function test_import_runs_are_listed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/sync')->assertStatus(202);

        $this->actingAs($user)->getJson('/api/import-runs')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'status', 'trigger']]]);
    }

    public function test_google_errors_surface_as_validation_messages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/sources', [
            'spreadsheet_id' => 'unknown-sheet-id',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['spreadsheet_id']);
    }
}
