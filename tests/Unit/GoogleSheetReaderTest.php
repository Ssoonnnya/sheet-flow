<?php

namespace Tests\Unit;

use App\Exceptions\SheetFetchException;
use App\Services\Sheets\GoogleSheetReader;
use App\Services\Sheets\SheetGridParser;
use PHPUnit\Framework\TestCase;

class GoogleSheetReaderTest extends TestCase
{
    public function test_missing_credentials_fail_before_a_network_call(): void
    {
        $reader = new GoogleSheetReader(
            parser: new SheetGridParser,
            credentialsPath: sys_get_temp_dir().'/sheet-flow-missing-service-account.json',
            timeoutSeconds: 5,
        );

        $this->expectException(SheetFetchException::class);
        $this->expectExceptionMessage('was not found');

        $reader->rows('example-spreadsheet-id', 'Sales');
    }
}
