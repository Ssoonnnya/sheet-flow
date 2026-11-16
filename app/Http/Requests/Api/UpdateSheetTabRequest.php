<?php

namespace App\Http\Requests\Api;

use App\Models\SheetSource;
use App\Models\SheetTab;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateSheetTabRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $source = $this->route('source');
        $tab = $this->route('tab');

        $sourceId = $source instanceof SheetSource
            ? $source->id
            : ($tab instanceof SheetTab ? $tab->sheet_source_id : null);
        $tabId = $tab instanceof SheetTab ? $tab->id : null;

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('sheet_tabs', 'name')
                    ->where(fn ($query) => $query->where('sheet_source_id', $sourceId))
                    ->ignore($tabId),
            ],
            'header_row' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
