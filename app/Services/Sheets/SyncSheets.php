<?php

namespace App\Services\Sheets;

use App\Contracts\SheetReader;
use App\Enums\ImportEntryStatus;
use App\Enums\ImportRunStatus;
use App\Models\ImportRun;
use App\Models\ImportRunEntry;
use App\Models\SheetSource;
use App\Models\TableMapping;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SyncSheets
{
    public function __construct(
        private readonly SheetReader $reader,
        private readonly EnsureDestinationSchema $schema,
        private readonly MapSheetRows $mapper,
        private readonly UpsertMappedRows $writer,
    ) {}

    public function process(ImportRun $run): ImportRun
    {
        $run->forceFill([
            'status' => ImportRunStatus::Running,
            'started_at' => now(),
            'finished_at' => null,
            'message' => null,
        ])->save();

        $failed = false;

        $sources = SheetSource::query()
            ->where('is_active', true)
            ->with([
                'tabs' => fn ($query) => $query->where('is_active', true),
                'tabs.mappings' => fn ($query) => $query->where('is_active', true),
                'tabs.mappings.columns',
            ])
            ->get();

        foreach ($sources as $source) {
            foreach ($source->tabs as $tab) {
                foreach ($tab->mappings as $mapping) {
                    if (! $this->importMapping($run, $source, $tab->name, $tab->header_row, $mapping)) {
                        $failed = true;
                    }
                }
            }
        }

        $run->forceFill([
            'status' => $failed ? ImportRunStatus::Failed : ImportRunStatus::Completed,
            'finished_at' => now(),
            'message' => $failed ? 'One or more mappings failed.' : null,
        ])->save();

        return $run->refresh()->load('entries');
    }

    private function importMapping(
        ImportRun $run,
        SheetSource $source,
        string $tabName,
        int $headerRow,
        TableMapping $mapping,
    ): bool {
        $entry = ImportRunEntry::query()->create([
            'import_run_id' => $run->id,
            'table_mapping_id' => $mapping->id,
            'destination_table' => $mapping->destination_table,
            'rows_read' => 0,
            'rows_written' => 0,
            'rows_skipped' => 0,
            'columns_added' => null,
            'status' => ImportEntryStatus::Completed,
            'error' => null,
        ]);

        try {
            $sourceRows = $this->reader->rows(
                $source->spreadsheet_id,
                $tabName,
                $headerRow,
            );

            $columnsAdded = $this->schema->ensure($mapping);
            $mapped = $this->mapper->map($mapping, $sourceRows);
            $written = $this->writer->upsert($mapping, $mapped->rows);

            $entry->forceFill([
                'rows_read' => count($sourceRows),
                'rows_written' => $written,
                'rows_skipped' => $mapped->skipped,
                'columns_added' => $columnsAdded === [] ? null : $columnsAdded,
                'status' => ImportEntryStatus::Completed,
            ])->save();

            return true;
        } catch (Throwable $exception) {
            Log::warning('Sheet mapping import failed.', [
                'import_run_id' => $run->id,
                'table_mapping_id' => $mapping->id,
                'destination_table' => $mapping->destination_table,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            $entry->forceFill([
                'status' => ImportEntryStatus::Failed,
                'error' => $exception->getMessage(),
            ])->save();

            return false;
        }
    }
}
