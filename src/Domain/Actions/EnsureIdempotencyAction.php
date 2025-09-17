<?php

namespace Ziptied\Bedrock\Domain\Actions;

use Illuminate\Support\Facades\DB;
use Ziptied\Bedrock\Domain\Enums\ActionType;
use Ziptied\Bedrock\Domain\Enums\OperationStatus;
use Ziptied\Bedrock\Domain\Exceptions\IdempotencyException;
use Ziptied\Bedrock\Domain\Models\MarketplaceOperation;
use Ziptied\Bedrock\Domain\Models\MarketplaceSubscription;

class EnsureIdempotencyAction
{
    /**
     * @param array<string, mixed> $payload
     */
    public function handle(string $operationId, ActionType $action, array $payload): MarketplaceOperation
    {
        return DB::transaction(function () use ($operationId, $action, $payload) {
            $operation = MarketplaceOperation::query()
                ->where('azure_operation_id', $operationId)
                ->lockForUpdate()
                ->first();

            if ($operation) {
                throw new IdempotencyException('Operation already processed.');
            }

            $operation = new MarketplaceOperation([
                'azure_operation_id' => $operationId,
            ]);

            $operation->action = $action->value;
            $operation->status = $payload['status'] ?? OperationStatus::InProgress->value;
            $operation->payload = $payload;

            if (isset($payload['subscriptionId'])) {
                $subscription = MarketplaceSubscription::query()
                    ->where(MarketplaceSubscription::providerColumn(), 'azure')
                    ->where('azure_subscription_id', $payload['subscriptionId'])
                    ->first();

                if ($subscription) {
                    $operation->subscription()->associate($subscription);
                }
            }

            $operation->save();

            return $operation;
        });
    }
}
