<?php

namespace Tests\Unit;

use App\Enums\ColumnType;
use App\Models\ColumnMapping;
use App\Models\TableMapping;
use App\Services\Sheets\MapSheetRows;
use Tests\TestCase;

class MapSheetRowsTest extends TestCase
{
    public function test_headers_are_matched_without_case_or_extra_spaces(): void
    {
        $mapping = $this->mapping([
            ['product_name', 'name', ColumnType::String],
            ['amount', 'amount', ColumnType::Integer],
        ]);

        $result = (new MapSheetRows)->map($mapping, [
            [
                ' Product_Name ' => 'Pencil',
                'AMOUNT' => '3',
            ],
        ]);

        $this->assertSame(0, $result->skipped);
        $this->assertSame([
            ['name' => 'Pencil', 'amount' => 3],
        ], $result->rows);
    }

    public function test_invalid_integer_is_skipped_instead_of_becoming_zero(): void
    {
        $mapping = $this->mapping([
            ['amount', 'amount', ColumnType::Integer],
        ]);

        $result = (new MapSheetRows)->map($mapping, [
            ['amount' => 'not-a-number'],
            ['amount' => '4'],
        ]);

        $this->assertSame(1, $result->skipped);
        $this->assertSame([
            ['amount' => 4],
        ], $result->rows);
    }

    public function test_missing_source_cell_becomes_null(): void
    {
        $mapping = $this->mapping([
            ['date', 'date', ColumnType::Date],
            ['product_name', 'name', ColumnType::String],
        ]);

        $result = (new MapSheetRows)->map($mapping, [
            ['date' => '2026-01-02'],
        ]);

        $this->assertSame([
            ['date' => '2026-01-02', 'name' => null],
        ], $result->rows);
    }

    /**
     * @param  list<array{0: string, 1: string, 2: ColumnType}>  $columns
     */
    private function mapping(array $columns): TableMapping
    {
        $mapping = new TableMapping([
            'destination_table' => 'sales',
            'unique_columns' => ['date', 'name'],
        ]);

        $mapping->setRelation('columns', collect(array_map(
            fn (array $column): ColumnMapping => new ColumnMapping([
                'source_column' => $column[0],
                'destination_column' => $column[1],
                'data_type' => $column[2],
            ]),
            $columns,
        )));

        return $mapping;
    }
}
