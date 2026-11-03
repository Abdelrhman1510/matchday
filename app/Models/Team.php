<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'wikidata_id',
        'name',
        'name_ar',
        'short_name',
        'logo',
        'league',
        'type',
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

    /**
     * Never shorter than 2 letters: imported teams can hold a single letter
     * ("A" for Arsenal), so fall back to the start of the name's longest word.
     */
    protected function shortName(): Attribute
    {
        return Attribute::get(function ($value, array $attributes) {
            if (mb_strlen((string) $value) >= 2) {
                return $value;
            }

            $words = array_map(fn ($word) => preg_replace('/[^\p{L}\p{N}]/u', '', $word), explode(' ', $attributes['name'] ?? ''));
            usort($words, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

            return mb_strtoupper(mb_substr($words[0], 0, 2)) ?: $value;
        });
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

    // Arabic league name (mapped; null if unknown).
    public function getLeagueArAttribute(): ?string
    {
        return \App\Support\Leagues::ar($this->league);
    }
}
