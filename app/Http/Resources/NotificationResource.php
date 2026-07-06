<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Parse the data JSON and remove null values for cleaner response
        $data = is_array($this->data) ? $this->data : json_decode($this->data, true) ?? [];
        
        // Remove null values to keep response clean
        $cleanData = array_filter($data, function ($value) {
            return $value !== null;
        });
        
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'title_ar' => $this->title_ar ?? ([
                'achievement_unlocked' => '🎉 إنجاز جديد!',
                'booking_confirmed'    => 'تم تأكيد الحجز!',
                'booking_cancelled'    => 'تم إلغاء الحجز',
                'match_reminder'       => 'المباراة تبدأ قريبًا!',
                'match_score_update'   => 'تحديث المباراة!',
                'points_earned'        => '⭐ نقاط جديدة!',
                'staff_invitation'     => 'دعوة للانضمام',
                'welcome'              => 'مرحبًا بك في ماتش داي! ⚽',
            ][$cleanData['type'] ?? ''] ?? null),
            'body' => $this->body,
            'body_ar' => $this->body_ar,
            'data' => $cleanData,
            'read' => $this->read_at !== null,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
