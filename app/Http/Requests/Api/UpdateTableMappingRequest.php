<?php

namespace App\Http\Requests\Api;

use App\Models\SheetTab;
use App\Models\TableMapping;
use App\Rules\IdentifierName;
use App\Rules\WritableTableName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateTableMappingRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tab = $this->route('tab');
        $mapping = $this->route('mapping');

        $tabId = $tab instanceof SheetTab
            ? $tab->id
            : ($mapping instanceof TableMapping ? $mapping->sheet_tab_id : null);
        $mappingId = $mapping instanceof TableMapping ? $mapping->id : null;

        return [
            'destination_table' => [
                'sometimes',
                'required',
                'string',
                'max:64',
                new WritableTableName,
                Rule::unique('table_mappings', 'destination_table')
                    ->where(fn ($query) => $query->where('sheet_tab_id', $tabId))
                    ->ignore($mappingId),
            ],
            'unique_columns' => ['sometimes', 'required', 'array', 'min:1'],
            'unique_columns.*' => ['required', 'string', 'distinct', new IdentifierName],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
