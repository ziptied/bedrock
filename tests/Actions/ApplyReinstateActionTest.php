<?php

use Ziptied\Bedrock\Domain\Actions\ApplyReinstateAction;
use Ziptied\Bedrock\Domain\Enums\SubscriptionState;
use Ziptied\Bedrock\Domain\Models\MarketplaceSubscription;
use Ziptied\Bedrock\Tests\Fakes\User;

uses()->group('database');

test('apply reinstate restores active status and clears end date', function () {
    $user = User::create(['email' => 'reinstate@example.test']);
    $subscription = MarketplaceSubscription::create([
        'user_id' => $user->id,
        'azure_subscription_id' => 'sub-123',
        'azure_status' => SubscriptionState::Suspended->value,
        'ends_at' => now(),
    ]);

    $action = app(ApplyReinstateAction::class);
    $updated = $action->handle($subscription);

    expect($updated->azure_status)->toBe(SubscriptionState::Active->value)
        ->and($updated->ends_at)->toBeNull();
});
