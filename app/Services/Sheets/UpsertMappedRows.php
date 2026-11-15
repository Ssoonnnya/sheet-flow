<?php

namespace App\Services\Sheets;

use App\Models\TableMapping;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class UpsertMappedRows
{
    public function __construct(
        private readonly Identifier $identifiers,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function upsert(TableMapping $mapping, array $rows): int
    {
        if ($rows === []) {
            return 0;
        }

        $table = $this->identifiers->assertWritableTable($mapping->destination_table);

        try {
            $uniqueColumns = $this->identifiers->uniqueKeys($mapping->unique_columns);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException(
                "Mapping [{$mapping->id}] requires unique_columns for upsert.",
                previous: $exception,
            );
        }

        $updateColumns = array_values(array_diff(array_keys($rows[0]), $uniqueColumns));

        if ($updateColumns === []) {
            $updateColumns = [$uniqueColumns[0]];
        }

        DB::table($table)->upsert($rows, $uniqueColumns, $updateColumns);

        return count($rows);
    }
}
