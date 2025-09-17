<?php

use Carbon\CarbonImmutable;
use Ziptied\Bedrock\Domain\Actions\ApplyRenewAction;
use Ziptied\Bedrock\Domain\Enums\SubscriptionState;
use Ziptied\Bedrock\Domain\Models\MarketplaceSubscription;
use Ziptied\Bedrock\Tests\Fakes\User;

uses()->group('database');

test('apply renew updates term dates and status', function () {
    $user = User::create(['email' => 'renew@example.test']);
    $subscription = MarketplaceSubscription::create([
        'user_id' => $user->id,
        'azure_subscription_id' => 'sub-123',
        'azure_status' => SubscriptionState::Suspended->value,
    ]);

    $action = app(ApplyRenewAction::class);
    $start = CarbonImmutable::parse('2024-01-01T00:00:00Z');
    $end = CarbonImmutable::parse('2024-12-31T23:59:59Z');

    $updated = $action->handle($subscription, $start, $end);

    expect($updated->azure_status)->toBe(SubscriptionState::Active->value)
        ->and($updated->azure_term_start->toIso8601String())->toBe($start->toIso8601String())
        ->and($updated->azure_term_end->toIso8601String())->toBe($end->toIso8601String());
});
