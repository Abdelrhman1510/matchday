<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommissionOverride extends Model
{
    protected $table = 'commission_overrides';

    // Written only by the wallet services, never mass-assigned from a request.
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['value' => 'decimal:2', 'min' => 'decimal:2', 'max' => 'decimal:2'];
    }

}
