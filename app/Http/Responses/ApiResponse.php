<?php

namespace App\Http\Responses;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * Every endpoint answers with the same envelope:
 *
 *   { "message": string, "data": object|array|null, "meta"?: object }
 *
 * `meta` is present only on paginated collections and is deliberately kept to
 * the four keys a client actually needs to render pagination.
 */
final class ApiResponse
{
    /**
     * A single resource.
     */
    public static function item(JsonResource $resource, string $message = '', int $status = 200): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $resource->resolve(),
        ], $status);
    }

    /**
     * A full, unpaginated list.
     *
     * @param  class-string<JsonResource>  $resourceClass
     * @param  Collection<int, mixed>|iterable<int, mixed>  $items
     */
    public static function collection(iterable $items, string $resourceClass, string $message = ''): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $resourceClass::collection($items)->resolve(),
        ]);
    }

    /**
     * A paginated list, flattened so `data` is always a plain array.
     *
     * @param  class-string<JsonResource>  $resourceClass
     */
    public static function paginated(LengthAwarePaginator $paginator, string $resourceClass, string $message = ''): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $resourceClass::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * An acknowledgement with no payload (deletes, logout, and similar).
     */
    public static function message(string $message, int $status = 200): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => null,
        ], $status);
    }

    /**
     * A payload that is not backed by a resource class, such as the dashboard
     * aggregate which is assembled from several sources.
     *
     * @param  array<string, mixed>  $data
     */
    public static function raw(array $data, string $message = '', int $status = 200): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
        ], $status);
    }
}
