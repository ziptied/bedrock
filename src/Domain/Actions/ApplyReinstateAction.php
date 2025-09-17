<?php

namespace Ziptied\Bedrock\Domain\Actions;

use Ziptied\Bedrock\Domain\Enums\SubscriptionState;
use Ziptied\Bedrock\Domain\Models\MarketplaceSubscription;

class ApplyReinstateAction
{
    public function handle(MarketplaceSubscription $subscription): MarketplaceSubscription
    {
        $subscription->markStatus(SubscriptionState::Active);
        $subscription->ends_at = null;
        $subscription->save();

        return $subscription->fresh();
    }
}
