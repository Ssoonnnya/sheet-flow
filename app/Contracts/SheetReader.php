<?php

namespace App\Contracts;

interface SheetReader
{
    /**
     * Read one tab. Keys are header cells from the header row; positions do not matter.
     *
     * @return list<array<string, string|null>>
     */
    public function rows(string $spreadsheetId, string $tabName, int $headerRow = 1): array;
}
