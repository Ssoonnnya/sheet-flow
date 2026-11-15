<?php

namespace App\Services\Sheets;

use App\Enums\ColumnType;
use App\Models\ColumnMapping;
use App\Models\TableMapping;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Throwable;

final class MapSheetRows
{
    /**
     * @param  list<array<string, string|null>>  $sourceRows
     */
    public function map(TableMapping $mapping, array $sourceRows): MappedRowsResult
    {
        /** @var Collection<int, ColumnMapping> $columns */
        $columns = $mapping->columns;

        if ($columns->isEmpty()) {
            return new MappedRowsResult([], count($sourceRows));
        }

        $rows = [];
        $skipped = 0;

        foreach ($sourceRows as $sourceRow) {
            $lookup = $this->lookup($sourceRow);
            $mapped = [];
            $valid = true;

            foreach ($columns as $column) {
                $raw = $this->valueFor($lookup, $column->source_column);

                try {
                    $mapped[$column->destination_column] = $this->cast($raw, $column->data_type);
                } catch (Throwable) {
                    $valid = false;
                    break;
                }
            }

            if (! $valid) {
                $skipped++;

                continue;
            }

            $rows[] = $mapped;
        }

        return new MappedRowsResult($rows, $skipped);
    }

    /**
     * @param  array<string, string|null>  $sourceRow
     * @return array<string, string|null>
     */
    private function lookup(array $sourceRow): array
    {
        $lookup = [];

        foreach ($sourceRow as $header => $value) {
            $lookup[$this->normalize($header)] = $value;
        }

        return $lookup;
    }

    /**
     * @param  array<string, string|null>  $lookup
     */
    private function valueFor(array $lookup, string $sourceColumn): ?string
    {
        return $lookup[$this->normalize($sourceColumn)] ?? null;
    }

    private function normalize(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    private function cast(?string $value, ColumnType $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            ColumnType::String, ColumnType::Text => $value,
            ColumnType::Integer => $this->integer($value),
            ColumnType::Decimal => $this->decimal($value),
            ColumnType::Boolean => $this->boolean($value),
            ColumnType::Date => $this->date($value),
            ColumnType::Datetime => $this->datetime($value),
        };
    }

    private function integer(string $value): int
    {
        if (preg_match('/^-?\d+$/', $value) !== 1) {
            throw new InvalidArgumentException('Not an integer.');
        }

        return (int) $value;
    }

    private function decimal(string $value): string
    {
        $normalized = str_replace(',', '.', $value);

        if (! is_numeric($normalized)) {
            throw new InvalidArgumentException('Not a decimal.');
        }

        return $normalized;
    }

    private function boolean(string $value): bool
    {
        return match (mb_strtolower($value)) {
            '1', 'true', 'yes', 'y' => true,
            '0', 'false', 'no', 'n' => false,
            default => throw new InvalidArgumentException('Not a boolean.'),
        };
    }

    private function date(string $value): string
    {
        try {
            return CarbonImmutable::parse($value)->toDateString();
        } catch (Throwable $exception) {
            throw new InvalidArgumentException('Not a date.', 0, $exception);
        }
    }

    private function datetime(string $value): string
    {
        try {
            return CarbonImmutable::parse($value)->toDateTimeString();
        } catch (Throwable $exception) {
            throw new InvalidArgumentException('Not a datetime.', 0, $exception);
        }
    }
}
