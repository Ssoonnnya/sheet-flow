<?php

namespace Tests\Feature;

use App\Contracts\SheetReader;
use App\Enums\ColumnType;
use App\Enums\ImportEntryStatus;
use App\Enums\ImportRunStatus;
use App\Enums\ImportTrigger;
use App\Models\ColumnMapping;
use App\Models\ImportRun;
use App\Models\SheetSource;
use App\Models\SheetTab;
use App\Models\TableMapping;
use App\Services\Sheets\SyncSheets;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\FakeSheetReader;
use Tests\TestCase;

class SyncSheetsTest extends TestCase
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

    public function test_rows_are_imported_when_sheet_columns_are_in_a_different_order(): void
    {
        $source = $this->salesSource();

        $this->reader->put($source->spreadsheet_id, 'Sales', [
            [
                'amount' => '2',
                'date' => '2026-01-02',
                'product_name' => 'Pencil',
                'price' => '1.50',
            ],
        ]);

        $run = $this->sync();

        $this->assertSame(ImportRunStatus::Completed, $run->status);
        $this->assertDatabaseHas('sales', [
            'date' => '2026-01-02',
            'name' => 'Pencil',
            'product_price' => '1.500000',
            'amount' => 2,
        ]);
    }

    public function test_one_sheet_can_be_saved_into_two_destination_tables(): void
    {
        $source = SheetSource::factory()->create([
            'spreadsheet_id' => 'sheet-two-tables',
        ]);
        $tab = SheetTab::factory()->for($source, 'source')->create([
            'name' => 'Sales',
        ]);

        $this->mapping($tab, 'sales', ['date', 'name'], [
            ['date', 'date', ColumnType::Date],
            ['product_name', 'name', ColumnType::String],
            ['price', 'product_price', ColumnType::Decimal],
            ['amount', 'amount', ColumnType::Integer],
        ]);

        $this->mapping($tab, 'inventory', ['name'], [
            ['product_name', 'name', ColumnType::String],
            ['amount', 'stock', ColumnType::Integer],
        ]);

        $this->reader->put($source->spreadsheet_id, 'Sales', [
            [
                'date' => '2026-01-02',
                'product_name' => 'Pencil',
                'price' => '1.50',
                'amount' => '5',
            ],
        ]);

        $run = $this->sync();

        $this->assertSame(ImportRunStatus::Completed, $run->status);
        $this->assertDatabaseHas('sales', [
            'date' => '2026-01-02',
            'name' => 'Pencil',
            'amount' => 5,
        ]);
        $this->assertDatabaseHas('inventory', [
            'name' => 'Pencil',
            'stock' => 5,
        ]);
    }

    public function test_a_new_mapped_column_is_added_to_an_existing_destination_table(): void
    {
        $source = $this->salesSource();

        Schema::create('sales', function ($table): void {
            $table->id();
            $table->date('date')->nullable();
            $table->string('name')->nullable();
            $table->decimal('product_price', 20, 6)->nullable();
            $table->integer('amount')->nullable();
            $table->unique(['date', 'name']);
        });

        ColumnMapping::query()->create([
            'table_mapping_id' => TableMapping::query()->firstOrFail()->id,
            'source_column' => 'category',
            'destination_column' => 'category',
            'data_type' => ColumnType::String,
        ]);

        $this->reader->put($source->spreadsheet_id, 'Sales', [
            [
                'date' => '2026-01-02',
                'product_name' => 'Pencil',
                'price' => '1.50',
                'amount' => '2',
                'category' => 'office',
            ],
        ]);

        $run = $this->sync();

        $this->assertSame(ImportRunStatus::Completed, $run->status);
        $this->assertTrue(Schema::hasColumn('sales', 'category'));
        $this->assertDatabaseHas('sales', [
            'name' => 'Pencil',
            'category' => 'office',
        ]);
        $this->assertSame(['category'], $run->entries->first()?->columns_added);
    }

    public function test_repeated_sync_updates_the_row_by_unique_columns(): void
    {
        $source = $this->salesSource();

        $this->reader->put($source->spreadsheet_id, 'Sales', [
            [
                'date' => '2026-01-02',
                'product_name' => 'Pencil',
                'price' => '1.50',
                'amount' => '2',
            ],
        ]);

        $this->sync();

        $this->reader->put($source->spreadsheet_id, 'Sales', [
            [
                'date' => '2026-01-02',
                'product_name' => 'Pencil',
                'price' => '2.00',
                'amount' => '9',
            ],
        ]);

        $this->sync();

        $this->assertSame(1, DB::table('sales')->count());
        $this->assertDatabaseHas('sales', [
            'date' => '2026-01-02',
            'name' => 'Pencil',
            'product_price' => '2.000000',
            'amount' => 9,
        ]);
    }

    public function test_invalid_numbers_are_skipped_and_other_rows_are_written(): void
    {
        $source = $this->salesSource();

        $this->reader->put($source->spreadsheet_id, 'Sales', [
            [
                'date' => '2026-01-02',
                'product_name' => 'Pencil',
                'price' => '1.50',
                'amount' => 'abc',
            ],
            [
                'date' => '2026-01-03',
                'product_name' => 'Notebook',
                'price' => '3.00',
                'amount' => '4',
            ],
        ]);

        $run = $this->sync();

        $this->assertSame(ImportRunStatus::Completed, $run->status);
        $this->assertSame(1, $run->entries->first()?->rows_skipped);
        $this->assertSame(1, $run->entries->first()?->rows_written);
        $this->assertSame(1, DB::table('sales')->count());
        $this->assertDatabaseHas('sales', [
            'name' => 'Notebook',
            'amount' => 4,
        ]);
        $this->assertDatabaseMissing('sales', [
            'name' => 'Pencil',
        ]);
    }

    public function test_a_mapping_to_users_fails_without_changing_the_users_table(): void
    {
        $columnsBefore = Schema::getColumnListing('users');

        $source = SheetSource::factory()->create([
            'spreadsheet_id' => 'sheet-users',
        ]);
        $tab = SheetTab::factory()->for($source, 'source')->create([
            'name' => 'People',
        ]);
        $this->mapping($tab, 'users', ['email'], [
            ['email', 'email', ColumnType::String],
            ['full_name', 'name', ColumnType::String],
        ]);

        $this->reader->put($source->spreadsheet_id, 'People', [
            [
                'email' => 'attacker@example.com',
                'full_name' => 'Attacker',
            ],
        ]);

        $run = $this->sync();

        $this->assertSame(ImportRunStatus::Failed, $run->status);
        $this->assertSame(ImportEntryStatus::Failed, $run->entries->first()?->status);
        $this->assertSame($columnsBefore, Schema::getColumnListing('users'));
        $this->assertDatabaseMissing('users', [
            'email' => 'attacker@example.com',
        ]);
    }

    private function sync(): ImportRun
    {
        $run = ImportRun::query()->create([
            'status' => ImportRunStatus::Pending,
            'trigger' => ImportTrigger::Manual,
        ]);

        return $this->app->make(SyncSheets::class)->process($run);
    }

    private function salesSource(): SheetSource
    {
        $source = SheetSource::factory()->create([
            'spreadsheet_id' => 'sheet-sales',
        ]);
        $tab = SheetTab::factory()->for($source, 'source')->create([
            'name' => 'Sales',
        ]);

        $this->mapping($tab, 'sales', ['date', 'name'], [
            ['date', 'date', ColumnType::Date],
            ['product_name', 'name', ColumnType::String],
            ['price', 'product_price', ColumnType::Decimal],
            ['amount', 'amount', ColumnType::Integer],
        ]);

        return $source;
    }

    /**
     * @param  list<string>  $uniqueColumns
     * @param  list<array{0: string, 1: string, 2: ColumnType}>  $columns
     */
    private function mapping(SheetTab $tab, string $table, array $uniqueColumns, array $columns): TableMapping
    {
        $mapping = TableMapping::factory()->for($tab, 'tab')->create([
            'destination_table' => $table,
            'unique_columns' => $uniqueColumns,
        ]);

        foreach ($columns as [$sourceColumn, $destinationColumn, $dataType]) {
            ColumnMapping::query()->create([
                'table_mapping_id' => $mapping->id,
                'source_column' => $sourceColumn,
                'destination_column' => $destinationColumn,
                'data_type' => $dataType,
            ]);
        }

        return $mapping->load('columns');
    }
}
