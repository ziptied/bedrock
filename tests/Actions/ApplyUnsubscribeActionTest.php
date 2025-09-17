<?php

use Ziptied\Bedrock\Domain\Actions\ApplyUnsubscribeAction;
use Ziptied\Bedrock\Domain\Enums\SubscriptionState;
use Ziptied\Bedrock\Domain\Models\MarketplaceSubscription;
use Ziptied\Bedrock\Tests\Fakes\User;

uses()->group('database');

test('apply unsubscribe sets status and end timestamp', function () {
    $user = User::create(['email' => 'unsubscribe@example.test']);
    $subscription = MarketplaceSubscription::create([
        'user_id' => $user->id,
        'azure_subscription_id' => 'sub-123',
        'azure_status' => SubscriptionState::Active->value,
    ]);

    $action = app(ApplyUnsubscribeAction::class);
    $updated = $action->handle($subscription);

    expect($updated->azure_status)->toBe(SubscriptionState::Unsubscribed->value)
        ->and($updated->ends_at)->not->toBeNull();
});
