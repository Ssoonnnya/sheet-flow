<?php

namespace App\Http\Requests\Api;

use App\Models\SheetTab;
use App\Rules\IdentifierName;
use App\Rules\WritableTableName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreTableMappingRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tab = $this->route('tab');
        $tabId = $tab instanceof SheetTab ? $tab->id : null;

        return [
            'destination_table' => [
                'required',
                'string',
                'max:64',
                new WritableTableName,
                Rule::unique('table_mappings', 'destination_table')->where(
                    fn ($query) => $query->where('sheet_tab_id', $tabId),
                ),
            ],
            'unique_columns' => ['required', 'array', 'min:1'],
            'unique_columns.*' => ['required', 'string', 'distinct', new IdentifierName],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
