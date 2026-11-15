<?php

namespace App\Models;

use App\Enums\ImportEntryStatus;
use Database\Factories\ImportRunEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $import_run_id
 * @property int|null $table_mapping_id
 * @property string|null $destination_table
 * @property int $rows_read
 * @property int $rows_written
 * @property int $rows_skipped
 * @property list<string>|null $columns_added
 * @property ImportEntryStatus $status
 * @property string|null $error
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'import_run_id',
    'table_mapping_id',
    'destination_table',
    'rows_read',
    'rows_written',
    'rows_skipped',
    'columns_added',
    'status',
    'error',
])]
class ImportRunEntry extends Model
{
    /** @use HasFactory<ImportRunEntryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rows_read' => 'integer',
            'rows_written' => 'integer',
            'rows_skipped' => 'integer',
            'columns_added' => 'array',
            'status' => ImportEntryStatus::class,
        ];
    }

    /**
     * @return BelongsTo<ImportRun, $this>
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(ImportRun::class, 'import_run_id');
    }

    /**
     * @return BelongsTo<TableMapping, $this>
     */
    public function mapping(): BelongsTo
    {
        return $this->belongsTo(TableMapping::class, 'table_mapping_id');
    }
}
