<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Achievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'icon',
        'criteria_type',
        'criteria_value',
        'points_reward',
        'requirement',
        'category',
    ];

    protected function casts(): array
    {
        return [
            'criteria_value' => 'integer',
            'points_reward' => 'integer',
        ];
    }

    // Relationships
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_achievements')
            ->withPivot('unlocked_at')
            ->withTimestamps();
    }

    // Scopes
    public function scopeByCriteria($query, string $criteriaType)
    {
        return $query->where('criteria_type', $criteriaType);
    }
}
