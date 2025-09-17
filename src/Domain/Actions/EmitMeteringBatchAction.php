<?php

namespace Ziptied\Bedrock\Domain\Actions;

use Illuminate\Support\Facades\DB;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Ziptied\Bedrock\Domain\Contracts\MarketplaceHttpClient;
use Ziptied\Bedrock\Domain\Dto\UsageEvent;
use Ziptied\Bedrock\Domain\Models\MarketplaceSubscription;
use Ziptied\Bedrock\Domain\Models\MarketplaceUsageEvent;

class EmitMeteringBatchAction
{
    public function __construct(private readonly MarketplaceHttpClient $client)
    {
    }

    /**
     * @param array<int, UsageEvent|MarketplaceUsageEvent> $events
     */
    public function handle(array $events): void
    {
        if (!config('azure_marketplace.metering.enabled')) {
            return;
        }

        if (empty($events)) {
            return;
        }

        $accessToken = $this->client->acquireToken();
        $batchSize = (int) config('azure_marketplace.metering.batch_size', 25);

        $normalized = array_map(fn ($event) => $this->normalizeEvent($event), $events);

        foreach (array_chunk($normalized, $batchSize) as $chunk) {
            $payload = array_map(static fn (array $item) => $item['event']->toArray(), $chunk);

            $response = $this->client->emitUsageBatch($payload, $accessToken);

            DB::transaction(function () use ($chunk, $response) {
                foreach ($chunk as $item) {
                    /** @var UsageEvent $event */
                    $event = $item['event'];
                    /** @var MarketplaceUsageEvent|null $record */
                    $record = $item['record'];

                    if ($record) {
                        $record->update([
                            'sent_at' => now(),
                            'response' => $response,
                        ]);
                        continue;
                    }

                    $subscription = MarketplaceSubscription::query()
                        ->where(MarketplaceSubscription::providerColumn(), 'azure')
                        ->where('azure_subscription_id', $event->subscriptionId)
                        ->first();

                    MarketplaceUsageEvent::create([
                        'subscription_id' => $subscription?->getKey(),
                        'azure_subscription_id' => $event->subscriptionId,
                        'dimension' => $event->dimension,
                        'quantity' => $event->quantity,
                        'effective_start_time' => $event->effectiveStart,
                        'plan_id' => $event->planId,
                        'sent_at' => now(),
                        'response' => $response,
                    ]);
                }
            });
        }
    }

    private function normalizeEvent(UsageEvent|MarketplaceUsageEvent $event): array
    {
        if ($event instanceof UsageEvent) {
            return ['event' => $event, 'record' => null];
        }

        $subscriptionId = $event->azure_subscription_id
            ?? $event->subscription?->azure_subscription_id;

        if (!$subscriptionId) {
            throw new InvalidArgumentException('Marketplace usage event missing azure subscription id.');
        }

        $usageEvent = new UsageEvent(
            $subscriptionId,
            $event->dimension,
            (float) $event->quantity,
            CarbonImmutable::parse($event->effective_start_time),
            $event->plan_id
        );

        return ['event' => $usageEvent, 'record' => $event];
    }
}
