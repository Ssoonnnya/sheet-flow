<?php

namespace App\Services\Sheets;

use App\Contracts\SheetReader;
use App\Exceptions\InvalidIdentifier;
use App\Exceptions\ProtectedDestinationTable;
use InvalidArgumentException;

final class DiscoverSheetStructure
{
    public function __construct(
        private readonly SheetReader $reader,
        private readonly HeaderSlug $slugs,
        private readonly InferColumnType $types,
        private readonly Identifier $identifiers,
    ) {}

    public function discover(string $spreadsheetId, int $headerRow = 1): DiscoveredSpreadsheet
    {
        $spreadsheetId = SpreadsheetId::from($spreadsheetId);
        $title = $this->reader->spreadsheetTitle($spreadsheetId);
        $tabNames = $this->reader->tabNames($spreadsheetId);
        $takenTables = [];
        $tabs = [];

        foreach ($tabNames as $tabName) {
            $headers = $this->reader->headers($spreadsheetId, $tabName, $headerRow);

            if ($headers === []) {
                continue;
            }

            $rows = $this->reader->rows($spreadsheetId, $tabName, $headerRow);
            $destinationTable = $this->destinationTable($tabName, $takenTables);
            $takenTables[] = $destinationTable;

            $columns = $this->columns($headers, $rows);
            $uniqueColumns = array_map(
                fn (DiscoveredColumn $column): string => $column->destinationColumn,
                $columns,
            );

            if ($uniqueColumns === []) {
                continue;
            }

            /** @var non-empty-list<non-empty-string> $uniqueColumns */
            $tabs[] = new DiscoveredTab(
                name: $tabName,
                destinationTable: $destinationTable,
                columns: $columns,
                uniqueColumns: $uniqueColumns,
            );
        }

        if ($tabs === []) {
            throw new InvalidArgumentException(
                'No readable tabs with headers were found in this spreadsheet.',
            );
        }

        return new DiscoveredSpreadsheet(
            spreadsheetId: $spreadsheetId,
            title: $title,
            tabs: $tabs,
        );
    }

    /**
     * @param  list<string>  $headers
     * @param  list<array<string, string|null>>  $rows
     * @return list<DiscoveredColumn>
     */
    private function columns(array $headers, array $rows): array
    {
        $taken = [];
        $columns = [];

        foreach ($headers as $header) {
            $destination = $this->slugs->make($header, $taken);
            $taken[] = $destination;

            $samples = [];

            foreach (array_slice($rows, 0, 20) as $row) {
                $samples[] = $row[$header] ?? null;
            }

            $columns[] = new DiscoveredColumn(
                sourceColumn: $header,
                destinationColumn: $destination,
                dataType: $this->types->infer($samples),
            );
        }

        return $columns;
    }

    /**
     * @param  list<string>  $taken
     * @return non-empty-string
     */
    private function destinationTable(string $tabName, array $taken): string
    {
        $candidate = $this->slugs->make($tabName, $taken);
        $suffix = 2;

        while (true) {
            try {
                return $this->identifiers->assertWritableTable($candidate);
            } catch (ProtectedDestinationTable|InvalidIdentifier) {
                $candidate = $this->slugs->make($tabName.'_'.$suffix, $taken);
                $suffix++;
            }
        }
    }
}
