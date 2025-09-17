<?php

namespace Ziptied\Bedrock\Domain\Actions;

use Ziptied\Bedrock\Domain\Models\MarketplaceSubscription;

class ApplyChangeQuantityAction
{
    public function handle(MarketplaceSubscription $subscription, int $quantity): MarketplaceSubscription
    {
        $subscription->azure_quantity = $quantity;
        $subscription->quantity = $quantity;
        $subscription->save();

        return $subscription->fresh();
    }
}
