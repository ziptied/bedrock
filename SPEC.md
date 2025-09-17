# SPEC: `laravel-azure-marketplace` (Standalone Laravel package to replace Microsoft SaaS Accelerator)

> Purpose: Implement the Azure Commercial Marketplace SaaS plumbing **inside Laravel**, with a thin, testable, production-ready package that is compatible with **Cashier tables/settings** so it can run side-by-side with Stripe/Cashier later without divergent handling. **Cashier is NOT required** but database layout must be Cashier-compatible/superset. Use **action/pipeline pattern**; **actions are unit-tested**, pipelines are not. Use **backed enums** wherever possible.

---

## 0. Package metadata

**Package name:** `vendor/laravel-azure-marketplace`  
**Namespace root:** `Vendor\\AzureMarketplace`  
**PHP/Laravel:** PHP >= 8.2, Laravel >= 10.x (HTTP Client, Bus, Events, Migrations, Eloquent, Route Model Binding)  
**Testing:** PHPUnit or Pest (use Pest), Orchestra Testbench for package tests  
**HTTP:** Use Laravel `Http` client (`Illuminate\\Support\\Facades\\Http`)  
**Queue:** Use Laravel queue for async work (default sync driver supported, but queue recommended)

**High-level capabilities**

- Fulfillment lifecycle:
  - Landing flow: `Resolve` → provision/invite → `Activate`
  - Webhook (Operations) handling: `ChangePlan`, `ChangeQuantity`, `Reinstate`, `Suspend`, `Unsubscribe`, `Renew`
  - ACK loop (Operations GET + PATCH) for actionable events (ChangePlan/Quantity/Reinstate)
- Optional **Metering** (batch and single usage events) via command/queued job
- **Idempotency** on operation ID + replay-safe processing
- **Cashier-table compatibility** (works without Cashier, but tables/layout align so Cashier/Stripe can be added later)
- **Configurable billable model** (morph) like Cashier (`billable.model`), default `App\\Models\\User`
- **Backed enums** for marketplace actions, operation statuses, subscription states
- **Action/Pipeline pattern** with small, composable Actions (unit-tested)
- **Events** dispatched for application hooks (before/after each stage)
- Optional **webhook authentication** (HMAC/shared secret allowlist/mTLS toggle-able)
- **Health checks** and **diagnostics** route group (optional)
- **Observability** hooks (PSR-3 logger, event counters)

---

## 1. Folder structure

