<?php

namespace Ziptied\Bedrock\Jobs;

use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;
use Ziptied\Bedrock\Domain\Actions\AcquireTokenAction;
use Ziptied\Bedrock\Domain\Actions\ApplyChangePlanAction;
use Ziptied\Bedrock\Domain\Actions\ApplyChangeQuantityAction;
use Ziptied\Bedrock\Domain\Actions\ApplyReinstateAction;
use Ziptied\Bedrock\Domain\Actions\ApplyRenewAction;
use Ziptied\Bedrock\Domain\Actions\ApplySuspendAction;
use Ziptied\Bedrock\Domain\Actions\ApplyUnsubscribeAction;
use Ziptied\Bedrock\Domain\Actions\GetOperationAction;
use Ziptied\Bedrock\Domain\Actions\PatchOperationAction;
use Ziptied\Bedrock\Domain\Enums\AckStatus;
use Ziptied\Bedrock\Domain\Enums\ActionType;
use Ziptied\Bedrock\Domain\Enums\OperationStatus;
use Ziptied\Bedrock\Domain\Events\PlanChanged;
use Ziptied\Bedrock\Domain\Events\QuantityChanged;
use Ziptied\Bedrock\Domain\Events\Reinstated;
use Ziptied\Bedrock\Domain\Events\Renewed;
use Ziptied\Bedrock\Domain\Events\Suspended;
use Ziptied\Bedrock\Domain\Events\Unsubscribed;
use Ziptied\Bedrock\Domain\Models\MarketplaceOperation;
use Ziptied\Bedrock\Domain\Models\MarketplaceSubscription;

class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly int $operationId)
    {
    }

    public function handle(
        AcquireTokenAction $acquireToken,
        GetOperationAction $getOperation,
        PatchOperationAction $patchOperation,
        ApplyChangePlanAction $applyChangePlan,
        ApplyChangeQuantityAction $applyChangeQuantity,
        ApplySuspendAction $applySuspend,
        ApplyReinstateAction $applyReinstate,
        ApplyUnsubscribeAction $applyUnsubscribe,
        ApplyRenewAction $applyRenew,
        Dispatcher $events
    ): void {
        $operation = MarketplaceOperation::query()->find($this->operationId);
        if (!$operation) {
            return;
        }

        $payload = $operation->payload ?? [];
        $action = ActionType::tryFrom($payload['action'] ?? '') ?? ActionType::Pending;
        $subscriptionId = $payload['subscriptionId'] ?? null;

        if (!$subscriptionId) {
            $operation->markAck(AckStatus::Failure);
            return;
        }

        $ackRequired = in_array($action, [ActionType::ChangePlan, ActionType::ChangeQuantity, ActionType::Reinstate], true);
        $accessToken = $ackRequired ? $acquireToken->handle() : null;

        if ($ackRequired && $accessToken) {
            $remoteOperation = $getOperation->handle($accessToken, $subscriptionId, $operation->azure_operation_id, $action);
            $operation->markStatus($remoteOperation->status);
        }

        $subscription = MarketplaceSubscription::query()
            ->where(MarketplaceSubscription::providerColumn(), 'azure')
            ->where('azure_subscription_id', $subscriptionId)
            ->first();

        if (!$subscription) {
            if ($ackRequired && $accessToken) {
                $patchOperation->handle($accessToken, $subscriptionId, $operation->azure_operation_id, AckStatus::Failure, 'Subscription not found locally.');
            }

            $operation->markAck(AckStatus::Failure);

            return;
        }

        try {
            $this->applyAction(
                $action,
                $subscription,
                $payload,
                $applyChangePlan,
                $applyChangeQuantity,
                $applySuspend,
                $applyReinstate,
                $applyUnsubscribe,
                $applyRenew,
                $events
            );

            if ($ackRequired && $accessToken) {
                $patchOperation->handle($accessToken, $subscriptionId, $operation->azure_operation_id, AckStatus::Success);
            }

            $operation->markAck(AckStatus::Success);
        } catch (Throwable $exception) {
            if ($ackRequired && $accessToken) {
                $patchOperation->handle($accessToken, $subscriptionId, $operation->azure_operation_id, AckStatus::Failure, $exception->getMessage());
            }

            $operation->markAck(AckStatus::Failure);

            throw $exception;
        }
    }

    private function applyAction(
        ActionType $action,
        MarketplaceSubscription $subscription,
        array $payload,
        ApplyChangePlanAction $applyChangePlan,
        ApplyChangeQuantityAction $applyChangeQuantity,
        ApplySuspendAction $applySuspend,
        ApplyReinstateAction $applyReinstate,
        ApplyUnsubscribeAction $applyUnsubscribe,
        ApplyRenewAction $applyRenew,
        Dispatcher $events
    ): void {
        switch ($action) {
            case ActionType::ChangePlan:
                $newPlan = $payload['planId'] ?? $subscription->azure_plan_id;
                $oldPlan = $subscription->azure_plan_id;
                $updated = $applyChangePlan->handle($subscription, $newPlan);
                if ($oldPlan !== $newPlan) {
                    $events->dispatch(new PlanChanged($updated, $oldPlan ?? '', $newPlan));
                }
                break;

            case ActionType::ChangeQuantity:
                $quantity = (int) ($payload['quantity'] ?? $subscription->seats() ?? 0);
                $oldQuantity = $subscription->seats() ?? 0;
                $updated = $applyChangeQuantity->handle($subscription, $quantity);
                if ($oldQuantity !== $quantity) {
                    $events->dispatch(new QuantityChanged($updated, $oldQuantity, $quantity));
                }
                break;

            case ActionType::Suspend:
                $updated = $applySuspend->handle($subscription);
                $events->dispatch(new Suspended($updated));
                break;

            case ActionType::Reinstate:
                $updated = $applyReinstate->handle($subscription);
                $events->dispatch(new Reinstated($updated));
                break;

            case ActionType::Unsubscribe:
                $updated = $applyUnsubscribe->handle($subscription);
                $events->dispatch(new Unsubscribed($updated));
                break;

            case ActionType::Renew:
                $term = $payload['term'] ?? [];
                $termStart = isset($term['startDate']) ? CarbonImmutable::parse($term['startDate']) : null;
                $termEnd = isset($term['endDate']) ? CarbonImmutable::parse($term['endDate']) : null;
                $updated = $applyRenew->handle($subscription, $termStart, $termEnd);
                $events->dispatch(new Renewed($updated, $termStart, $termEnd));
                break;

            default:
                break;
        }
    }
}
