<?php

namespace Ziptied\Bedrock\Domain\Events;

use Illuminate\Queue\SerializesModels;
use Ziptied\Bedrock\Domain\Models\MarketplaceSubscription;

class QuantityChanged
{
    use SerializesModels;

    public function __construct(
        public readonly MarketplaceSubscription $subscription,
        public readonly int $oldQuantity,
        public readonly int $newQuantity
    ) {
    }
}
