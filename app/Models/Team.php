<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'short_name',
        'logo',
        'league',
        'country',
        'is_popular',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_popular' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // Relationships
    public function homeMatches(): HasMany
    {
        return $this->hasMany(GameMatch::class, 'home_team_id');
    }

    public function awayMatches(): HasMany
    {
        return $this->hasMany(GameMatch::class, 'away_team_id');
    }

    public function fanProfiles(): HasMany
    {
        return $this->hasMany(FanProfile::class, 'favorite_team_id');
    }

    // Scopes
    public function scopePopular($query)
    {
        return $query->where('is_popular', true)->orderBy('sort_order');
    }

    public function scopeByLeague($query, string $league)
    {
        return $query->where('league', $league);
    }
}
