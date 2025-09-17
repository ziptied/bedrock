<?php

namespace Ziptied\Bedrock\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Ziptied\Bedrock\Domain\Actions\EmitMeteringBatchAction;
use Ziptied\Bedrock\Domain\Dto\UsageEvent;
use Ziptied\Bedrock\Domain\Models\MarketplaceUsageEvent;

class EmitUsageCommand extends Command
{
    protected $signature = 'marketplace:emit-usage
        {--subscription= : Azure subscription ID}
        {--dimension= : Usage dimension}
        {--quantity= : Quantity to report}
        {--at= : Effective start time (ISO8601)}
        {--plan= : Optional plan identifier}';

    protected $description = 'Emit Azure Marketplace usage events either from options or pending rows.';

    public function handle(EmitMeteringBatchAction $emitter): int
    {
        if (!config('azure_marketplace.metering.enabled')) {
            $this->warn('Metering is disabled. Enable azure_marketplace.metering.enabled to emit usage.');

            return self::SUCCESS;
        }

        $events = $this->buildEvents();

        if (empty($events)) {
            $this->info('No usage events to emit.');

            return self::SUCCESS;
        }

        $emitter->handle($events);

        $this->info(sprintf('Dispatched %d usage event(s).', count($events)));

        return self::SUCCESS;
    }

    private function buildEvents(): array
    {
        $subscription = $this->option('subscription');
        $dimension = $this->option('dimension') ?? config('azure_marketplace.metering.dimension');
        $quantity = $this->option('quantity');
        $effective = $this->option('at');

        if ($subscription && $dimension && $quantity) {
            $effectiveTime = $effective
                ? CarbonImmutable::parse($effective)
                : CarbonImmutable::now();

            return [
                new UsageEvent(
                    $subscription,
                    $dimension,
                    (float) $quantity,
                    $effectiveTime,
                    $this->option('plan')
                ),
            ];
        }

        return MarketplaceUsageEvent::query()
            ->whereNull('sent_at')
            ->limit(100)
            ->get()
            ->all();
    }
}
