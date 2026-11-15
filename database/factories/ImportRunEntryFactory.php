<?php

namespace Database\Factories;

use App\Enums\ImportEntryStatus;
use App\Models\ImportRun;
use App\Models\ImportRunEntry;
use App\Models\TableMapping;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportRunEntry>
 */
class ImportRunEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'import_run_id' => ImportRun::factory(),
            'table_mapping_id' => TableMapping::factory(),
            'destination_table' => 'sales',
            'rows_read' => 0,
            'rows_written' => 0,
            'rows_skipped' => 0,
            'columns_added' => null,
            'status' => ImportEntryStatus::Completed,
            'error' => null,
        ];
    }
}
