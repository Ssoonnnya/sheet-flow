<?php

namespace App\Services\Sheets;

use App\Exceptions\InvalidIdentifier;
use App\Exceptions\ProtectedDestinationTable;
use InvalidArgumentException;

final class Identifier
{
    /**
     * @return non-empty-string
     */
    public function assertValid(string $identifier): string
    {
        $pattern = (string) config('sheets.identifier_pattern');

        if ($identifier === '' || preg_match($pattern, $identifier) !== 1) {
            throw InvalidIdentifier::make($identifier);
        }

        return $identifier;
    }

    /**
     * @return non-empty-string
     */
    public function assertWritableTable(string $table): string
    {
        $table = $this->assertValid($table);

        /** @var list<string> $protected */
        $protected = config('sheets.protected_tables', []);

        if (in_array($table, $protected, true)) {
            throw ProtectedDestinationTable::make($table);
        }

        return $table;
    }

    /**
     * @param  list<string>  $columns
     * @return non-empty-list<non-empty-string>
     */
    public function uniqueKeys(array $columns): array
    {
        if ($columns === []) {
            throw new InvalidArgumentException('At least one unique column is required.');
        }

        $keys = [];

        foreach ($columns as $column) {
            $keys[] = $this->assertValid($column);
        }

        return $keys;
    }
}