```
/ (package root)
├─ composer.json
├─ src/
│  ├─ AzureMarketplaceServiceProvider.php
│  ├─ Http/
│  │  ├─ Controllers/
│  │  │  ├─ LandingController.php
│  │  │  └─ WebhookController.php
│  │  ├─ Middleware/
│  │  │  └─ VerifyMarketplaceSignature.php
│  │  └─ Routes/azure_marketplace.php
│  ├─ Config/azure_marketplace.php
│  ├─ Database/
│  │  ├─ migrations/
│  │  │  ├─ 0001_00_00_000000_create_cashier_compatible_tables.php
│  │  │  ├─ 0001_00_00_000001_add_azure_columns_to_cashier_tables.php
│  │  │  └─ 0001_00_00_000002_create_marketplace_aux_tables.php
│  ├─ Domain/
│  │  ├─ Contracts/
│  │  │  ├─ MarketplaceHttpClient.php
│  │  │  └─ Provisioner.php
│  │  ├─ Dto/
│  │  │  ├─ ResolveResult.php
│  │  │  ├─ Operation.php
│  │  │  └─ UsageEvent.php
│  │  ├─ Enums/
│  │  │  ├─ ActionType.php
│  │  │  ├─ OperationStatus.php
│  │  │  ├─ AckStatus.php
│  │  │  └─ SubscriptionState.php
│  │  ├─ Models/
│  │  │  ├─ MarketplaceSubscription.php
│  │  │  ├─ SubscriptionItem.php
│  │  │  └─ Concerns/Billable.php
│  │  ├─ Actions/  # (unit-test these)
│  │  │  ├─ AcquireTokenAction.php
│  │  │  ├─ ResolveSubscriptionAction.php
│  │  │  ├─ ActivateSubscriptionAction.php
│  │  │  ├─ GetOperationAction.php
│  │  │  ├─ PatchOperationAction.php
│  │  │  ├─ UpsertLocalSubscriptionAction.php
│  │  │  ├─ ApplyChangePlanAction.php
│  │  │  ├─ ApplyChangeQuantityAction.php
│  │  │  ├─ ApplySuspendAction.php
│  │  │  ├─ ApplyReinstateAction.php
│  │  │  ├─ ApplyUnsubscribeAction.php
│  │  │  ├─ ApplyRenewAction.php
│  │  │  ├─ VerifyWebhookSignatureAction.php
│  │  │  ├─ EnsureIdempotencyAction.php
│  │  │  ├─ EmitMeteringBatchAction.php
│  │  │  └─ MapPlanToFeaturesAction.php
│  │  ├─ Pipelines/  # (no tests required)
│  │  │  ├─ HandleWebhookPipeline.php
│  │  │  └─ LandingResolveActivatePipeline.php
│  │  ├─ Events/
│  │  │  ├─ SubscriptionResolved.php
│  │  │  ├─ SubscriptionActivated.php
│  │  │  ├─ PlanChanged.php
│  │  │  ├─ QuantityChanged.php
│  │  │  ├─ Suspended.php
│  │  │  ├─ Reinstated.php
│  │  │  ├─ Unsubscribed.php
│  │  │  └─ Renewed.php
│  │  ├─ Exceptions/
│  │  │  ├─ MarketplaceHttpException.php
│  │  │  ├─ WebhookSignatureException.php
│  │  │  └─ IdempotencyException.php
│  │  └─ Support/
│  │     ├─ HashedSignature.php
│  │     └─ Idempotency.php
│  ├─ Console/
│  │  ├─ Commands/
│  │  │  ├─ EmitUsageCommand.php
│  │  │  └─ SyncOperationsCommand.php
│  │  └─ Kernel.php
│  └─ Clients/
│     └─ DefaultMarketplaceHttpClient.php
├─ tests/
│  ├─ PEST.php
│  ├─ TestCase.php
│  ├─ Actions/
│  │  ├─ AcquireTokenActionTest.php
│  │  ├─ ResolveSubscriptionActionTest.php
│  │  ├─ ActivateSubscriptionActionTest.php
│  │  ├─ GetOperationActionTest.php
│  │  ├─ PatchOperationActionTest.php
│  │  ├─ UpsertLocalSubscriptionActionTest.php
│  │  ├─ ApplyChangePlanActionTest.php
│  │  ├─ ApplyChangeQuantityActionTest.php
│  │  ├─ ApplySuspendActionTest.php
│  │  ├─ ApplyReinstateActionTest.php
│  │  ├─ ApplyUnsubscribeActionTest.php
│  │  └─ ApplyRenewActionTest.php
│  └─ Fakes/
│     ├─ FakeMarketplaceHttpClient.php
│     └─ Fixtures/*.json
└─ README.md
```

---

## 2. Composer & service provider

**composer.json**

```json
{
  "name": "vendor/laravel-azure-marketplace",
  "description": "Azure Commercial Marketplace SaaS plumbing for Laravel (Accelerator replacement).",
  "type": "library",
  "license": "MIT",
  "require": {
    "php": ">=8.2",
    "illuminate/support": "^10.0|^11.0",
    "illuminate/http": "^10.0|^11.0",
    "illuminate/database": "^10.0|^11.0",
    "illuminate/events": "^10.0|^11.0",
    "illuminate/bus": "^10.0|^11.0"
  },
  "require-dev": {
    "orchestra/testbench": "^8.0|^9.0",
    "pestphp/pest": "^2.0",
    "pestphp/pest-plugin-laravel": "^2.0",
    "mockery/mockery": "^1.5"
  },
  "autoload": {
    "psr-4": {
      "Vendor\\\\AzureMarketplace\\\\": "src/"
    }
  },
  "extra": {
    "laravel": {
      "providers": [
        "Vendor\\\\AzureMarketplace\\\\AzureMarketplaceServiceProvider"
      ]
    }
  }
}
```

