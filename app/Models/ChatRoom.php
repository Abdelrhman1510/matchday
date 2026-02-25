<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_id',
        'branch_id',
        'cafe_id',
        'name',
        'type',
        'is_active',
        'viewers_count',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'viewers_count' => 'integer',
        ];
    }

    // Relationships
    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'room_id');
    }

    public function cafe(): BelongsTo
    {
        return $this->belongsTo(Cafe::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_room_user')->withTimestamps();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublic($query)
    {
        return $query->where('type', 'public')->whereNull('branch_id');
    }

    public function scopeCafe($query)
    {
        return $query->where('type', 'cafe')->whereNotNull('branch_id');
    }
}
