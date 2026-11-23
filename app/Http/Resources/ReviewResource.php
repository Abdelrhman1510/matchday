<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
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
            'rating' => $this->rating,
            'comment' => $this->comment,
            'user' => $this->when($this->relationLoaded('user'), function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'profile_picture' => $this->avatarUrl($this->user->avatar),
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function avatarUrl($avatar): ?string
    {
        $path = is_array($avatar)
            ? ($avatar['medium'] ?? $avatar['original'] ?? $avatar['url'] ?? $avatar['thumbnail'] ?? null)
            : $avatar;

        if (!is_string($path) || $path === '') {
            return null;
        }

        return str_starts_with($path, 'http') ? $path : url('storage/' . ltrim($path, '/'));
    }
}
