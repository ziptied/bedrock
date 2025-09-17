<?php

namespace Ziptied\Bedrock\Domain\Events;

use Carbon\CarbonImmutable;
use Illuminate\Queue\SerializesModels;
use Ziptied\Bedrock\Domain\Models\MarketplaceSubscription;

class Renewed
{
    use SerializesModels;

    public function __construct(
        public readonly MarketplaceSubscription $subscription,
        public readonly ?CarbonImmutable $termStart,
        public readonly ?CarbonImmutable $termEnd
    ) {
    }
}
