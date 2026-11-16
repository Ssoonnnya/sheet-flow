<?php

namespace App\Http\Resources;

use App\Models\SheetSource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SheetSource
 */
final class SheetSourceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'spreadsheet_id' => $this->spreadsheet_id,
            'is_active' => $this->is_active,
            'tabs' => SheetTabResource::collection($this->whenLoaded('tabs')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
