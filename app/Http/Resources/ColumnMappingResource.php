<?php

namespace App\Http\Resources;

use App\Models\ColumnMapping;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ColumnMapping
 */
final class ColumnMappingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'table_mapping_id' => $this->table_mapping_id,
            'source_column' => $this->source_column,
            'destination_column' => $this->destination_column,
            'data_type' => $this->data_type->value,
        ];
    }
}
