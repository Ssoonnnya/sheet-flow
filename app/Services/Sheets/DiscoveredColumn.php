<?php

namespace App\Services\Sheets;

use App\Enums\ColumnType;

final class DiscoveredColumn
{
    /**
     * @param  non-empty-string  $destinationColumn
     */
    public function __construct(
        public readonly string $sourceColumn,
        public readonly string $destinationColumn,
        public readonly ColumnType $dataType,
    ) {}
}
