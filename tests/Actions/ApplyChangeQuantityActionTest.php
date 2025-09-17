<?php

use Ziptied\Bedrock\Domain\Actions\ApplyChangeQuantityAction;
use Ziptied\Bedrock\Domain\Models\MarketplaceSubscription;
use Ziptied\Bedrock\Tests\Fakes\User;

uses()->group('database');

test('apply change quantity updates both fields', function () {
    $user = User::create(['email' => 'quantity@example.test']);
    $subscription = MarketplaceSubscription::create([
        'user_id' => $user->id,
        'azure_subscription_id' => 'sub-123',
        'quantity' => 1,
        'azure_quantity' => 1,
    ]);

    $action = app(ApplyChangeQuantityAction::class);
    $updated = $action->handle($subscription, 10);

    expect($updated->quantity)->toBe(10)
        ->and($updated->azure_quantity)->toBe(10);
});
