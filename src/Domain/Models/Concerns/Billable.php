<?php

namespace Ziptied\Bedrock\Domain\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Ziptied\Bedrock\Domain\Models\MarketplaceSubscription;

trait Billable
{
    public function subscriptions(): HasMany
    {
        return $this->hasMany(MarketplaceSubscription::class, 'user_id');
    }

    public function marketplaceSubscription(string $name = 'default'): HasOne
    {
        return $this->hasOne(MarketplaceSubscription::class, 'user_id')
            ->where(MarketplaceSubscription::providerColumn(), 'azure')
            ->where('name', $name);
    }

    public function subscribed(string $name = 'default'): bool
    {
        return $this->marketplaceSubscription($name)->exists();
    }

    public function onPlan(string $planId, string $name = 'default'): bool
    {
        $subscription = $this->marketplaceSubscription($name)->first();

        return $subscription?->azure_plan_id === $planId;
    }
}
