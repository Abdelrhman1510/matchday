<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'user_id',
        'message',
        'type',
    ];

    // Relationships
    public function chatRoom(): BelongsTo
    {
        return $this->belongsTo(ChatRoom::class, 'room_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeRecent($query, int $limit = 50)
    {
        return $query->latest()->limit($limit);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
