<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatMessageResource extends JsonResource
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
            'message' => $this->message,
            'type' => $this->type,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'avatar' => $this->user->avatar,
                'favorite_team' => $this->when(
                    $this->user->fanProfile && $this->user->fanProfile->favoriteTeam,
                    function () {
                        return [
                            'id' => $this->user->fanProfile->favoriteTeam->id,
                            'name' => $this->user->fanProfile->favoriteTeam->name,
                            'logo' => $this->user->fanProfile->favoriteTeam->logo,
                        ];
                    }
                ),
            ],
            'created_at' => $this->created_at->toIso8601String(),
            
            // UI helper flags
            'is_own_message' => $this->user_id === $request->user()?->id,
            'is_emoji' => $this->type === 'emoji',
        ];
    }
}
