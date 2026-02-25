<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class FaqController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/faqs
     * List all active FAQs ordered by sort_order.
     */
    public function index(): JsonResponse
    {
        $faqs = Faq::active()
            ->ordered()
            ->get(['id', 'question', 'answer', 'category', 'sort_order']);

        return $this->successResponse([
            'faqs' => $faqs,
        ], 'FAQs retrieved successfully.');
    }
}
