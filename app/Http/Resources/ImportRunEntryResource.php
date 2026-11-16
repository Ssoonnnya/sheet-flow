<?php

namespace App\Http\Resources;

use App\Models\ImportRunEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ImportRunEntry
 */
final class ImportRunEntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'table_mapping_id' => $this->table_mapping_id,
            'destination_table' => $this->destination_table,
            'rows_read' => $this->rows_read,
            'rows_written' => $this->rows_written,
            'rows_skipped' => $this->rows_skipped,
            'columns_added' => $this->columns_added,
            'status' => $this->status->value,
            'error' => $this->error,
        ];
    }
}
