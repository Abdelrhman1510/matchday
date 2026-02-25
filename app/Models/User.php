<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'role',
        'google_id',
        'apple_id',
        'locale',
        'device_token',
        'notification_settings',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
        'apple_id',
        'device_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'avatar' => 'array',
            'notification_settings' => 'array',
        ];
    }

    // Relationships
    public function fanProfile(): HasOne
    {
        return $this->hasOne(FanProfile::class);
    }

    /**
     * Get or auto-create fan profile
     */
    public function getFanProfileAttribute()
    {
        $profile = $this->getRelationValue('fanProfile');

        if (!$profile && $this->exists) {
            $profile = FanProfile::create([
                'user_id' => $this->id,
                'matches_attended' => 0,
                'member_since' => now(),
            ]);
            $this->setRelation('fanProfile', $profile);
        }

        return $profile;
    }

    public function ownedCafes(): HasMany
    {
        return $this->hasMany(Cafe::class, 'owner_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function loyaltyCard(): HasOne
    {
        return $this->hasOne(LoyaltyCard::class);
    }

    public function staffMemberships(): HasMany
    {
        return $this->hasMany(StaffMember::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function savedCafes(): BelongsToMany
    {
        return $this->belongsToMany(Cafe::class, 'saved_cafes')->withTimestamps();
    }

    public function savedMatches(): BelongsToMany
    {
        return $this->belongsToMany(GameMatch::class, 'saved_matches', 'user_id', 'match_id')->withTimestamps();
    }

    public function chatRooms(): BelongsToMany
    {
        return $this->belongsToMany(ChatRoom::class, 'chat_room_user')->withTimestamps();
    }

    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')
            ->withPivot('unlocked_at')
            ->withTimestamps();
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFans($query)
    {
        return $query->where('role', 'fan');
    }

    public function scopeCafeOwners($query)
    {
        return $query->where('role', 'cafe_owner');
    }

    public function scopeStaff($query)
    {
        return $query->where('role', 'staff');
    }

    /**
     * Override can() to support role-based permissions when Spatie permissions aren't seeded.
     * Cafe owners get all management permissions by default.
     */
    public function can($abilities, $arguments = []): bool
    {
        // Define which roles get which permissions
        $rolePermissions = [
            'cafe_owner' => [
                'manage-cafe-profile',
                'manage-matches',
                'view-bookings',
                'manage-bookings',
                'view-analytics',
                'manage-offers',
                'manage-subscription',
                'manage-staff',
                'manage-seating',
                'check-in-customers',
                'scan-qr',
                'view-occupancy',
            ],
            'admin' => ['*'],
        ];

        if (is_string($abilities)) {
            $permissions = $rolePermissions[$this->role] ?? [];
            if (in_array('*', $permissions) || in_array($abilities, $permissions)) {
                return true;
            }
        }

        // Check branch_staff_permissions table for staff users
        if (is_string($abilities)) {
            $hasPermission = \App\Models\Permission::where('user_id', $this->id)
                ->where('permission', $abilities)
                ->exists();
            if ($hasPermission) {
                return true;
            }
        }

        return false;
    }

    /**
     * Override hasPermissionTo to support role-based fallback
     */
    public function hasPermissionTo($permission, $guardName = null): bool
    {
        // Use role-based permission check (same as can())
        return $this->can($permission);
    }
}
