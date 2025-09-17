<?php

namespace Ziptied\Bedrock\Console\Commands;

use Illuminate\Console\Command;
use Ziptied\Bedrock\Domain\Models\MarketplaceOperation;

class SyncOperationsCommand extends Command
{
    protected $signature = 'marketplace:sync-operations {--limit=50 : Number of operations to display}';

    protected $description = 'Display recent Azure Marketplace operations for diagnostics.';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $operations = MarketplaceOperation::query()
            ->latest('updated_at')
            ->limit($limit)
            ->get(['azure_operation_id', 'action', 'status', 'ack_status', 'processed_at'])
            ->toArray();

        if (empty($operations)) {
            $this->info('No marketplace operations recorded.');

            return self::SUCCESS;
        }

        $this->table(['Operation', 'Action', 'Status', 'Ack', 'Processed At'], array_map(function ($row) {
            return [
                $row['azure_operation_id'],
                $row['action'],
                $row['status'],
                $row['ack_status'] ?? '-',
                $row['processed_at'] ?? '-',
            ];
        }, $operations));

        return self::SUCCESS;
    }
}
