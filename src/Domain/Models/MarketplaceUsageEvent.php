<?php

namespace Ziptied\Bedrock\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceUsageEvent extends Model
{
    protected $table = 'marketplace_usage_events';

    protected $guarded = [];

    protected $casts = [
        'effective_start_time' => 'datetime',
        'sent_at' => 'datetime',
        'response' => 'array',
        'quantity' => 'float',
        'azure_subscription_id' => 'string',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(MarketplaceSubscription::class, 'subscription_id');
    }
}
