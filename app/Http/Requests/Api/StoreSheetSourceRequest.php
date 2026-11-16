<?php

namespace App\Http\Requests\Api;

use App\Exceptions\InvalidSpreadsheetId;
use App\Services\Sheets\SpreadsheetId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreSheetSourceRequest extends FormRequest
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
        return [
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'spreadsheet_id' => ['required', 'string', 'max:128', Rule::unique('sheet_sources', 'spreadsheet_id')],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $value = $this->string('spreadsheet_id')->toString();

            if ($value === '') {
                return;
            }

            try {
                SpreadsheetId::from($value);
            } catch (InvalidSpreadsheetId) {
                $validator->errors()->add('spreadsheet_id', 'Provide a spreadsheet id or Google Sheets URL.');
            }
        });
    }
}
