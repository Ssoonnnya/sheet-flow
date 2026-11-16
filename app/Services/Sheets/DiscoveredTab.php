<?php

namespace App\Services\Sheets;

final class DiscoveredTab
{
    /**
     * @param  non-empty-string  $destinationTable
     * @param  list<DiscoveredColumn>  $columns
     * @param  non-empty-list<non-empty-string>  $uniqueColumns
     */
    public function __construct(
        public readonly string $name,
        public readonly string $destinationTable,
        public readonly array $columns,
        public readonly array $uniqueColumns,
    ) {}
}
