<?php

use Ziptied\Bedrock\Domain\Actions\AcquireTokenAction;
use Ziptied\Bedrock\Tests\Fakes\FakeMarketplaceHttpClient;

test('acquire token action returns token from client', function () {
    $client = new FakeMarketplaceHttpClient();
    $client->token = 'expected-token';

    $action = new AcquireTokenAction($client);

    expect($action->handle())->toBe('expected-token');
});
