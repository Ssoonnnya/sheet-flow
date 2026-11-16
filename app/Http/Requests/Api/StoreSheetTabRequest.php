<?php

namespace App\Http\Requests\Api;

use App\Models\SheetSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreSheetTabRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $source = $this->route('source');
        $sourceId = $source instanceof SheetSource ? $source->id : null;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sheet_tabs', 'name')->where(
                    fn ($query) => $query->where('sheet_source_id', $sourceId),
                ),
            ],
            'header_row' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
