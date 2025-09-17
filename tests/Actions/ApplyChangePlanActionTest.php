<?php

use Ziptied\Bedrock\Domain\Actions\ApplyChangePlanAction;
use Ziptied\Bedrock\Domain\Models\MarketplaceSubscription;
use Ziptied\Bedrock\Tests\Fakes\User;

uses()->group('database');

test('apply change plan updates azure plan id', function () {
    $user = User::create(['email' => 'plan@example.test']);
    $subscription = MarketplaceSubscription::create([
        'user_id' => $user->id,
        'azure_subscription_id' => 'sub-123',
        'azure_plan_id' => 'plan-basic',
    ]);

    $action = app(ApplyChangePlanAction::class);
    $updated = $action->handle($subscription, 'plan-gold');

    expect($updated->azure_plan_id)->toBe('plan-gold');
});
