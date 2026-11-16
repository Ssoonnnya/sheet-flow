<?php

namespace App\Services\Sheets;

use App\Exceptions\InvalidIdentifier;

final class HeaderSlug
{
    public function __construct(
        private readonly Identifier $identifiers,
    ) {}

    /**
     * @param  list<string>  $taken
     * @return non-empty-string
     */
    public function make(string $header, array $taken = []): string
    {
        $base = strtolower(trim($header));
        $base = preg_replace('/[^a-z0-9]+/', '_', $base) ?? '';
        $base = trim($base, '_');

        if ($base === '' || ! preg_match('/^[a-z]/', $base)) {
            $base = 'col_'.$base;
            $base = trim($base, '_');
        }

        if ($base === '') {
            $base = 'col';
        }

        $candidate = $base;
        $suffix = 2;

        while (true) {
            try {
                $valid = $this->identifiers->assertValid($candidate);
            } catch (InvalidIdentifier) {
                $candidate = 'col_'.$suffix;
                $suffix++;

                continue;
            }

            if (! in_array($valid, $taken, true)) {
                return $valid;
            }

            $candidate = $base.'_'.$suffix;
            $suffix++;
        }
    }
}
