<?php

namespace App\Support;

use Illuminate\Http\Request;

class AdminPagination
{
    public const OPTIONS = [10, 25, 50, 100];

    public const DEFAULT = 25;

    public const MAX = 100;

    public static function resolve(Request $request, ?int $default = null): int
    {
        $default = $default ?? self::DEFAULT;
        $perPage = (int) $request->input('per_page', $default);

        return max(1, min(self::MAX, $perPage));
    }
}
