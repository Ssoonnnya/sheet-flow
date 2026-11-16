<?php

namespace App\Rules;

use App\Exceptions\InvalidIdentifier;
use App\Exceptions\ProtectedDestinationTable;
use App\Services\Sheets\Identifier;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class WritableTableName implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a string.');

            return;
        }

        try {
            app(Identifier::class)->assertWritableTable($value);
        } catch (InvalidIdentifier) {
            $fail('The :attribute must be a lowercase identifier like sales or product_price.');
        } catch (ProtectedDestinationTable) {
            $fail('The :attribute is a protected system table.');
        }
    }
}
