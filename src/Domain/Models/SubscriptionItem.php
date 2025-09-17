<?php

namespace Ziptied\Bedrock\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionItem extends Model
{
    protected $table = 'subscription_items';

    protected $fillable = [
        'id',
        'subscription_id',
        'stripe_id',
        'stripe_product',
        'stripe_price',
        'quantity',
        'created_at',
        'updated_at',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(MarketplaceSubscription::class, 'subscription_id');
    }
}
