<?php

namespace Ziptied\Bedrock\Domain\Actions;

class MapPlanToFeaturesAction
{
    public function handle(string $planId): array
    {
        $plans = config('azure_marketplace.plans', []);

        return $plans[$planId] ?? [];
    }
}
