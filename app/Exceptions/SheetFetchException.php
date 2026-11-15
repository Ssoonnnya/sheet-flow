<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

final class SheetFetchException extends RuntimeException
{
    public static function missingCredentials(string $path): self
    {
        return new self("Google service account file was not found at [{$path}].");
    }

    public static function failed(string $spreadsheetId, string $tabName, Throwable $previous): self
    {
        return new self(
            "Google Sheets request failed for spreadsheet [{$spreadsheetId}] tab [{$tabName}].",
            previous: $previous,
        );
    }
}
