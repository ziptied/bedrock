<?php

namespace Ziptied\Bedrock\Domain\Dto;

use Carbon\CarbonImmutable;

final class UsageEvent
{
    public function __construct(
        public readonly string $subscriptionId,
        public readonly string $dimension,
        public readonly float $quantity,
        public readonly CarbonImmutable $effectiveStart,
        public readonly ?string $planId = null,
    ) {
    }

    public static function fromArray(array $payload): self
    {
        $requiredKeys = ['subscriptionId', 'dimension', 'quantity', 'effectiveStart'];
        $missing = array_filter($requiredKeys, static fn (string $key) => !array_key_exists($key, $payload));

        if (!empty($missing)) {
            throw new \InvalidArgumentException('Usage event payload missing required keys: ' . implode(', ', $missing));
        }

        if (!is_numeric($payload['quantity'])) {
            throw new \InvalidArgumentException('Usage event quantity must be numeric.');
        }

        try {
            $effectiveStart = CarbonImmutable::parse($payload['effectiveStart']);
        } catch (\Throwable $exception) {
            throw new \InvalidArgumentException('Usage event effectiveStart is invalid or unparsable.', 0, $exception);
        }

        return new self(
            (string) $payload['subscriptionId'],
            (string) $payload['dimension'],
            (float) $payload['quantity'],
            $effectiveStart,
            $payload['planId'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'resourceId' => $this->subscriptionId,
            'quantity' => $this->quantity,
            'dimension' => $this->dimension,
            'effectiveStartTime' => $this->effectiveStart->toIso8601String(),
            'planId' => $this->planId,
        ];
    }
}
