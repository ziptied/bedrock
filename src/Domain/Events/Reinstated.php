<?php

namespace Ziptied\Bedrock\Domain\Events;

use Illuminate\Queue\SerializesModels;
use Ziptied\Bedrock\Domain\Models\MarketplaceSubscription;

class Reinstated
{
    use SerializesModels;

    public function __construct(public readonly MarketplaceSubscription $subscription)
    {
    }
}
