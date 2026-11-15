<?php

namespace App\Services\Sheets;

final class MappedRowsResult
{
    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function __construct(
        public readonly array $rows,
        public readonly int $skipped,
    ) {}
}
