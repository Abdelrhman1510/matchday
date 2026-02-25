<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Cafe;
use App\Models\GameMatch;
use App\Models\Offer;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $upcomingMatches = GameMatch::with(['homeTeam', 'awayTeam'])
            ->where('is_published', true)
            ->where('match_date', '>=', now()->toDateString())
            ->orderBy('match_date')
            ->limit(10)
            ->get()
            ->map(function ($match) {
                return [
                    'id' => $match->id,
                    'home_team' => $match->homeTeam->name ?? null,
                    'away_team' => $match->awayTeam->name ?? null,
                    'match_date' => $match->match_date?->format('Y-m-d'),
                ];
            });

        $featuredCafes = Cafe::where('is_featured', true)
            ->limit(10)
            ->get()
            ->map(function ($cafe) {
                return [
                    'id' => $cafe->id,
                    'name' => $cafe->name,
                    'logo' => $cafe->logo,
                ];
            });

        $activeOffers = Offer::where('status', 'active')
            ->limit(10)
            ->get()
            ->map(function ($offer) {
                return [
                    'id' => $offer->id,
                    'title' => $offer->title,
                    'discount' => $offer->discount ?? $offer->discount_value ?? $offer->discount_percent,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Home feed retrieved.',
            'data' => [
                'upcoming_matches' => $upcomingMatches,
                'featured_cafes' => $featuredCafes,
                'active_offers' => $activeOffers,
            ],
        ]);
    }

    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();

        $recommendedMatches = GameMatch::with(['homeTeam', 'awayTeam'])
            ->where('is_published', true)
            ->where('match_date', '>=', now()->toDateString())
            ->orderBy('match_date')
            ->limit(10)
            ->get()
            ->map(function ($match) {
                return [
                    'id' => $match->id,
                    'home_team' => $match->homeTeam->name ?? null,
                    'away_team' => $match->awayTeam->name ?? null,
                    'match_date' => $match->match_date?->format('Y-m-d'),
                ];
            });

        $nearbyCafes = Cafe::limit(10)->get()->map(function ($cafe) {
            return [
                'id' => $cafe->id,
                'name' => $cafe->name,
                'logo' => $cafe->logo,
            ];
        });

        $personalizedOffers = Offer::where('status', 'active')
            ->limit(10)
            ->get()
            ->map(function ($offer) {
                return [
                    'id' => $offer->id,
                    'title' => $offer->title,
                    'discount' => $offer->discount ?? $offer->discount_value,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Personalized feed retrieved.',
            'data' => [
                'recommended_matches' => $recommendedMatches,
                'nearby_cafes' => $nearbyCafes,
                'personalized_offers' => $personalizedOffers,
            ],
        ]);
    }

    public function trending(Request $request): JsonResponse
    {
        $trendingMatches = GameMatch::with(['homeTeam', 'awayTeam'])
            ->where('is_published', true)
            ->where('is_trending', true)
            ->limit(10)
            ->get()
            ->map(function ($match) {
                return [
                    'id' => $match->id,
                    'home_team' => $match->homeTeam->name ?? null,
                    'away_team' => $match->awayTeam->name ?? null,
                    'match_date' => $match->match_date?->format('Y-m-d'),
                ];
            });

        $trendingCafes = Cafe::orderByDesc('total_reviews')
            ->limit(10)
            ->get()
            ->map(function ($cafe) {
                return [
                    'id' => $cafe->id,
                    'name' => $cafe->name,
                    'logo' => $cafe->logo,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Trending content retrieved.',
            'data' => [
                'trending_matches' => $trendingMatches,
                'trending_cafes' => $trendingCafes,
            ],
        ]);
    }
}
