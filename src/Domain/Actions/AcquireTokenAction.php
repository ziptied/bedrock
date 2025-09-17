<?php

namespace Ziptied\Bedrock\Domain\Actions;

use Ziptied\Bedrock\Domain\Contracts\MarketplaceHttpClient;

class AcquireTokenAction
{
    public function __construct(private readonly MarketplaceHttpClient $client)
    {
    }

    public function handle(): string
    {
        return $this->client->acquireToken();
    }

    public function __invoke(): string
    {
        return $this->handle();
    }
}
