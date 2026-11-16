<?php

namespace App\Http\Resources;

use App\Models\SheetTab;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SheetTab
 */
final class SheetTabResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sheet_source_id' => $this->sheet_source_id,
            'name' => $this->name,
            'header_row' => $this->header_row,
            'is_active' => $this->is_active,
            'mappings' => TableMappingResource::collection($this->whenLoaded('mappings')),
        ];
    }
}
