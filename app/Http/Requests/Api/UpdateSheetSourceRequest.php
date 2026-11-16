<?php

namespace App\Http\Requests\Api;

use App\Exceptions\InvalidSpreadsheetId;
use App\Models\SheetSource;
use App\Services\Sheets\SpreadsheetId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class UpdateSheetSourceRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->filled('spreadsheet_id')) {
            return;
        }

        try {
            $this->merge([
                'spreadsheet_id' => SpreadsheetId::from($this->string('spreadsheet_id')->toString()),
            ]);
        } catch (InvalidSpreadsheetId) {
            // Validated in withValidator.
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $source = $this->route('source');
        $sourceId = $source instanceof SheetSource ? $source->id : null;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'spreadsheet_id' => [
                'sometimes',
                'required',
                'string',
                'max:128',
                Rule::unique('sheet_sources', 'spreadsheet_id')->ignore($sourceId),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled('spreadsheet_id')) {
                return;
            }

            try {
                SpreadsheetId::from($this->string('spreadsheet_id')->toString());
            } catch (InvalidSpreadsheetId) {
                $validator->errors()->add('spreadsheet_id', 'Provide a spreadsheet id or Google Sheets URL.');
            }
        });
    }
}
