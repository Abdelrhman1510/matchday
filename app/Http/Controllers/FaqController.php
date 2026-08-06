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
        // BUG-084: Fetch Arabic columns as well so toLocalizedArray() can pick
        // the right language for this request's locale (set by SetLocale middleware).
        $faqs = Faq::active()
            ->ordered()
            ->get(['id', 'question', 'question_ar', 'answer', 'answer_ar', 'category', 'sort_order']);

        return $this->successResponse([
            'faqs' => $faqs->map(fn (Faq $faq) => $faq->toLocalizedArray()),
        ], 'FAQs retrieved successfully.');
    }
}
