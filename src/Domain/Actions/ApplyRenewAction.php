<?php

namespace Ziptied\Bedrock\Domain\Actions;

use Carbon\CarbonImmutable;
use Ziptied\Bedrock\Domain\Enums\SubscriptionState;
use Ziptied\Bedrock\Domain\Models\MarketplaceSubscription;

class ApplyRenewAction
{
    public function handle(MarketplaceSubscription $subscription, ?CarbonImmutable $termStart = null, ?CarbonImmutable $termEnd = null): MarketplaceSubscription
    {
        $subscription->markStatus(SubscriptionState::Active);
        if ($termStart) {
            $subscription->azure_term_start = $termStart;
        }
        if ($termEnd) {
            $subscription->azure_term_end = $termEnd;
        }
        $subscription->save();

        return $subscription->fresh();
    }
}