**Service Provider** responsibilities:
- Publish config + migrations
- Register routes (landing, webhook, diagnostics)
- Bind interfaces:
  - `MarketplaceHttpClient::class` → `DefaultMarketplaceHttpClient::class`
  - `Provisioner::class` → default no-op (publishers override in app)
- Register console commands
- Merge config

---

## 3. Configuration (`config/azure_marketplace.php`)

```php
return [
    'billable_model' => env('AZURE_MP_BILLABLE_MODEL', App\Models\User::class),
    'route' => [
        'prefix' => env('AZURE_MP_ROUTE_PREFIX', 'marketplace'),
        'middleware' => ['web'], // webhook can opt into 'api'
        'webhook_middleware' => ['api', \Vendor\AzureMarketplace\Http\Middleware\VerifyMarketplaceSignature::class],
    ],

    // Azure AD OAuth2 client credentials for Marketplace APIs
    'tenant_id' => env('AZURE_MP_TENANT_ID'),
    'client_id' => env('AZURE_MP_CLIENT_ID'),
    'client_secret' => env('AZURE_MP_CLIENT_SECRET'),

    // Endpoints & versions
    'fulfillment_base' => env('AZURE_MP_FULFILLMENT_BASE', 'https://marketplaceapi.microsoft.com/api/saas'),
    'fulfillment_api_version' => env('AZURE_MP_FULFILLMENT_VERSION', '2022-12-01'),
    'metering_base' => env('AZURE_MP_METERING_BASE', 'https://marketplaceapi.microsoft.com/api'),
    'metering_api_version' => env('AZURE_MP_METERING_VERSION', '2018-08-31'),
    'token_scope' => env('AZURE_MP_TOKEN_SCOPE', 'https://marketplaceapi.microsoft.com/.default'),

    // Webhook security
    'webhook' => [
        'shared_secret' => env('AZURE_MP_WEBHOOK_SECRET'), // optional HMAC
        'ip_allowlist' => array_filter(array_map('trim', explode(',', env('AZURE_MP_WEBHOOK_IPS', '')))),
        'enforce_tls_client_cert' => (bool) env('AZURE_MP_WEBHOOK_MTLS', false),
    ],

    // Cashier compatibility / tables
    'cashier_compat' => [
        'use_cashier_tables' => true, // create Cashier-like tables if absent
        'provider_column' => 'provider', // enum: 'azure','stripe','paddle','custom'
        'map_plan_to_price_column' => 'stripe_price', // reuse column for planId
        'map_status_to_stripe_status' => true,
    ],

    // Plan → entitlements map (publisher-defined)
    'plans' => [
        // 'gold' => ['seats' => 100, 'features' => ['x','y']],
    ],

    // Metering
    'metering' => [
        'enabled' => env('AZURE_MP_METERING_ENABLED', false),
        'dimension' => env('AZURE_MP_DIMENSION', null), // default dimension name
        'batch_size' => 25,
    ],

    'logging' => [
        'channel' => env('AZURE_MP_LOG_CHANNEL', null), // null => default
    ],
];
```

---

## 4. Database schema (Cashier-compatible superset)

**Goal:** Work without Cashier installed, but if/when Cashier (Stripe) is added later, both can share tables without conflicts. We create **Cashier-like tables** with **additional Azure columns** and a **`provider` enum**.

### Tables

