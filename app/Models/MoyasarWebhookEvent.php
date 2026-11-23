<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MoyasarWebhookEvent extends Model
{
    protected $table = 'moyasar_webhook_events';

    // Written only by the wallet services, never mass-assigned from a request.
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['live' => 'boolean', 'payload' => 'array', 'processed_at' => 'datetime'];
    }

}
