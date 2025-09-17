<?php

namespace Ziptied\Bedrock\Domain\Actions;

use Ziptied\Bedrock\Domain\Models\MarketplaceSubscription;

class ApplyChangePlanAction
{
    public function handle(MarketplaceSubscription $subscription, string $newPlanId): MarketplaceSubscription
    {
        $subscription->azure_plan_id = $newPlanId;
        $subscription->save();

        return $subscription->fresh();
    }
}
