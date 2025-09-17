<?php

use Ziptied\Bedrock\Domain\Actions\MapPlanToFeaturesAction;

it('returns plan configuration when defined', function () {
    config()->set('azure_marketplace.plans', [
        'gold' => ['seats' => 10],
    ]);

    $action = new MapPlanToFeaturesAction();

    expect($action->handle('gold'))->toBe(['seats' => 10])
        ->and($action->handle('silver'))->toBe([]);
});
