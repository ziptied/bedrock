<?php

namespace Ziptied\Bedrock\Domain\Events;

use Illuminate\Queue\SerializesModels;
use Ziptied\Bedrock\Domain\Models\MarketplaceSubscription;

class PlanChanged
{
    use SerializesModels;

    public function __construct(
        public readonly MarketplaceSubscription $subscription,
        public readonly string $oldPlan,
        public readonly string $newPlan
    ) {
    }
}
