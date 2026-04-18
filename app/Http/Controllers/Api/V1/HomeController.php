<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Cafe;
use App\Models\GameMatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $banners = Banner::active()
            ->orderBy('sort_order')
            ->limit(5)
            ->get()
            ->map(fn($b) => [
                'id'          => $b->id,
                'title'       => $b->title,
                'subtitle'    => $b->subtitle,
                'image_url'   => $b->image_url,
                'action_type' => $b->action_type,
                'action_id'   => $b->action_id,
                'action_url'  => $b->action_url,
            ]);

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

        $lat = $request->query('lat');
        $lng = $request->query('lng');

        if ($lat && $lng) {
            $nearbyCafes = Cafe::select('cafes.id', 'cafes.name', 'cafes.logo')
                ->join('branches', 'branches.cafe_id', '=', 'cafes.id')
                ->selectRaw('MIN(( 6371 * acos( cos(radians(?)) * cos(radians(branches.latitude)) * cos(radians(branches.longitude) - radians(?)) + sin(radians(?)) * sin(radians(branches.latitude)) ) )) AS distance', [$lat, $lng, $lat])
                ->groupBy('cafes.id', 'cafes.name', 'cafes.logo')
                ->orderBy('distance')
                ->limit(10)
                ->get()
                ->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'logo' => $c->logo, 'distance_km' => round($c->distance, 2)]);
        } else {
            $nearbyCafes = Cafe::select('id', 'name', 'logo')->limit(10)->get()
                ->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'logo' => $c->logo, 'distance_km' => null]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Home feed retrieved.',
            'data' => [
                'banners'          => $banners,
                'upcoming_matches' => $upcomingMatches,
                'nearby_cafes'     => $nearbyCafes,
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
