<?php

namespace Ziptied\Bedrock\Tests\Fakes;

use Ziptied\Bedrock\Domain\Contracts\MarketplaceHttpClient;

class FakeMarketplaceHttpClient implements MarketplaceHttpClient
{
    public string $token = 'fake-token';
    public array $resolveResponse = [];
    public array $activateResponse = [];
    public array $operationResponse = [];
    public array $patchResponse = [];
    public array $usageResponse = [];
    public array $lastEmittedEvents = [];

    public function acquireToken(): string
    {
        return $this->token;
    }

    public function resolve(string $marketplaceToken, string $accessToken): array
    {
        return $this->resolveResponse;
    }

    public function activate(string $subscriptionId, string $planId, int $quantity, string $accessToken): array
    {
        $this->activateResponse = compact('subscriptionId', 'planId', 'quantity');

        return $this->activateResponse;
    }

    public function getOperation(string $subscriptionId, string $operationId, string $accessToken): array
    {
        return $this->operationResponse;
    }

    public function patchOperation(string $subscriptionId, string $operationId, string $status, ?string $reason, string $accessToken): array
    {
        $this->patchResponse = compact('subscriptionId', 'operationId', 'status', 'reason');

        return $this->patchResponse;
    }

    public function emitUsageBatch(array $events, string $accessToken): array
    {
        $this->lastEmittedEvents = $events;

        return $this->usageResponse ?: ['status' => 'Accepted'];
    }
}
