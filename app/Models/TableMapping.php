<?php

namespace App\Models;

use Database\Factories\TableMappingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $sheet_tab_id
 * @property string $destination_table
 * @property list<string> $unique_columns
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['sheet_tab_id', 'destination_table', 'unique_columns', 'is_active'])]
class TableMapping extends Model
{
    /** @use HasFactory<TableMappingFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unique_columns' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<SheetTab, $this>
     */
    public function tab(): BelongsTo
    {
        return $this->belongsTo(SheetTab::class, 'sheet_tab_id');
    }

    /**
     * @return HasMany<ColumnMapping, $this>
     */
    public function columns(): HasMany
    {
        return $this->hasMany(ColumnMapping::class);
    }
}
