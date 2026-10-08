<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ApiResponse
{
    public static function success(mixed $data = null, string $message = 'Berhasil', int $status = 200): JsonResponse
    {
        if ($data instanceof JsonResource) {
            $data = $data->resolve(request());
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    public static function created(mixed $data = null, string $message = 'Data berhasil dibuat'): JsonResponse
    {
        return self::success($data, $message, 201);
    }

    /**
     * Response daftar berpagination: data + meta + links.
     *
     * @param class-string<JsonResource|ResourceCollection> $resourceClass
     */
    public static function paginated(LengthAwarePaginator $paginator, string $resourceClass, string $message = 'Berhasil'): JsonResponse
    {
        if (is_subclass_of($resourceClass, ResourceCollection::class)) {
            $items = array_values((new $resourceClass(collect($paginator->items())))->resolve(request()));
        } else {
            $items = collect($paginator->items())
                ->map(fn ($item) => (new $resourceClass($item))->resolve(request()))
                ->values()
                ->all();
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ]);
    }

    public static function error(string $message, int $status = 400, ?array $errors = null): JsonResponse
    {
        $body = [
            'success' => false,
            'message' => $message,
            'data' => null,
        ];

        if (! empty($errors)) {
            $body['errors'] = $errors;
        }

        return response()->json($body, $status);
    }
}
