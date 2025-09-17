<?php

use Ziptied\Bedrock\Domain\Actions\ResolveSubscriptionAction;
use Ziptied\Bedrock\Domain\Enums\SubscriptionState;
use Ziptied\Bedrock\Tests\Fakes\FakeMarketplaceHttpClient;

test('resolve subscription action returns dto with expected data', function () {
    $client = new FakeMarketplaceHttpClient();
    $client->resolveResponse = [
        'subscription' => [
            'id' => 'sub-123',
            'offerId' => 'offer-basic',
            'planId' => 'plan-gold',
            'quantity' => 5,
            'status' => 'Subscribed',
            'term' => [
                'startDate' => '2024-01-01T00:00:00Z',
                'endDate' => '2025-01-01T00:00:00Z',
            ],
        ],
    ];

    $action = new ResolveSubscriptionAction($client);

    $result = $action->handle('token', 'resolve-token');

    expect($result->subscriptionId)->toBe('sub-123')
        ->and($result->status)->toBe(SubscriptionState::Active)
        ->and($result->quantity)->toBe(5)
        ->and($result->termEnd->toIso8601String())->toBe('2025-01-01T00:00:00+00:00');
});

it('throws when required resolve fields missing', function () {
    $client = new FakeMarketplaceHttpClient();
    $client->resolveResponse = [];

    $action = new ResolveSubscriptionAction($client);

    $action->handle('token', 'resolve-token');
})->throws(InvalidArgumentException::class);
