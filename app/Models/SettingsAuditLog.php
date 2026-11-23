<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SettingsAuditLog extends Model
{
    protected $table = 'settings_audit_log';

    // Written only by the wallet services, never mass-assigned from a request.
    protected $guarded = ['id'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['old' => 'array', 'new' => 'array', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
