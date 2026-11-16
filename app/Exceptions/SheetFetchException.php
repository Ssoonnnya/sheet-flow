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

    public static function failed(string $spreadsheetId, string $context, Throwable $previous): self
    {
        $detail = self::summarize($previous);

        return new self(
            "Google Sheets request failed for spreadsheet [{$spreadsheetId}] ({$context}): {$detail}",
            previous: $previous,
        );
    }

    public function clientMessage(): string
    {
        $previous = $this->getPrevious();

        if ($previous instanceof Throwable) {
            return self::summarize($previous);
        }

        return $this->getMessage();
    }

    private static function summarize(Throwable $previous): string
    {
        $message = $previous->getMessage();
        $decoded = json_decode($message, true);

        if (is_array($decoded) && isset($decoded['error']['message']) && is_string($decoded['error']['message'])) {
            return $decoded['error']['message'];
        }

        return $message;
    }
}
