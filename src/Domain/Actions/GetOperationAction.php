<?php

namespace Ziptied\Bedrock\Domain\Actions;

use InvalidArgumentException;
use Ziptied\Bedrock\Domain\Contracts\MarketplaceHttpClient;
use Ziptied\Bedrock\Domain\Dto\Operation;
use Ziptied\Bedrock\Domain\Enums\ActionType;

class GetOperationAction
{
    public function __construct(private readonly MarketplaceHttpClient $client)
    {
    }

    public function handle(string $accessToken, string $subscriptionId, string $operationId, ActionType $expectedAction): Operation
    {
        $payload = $this->client->getOperation($subscriptionId, $operationId, $accessToken);
        $operation = Operation::fromArray($payload);

        if ($operation->subscriptionId !== $subscriptionId) {
            throw new InvalidArgumentException('Operation subscription mismatch.');
        }

        if ($operation->action !== $expectedAction) {
            throw new InvalidArgumentException('Operation action mismatch.');
        }

        return $operation;
    }
}
