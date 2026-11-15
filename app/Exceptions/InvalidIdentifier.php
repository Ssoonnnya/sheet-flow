<?php

namespace App\Exceptions;

use InvalidArgumentException;

final class InvalidIdentifier extends InvalidArgumentException
{
    public static function make(string $identifier): self
    {
        return new self("Identifier [{$identifier}] is not a valid table or column name.");
    }
}
