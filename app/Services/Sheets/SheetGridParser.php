<?php

namespace App\Services\Sheets;

use InvalidArgumentException;

final class SheetGridParser
{
    /**
     * @param  list<list<mixed>>  $grid
     * @return list<array<string, string|null>>
     */
    public function rows(array $grid, int $headerRow): array
    {
        if ($headerRow < 1) {
            throw new InvalidArgumentException('Header row must be at least 1.');
        }

        $headerIndex = $headerRow - 1;

        if (! array_key_exists($headerIndex, $grid)) {
            return [];
        }

        $headers = $this->headers($grid[$headerIndex]);
        $rows = [];

        foreach (array_slice($grid, $headerIndex + 1) as $line) {
            $row = $this->associate($headers, $line);

            if ($row === null) {
                continue;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param  list<mixed>  $headerLine
     * @return array<int, string>
     */
    private function headers(array $headerLine): array
    {
        $headers = [];
        $seen = [];

        foreach ($headerLine as $index => $cell) {
            $name = $this->stringCell($cell);

            if ($name === null || isset($seen[$name])) {
                continue;
            }

            $seen[$name] = true;
            $headers[$index] = $name;
        }

        return $headers;
    }

    /**
     * @param  array<int, string>  $headers
     * @param  list<mixed>  $line
     * @return array<string, string|null>|null
     */
    private function associate(array $headers, array $line): ?array
    {
        if ($headers === []) {
            return null;
        }

        $row = [];
        $hasValue = false;

        foreach ($headers as $index => $name) {
            $value = $this->stringCell($line[$index] ?? null);
            $row[$name] = $value;
            $hasValue = $hasValue || $value !== null;
        }

        return $hasValue ? $row : null;
    }

    private function stringCell(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            $formatted = rtrim(rtrim(sprintf('%.10F', $value), '0'), '.');

            return $formatted === '' ? '0' : $formatted;
        }

        $string = trim($value);

        return $string === '' ? null : $string;
    }
}
