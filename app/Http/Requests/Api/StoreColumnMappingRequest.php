<?php

namespace App\Http\Requests\Api;

use App\Enums\ColumnType;
use App\Models\TableMapping;
use App\Rules\IdentifierName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreColumnMappingRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $mapping = $this->route('mapping');
        $mappingId = $mapping instanceof TableMapping ? $mapping->id : null;

        return [
            'source_column' => [
                'required',
                'string',
                'max:64',
                Rule::unique('column_mappings', 'source_column')->where(
                    fn ($query) => $query->where('table_mapping_id', $mappingId),
                ),
            ],
            'destination_column' => [
                'required',
                'string',
                'max:64',
                new IdentifierName,
                Rule::unique('column_mappings', 'destination_column')->where(
                    fn ($query) => $query->where('table_mapping_id', $mappingId),
                ),
            ],
            'data_type' => ['required', Rule::enum(ColumnType::class)],
        ];
    }
}
