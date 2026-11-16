<?php

namespace App\Http\Requests\Api;

use App\Enums\ColumnType;
use App\Models\ColumnMapping;
use App\Models\TableMapping;
use App\Rules\IdentifierName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateColumnMappingRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $mapping = $this->route('mapping');
        $column = $this->route('column');

        $mappingId = $mapping instanceof TableMapping
            ? $mapping->id
            : ($column instanceof ColumnMapping ? $column->table_mapping_id : null);
        $columnId = $column instanceof ColumnMapping ? $column->id : null;

        return [
            'source_column' => [
                'sometimes',
                'required',
                'string',
                'max:64',
                Rule::unique('column_mappings', 'source_column')
                    ->where(fn ($query) => $query->where('table_mapping_id', $mappingId))
                    ->ignore($columnId),
            ],
            'destination_column' => [
                'sometimes',
                'required',
                'string',
                'max:64',
                new IdentifierName,
                Rule::unique('column_mappings', 'destination_column')
                    ->where(fn ($query) => $query->where('table_mapping_id', $mappingId))
                    ->ignore($columnId),
            ],
            'data_type' => ['sometimes', 'required', Rule::enum(ColumnType::class)],
        ];
    }
}
