<?php

namespace App\Services\Sheets;

use App\Enums\ColumnType;
use Carbon\CarbonImmutable;
use Throwable;

final class InferColumnType
{
    /**
     * @param  list<string|null>  $samples
     */
    public function infer(array $samples): ColumnType
    {
        $values = array_values(array_filter(
            $samples,
            fn (?string $value): bool => $value !== null && trim($value) !== '',
        ));

        if ($values === []) {
            return ColumnType::String;
        }

        if ($this->all($values, fn (string $value): bool => $this->isBoolean($value))) {
            return ColumnType::Boolean;
        }

        if ($this->all($values, fn (string $value): bool => preg_match('/^-?\d+$/', $value) === 1)) {
            return ColumnType::Integer;
        }

        if ($this->all($values, fn (string $value): bool => is_numeric(str_replace(',', '.', $value)))) {
            return ColumnType::Decimal;
        }

        if ($this->all($values, fn (string $value): bool => $this->isDate($value))) {
            return $this->all($values, fn (string $value): bool => $this->hasTime($value))
                ? ColumnType::Datetime
                : ColumnType::Date;
        }

        return ColumnType::String;
    }

    /**
     * @param  list<string>  $values
     * @param  callable(string): bool  $predicate
     */
    private function all(array $values, callable $predicate): bool
    {
        foreach ($values as $value) {
            if (! $predicate($value)) {
                return false;
            }
        }

        return true;
    }

    private function isBoolean(string $value): bool
    {
        return in_array(mb_strtolower($value), ['1', '0', 'true', 'false', 'yes', 'no', 'y', 'n'], true);
    }

    private function isDate(string $value): bool
    {
        try {
            CarbonImmutable::parse($value);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function hasTime(string $value): bool
    {
        return preg_match('/\d{1,2}:\d{2}/', $value) === 1;
    }
}
