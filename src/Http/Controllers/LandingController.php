<?php

namespace Ziptied\Bedrock\Http\Controllers;

use Composer\InstalledVersions;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Ziptied\Bedrock\Domain\Models\MarketplaceSubscription;
use Ziptied\Bedrock\Domain\Pipelines\LandingResolveActivatePipeline;

class LandingController extends Controller
{
    public function __construct(
        private readonly LandingResolveActivatePipeline $pipeline,
        private readonly DatabaseManager $db
    ) {
    }

    public function landing(Request $request): JsonResponse|RedirectResponse
    {
        $data = $this->validate($request, [
            'token' => ['required', 'string'],
        ]);

        $subscription = ($this->pipeline)($data['token']);

        if ($request->expectsJson()) {
            return response()->json([
                'subscription_id' => $subscription->azure_subscription_id,
                'status' => $subscription->azure_status,
            ]);
        }

        $redirectTo = config('azure_marketplace.landing_redirect', '/dashboard');

        return redirect()->to($redirectTo)->with([
            'subscription_id' => $subscription->azure_subscription_id,
        ]);
    }

    public function health(Request $request): JsonResponse
    {
        $version = InstalledVersions::isInstalled('ziptied/bedrock')
            ? InstalledVersions::getPrettyVersion('ziptied/bedrock')
            : 'dev';

        $tables = [
            'subscriptions',
            'subscription_items',
            'marketplace_operations',
            'marketplace_usage_events',
        ];

        $migrations = [];
        foreach ($tables as $table) {
            $migrations[$table] = Schema::hasTable($table);
        }

        $lastOperation = null;
        if ($migrations['marketplace_operations']) {
            $lastOperation = $this->db->table('marketplace_operations')
                ->whereNotNull('processed_at')
                ->max('processed_at');
        }

        return response()->json([
            'version' => $version,
            'config' => [
                'metering_enabled' => (bool) config('azure_marketplace.metering.enabled'),
                'webhook_signature' => (bool) config('azure_marketplace.webhook.shared_secret'),
                'ip_allowlist' => !empty(config('azure_marketplace.webhook.ip_allowlist')),
            ],
            'migrations' => $migrations,
            'last_operation_processed_at' => $lastOperation,
        ]);
    }

    /**
     * Validate the request manually to give clearer feedback to callers.
     */
    private function validate(Request $request, array $rules): array
    {
        $validator = validator($request->all(), $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
