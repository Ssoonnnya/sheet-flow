<?php

namespace App\Exceptions;

use InvalidArgumentException;

final class InvalidSpreadsheetId extends InvalidArgumentException
{
    public static function make(): self
    {
        return new self('Spreadsheet id or URL is not valid.');
    }
}
