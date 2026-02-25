<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Offer::with('cafe')->where('status', 'active');

        // Filter out expired offers
        $query->where(function ($q) {
            $q->whereNull('end_date')
              ->orWhere('end_date', '>=', now()->toDateString());
        });

        // Filter by featured
        if ($request->has('featured') && $request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        // Filter by cafe_id (through branch)
        if ($request->has('cafe_id')) {
            $cafeId = $request->query('cafe_id');
            $branchIds = Branch::where('cafe_id', $cafeId)->pluck('id');
            $query->where(function ($q) use ($cafeId, $branchIds) {
                $q->where('cafe_id', $cafeId)
                  ->orWhereIn('branch_id', $branchIds);
            });
        }

        // Search by title
        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where('title', 'like', '%' . $search . '%');
        }

        // Pagination
        $perPage = $request->query('per_page');
        if ($perPage) {
            $perPage = max(1, min(100, (int) $perPage));
            $offers = $query->orderByDesc('is_featured')->orderByDesc('created_at')->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Offers retrieved.',
                'data' => [
                    'data' => $offers->map(function ($offer) {
                        return $this->formatOffer($offer);
                    }),
                    'current_page' => $offers->currentPage(),
                    'per_page' => $offers->perPage(),
                    'total' => $offers->total(),
                    'last_page' => $offers->lastPage(),
                ],
            ]);
        }

        $offers = $query->orderByDesc('is_featured')->orderByDesc('created_at')->get();

        return response()->json([
            'success' => true,
            'message' => 'Offers retrieved.',
            'data' => $offers->map(function ($offer) {
                return $this->formatOffer($offer);
            }),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $offer = Offer::with('cafe')->find($id);

        if (!$offer) {
            return response()->json([
                'success' => false,
                'message' => 'Offer not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Offer retrieved.',
            'data' => [
                'id' => $offer->id,
                'title' => $offer->title,
                'description' => $offer->description,
                'discount' => $offer->discount ?? $offer->discount_value ?? $offer->discount_percent,
                'terms' => $offer->terms,
                'start_date' => $offer->start_date?->format('Y-m-d'),
                'end_date' => $offer->end_date?->format('Y-m-d'),
                'cafe' => $offer->cafe ? [
                    'id' => $offer->cafe->id,
                    'name' => $offer->cafe->name,
                ] : null,
            ],
        ]);
    }

    private function formatOffer(Offer $offer): array
    {
        return [
            'id' => $offer->id,
            'title' => $offer->title,
            'description' => $offer->description,
            'discount' => $offer->discount ?? $offer->discount_value ?? $offer->discount_percent,
            'cafe' => $offer->cafe ? [
                'id' => $offer->cafe->id,
                'name' => $offer->cafe->name,
            ] : null,
        ];
    }
}
