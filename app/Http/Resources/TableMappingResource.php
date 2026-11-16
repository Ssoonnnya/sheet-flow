<?php

namespace App\Http\Resources;

use App\Models\TableMapping;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TableMapping
 */
final class TableMappingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sheet_tab_id' => $this->sheet_tab_id,
            'destination_table' => $this->destination_table,
            'unique_columns' => $this->unique_columns,
            'is_active' => $this->is_active,
            'columns' => ColumnMappingResource::collection($this->whenLoaded('columns')),
        ];
    }
}
