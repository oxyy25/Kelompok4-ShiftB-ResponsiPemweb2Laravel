<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

abstract class ApiController extends Controller
{
    /** per_page dari query string, dibatasi 1..maks (default 10, maks 50). */
    protected function perPage(Request $request): int
    {
        $default = (int) config('sipinlab.per_page_default', 10);
        $maks = (int) config('sipinlab.per_page_maks', 50);

        return max(1, min($maks, (int) $request->query('per_page', $default)));
    }

    /**
     * Urutan dari ?sort=kolom atau ?sort=-kolom (menurun).
     * Kolom di luar daftar $diizinkan diabaikan dan memakai $default.
     */
    protected function urutkan(Builder $query, Request $request, array $diizinkan, string $default): Builder
    {
        $sort = (string) $request->query('sort', $default);
        $desc = str_starts_with($sort, '-');
        $kolom = ltrim($sort, '-');

        if (! in_array($kolom, $diizinkan, true)) {
            $desc = str_starts_with($default, '-');
            $kolom = ltrim($default, '-');
        }

        return $query->orderBy($kolom, $desc ? 'desc' : 'asc')->orderBy('id', 'desc');
    }
}
