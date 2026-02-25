<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffMember extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'cafe_id',
        'user_id',
        'role',
        'can_manage_bookings',
        'can_view_analytics',
        'can_manage_menu',
        'invited_by',
        'invitation_status',
    ];

    protected function casts(): array
    {
        return [
            'can_manage_bookings' => 'boolean',
            'can_view_analytics' => 'boolean',
            'can_manage_menu' => 'boolean',
        ];
    }

    // Relationships
    public function cafe(): BelongsTo
    {
        return $this->belongsTo(Cafe::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    // Scopes
    public function scopeAccepted($query)
    {
        return $query->where('invitation_status', 'accepted');
    }

    public function scopePending($query)
    {
        return $query->where('invitation_status', 'pending');
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopeManagers($query)
    {
        return $query->where('role', 'manager');
    }
}
