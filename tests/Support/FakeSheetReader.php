<?php

namespace Tests\Support;

use App\Contracts\SheetReader;

final class FakeSheetReader implements SheetReader
{
    /**
     * @param  array<string, list<array<string, string|null>>>  $tabs
     */
    public function __construct(
        private array $tabs = [],
    ) {}

    /**
     * @param  list<array<string, string|null>>  $rows
     */
    public function put(string $spreadsheetId, string $tabName, array $rows): void
    {
        $this->tabs[$this->key($spreadsheetId, $tabName)] = $rows;
    }

    public function rows(string $spreadsheetId, string $tabName, int $headerRow = 1): array
    {
        return $this->tabs[$this->key($spreadsheetId, $tabName)] ?? [];
    }

    private function key(string $spreadsheetId, string $tabName): string
    {
        return $spreadsheetId."\0".$tabName;
    }
}
