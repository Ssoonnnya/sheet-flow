<?php

namespace Tests\Unit;

use App\Services\Sheets\SheetGridParser;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SheetGridParserTest extends TestCase
{
    private SheetGridParser $parser;

    protected function setUp(): void
    {
        $this->parser = new SheetGridParser;
    }

    public function test_columns_are_matched_by_header_not_position(): void
    {
        $rows = $this->parser->rows([
            ['amount', 'date', 'product_name', 'price'],
            ['2', '2026-01-02', 'Pencil', '1.50'],
        ], 1);

        $this->assertSame([
            [
                'amount' => '2',
                'date' => '2026-01-02',
                'product_name' => 'Pencil',
                'price' => '1.50',
            ],
        ], $rows);
    }

    public function test_missing_cells_become_null_and_blank_rows_are_skipped(): void
    {
        $rows = $this->parser->rows([
            ['date', 'product_name'],
            ['2026-01-02'],
            ['', '   '],
            ['2026-01-03', 'Notebook'],
        ], 1);

        $this->assertSame([
            ['date' => '2026-01-02', 'product_name' => null],
            ['date' => '2026-01-03', 'product_name' => 'Notebook'],
        ], $rows);
    }

    public function test_duplicate_header_keeps_the_first_column(): void
    {
        $rows = $this->parser->rows([
            ['price', 'price'],
            ['1.00', '2.00'],
        ], 1);

        $this->assertSame([
            ['price' => '1.00'],
        ], $rows);
    }

    public function test_header_row_can_be_below_the_first_line(): void
    {
        $rows = $this->parser->rows([
            ['ignore me'],
            ['date', 'amount'],
            ['2026-01-02', '4'],
        ], 2);

        $this->assertSame([
            ['date' => '2026-01-02', 'amount' => '4'],
        ], $rows);
    }

    public function test_sheet_shorter_than_the_header_row_is_empty(): void
    {
        $this->assertSame([], $this->parser->rows([['date']], 3));
    }

    public function test_header_row_must_be_positive(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->parser->rows([], 0);
    }
}
