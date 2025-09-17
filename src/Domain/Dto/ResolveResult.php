<?php

namespace Ziptied\Bedrock\Domain\Dto;

use Carbon\CarbonImmutable;
use Ziptied\Bedrock\Domain\Enums\SubscriptionState;

final class ResolveResult
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public readonly string $subscriptionId,
        public readonly string $offerId,
        public readonly string $planId,
        public readonly ?int $quantity,
        public readonly ?CarbonImmutable $termStart,
        public readonly ?CarbonImmutable $termEnd,
        public readonly SubscriptionState $status,
        public readonly array $raw,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $subscription = $payload['subscription'] ?? $payload;

        $subscriptionId = $subscription['id'] ?? throw new \InvalidArgumentException('Resolve payload missing subscription id.');
        $offerId = $subscription['offerId'] ?? throw new \InvalidArgumentException('Resolve payload missing offerId.');
        $planId = $subscription['planId'] ?? throw new \InvalidArgumentException('Resolve payload missing planId.');

        $quantity = isset($subscription['quantity']) ? (int) $subscription['quantity'] : null;

        $term = $subscription['term'] ?? [];
        $termStart = isset($term['startDate']) ? CarbonImmutable::parse($term['startDate']) : null;
        $termEnd = isset($term['endDate']) ? CarbonImmutable::parse($term['endDate']) : null;

        $statusValue = $subscription['status'] ?? SubscriptionState::PendingFulfillmentStart->value;
        $status = SubscriptionState::tryFrom($statusValue) ?? match ($statusValue) {
            'Subscribed' => SubscriptionState::Active,
            'PendingActivation' => SubscriptionState::PendingActivation,
            'PendingFulfillmentStart' => SubscriptionState::PendingFulfillmentStart,
            'Suspended' => SubscriptionState::Suspended,
            'Unsubscribed', 'Canceled' => SubscriptionState::Unsubscribed,
            default => SubscriptionState::PendingFulfillmentStart,
        };

        return new self(
            $subscriptionId,
            $offerId,
            $planId,
            $quantity,
            $termStart,
            $termEnd,
            $status,
            $payload,
        );
    }
}
