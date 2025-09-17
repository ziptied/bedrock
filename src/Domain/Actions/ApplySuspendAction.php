<?php

namespace Ziptied\Bedrock\Domain\Actions;

use Ziptied\Bedrock\Domain\Enums\SubscriptionState;
use Ziptied\Bedrock\Domain\Models\MarketplaceSubscription;

class ApplySuspendAction
{
    public function handle(MarketplaceSubscription $subscription): MarketplaceSubscription
    {
        $subscription->markStatus(SubscriptionState::Suspended);
        $subscription->save();

        return $subscription->fresh();
    }
}
