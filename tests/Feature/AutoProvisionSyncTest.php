<?php

namespace Tests\Feature;

use App\Contracts\SheetReader;
use App\Enums\ImportRunStatus;
use App\Enums\ImportTrigger;
use App\Models\ColumnMapping;
use App\Models\ImportRun;
use App\Services\Sheets\ProvisionSheetSource;
use App\Services\Sheets\SyncSheets;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Support\FakeSheetReader;
use Tests\TestCase;

class AutoProvisionSyncTest extends TestCase
{
    use RefreshDatabase;

    private FakeSheetReader $reader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reader = new FakeSheetReader;
        $this->app->instance(SheetReader::class, $this->reader);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('sales');
        Schema::dropIfExists('inventory');

        parent::tearDown();
    }

    public function test_provision_and_sync_create_destination_rows(): void
    {
        $this->reader->setTitle('auto-sheet', 'Auto Sheet');
        $this->reader->put('auto-sheet', 'Sales', [
            [
                'date' => '2026-01-02',
                'product_name' => 'Pencil',
                'price' => '1.50',
                'amount' => '2',
            ],
        ]);

        $source = $this->app->make(ProvisionSheetSource::class)->provision('auto-sheet');

        $this->assertSame('Auto Sheet', $source->name);
        $this->assertDatabaseHas('table_mappings', [
            'destination_table' => 'sales',
        ]);

        $run = ImportRun::query()->create([
            'status' => ImportRunStatus::Pending,
            'trigger' => ImportTrigger::Manual,
        ]);

        $result = $this->app->make(SyncSheets::class)->process($run);

        $this->assertSame(ImportRunStatus::Completed, $result->status);
        $this->assertTrue(Schema::hasTable('sales'));
        $this->assertDatabaseHas('sales', [
            'date' => '2026-01-02',
            'product_name' => 'Pencil',
            'amount' => 2,
        ]);
    }

    public function test_new_sheet_header_is_added_to_config_and_table_on_sync(): void
    {
        $this->reader->setTitle('grow-sheet', 'Grow Sheet');
        $this->reader->put('grow-sheet', 'Sales', [
            [
                'date' => '2026-01-02',
                'product_name' => 'Pencil',
                'amount' => '2',
            ],
        ]);

        $this->app->make(ProvisionSheetSource::class)->provision('grow-sheet');

        $this->reader->put('grow-sheet', 'Sales', [
            [
                'date' => '2026-01-03',
                'product_name' => 'Notebook',
                'amount' => '4',
                'category' => 'office',
            ],
        ]);

        $run = ImportRun::query()->create([
            'status' => ImportRunStatus::Pending,
            'trigger' => ImportTrigger::Manual,
        ]);

        $result = $this->app->make(SyncSheets::class)->process($run);

        $this->assertSame(ImportRunStatus::Completed, $result->status);
        $this->assertTrue(Schema::hasColumn('sales', 'category'));
        $this->assertDatabaseHas('column_mappings', [
            'source_column' => 'category',
            'destination_column' => 'category',
        ]);
        $this->assertDatabaseHas('sales', [
            'product_name' => 'Notebook',
            'category' => 'office',
        ]);
        $this->assertContains('category', $result->entries->first()?->columns_added ?? []);
    }

    public function test_manual_rename_is_not_overwritten_when_sheet_gains_columns(): void
    {
        $this->reader->setTitle('rename-sheet', 'Rename Sheet');
        $this->reader->put('rename-sheet', 'Sales', [
            [
                'date' => '2026-01-02',
                'product_name' => 'Pencil',
            ],
        ]);

        $source = $this->app->make(ProvisionSheetSource::class)->provision('rename-sheet');
        $mapping = $source->tabs->firstOrFail()->mappings->firstOrFail();

        ColumnMapping::query()
            ->where('table_mapping_id', $mapping->id)
            ->where('source_column', 'product_name')
            ->update(['destination_column' => 'name']);

        $mapping->forceFill([
            'unique_columns' => ['date', 'name'],
        ])->save();

        $this->reader->put('rename-sheet', 'Sales', [
            [
                'date' => '2026-01-02',
                'product_name' => 'Pencil',
                'category' => 'office',
            ],
        ]);

        $run = ImportRun::query()->create([
            'status' => ImportRunStatus::Pending,
            'trigger' => ImportTrigger::Manual,
        ]);

        $this->app->make(SyncSheets::class)->process($run);

        $this->assertDatabaseHas('column_mappings', [
            'source_column' => 'product_name',
            'destination_column' => 'name',
        ]);
        $this->assertDatabaseHas('column_mappings', [
            'source_column' => 'category',
            'destination_column' => 'category',
        ]);
    }
}
