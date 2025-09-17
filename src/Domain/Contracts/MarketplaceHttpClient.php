<?php

namespace Ziptied\Bedrock\Domain\Contracts;

interface MarketplaceHttpClient
{
    public function acquireToken(): string;

    public function resolve(string $marketplaceToken, string $accessToken): array;

    public function activate(string $subscriptionId, string $planId, int $quantity, string $accessToken): array;

    public function getOperation(string $subscriptionId, string $operationId, string $accessToken): array;

    public function patchOperation(string $subscriptionId, string $operationId, string $status, ?string $reason, string $accessToken): array;

    public function emitUsageBatch(array $events, string $accessToken): array;
}
