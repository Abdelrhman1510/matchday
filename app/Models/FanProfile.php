<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FanProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'favorite_team_id',
        'matches_attended',
        'member_since',
    ];

    protected $hidden = ['user'];  // Prevent circular reference

    protected function casts(): array
    {
        return [
            'matches_attended' => 'integer',
            'member_since' => 'date',
        ];
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function favoriteTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'favorite_team_id');
    }

    // Alias for consistency
    public function preferredTeam(): BelongsTo
    {
        return $this->favoriteTeam();
    }
}
