<?php

namespace App\Models;

use App\Enums\ImportRunStatus;
use App\Enums\ImportTrigger;
use Database\Factories\ImportRunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property ImportRunStatus $status
 * @property ImportTrigger $trigger
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property string|null $message
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['status', 'trigger', 'started_at', 'finished_at', 'message'])]
class ImportRun extends Model
{
    /** @use HasFactory<ImportRunFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ImportRunStatus::class,
            'trigger' => ImportTrigger::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<ImportRunEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(ImportRunEntry::class);
    }
}
