<?php

namespace App\Exceptions;

use RuntimeException;

final class ProtectedDestinationTable extends RuntimeException
{
    public static function make(string $table): self
    {
        return new self("Destination table [{$table}] is protected and cannot be created or altered.");
    }
}
