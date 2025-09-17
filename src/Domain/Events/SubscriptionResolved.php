<?php

namespace Ziptied\Bedrock\Domain\Events;

use Illuminate\Queue\SerializesModels;
use Ziptied\Bedrock\Domain\Models\MarketplaceSubscription;

class SubscriptionResolved
{
    use SerializesModels;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public readonly MarketplaceSubscription $subscription,
        public readonly array $payload
    ) {
    }
}
