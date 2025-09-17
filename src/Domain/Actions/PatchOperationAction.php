<?php

namespace Ziptied\Bedrock\Domain\Actions;

use Ziptied\Bedrock\Domain\Contracts\MarketplaceHttpClient;
use Ziptied\Bedrock\Domain\Enums\AckStatus;

class PatchOperationAction
{
    public function __construct(private readonly MarketplaceHttpClient $client)
    {
    }

    public function handle(string $accessToken, string $subscriptionId, string $operationId, AckStatus $status, ?string $reason = null): array
    {
        return $this->client->patchOperation(
            $subscriptionId,
            $operationId,
            $status === AckStatus::Success ? 'Success' : 'Failure',
            $reason,
            $accessToken
        );
    }
}
