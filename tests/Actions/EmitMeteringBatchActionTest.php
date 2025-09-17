<?php

use Carbon\CarbonImmutable;
use Ziptied\Bedrock\Domain\Actions\EmitMeteringBatchAction;
use Ziptied\Bedrock\Domain\Contracts\MarketplaceHttpClient;
use Ziptied\Bedrock\Domain\Dto\UsageEvent;
use Ziptied\Bedrock\Domain\Models\MarketplaceSubscription;
use Ziptied\Bedrock\Domain\Models\MarketplaceUsageEvent;
use Ziptied\Bedrock\Tests\Fakes\FakeMarketplaceHttpClient;
use Ziptied\Bedrock\Tests\Fakes\User;

uses()->group('database');

test('emit metering batch stores events and calls client', function () {
    $client = new FakeMarketplaceHttpClient();
    app()->instance(MarketplaceHttpClient::class, $client);

    $user = User::create(['email' => 'meter@example.test']);
    MarketplaceSubscription::create([
        'user_id' => $user->id,
        'azure_subscription_id' => 'sub-123',
    ]);

    $event = new UsageEvent('sub-123', 'emails_processed', 3.5, CarbonImmutable::parse('2024-01-01T00:00:00Z'));

    app(EmitMeteringBatchAction::class)->handle([$event]);

    expect($client->lastEmittedEvents)->toHaveCount(1)
        ->and(MarketplaceUsageEvent::count())->toBe(1)
        ->and(MarketplaceUsageEvent::first()->azure_subscription_id)->toBe('sub-123');
});

it('updates existing pending usage rows', function () {
    $client = new FakeMarketplaceHttpClient();
    app()->instance(MarketplaceHttpClient::class, $client);

    $user = User::create(['email' => 'pending@example.test']);
    $subscription = MarketplaceSubscription::create([
        'user_id' => $user->id,
        'azure_subscription_id' => 'sub-999',
    ]);

    $record = MarketplaceUsageEvent::create([
        'subscription_id' => $subscription->id,
        'azure_subscription_id' => 'sub-999',
        'dimension' => 'emails_processed',
        'quantity' => 1,
        'effective_start_time' => CarbonImmutable::parse('2024-01-02T00:00:00Z'),
    ]);

    app(EmitMeteringBatchAction::class)->handle([$record]);

    $record->refresh();

    expect($record->sent_at)->not->toBeNull();
});
