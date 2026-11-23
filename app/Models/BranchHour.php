<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchHour extends Model
{
    use HasFactory;

    public const DAYS = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

    /** 0 (Sunday) to 6 (Saturday) from a number or an English day name; null if neither. */
    public static function normalizeDay($day): ?int
    {
        if (is_int($day) || (is_string($day) && ctype_digit($day))) {
            return (int) $day >= 0 && (int) $day <= 6 ? (int) $day : null;
        }
        if (!is_string($day)) {
            return null;
        }
        $index = array_search(strtolower(trim($day)), self::DAYS, true);

        return $index === false ? null : $index;
    }

    protected $fillable = [
        'branch_id',
        'day_of_week',
        'is_open',
        'open_time',
        'close_time',
    ];

    protected function casts(): array
    {
        return [
            'is_open' => 'boolean',
        ];
    }

    // Relationships
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('is_open', true);
    }

    public function scopeForDay($query, int $day)
    {
        return $query->where('day_of_week', $day);
    }
}
