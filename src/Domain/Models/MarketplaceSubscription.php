<?php

namespace Ziptied\Bedrock\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Ziptied\Bedrock\Domain\Enums\SubscriptionState;

class MarketplaceSubscription extends Model
{
    protected $table = 'subscriptions';

    protected $guarded = [];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'ends_at' => 'datetime',
        'azure_term_start' => 'datetime',
        'azure_term_end' => 'datetime',
        'quantity' => 'integer',
        'azure_quantity' => 'integer',
    ];

    protected $attributes = [
        'name' => 'default',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $subscription): void {
            $column = self::providerColumn();
            if (!$subscription->getAttribute($column)) {
                $subscription->setAttribute($column, 'azure');
            }
        });
    }

    public function billable(): BelongsTo
    {
        $model = config('azure_marketplace.billable_model');

        return $this->belongsTo($model ?? 'App\\Models\\User', 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SubscriptionItem::class, 'subscription_id');
    }

    public function scopeAzure(Builder $query): Builder
    {
        return $query->where(self::providerColumn(), 'azure');
    }

    public function isActive(): bool
    {
        return $this->azure_status === SubscriptionState::Active->value;
    }

    public function isSuspended(): bool
    {
        return $this->azure_status === SubscriptionState::Suspended->value;
    }

    public function seats(): ?int
    {
        return $this->azure_quantity ?? $this->quantity;
    }

    public function markStatus(SubscriptionState $state): void
    {
        $this->azure_status = $state->value;
    }

    public static function providerColumn(): string
    {
        return config('azure_marketplace.cashier_compat.provider_column', 'provider');
    }
}
