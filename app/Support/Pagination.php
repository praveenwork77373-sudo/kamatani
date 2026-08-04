<?php

namespace App\Support;

use Illuminate\Http\Request;

class Pagination
{
    public const DEFAULT_PER_PAGE = 10;

    /** @var list<int> */
    public const ALLOWED_PER_PAGE = [10, 20, 30, 50];

    /**
     * Resolve a safe per-page value from the request (server-side allow-list).
     * Invalid / missing / non-scalar values fall back to the default.
     */
    public static function perPage(?Request $request = null): int
    {
        $request ??= request();
        $raw = $request->input('per_page', self::DEFAULT_PER_PAGE);

        if (is_array($raw) || is_object($raw) || $raw === null || $raw === '') {
            return self::DEFAULT_PER_PAGE;
        }

        if (filter_var($raw, FILTER_VALIDATE_INT) === false) {
            return self::DEFAULT_PER_PAGE;
        }

        $perPage = (int) $raw;

        return in_array($perPage, self::ALLOWED_PER_PAGE, true)
            ? $perPage
            : self::DEFAULT_PER_PAGE;
    }
}
