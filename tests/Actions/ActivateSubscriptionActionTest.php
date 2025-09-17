<?php

use Ziptied\Bedrock\Domain\Actions\ActivateSubscriptionAction;
use Ziptied\Bedrock\Tests\Fakes\FakeMarketplaceHttpClient;

test('activate subscription action forwards payload to client', function () {
    $client = new FakeMarketplaceHttpClient();
    $action = new ActivateSubscriptionAction($client);

    $action->handle('token', 'sub-123', 'plan-gold', 3);

    expect($client->activateResponse)->toMatchArray([
        'subscriptionId' => 'sub-123',
        'planId' => 'plan-gold',
        'quantity' => 3,
    ]);
});
