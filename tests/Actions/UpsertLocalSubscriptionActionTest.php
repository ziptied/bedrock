<?php

use Ziptied\Bedrock\Domain\Actions\UpsertLocalSubscriptionAction;
use Ziptied\Bedrock\Domain\Dto\ResolveResult;
use Ziptied\Bedrock\Domain\Models\MarketplaceSubscription;
use Ziptied\Bedrock\Tests\Fakes\User;

uses()->group('database');

test('upsert local subscription creates new record and associates billable', function () {
    $action = app(UpsertLocalSubscriptionAction::class);
    $user = User::create(['email' => 'azure@example.test']);

    $resolve = ResolveResult::fromArray([
        'subscription' => [
            'id' => 'sub-123',
            'offerId' => 'offer-basic',
            'planId' => 'plan-gold',
            'quantity' => 2,
            'status' => 'Subscribed',
        ],
    ]);

    $subscription = $action->handle($resolve, $user);

    expect($subscription->user_id)->toBe($user->id)
        ->and($subscription->azure_plan_id)->toBe('plan-gold')
        ->and(MarketplaceSubscription::count())->toBe(1);

    $resolveUpdated = ResolveResult::fromArray([
        'subscription' => [
            'id' => 'sub-123',
            'offerId' => 'offer-basic',
            'planId' => 'plan-platinum',
            'quantity' => 5,
            'status' => 'Subscribed',
        ],
    ]);

    $action->handle($resolveUpdated, $user);

    expect(MarketplaceSubscription::count())->toBe(1)
        ->and(MarketplaceSubscription::first()->azure_plan_id)->toBe('plan-platinum')
        ->and(MarketplaceSubscription::first()->quantity)->toBe(5);
});