1) `subscriptions` (Cashier-compatible with extras)
- `id` BIGINT unsigned AI
- `user_id` (or morphs via config if needed; default `unsignedBigInteger` referencing billable)
- `name` string (default 'default')
- `stripe_id` string nullable (unused by Azure)
- `stripe_status` string nullable (for compat; mirror Azure state if `map_status_to_stripe_status = true`)
- `stripe_price` string nullable (store Azure `planId` if `map_plan_to_price_column` = 'stripe_price')
- `quantity` integer nullable (store plan seats/quantity)
- `trial_ends_at` timestamp nullable
- `ends_at` timestamp nullable
- Timestamps

**Azure extensions:**
- `provider` enum: ['azure','stripe','paddle','custom'] default 'azure'
- `azure_subscription_id` uuid indexed
- `azure_offer_id` string nullable
- `azure_plan_id` string nullable
- `azure_status` string nullable  // mapped from SubscriptionState enum
- `azure_quantity` integer nullable
- `azure_term_start` timestamp nullable
- `azure_term_end` timestamp nullable
- unique index on (`provider`,`azure_subscription_id`) where provider='azure'

2) `subscription_items` (optional for metered dimensions/features; Cashier compat)
- `id` BIGINT AI
- `subscription_id` FK → subscriptions
- `stripe_id` string nullable
- `stripe_product` string nullable
- `stripe_price` string nullable
- `quantity` integer nullable
- Timestamps

3) `marketplace_operations` (idempotency + audit)
- `id` BIGINT AI
- `azure_operation_id` uuid unique
- `subscription_id` FK → subscriptions nullable
- `action` string (from ActionType enum)
- `status` string (from OperationStatus enum)
- `payload` json
- `ack_status` string nullable (from AckStatus enum)
- `processed_at` timestamp nullable
- Timestamps

4) `marketplace_usage_events` (optional buffer/batch)
- `id` BIGINT AI
- `subscription_id` FK → subscriptions
- `dimension` string
- `quantity` decimal(12,4)
- `effective_start_time` timestamp
- `plan_id` string nullable
- `sent_at` timestamp nullable
- `response` json nullable
- index (`subscription_id`,`sent_at`)

> Provide migrations `0001_*` that: (a) create tables if missing, (b) add Azure columns if `subscriptions` exists already.

---

## 5. Enums (PHP backed enums)

```php
namespace Vendor\AzureMarketplace\Domain\Enums;

enum ActionType: string {
    case ChangePlan = 'ChangePlan';
    case ChangeQuantity = 'ChangeQuantity';
    case Reinstate = 'Reinstate';
    case Suspend = 'Suspend';
    case Unsubscribe = 'Unsubscribe';
    case Renew = 'Renew';
}

enum OperationStatus: string {
    case InProgress = 'InProgress';
    case Succeeded  = 'Succeeded';
    case Failed     = 'Failed';
}

enum AckStatus: string {
    case Pending  = 'Pending';
    case Success  = 'Success';
    case Failure  = 'Failure';
}

enum SubscriptionState: string {
    case Active     = 'Active';
    case Suspended  = 'Suspended';
    case Unsubscribed = 'Unsubscribed';
    case PendingActivation = 'PendingActivation';
    case PendingFulfillmentStart = 'PendingFulfillmentStart';
    case NotStarted = 'NotStarted';
    case Deleted    = 'Deleted';
}
```

---

## 6. Contracts

```php
namespace Vendor\AzureMarketplace\Domain\Contracts;

interface MarketplaceHttpClient {
    public function acquireToken(): string;
    public function resolve(string $marketplaceToken): array;
    public function activate(string $subscriptionId, string $planId, int $quantity = 1): array;
    public function getOperation(string $subscriptionId, string $operationId): array;
    public function patchOperation(string $subscriptionId, string $operationId, string $status, ?string $reason = null): array;
    public function emitUsageBatch(array $events): array;
}

interface Provisioner {
    /**
     * Called after Resolve and before Activate to ensure a local tenant exists.
     * Return Eloquent model instance representing the billable owner (user/org).
     */
    public function provision(array $resolvePayload): \Illuminate\Database\Eloquent\Model;
}
```

