<?php

namespace Ziptied\Bedrock\Domain\Actions;

use Ziptied\Bedrock\Domain\Contracts\MarketplaceHttpClient;
use Ziptied\Bedrock\Domain\Dto\ResolveResult;

class ResolveSubscriptionAction
{
    public function __construct(private readonly MarketplaceHttpClient $client)
    {
    }

    public function handle(string $accessToken, string $resolveToken): ResolveResult
    {
        $payload = $this->client->resolve($resolveToken, $accessToken);

        return ResolveResult::fromArray($payload);
    }
}
