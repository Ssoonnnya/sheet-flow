<?php

namespace Database\Seeders;

use App\Enums\ColumnType;
use App\Models\ColumnMapping;
use App\Models\SheetSource;
use App\Models\SheetTab;
use App\Models\TableMapping;
use Illuminate\Database\Seeder;

/**
 * Example from the task brief: a sales sheet mapped onto the sales table.
 *
 * spreadsheet_id is a placeholder. Replace it with a real Google spreadsheet
 * id before running a sync.
 */
class SheetConfigurationSeeder extends Seeder
{
    public const PLACEHOLDER_SPREADSHEET_ID = 'example-spreadsheet-id';

    public function run(): void
    {
        $source = SheetSource::query()->updateOrCreate(
            ['spreadsheet_id' => self::PLACEHOLDER_SPREADSHEET_ID],
            ['name' => 'Sales workbook', 'is_active' => true],
        );

        $tab = SheetTab::query()->updateOrCreate(
            ['sheet_source_id' => $source->id, 'name' => 'Sales'],
            ['header_row' => 1, 'is_active' => true],
        );

        $mapping = TableMapping::query()->updateOrCreate(
            ['sheet_tab_id' => $tab->id, 'destination_table' => 'sales'],
            ['unique_columns' => ['date', 'name'], 'is_active' => true],
        );

        $columns = [
            ['source' => 'date', 'destination' => 'date', 'type' => ColumnType::Date],
            ['source' => 'product_name', 'destination' => 'name', 'type' => ColumnType::String],
            ['source' => 'price', 'destination' => 'product_price', 'type' => ColumnType::Decimal],
            ['source' => 'amount', 'destination' => 'amount', 'type' => ColumnType::Integer],
        ];

        foreach ($columns as $column) {
            ColumnMapping::query()->updateOrCreate(
                [
                    'table_mapping_id' => $mapping->id,
                    'destination_column' => $column['destination'],
                ],
                [
                    'source_column' => $column['source'],
                    'data_type' => $column['type'],
                ],
            );
        }
    }
}
