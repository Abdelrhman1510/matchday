<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class PageController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/pages/{slug}
     * Get a page by its slug.
     */
    public function show(string $slug): JsonResponse
    {
        $page = Page::active()->bySlug($slug)->first();

        if (!$page) {
            return $this->errorResponse('Page not found.', 404);
        }

        return $this->successResponse([
            'page' => [
                'id' => $page->id,
                'slug' => $page->slug,
                'title' => $page->title,
                'content' => $page->content,
                'updated_at' => $page->updated_at,
            ],
        ], 'Page retrieved successfully.');
    }
}