Default `Provisioner` does nothing and returns/creates a placeholder user based on `billable_model` (publisher overrides in app service provider).

---

## 7. DTOs

`ResolveResult`, `Operation`, `UsageEvent` as read-only value objects with typed properties and `fromArray()` factories. (Use simple classes; no tests required unless logic present.)

---

## 8. Models

- `MarketplaceSubscription` → Eloquent model for `subscriptions` table; casts:
  - `provider` to string, default 'azure'
  - `azure_term_start`, `azure_term_end` to datetime
  - accessors like `isActive()`, `isSuspended()`, `seats()`
- `SubscriptionItem` → Eloquent model for `subscription_items`
- Trait `Concerns\Billable` to add to billable model:
  - `subscriptions()` relation
  - `marketplaceSubscription()` helper (provider = 'azure')
  - `subscribed()`/`onPlan($planId)` helpers

---

## 9. HTTP client (default implementation)

`Clients/DefaultMarketplaceHttpClient.php`
- Implements `MarketplaceHttpClient` using Laravel `Http`:
  - `acquireToken()` → OAuth2 client credentials to `https://login.microsoftonline.com/{tenant}/oauth2/v2.0/token` with scope from config
  - Adds `Authorization: Bearer` for Fulfillment/Metering calls
  - Methods build URLs with `api-version` query from config
  - Throw `MarketplaceHttpException` on non-2xx; include response JSON

Inject via container; allow swapping with a Fake in tests.

---

## 10. Routes & Controllers

**Routes** (`src/Http/Routes/azure_marketplace.php`):
```
GET   /{prefix}/landing        → LandingController@landing
POST  /{prefix}/webhook        → WebhookController@handle
GET   /{prefix}/health         → diagnostics (optional)
```

Apply middleware groups from config. `VerifyMarketplaceSignature` supports:
- HMAC `X-Marketplace-Signature` (if configured)
- IP allowlist (if configured)
- mTLS (no code; rely on infra + env flag to enforce)

**LandingController@landing** (Pipeline):
1) Validate `token` query param
2) `AcquireTokenAction`
3) `ResolveSubscriptionAction` (calls Fulfillment `subscriptions/resolve` with header `x-ms-marketplace-token`)
4) `UpsertLocalSubscriptionAction` (create/update `subscriptions` row; attach to billable via Provisioner)
5) Optional `MapPlanToFeaturesAction`
6) `ActivateSubscriptionAction` (Fulfillment `subscriptions/{id}/activate` with planId/quantity)
7) Dispatch `SubscriptionResolved`, `SubscriptionActivated`
8) Redirect → configurable URL or JSON ok

**WebhookController@handle** (Pipeline):
1) `VerifyWebhookSignatureAction`
2) `EnsureIdempotencyAction` (insert/update `marketplace_operations` by `azure_operation_id`)
3) Parse payload → `ActionType` enum
4) For actionable actions: `GetOperationAction` (validate current status/eligibility)
5) Apply action:
   - `ApplyChangePlanAction`
   - `ApplyChangeQuantityAction`
   - `ApplyReinstateAction`
   - `ApplySuspendAction`
   - `ApplyUnsubscribeAction`
   - `ApplyRenewAction`
6) If actionable: `PatchOperationAction` with `Success`/`Failure`
7) Update `marketplace_operations` with `ack_status`, `processed_at`
8) Dispatch domain events (`PlanChanged`, `QuantityChanged`, etc.)
9) Return 204 quickly

