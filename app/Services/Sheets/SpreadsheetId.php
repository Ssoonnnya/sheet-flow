<?php

namespace App\Services\Sheets;

use App\Exceptions\InvalidSpreadsheetId;

final class SpreadsheetId
{
    public static function from(string $value): string
    {
        $value = trim($value);

        if (preg_match('#/spreadsheets/d/([a-zA-Z0-9_-]+)#', $value, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('/^[a-zA-Z0-9_-]{8,128}$/', $value) === 1) {
            return $value;
        }

        throw InvalidSpreadsheetId::make();
    }
}
