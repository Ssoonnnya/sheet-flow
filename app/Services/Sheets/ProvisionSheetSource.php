<?php

namespace App\Services\Sheets;

use App\Models\ColumnMapping;
use App\Models\SheetSource;
use App\Models\SheetTab;
use App\Models\TableMapping;
use Illuminate\Support\Facades\DB;

final class ProvisionSheetSource
{
    public function __construct(
        private readonly DiscoverSheetStructure $discover,
    ) {}

    public function provision(string $spreadsheetIdOrUrl, ?string $name = null): SheetSource
    {
        $discovered = $this->discover->discover($spreadsheetIdOrUrl);

        return DB::transaction(function () use ($discovered, $name): SheetSource {
            $source = SheetSource::query()->updateOrCreate(
                ['spreadsheet_id' => $discovered->spreadsheetId],
                [
                    'name' => $name !== null && trim($name) !== '' ? trim($name) : $discovered->title,
                    'is_active' => true,
                ],
            );

            foreach ($discovered->tabs as $tabData) {
                $tab = SheetTab::query()->updateOrCreate(
                    [
                        'sheet_source_id' => $source->id,
                        'name' => $tabData->name,
                    ],
                    [
                        'header_row' => 1,
                        'is_active' => true,
                    ],
                );

                $mapping = TableMapping::query()->updateOrCreate(
                    [
                        'sheet_tab_id' => $tab->id,
                        'destination_table' => $tabData->destinationTable,
                    ],
                    [
                        'unique_columns' => $tabData->uniqueColumns,
                        'is_active' => true,
                    ],
                );

                foreach ($tabData->columns as $columnData) {
                    ColumnMapping::query()->updateOrCreate(
                        [
                            'table_mapping_id' => $mapping->id,
                            'source_column' => $columnData->sourceColumn,
                        ],
                        [
                            'destination_column' => $columnData->destinationColumn,
                            'data_type' => $columnData->dataType,
                        ],
                    );
                }
            }

            return $source->load(['tabs.mappings.columns']);
        });
    }
}
