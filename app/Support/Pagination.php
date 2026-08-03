<?php

namespace App\Support;

use Illuminate\Http\Request;

class Pagination
{
    public const DEFAULT_PER_PAGE = 10;

    /** @var list<int> */
    public const ALLOWED_PER_PAGE = [10, 20, 30, 50];

    public static function perPage(?Request $request = null): int
    {
        $request ??= request();
        $perPage = (int) $request->input('per_page', self::DEFAULT_PER_PAGE);

        return in_array($perPage, self::ALLOWED_PER_PAGE, true)
            ? $perPage
            : self::DEFAULT_PER_PAGE;
    }
}
