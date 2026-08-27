<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\CatalogFacetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function __construct(
        protected CatalogFacetService $facets
    ) {
    }

    /**
     * Filter metadata for the catalog sidebar. Takes the same filters as
     * /products so the numbers describe the view the customer is looking at.
     */
    public function facets(Request $request): JsonResponse
    {
        $request->validate([
            'min_price' => ['sometimes', 'numeric', 'min:0'],
            'max_price' => ['sometimes', 'numeric', 'min:0'],
        ]);

        $filters = $request->only([
            'search',
            'category_id',
            'featured',
            'in_stock',
            'min_price',
            'max_price',
        ]);

        return ApiResponse::raw(
            $this->facets->build($filters),
            'Catalog facets fetched successfully.'
        );
    }
}
