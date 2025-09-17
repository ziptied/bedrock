<?php

namespace Ziptied\Bedrock\Domain\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Ziptied\Bedrock\Domain\Dto\ResolveResult;
use Ziptied\Bedrock\Domain\Models\MarketplaceSubscription;

class UpsertLocalSubscriptionAction
{
    public function handle(ResolveResult $resolve, Model $billable): MarketplaceSubscription
    {
        return DB::transaction(function () use ($resolve, $billable) {
            if (!$billable->exists) {
                $billable->save();
            }

            $subscription = MarketplaceSubscription::query()
                ->where(MarketplaceSubscription::providerColumn(), 'azure')
                ->where('azure_subscription_id', $resolve->subscriptionId)
                ->lockForUpdate()
                ->first();

            if (!$subscription) {
                $subscription = new MarketplaceSubscription();
                $subscription->setAttribute(MarketplaceSubscription::providerColumn(), 'azure');
            }

            $subscription->setAttribute('azure_subscription_id', $resolve->subscriptionId);
            $subscription->setAttribute('azure_offer_id', $resolve->offerId);
            $subscription->setAttribute('azure_plan_id', $resolve->planId);
            $subscription->setAttribute('azure_status', $resolve->status->value);
            $subscription->setAttribute('azure_quantity', $resolve->quantity);
            $subscription->setAttribute('quantity', $resolve->quantity);
            $subscription->setAttribute('azure_term_start', $resolve->termStart);
            $subscription->setAttribute('azure_term_end', $resolve->termEnd);
            $subscription->setAttribute('user_id', $billable->getKey());

            $subscription->save();

            return $subscription;
        });
    }
}
