<?php

use Ziptied\Bedrock\Domain\Actions\EnsureIdempotencyAction;
use Ziptied\Bedrock\Domain\Enums\ActionType;
use Ziptied\Bedrock\Domain\Exceptions\IdempotencyException;
use Ziptied\Bedrock\Domain\Models\MarketplaceOperation;
use Ziptied\Bedrock\Domain\Models\MarketplaceSubscription;
use Ziptied\Bedrock\Tests\Fakes\User;

uses()->group('database');

test('ensure idempotency stores new operation', function () {
    $user = User::create(['email' => 'op@example.test']);
    $subscription = MarketplaceSubscription::create([
        'user_id' => $user->id,
        'azure_subscription_id' => 'sub-123',
    ]);

    $action = app(EnsureIdempotencyAction::class);

    $operation = $action->handle('op-1', ActionType::ChangePlan, [
        'id' => 'op-1',
        'subscriptionId' => 'sub-123',
        'status' => 'InProgress',
    ]);

    expect($operation->subscription_id)->toBe($subscription->id);
});

it('throws when operation already stored', function () {
    $action = app(EnsureIdempotencyAction::class);

    $action->handle('op-1', ActionType::ChangePlan, [
        'id' => 'op-1',
        'subscriptionId' => 'sub-123',
        'status' => 'InProgress',
    ]);

    $action->handle('op-1', ActionType::ChangePlan, [
        'id' => 'op-1',
        'subscriptionId' => 'sub-123',
        'status' => 'InProgress',
    ]);
})->throws(IdempotencyException::class);
