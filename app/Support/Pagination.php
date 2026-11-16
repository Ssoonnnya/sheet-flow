<?php

namespace App\Support;

use Illuminate\Http\Request;

final class Pagination
{
    /** @var list<int> */
    public const ALLOWED_PER_PAGE = [15, 25, 50];

    public const DEFAULT_PER_PAGE = 15;

    public static function perPage(Request $request): int
    {
        $perPage = (int) $request->integer('per_page', self::DEFAULT_PER_PAGE);

        if (! in_array($perPage, self::ALLOWED_PER_PAGE, true)) {
            return self::DEFAULT_PER_PAGE;
        }

        return $perPage;
    }
}
