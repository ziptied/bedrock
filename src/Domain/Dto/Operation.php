<?php

namespace Ziptied\Bedrock\Domain\Dto;

use Carbon\CarbonImmutable;
use Ziptied\Bedrock\Domain\Enums\ActionType;
use Ziptied\Bedrock\Domain\Enums\OperationStatus;

final class Operation
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public readonly string $operationId,
        public readonly string $subscriptionId,
        public readonly ActionType $action,
        public readonly OperationStatus $status,
        public readonly ?CarbonImmutable $updatedAt,
        public readonly array $raw,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $operationId = $payload['id'] ?? throw new \InvalidArgumentException('Operation payload missing id.');
        $subscriptionId = $payload['subscriptionId'] ?? throw new \InvalidArgumentException('Operation payload missing subscriptionId.');
        $actionValue = $payload['action'] ?? throw new \InvalidArgumentException('Operation payload missing action.');
        $statusValue = $payload['status'] ?? OperationStatus::InProgress->value;

        $action = ActionType::tryFrom($actionValue) ?? ActionType::Pending;
        $status = OperationStatus::tryFrom($statusValue) ?? OperationStatus::InProgress;

        $updatedAt = isset($payload['updateTime']) ? CarbonImmutable::parse($payload['updateTime']) : null;

        return new self(
            $operationId,
            $subscriptionId,
            $action,
            $status,
            $updatedAt,
            $payload,
        );
    }
}
