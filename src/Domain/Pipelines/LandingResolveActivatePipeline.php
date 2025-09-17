<?php

namespace Ziptied\Bedrock\Domain\Pipelines;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Ziptied\Bedrock\Domain\Actions\AcquireTokenAction;
use Ziptied\Bedrock\Domain\Actions\ActivateSubscriptionAction;
use Ziptied\Bedrock\Domain\Actions\MapPlanToFeaturesAction;
use Ziptied\Bedrock\Domain\Actions\ResolveSubscriptionAction;
use Ziptied\Bedrock\Domain\Actions\UpsertLocalSubscriptionAction;
use Ziptied\Bedrock\Domain\Contracts\Provisioner;
use Ziptied\Bedrock\Domain\Dto\ResolveResult;
use Ziptied\Bedrock\Domain\Events\SubscriptionActivated;
use Ziptied\Bedrock\Domain\Events\SubscriptionResolved;
use Ziptied\Bedrock\Domain\Models\MarketplaceSubscription;

class LandingResolveActivatePipeline
{
    public function __construct(
        private readonly AcquireTokenAction $acquireToken,
        private readonly ResolveSubscriptionAction $resolve,
        private readonly Provisioner $provisioner,
        private readonly UpsertLocalSubscriptionAction $upsert,
        private readonly MapPlanToFeaturesAction $mapPlan,
        private readonly ActivateSubscriptionAction $activate,
        private readonly Dispatcher $events
    ) {
    }

    public function __invoke(string $resolveToken): MarketplaceSubscription
    {
        try {
            $accessToken = ($this->acquireToken)();
        } catch (\Throwable $exception) {
            logger()->error('Failed to acquire Azure Marketplace access token.', [
                'resolve_token' => $resolveToken,
                'exception' => $exception,
            ]);

            throw new \RuntimeException('Unable to acquire Azure Marketplace access token.', 0, $exception);
        }

        try {
            $resolveResult = $this->resolve->handle($accessToken, $resolveToken);
        } catch (\Throwable $exception) {
            logger()->error('Failed to resolve Azure Marketplace subscription.', [
                'resolve_token' => $resolveToken,
                'exception' => $exception,
            ]);

            throw new \RuntimeException('Unable to resolve Azure Marketplace subscription.', 0, $exception);
        }

        try {
            $billable = $this->provisioner->provision($resolveResult->raw);
        } catch (\Throwable $exception) {
            logger()->error('Provisioner failed to create billable entity.', [
                'subscription_id' => $resolveResult->subscriptionId,
                'plan_id' => $resolveResult->planId,
                'exception' => $exception,
            ]);

            throw new \RuntimeException('Unable to provision billable entity for Azure Marketplace subscription.', 0, $exception);
        }

        try {
            $subscription = $this->upsert->handle($resolveResult, $billable);
        } catch (\Throwable $exception) {
            logger()->error('Failed to upsert marketplace subscription.', [
                'subscription_id' => $resolveResult->subscriptionId,
                'plan_id' => $resolveResult->planId,
                'exception' => $exception,
            ]);

            throw new \RuntimeException('Unable to upsert marketplace subscription.', 0, $exception);
        }

        $subscriptionFresh = $subscription->fresh();

        try {
            $features = $this->mapPlan->handle($resolveResult->planId);
        } catch (\Throwable $exception) {
            logger()->error('Failed to map plan to features.', [
                'plan_id' => $resolveResult->planId,
                'subscription_id' => $resolveResult->subscriptionId,
                'exception' => $exception,
            ]);

            throw new \RuntimeException('Unable to map plan to features.', 0, $exception);
        }

        $this->events->dispatch(new SubscriptionResolved($subscriptionFresh, [
            'resolve' => $resolveResult->raw,
            'features' => $features,
        ]));

        $quantity = $resolveResult->quantity ?? 1;

        try {
            $this->activate->handle($accessToken, $resolveResult->subscriptionId, $resolveResult->planId, $quantity);
        } catch (\Throwable $exception) {
            logger()->error('Failed to activate Azure Marketplace subscription.', [
                'subscription_id' => $resolveResult->subscriptionId,
                'plan_id' => $resolveResult->planId,
                'quantity' => $quantity,
                'exception' => $exception,
            ]);

            throw new \RuntimeException('Unable to activate Azure Marketplace subscription.', 0, $exception);
        }

        $subscription = $subscription->fresh();
        $this->events->dispatch(new SubscriptionActivated($subscription));

        return $subscription;
    }
}