**Payload shape (example)**
```json
{
  "id": "e39a4fef-fca2-4950-a694-546bacce3ca8",
  "activityId": "206e...",
  "subscriptionId": "8501dfcd-aa51-413d-bfee-4b7b40980cf7",
  "publisherId": "contoso",
  "offerId": "offer1",
  "planId": "gold",
  "quantity": "25",
  "timeStamp": "2019-04-15T20:17:31.7350641Z",
  "action": "ChangeQuantity",
  "status": "InProgress"
}
```

---

## 11. Actions (unit-test ALL)

> Each action is a small stateless class with `__invoke()` or `handle()` method, receiving typed inputs and returning typed outputs/arrays. Cover **happy path** and **error cases**. Use **Fakes** for HTTP client.

1. **AcquireTokenAction**
   - Input: none
   - Output: access token (string)
   - Calls MarketplaceHttpClient::acquireToken()

2. **ResolveSubscriptionAction**
   - Input: access token, resolve token (string)
   - Output: `ResolveResult` (id, offerId, planId, term, etc.)
   - Validates response keys

3. **ActivateSubscriptionAction**
   - Input: access token, subscriptionId, planId, quantity
   - Output: activation response array
   - Error: throw on non-2xx

4. **GetOperationAction**
   - Input: access token, subscriptionId, operationId
   - Output: `Operation` (status, action)
   - Validates `subscriptionId` and `action` match payload

5. **PatchOperationAction**
   - Input: access token, subscriptionId, operationId, AckStatus (Success/Failure), optional reason
   - Output: response array

6. **UpsertLocalSubscriptionAction**
   - Input: ResolveResult + billable model (from Provisioner)
   - Behavior:
     - Create or update `subscriptions` row with provider='azure'
     - Map `planId` → `stripe_price` if configured
     - Map status → `azure_status` (& optionally `stripe_status`)
     - Set `quantity`
     - Ensure unique (`provider`,`azure_subscription_id`)

7. **ApplyChangePlanAction**
   - Input: subscription model, new planId, (optional) items/features map
   - Behavior: update `azure_plan_id`, `stripe_price` (if mapped), feature flags via event hook
   - Output: void

8. **ApplyChangeQuantityAction**
   - Input: subscription model, new quantity (int)
   - Behavior: update `quantity` & `azure_quantity`

9. **ApplySuspendAction**
   - Input: subscription model
   - Behavior: mark suspended: `azure_status`, optionally `stripe_status`, set `ends_at` or `suspended_at` custom column if added (prefer not to add new column; use `azure_status`)

10. **ApplyReinstateAction**
    - Input: subscription model
    - Behavior: set status active; clear `ends_at` if used

11. **ApplyUnsubscribeAction**
    - Input: subscription model
    - Behavior: mark ended: set `ends_at` now; set status unsubscribed

12. **ApplyRenewAction**
    - Input: subscription model, term dates if provided
    - Behavior: bump `azure_term_end` and maintain active status

13. **VerifyWebhookSignatureAction**
    - Input: request, config
    - Behavior: if `shared_secret` present, compute HMAC over raw body and compare header; if allowlist present, verify IP; if mTLS enabled, check server param; throw `WebhookSignatureException` on failure

14. **EnsureIdempotencyAction**
    - Input: operationId
    - Behavior: upsert `marketplace_operations`; if already processed → throw `IdempotencyException` (controller catches and returns 204)

15. **EmitMeteringBatchAction**
    - Input: array<UsageEvent>
    - Behavior: chunk by 25; call `emitUsageBatch`; persist responses in `marketplace_usage_events`

16. **MapPlanToFeaturesAction** (optional)
    - Input: planId
    - Output: assoc array of entitlements; dispatch event so app can hook

---

## 12. Pipelines (no tests required)

- `LandingResolveActivatePipeline`
  - sequence: AcquireToken → Resolve → Provisioner::provision → UpsertLocal → MapPlanToFeatures → Activate → Events
- `HandleWebhookPipeline`
  - sequence: VerifySignature → EnsureIdempotency → Decode ActionType → (if actionable) AcquireToken + GetOperation → Apply*Action → (if actionable) PatchOperation → Update operation row → Events

