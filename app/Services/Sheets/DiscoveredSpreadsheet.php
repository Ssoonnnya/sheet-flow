<?php

namespace App\Services\Sheets;

final class DiscoveredSpreadsheet
{
    /**
     * @param  list<DiscoveredTab>  $tabs
     */
    public function __construct(
        public readonly string $spreadsheetId,
        public readonly string $title,
        public readonly array $tabs,
    ) {}
}
