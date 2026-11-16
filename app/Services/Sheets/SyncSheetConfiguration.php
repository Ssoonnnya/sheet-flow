<?php

namespace App\Services\Sheets;

use App\Contracts\SheetReader;
use App\Models\ColumnMapping;
use App\Models\SheetSource;
use App\Models\SheetTab;
use App\Models\TableMapping;

final class SyncSheetConfiguration
{
    public function __construct(
        private readonly SheetReader $reader,
        private readonly HeaderSlug $slugs,
        private readonly InferColumnType $types,
    ) {}

    /**
     * Add column mappings for headers that appeared in the sheet but are not configured yet.
     * Does not rename or remove existing mappings.
     *
     * @return list<string> newly added destination columns
     */
    public function syncNewColumns(SheetSource $source, SheetTab $tab, TableMapping $mapping): array
    {
        $headers = $this->reader->headers(
            $source->spreadsheet_id,
            $tab->name,
            $tab->header_row,
        );

        if ($headers === []) {
            return [];
        }

        $rows = $this->reader->rows(
            $source->spreadsheet_id,
            $tab->name,
            $tab->header_row,
        );

        $existingSources = $mapping->columns
            ->map(fn (ColumnMapping $column): string => mb_strtolower(trim($column->source_column)))
            ->all();

        /** @var list<string> $takenDestinations */
        $takenDestinations = array_values($mapping->columns
            ->map(fn (ColumnMapping $column): string => $column->destination_column)
            ->all());

        $added = [];

        foreach ($headers as $header) {
            $normalized = mb_strtolower(trim($header));

            if ($normalized === '' || in_array($normalized, $existingSources, true)) {
                continue;
            }

            $destination = $this->slugs->make($header, $takenDestinations);
            $takenDestinations[] = $destination;

            $samples = [];

            foreach (array_slice($rows, 0, 20) as $row) {
                $samples[] = $row[$header] ?? null;
            }

            ColumnMapping::query()->create([
                'table_mapping_id' => $mapping->id,
                'source_column' => $header,
                'destination_column' => $destination,
                'data_type' => $this->types->infer($samples),
            ]);

            $added[] = $destination;
        }

        if ($added !== []) {
            $mapping->load('columns');

            $unique = $mapping->unique_columns;

            foreach ($added as $column) {
                if (! in_array($column, $unique, true)) {
                    $unique[] = $column;
                }
            }

            $mapping->forceFill(['unique_columns' => $unique])->save();
        }

        return $added;
    }
}