Pipelines implemented via simple orchestrator classes; keep no logic inside beyond sequence and branching.

---

## 13. Controllers (only thin orchestration)

**LandingController@landing(Request $req)**  
- Extract `token`
- Run `LandingResolveActivatePipeline`
- On success: redirect to `config('azure_marketplace.landing_redirect')` with `subscription_id` (or return JSON if `Accept: application/json`)

**WebhookController@handle(Request $req)**  
- Run `HandleWebhookPipeline`
- Always return 204 (even on idempotent repeat); log errors

---

## 14. Console commands

- `marketplace:emit-usage`  
  - Options: `--subscription=ID`, `--dimension=NAME`, `--quantity=N`, `--at=ISO8601`
  - Or read pending rows from `marketplace_usage_events` and flush batch

- `marketplace:sync-operations`  
  - Poll operations (optional) if you store IDs; primarily for diagnostics

Register in provider; document usage.

---

## 15. Events (domain)

Dispatch after state changes:
- `SubscriptionResolved($subscription, array $resolve)`
- `SubscriptionActivated($subscription)`
- `PlanChanged($subscription, string $oldPlan, string $newPlan)`
- `QuantityChanged($subscription, int $oldQty, int $newQty)`
- `Suspended($subscription)`
- `Reinstated($subscription)`
- `Unsubscribed($subscription)`
- `Renewed($subscription)`

Allow app to listen and customize provisioning, entitlements, emails, etc.

---

## 16. Tests (Pest + Orchestra)

**Rule:** test **all Actions** in isolation with Fakes; do **not** test Pipelines.

Setup:
- `tests/TestCase.php` uses Orchestra Testbench; loads provider; runs migrations in memory (sqlite)
- Provide `FakeMarketplaceHttpClient`:
  - programmable responses for token, resolve, activate, get/patch operation, emit usage
  - fixtures under `tests/Fakes/Fixtures/*.json`

Write tests for:
- `AcquireTokenActionTest` (returns token; error handling)
- `ResolveSubscriptionActionTest` (happy path; missing keys)
- `ActivateSubscriptionActionTest` (posts payload; handles error)
- `GetOperationActionTest` (validates action/subscription match)
- `PatchOperationActionTest` (sends correct status)
- `UpsertLocalSubscriptionActionTest` (creates Cashier-compatible row; updates on re-resolve; uniqueness)
- `ApplyChangePlanActionTest` (switch plan; maps to `stripe_price` when configured)
- `ApplyChangeQuantityActionTest` (updates quantity/int)
- `ApplySuspendActionTest` / `ApplyReinstateActionTest` / `ApplyUnsubscribeActionTest` / `ApplyRenewActionTest`
- `VerifyWebhookSignatureActionTest` (HMAC valid/invalid; allowlist pass/fail)
- `EnsureIdempotencyActionTest` (first pass OK; second pass throws)

**HTTP client NOT tested here**; covered implicitly by Fake. Pipelines excluded by requirement.

---

## 17. Env variables (.env)

```
AZURE_MP_TENANT_ID=...
AZURE_MP_CLIENT_ID=...
AZURE_MP_CLIENT_SECRET=...
AZURE_MP_TOKEN_SCOPE=https://marketplaceapi.microsoft.com/.default

AZURE_MP_FULFILLMENT_BASE=https://marketplaceapi.microsoft.com/api/saas
AZURE_MP_FULFILLMENT_VERSION=2022-12-01
AZURE_MP_METERING_BASE=https://marketplaceapi.microsoft.com/api
AZURE_MP_METERING_VERSION=2018-08-31

AZURE_MP_ROUTE_PREFIX=marketplace
AZURE_MP_WEBHOOK_SECRET= # optional
AZURE_MP_WEBHOOK_IPS=     # optional CSV
AZURE_MP_WEBHOOK_MTLS=false

AZURE_MP_METERING_ENABLED=false
AZURE_MP_DIMENSION=emails_processed
```

