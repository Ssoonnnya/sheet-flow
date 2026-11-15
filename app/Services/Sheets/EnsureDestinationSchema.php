<?php

namespace App\Services\Sheets;

use App\Enums\ColumnType;
use App\Models\ColumnMapping;
use App\Models\TableMapping;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

final class EnsureDestinationSchema
{
    public function __construct(
        private readonly Identifier $identifiers,
    ) {}

    /**
     * @return list<string>
     */
    public function ensure(TableMapping $mapping): array
    {
        $table = $this->identifiers->assertWritableTable($mapping->destination_table);
        $uniqueColumns = $this->uniqueColumns($mapping);
        $columns = $this->columns($mapping);

        if ($columns === []) {
            throw new InvalidArgumentException("Mapping [{$mapping->id}] has no column mappings.");
        }

        if (! Schema::hasTable($table)) {
            $this->createTable($table, $columns, $uniqueColumns);

            return array_keys($columns);
        }

        return $this->addMissingColumns($table, $columns);
    }

    /**
     * @return non-empty-list<non-empty-string>
     */
    private function uniqueColumns(TableMapping $mapping): array
    {
        try {
            return $this->identifiers->uniqueKeys($mapping->unique_columns);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException(
                "Mapping [{$mapping->id}] requires unique_columns for upsert.",
                previous: $exception,
            );
        }
    }

    /**
     * @return array<string, ColumnType>
     */
    private function columns(TableMapping $mapping): array
    {
        $columns = [];

        foreach ($mapping->columns as $column) {
            /** @var ColumnMapping $column */
            $name = $this->identifiers->assertValid($column->destination_column);
            $columns[$name] = $column->data_type;
        }

        return $columns;
    }

    /**
     * @param  array<string, ColumnType>  $columns
     * @param  list<string>  $uniqueColumns
     */
    private function createTable(string $table, array $columns, array $uniqueColumns): void
    {
        foreach ($uniqueColumns as $column) {
            if (! array_key_exists($column, $columns)) {
                throw new InvalidArgumentException(
                    "Unique column [{$column}] is not present in the column mappings for [{$table}].",
                );
            }
        }

        Schema::create($table, function (Blueprint $blueprint) use ($table, $columns, $uniqueColumns): void {
            $blueprint->id();

            foreach ($columns as $name => $type) {
                $this->addColumn($blueprint, $name, $type);
            }

            $blueprint->unique($uniqueColumns, $this->uniqueIndexName($table, $uniqueColumns));
        });
    }

    /**
     * @param  array<string, ColumnType>  $columns
     * @return list<string>
     */
    private function addMissingColumns(string $table, array $columns): array
    {
        $existing = array_map('strtolower', Schema::getColumnListing($table));
        $added = [];

        foreach ($columns as $name => $type) {
            if (in_array(strtolower($name), $existing, true)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($name, $type): void {
                $this->addColumn($blueprint, $name, $type);
            });

            $added[] = $name;
        }

        return $added;
    }

    private function addColumn(Blueprint $blueprint, string $name, ColumnType $type): void
    {
        $column = match ($type) {
            ColumnType::String => $blueprint->string($name),
            ColumnType::Text => $blueprint->text($name),
            ColumnType::Integer => $blueprint->integer($name),
            ColumnType::Decimal => $blueprint->decimal($name, 20, 6),
            ColumnType::Boolean => $blueprint->boolean($name),
            ColumnType::Date => $blueprint->date($name),
            ColumnType::Datetime => $blueprint->dateTime($name),
        };

        $column->nullable();
    }

    /**
     * @param  list<string>  $uniqueColumns
     */
    private function uniqueIndexName(string $table, array $uniqueColumns): string
    {
        $name = 'uniq_'.$table.'_'.implode('_', $uniqueColumns);

        return substr($name, 0, 64);
    }
}
