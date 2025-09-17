<?php

namespace Ziptied\Bedrock\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ziptied\Bedrock\Domain\Enums\AckStatus;
use Ziptied\Bedrock\Domain\Enums\ActionType;
use Ziptied\Bedrock\Domain\Enums\OperationStatus;

class MarketplaceOperation extends Model
{
    protected $table = 'marketplace_operations';

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(MarketplaceSubscription::class, 'subscription_id');
    }

    public function markAck(AckStatus $status): void
    {
        $this->ack_status = $status->value;
        $this->processed_at = CarbonImmutable::now();
        $this->save();
    }

    public function markStatus(OperationStatus $status): void
    {
        $this->status = $status->value;
        $this->save();
    }

    public function actionEnum(): ?ActionType
    {
        return ActionType::tryFrom($this->action ?? '');
    }

    public function ackStatusEnum(): ?AckStatus
    {
        return $this->ack_status ? AckStatus::tryFrom($this->ack_status) : null;
    }
}