---

## 18. Public API (for host app)

- Routes auto-registered:
  - `GET /marketplace/landing?token=...`
  - `POST /marketplace/webhook`

- Trait for billable model:
```php
use Vendor\AzureMarketplace\Domain\Models\Concerns\Billable;

class User extends Authenticatable {
    use Billable;
}
```

- Provisioner override in app service provider (optional):
```php
$this->app->bind(Provisioner::class, App\Provisioning\MyProvisioner::class);
```

- Emit usage from app code (if enabled):
```php
EmitMeteringBatchAction::dispatchSync([
    new UsageEvent($subscriptionId, 'emails_processed', 123, now()->subHour(), 'gold')
]);
```

- Listen to events to map entitlements, send emails, etc.

---

## 19. Webhook enum + ACK matrix (hard requirements)

- Actions to support: `ChangePlan`, `ChangeQuantity`, `Reinstate` (ACK required), `Suspend`, `Unsubscribe`, `Renew` (notify-only).
- For ACK-required:
  1) Acquire token
  2) Get Operation (`GET /subscriptions/{subscriptionId}/operations/{operationId}`)
  3) Apply local change
  4) Patch Operation (`PATCH ... status=Success|Failure`)

- Always respond 204 quickly; retry-safe; idempotent on `operationId`.

---

## 20. Diagnostics

- `GET /marketplace/health` returns:
  - package version, config flags, DB migrations status, last operation processed timestamp
- Log channel configurable; all HTTP calls log request-id/operation-id

---

## 21. Security

- Optional HMAC verification over raw body (header `X-Marketplace-Signature` or configurable)
- IP allowlist
- mTLS enforcement (infra-based; package checks env flag and server var for certificate presence)
- CSRF disabled for webhook route

---

## 22. Publishing stubs

- `php artisan vendor:publish --tag=azure-marketplace-config`
- `php artisan vendor:publish --tag=azure-marketplace-migrations`

---

## 23. README checklist (for host apps)

- Add env vars
- Add `Billable` trait to billable model
- Optionally bind a custom `Provisioner`
- Configure plans/entitlements map
- Point Partner Center URLs:
  - **Landing URL** → `https://app.example.com/marketplace/landing`
  - **Webhook URL** → `https://app.example.com/marketplace/webhook`
- (Optional) enable metering

---

## 24. Non-goals / out of scope

- No Stripe/Checkout UI, invoices, taxes (that’s Cashier’s domain)
- No SSO with Entra ID (can be built separately)
- No generic tenant/user management beyond subscription rows
- No UI views; package is API-first

---

## 25. Acceptance criteria

- ✅ All required webhook actions handled with correct ACK behavior
- ✅ Landing flow: Resolve → Activate → Upsert local row
- ✅ Cashier-compatible `subscriptions` table exists (superset); app can later install Cashier without migration conflicts
- ✅ Backed enums used for actions, op status, subscription state
- ✅ Action/Pipeline architecture in place
- ✅ Unit tests for **all actions** pass (Pest, Testbench)
- ✅ Configurable billable model and plan mapping
- ✅ Idempotency guarantees on operationId
- ✅ Optional webhook security enabled via config
- ✅ Metering batch supported

---

## 26. Implementation notes

- Keep actions pure and small; inject dependencies via constructor
- Use `Bus::dispatch` / `dispatchSync` where helpful
- Use `DB::transaction` for local state changes around op handling
- Prefer timestamps in UTC (serialize as ISO8601)
- Ensure all external calls include `api-version` query
- Record full webhook payload in `marketplace_operations.payload` for audit
- Gracefully handle missing `quantity` or `planId` in notify-only events
- Avoid throwing in controllers; convert to 204 + log
- Keep public API stable; version package as `v0.x` until real-world validation
