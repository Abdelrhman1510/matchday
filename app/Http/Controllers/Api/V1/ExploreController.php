<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ExploreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExploreController extends Controller
{
    public function __construct(private ExploreService $exploreService) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $bearerToken = $request->bearerToken();
        $user = null;
        if ($bearerToken) {
            $token = \Laravel\Sanctum\PersonalAccessToken::findToken($bearerToken);
            $user = $token?->tokenable;
        }
        $lat  = $request->filled('lat') ? (float) $request->lat : null;
        $lng  = $request->filled('lng') ? (float) $request->lng : null;

        $data = $this->exploreService->getExploreData($user, $lat, $lng);
        unset($data['active_offers']);

        return response()->json([
            'success' => true,
            'message' => 'Explore data retrieved.',
            '_debug_user_id' => $user?->id,
            'data'    => $data,
        ]);
    }
}
