<?php

use Ziptied\Bedrock\Domain\Actions\PatchOperationAction;
use Ziptied\Bedrock\Domain\Enums\AckStatus;
use Ziptied\Bedrock\Tests\Fakes\FakeMarketplaceHttpClient;

test('patch operation action sends success status', function () {
    $client = new FakeMarketplaceHttpClient();
    $action = new PatchOperationAction($client);

    $action->handle('token', 'sub-123', 'op-1', AckStatus::Success, null);

    expect($client->patchResponse['status'])->toBe('Success');
});

test('patch operation action sends failure reason when provided', function () {
    $client = new FakeMarketplaceHttpClient();
    $action = new PatchOperationAction($client);

    $action->handle('token', 'sub-123', 'op-1', AckStatus::Failure, 'capacity exceeded');

    expect($client->patchResponse['status'])->toBe('Failure')
        ->and($client->patchResponse['reason'])->toBe('capacity exceeded');
});
