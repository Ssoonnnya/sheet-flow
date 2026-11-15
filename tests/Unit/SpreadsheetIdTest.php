<?php

namespace Tests\Unit;

use App\Exceptions\InvalidSpreadsheetId;
use App\Services\Sheets\SpreadsheetId;
use PHPUnit\Framework\TestCase;

class SpreadsheetIdTest extends TestCase
{
    public function test_plain_id_is_kept(): void
    {
        $this->assertSame(
            'example-spreadsheet-id',
            SpreadsheetId::from('  example-spreadsheet-id  '),
        );
    }

    public function test_id_is_taken_from_a_google_url(): void
    {
        $id = '1AbCDefGhijkLMnoPqRstuVwXyz0123456789-_ab';

        $this->assertSame(
            $id,
            SpreadsheetId::from("https://docs.google.com/spreadsheets/d/{$id}/edit?usp=sharing#gid=0"),
        );
    }

    public function test_a_url_that_is_not_a_spreadsheet_is_rejected(): void
    {
        $this->expectException(InvalidSpreadsheetId::class);

        SpreadsheetId::from('https://example.com/not-a-sheet');
    }

    public function test_blank_value_is_rejected(): void
    {
        $this->expectException(InvalidSpreadsheetId::class);

        SpreadsheetId::from('   ');
    }
}
