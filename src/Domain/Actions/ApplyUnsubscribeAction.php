<?php

namespace Ziptied\Bedrock\Domain\Actions;

use Carbon\CarbonImmutable;
use Ziptied\Bedrock\Domain\Enums\SubscriptionState;
use Ziptied\Bedrock\Domain\Models\MarketplaceSubscription;

class ApplyUnsubscribeAction
{
    public function handle(MarketplaceSubscription $subscription): MarketplaceSubscription
    {
        $subscription->markStatus(SubscriptionState::Unsubscribed);
        $subscription->ends_at = CarbonImmutable::now();
        $subscription->save();

        return $subscription->fresh();
    }
}
