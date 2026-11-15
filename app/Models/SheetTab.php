<?php

namespace App\Models;

use Database\Factories\SheetTabFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $sheet_source_id
 * @property string $name
 * @property int $header_row
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['sheet_source_id', 'name', 'header_row', 'is_active'])]
class SheetTab extends Model
{
    /** @use HasFactory<SheetTabFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'header_row' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<SheetSource, $this>
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(SheetSource::class, 'sheet_source_id');
    }

    /**
     * @return HasMany<TableMapping, $this>
     */
    public function mappings(): HasMany
    {
        return $this->hasMany(TableMapping::class);
    }
}
