<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CafeAdminResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'owner_id' => $this->owner_id,
            'name' => $this->name,
            'description' => $this->description,
            'phone' => $this->phone,
            'city' => $this->city,
            'logo' => $this->formatLogo($this->logo),
            'is_premium' => $this->is_premium,
            'subscription_plan' => $this->subscription_plan,
            'avg_rating' => (float) $this->avg_rating ?? 0,
            'total_reviews' => $this->total_reviews ?? 0,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // UI Helper Flags
            'can_add_branch' => !$this->is_premium || ($this->branches()->count() < 10),
            'has_active_subscription' => $this->is_premium,
            'profile_complete' => $this->isProfileComplete(),
            'total_branches' => $this->branches()->count(),
            
            // Relationships (when loaded)
            'branches' => BranchListResource::collection($this->whenLoaded('branches')),
            'owner' => new UserBasicResource($this->whenLoaded('owner')),
        ];
    }

    /**
     * Format logo to multi-size structure
     */
    private function formatLogo($logo): ?array
    {
        if (!$logo) {
            return null;
        }

        // If already an array (multi-size)
        if (is_array($logo)) {
            return [
                'original' => $this->getFullUrl($logo['original'] ?? null),
                'medium' => $this->getFullUrl($logo['medium'] ?? null),
                'thumbnail' => $this->getFullUrl($logo['thumbnail'] ?? null),
            ];
        }

        // Fallback for single path
        $url = $this->getFullUrl($logo);
        return [
            'original' => $url,
            'medium' => $url,
            'thumbnail' => $url,
        ];
    }

    /**
     * Get full URL for storage path
     */
    private function getFullUrl(?string $path): ?string
    {
        return $path ? Storage::disk('public')->url($path) : null;
    }

    /**
     * Check if profile is complete
     */
    private function isProfileComplete(): bool
    {
        return !empty($this->name) &&
               !empty($this->description) &&
               !empty($this->phone) &&
               !empty($this->city);
    }
}
