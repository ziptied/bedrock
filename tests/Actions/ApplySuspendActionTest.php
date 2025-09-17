<?php

use Ziptied\Bedrock\Domain\Actions\ApplySuspendAction;
use Ziptied\Bedrock\Domain\Enums\SubscriptionState;
use Ziptied\Bedrock\Domain\Models\MarketplaceSubscription;
use Ziptied\Bedrock\Tests\Fakes\User;

uses()->group('database');

test('apply suspend marks subscription suspended', function () {
    $user = User::create(['email' => 'suspend@example.test']);
    $subscription = MarketplaceSubscription::create([
        'user_id' => $user->id,
        'azure_subscription_id' => 'sub-123',
        'azure_status' => SubscriptionState::Active->value,
    ]);

    $action = app(ApplySuspendAction::class);
    $updated = $action->handle($subscription);

    expect($updated->azure_status)->toBe(SubscriptionState::Suspended->value);
});
