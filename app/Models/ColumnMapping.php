<?php

namespace App\Models;

use App\Enums\ColumnType;
use Database\Factories\ColumnMappingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $table_mapping_id
 * @property string $source_column
 * @property string $destination_column
 * @property ColumnType $data_type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['table_mapping_id', 'source_column', 'destination_column', 'data_type'])]
class ColumnMapping extends Model
{
    /** @use HasFactory<ColumnMappingFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data_type' => ColumnType::class,
        ];
    }

    /**
     * @return BelongsTo<TableMapping, $this>
     */
    public function mapping(): BelongsTo
    {
        return $this->belongsTo(TableMapping::class, 'table_mapping_id');
    }
}
