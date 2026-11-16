<?php

namespace Tests\Support;

use App\Contracts\SheetReader;
use App\Exceptions\SheetFetchException;
use RuntimeException;

final class FakeSheetReader implements SheetReader
{
    /**
     * @param  array<string, list<array<string, string|null>>>  $tabs
     * @param  array<string, string>  $titles
     * @param  array<string, list<string>>  $tabNames
     * @param  array<string, list<string>>  $headers
     */
    public function __construct(
        private array $tabs = [],
        private array $titles = [],
        private array $tabNames = [],
        private array $headers = [],
    ) {}

    public function setTitle(string $spreadsheetId, string $title): void
    {
        $this->titles[$spreadsheetId] = $title;
    }

    /**
     * @param  list<string>  $names
     */
    public function setTabNames(string $spreadsheetId, array $names): void
    {
        $this->tabNames[$spreadsheetId] = $names;
    }

    /**
     * @param  list<string>  $headers
     */
    public function setHeaders(string $spreadsheetId, string $tabName, array $headers): void
    {
        $this->headers[$this->key($spreadsheetId, $tabName)] = $headers;
        $this->rememberTab($spreadsheetId, $tabName);
    }

    /**
     * @param  list<array<string, string|null>>  $rows
     */
    public function put(string $spreadsheetId, string $tabName, array $rows): void
    {
        $this->tabs[$this->key($spreadsheetId, $tabName)] = $rows;
        $this->rememberTab($spreadsheetId, $tabName);

        if ($rows !== []) {
            $this->headers[$this->key($spreadsheetId, $tabName)] = array_keys($rows[0]);
        }
    }

    public function spreadsheetTitle(string $spreadsheetId): string
    {
        return $this->titles[$spreadsheetId] ?? $spreadsheetId;
    }

    public function tabNames(string $spreadsheetId): array
    {
        if (! array_key_exists($spreadsheetId, $this->tabNames)) {
            throw SheetFetchException::failed(
                $spreadsheetId,
                'metadata',
                new RuntimeException("Unknown spreadsheet [{$spreadsheetId}]."),
            );
        }

        return $this->tabNames[$spreadsheetId];
    }

    public function headers(string $spreadsheetId, string $tabName, int $headerRow = 1): array
    {
        return $this->headers[$this->key($spreadsheetId, $tabName)] ?? [];
    }

    public function rows(string $spreadsheetId, string $tabName, int $headerRow = 1): array
    {
        return $this->tabs[$this->key($spreadsheetId, $tabName)] ?? [];
    }

    private function rememberTab(string $spreadsheetId, string $tabName): void
    {
        if (! isset($this->tabNames[$spreadsheetId])) {
            $this->tabNames[$spreadsheetId] = [];
        }

        if (! in_array($tabName, $this->tabNames[$spreadsheetId], true)) {
            $this->tabNames[$spreadsheetId][] = $tabName;
        }
    }

    private function key(string $spreadsheetId, string $tabName): string
    {
        return $spreadsheetId."\0".$tabName;
    }
}
