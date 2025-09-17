<?php

namespace Ziptied\Bedrock\Domain\Actions;

use Ziptied\Bedrock\Domain\Contracts\MarketplaceHttpClient;

class ActivateSubscriptionAction
{
    public function __construct(private readonly MarketplaceHttpClient $client)
    {
    }

    public function handle(string $accessToken, string $subscriptionId, string $planId, int $quantity): array
    {
        return $this->client->activate($subscriptionId, $planId, $quantity, $accessToken);
    }
}
