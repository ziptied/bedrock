<?php

use Ziptied\Bedrock\Domain\Actions\GetOperationAction;
use Ziptied\Bedrock\Domain\Enums\ActionType;
use Ziptied\Bedrock\Domain\Enums\OperationStatus;
use Ziptied\Bedrock\Tests\Fakes\FakeMarketplaceHttpClient;

test('get operation returns dto and validates action', function () {
    $client = new FakeMarketplaceHttpClient();
    $client->operationResponse = [
        'id' => 'op-1',
        'subscriptionId' => 'sub-123',
        'action' => 'ChangePlan',
        'status' => 'InProgress',
    ];

    $action = new GetOperationAction($client);

    $operation = $action->handle('token', 'sub-123', 'op-1', ActionType::ChangePlan);

    expect($operation->status)->toBe(OperationStatus::InProgress);
});

it('throws if subscription mismatch', function () {
    $client = new FakeMarketplaceHttpClient();
    $client->operationResponse = [
        'id' => 'op-1',
        'subscriptionId' => 'other',
        'action' => 'ChangePlan',
        'status' => 'InProgress',
    ];

    $action = new GetOperationAction($client);

    $action->handle('token', 'sub-123', 'op-1', ActionType::ChangePlan);
})->throws(InvalidArgumentException::class);
