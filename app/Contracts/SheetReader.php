<?php

namespace App\Contracts;

interface SheetReader
{
    public function spreadsheetTitle(string $spreadsheetId): string;

    /**
     * @return list<string>
     */
    public function tabNames(string $spreadsheetId): array;

    /**
     * @return list<string>
     */
    public function headers(string $spreadsheetId, string $tabName, int $headerRow = 1): array;

    /**
     * Read one tab. Keys are header cells from the header row; positions do not matter.
     *
     * @return list<array<string, string|null>>
     */
    public function rows(string $spreadsheetId, string $tabName, int $headerRow = 1): array;
}
