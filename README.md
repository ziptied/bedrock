# Bedrock for Laravel

Azure Commercial Marketplace SaaS plumbing for Laravel applications. Bedrock replaces the Microsoft SaaS Accelerator with a first-class, testable package that integrates cleanly with Laravel's queues, events, HTTP client, and Cashier-compatible database layout.

<p align="center">
    <a href="https://github.com/ziptied/bedrock/actions"><img src="https://github.com/ziptied/bedrock/actions/workflows/tests.yml/badge.svg" alt="CI"></a>
    <a href="https://packagist.org/packages/ziptied/bedrock"><img src="https://img.shields.io/packagist/v/ziptied/bedrock.svg" alt="Packagist"></a>
    <a href="https://packagist.org/packages/ziptied/bedrock"><img src="https://img.shields.io/packagist/php-v/ziptied/bedrock.svg" alt="PHP Version"></a>
</p>

## Highlights

- ✅ End-to-end Azure SaaS fulfillment lifecycle (landing, resolve, activate, webhook operations, and ACK loop)
- ✅ Cashier-compatible schema while remaining framework-agnostic about billing provider
- ✅ Idempotent operation handling with replay protection
- ✅ Optional metering pipeline with batch emission support
- ✅ Hardened webhook verification (HMAC shared secret, IP allow list, mTLS toggle)
- ✅ Action & pipeline architecture with first-class test coverage using Pest and Orchestra Testbench

## Installation

Install the package via Composer:

```bash
composer require ziptied/bedrock
```

Publish the configuration and database migrations:

```bash
php artisan vendor:publish --provider="Ziptied\\Bedrock\\AzureMarketplaceServiceProvider"
php artisan migrate
```

Register your own marketplace provisioner implementation (optional) by binding the `Ziptied\Bedrock\Domain\Contracts\Provisioner` interface in your `AppServiceProvider` or a dedicated service provider.

## Configuration

All settings live in `config/azure_marketplace.php`:

- **billable_model** &mdash; Fully-qualified class name of the model that represents the billable customer (defaults to `App\Models\User`).
- **route prefix/middleware** &mdash; Customize landing and webhook route groups; webhook middleware defaults to `['api']` plus the signed request middleware.
- **OAuth credentials** &mdash; Tenant ID, client ID, client secret, and scope for Azure Marketplace APIs.
- **Endpoints & versions** &mdash; Override Azure API base URLs or api-version strings if Microsoft introduces breaking revisions.
- **Webhook security** &mdash; Shared secret, IP allow list, and optional mutual TLS enforcement.
- **Cashier compatibility** &mdash; Controls whether generated tables align with Laravel Cashier naming and columns.
- **Plans & metering** &mdash; Describe plan-specific features and configure metering enablement, dimension defaults, and batch size.
- **Logging & redirects** &mdash; Choose a log channel and the redirect path after landing flow activation.

Environment variables such as `AZURE_MP_TENANT_ID`, `AZURE_MP_CLIENT_ID`, `AZURE_MP_CLIENT_SECRET`, `AZURE_MP_WEBHOOK_SECRET`, and `AZURE_MP_ROUTE_PREFIX` can be set to tailor behavior per environment.

## Usage Overview

- **Landing Flow**: `LandingController` orchestrates resolve → provision → activate using the `LandingResolveActivatePipeline`.
- **Webhook Handling**: `WebhookController` validates signatures, ensures idempotency, dispatches specific action handlers, and optionally triggers the ACK loop.
- **Actions**: Each marketplace behavior (`ActivateSubscriptionAction`, `ApplyChangePlanAction`, `EmitMeteringBatchAction`, etc.) is encapsulated in a dedicated, easily testable action class.
- **Events**: Lifecycle hooks such as `SubscriptionResolved`, `PlanChanged`, and `QuantityChanged` enable application-level integrations.
- **Metering**: When enabled, `EmitMeteringBatchAction` (and the accompanying console command) submit batched usage events to Microsoft and persist responses for auditing.

Refer to the `tests/` directory for example-driven documentation of each action.

## Testing

The project ships with a comprehensive Pest suite.

```bash
composer test
```

Tests execute inside an in-memory SQLite database via Orchestra Testbench. Custom migrations for your application can be placed under `tests/database/migrations` and will be executed automatically.

## Contributing

1. Fork the repository and create a feature branch.
2. Run `composer install` and `composer test` locally.
3. Adhere to PSR-12 coding standards and the existing action/pipeline architecture.
4. Include Pest tests for any new or modified actions.
5. Submit a pull request describing your changes.

Security vulnerabilities or disclosures should be reported privately to [support@ziptied.com](mailto:support@ziptied.com).

## Releasing on Packagist

1. Ensure `composer.json` is updated with the desired version constraints and metadata.
2. Tag a semantic version (for example, `git tag v1.0.0 && git push origin v1.0.0`).
3. Submit the repository URL to [Packagist](https://packagist.org/packages/submit) or enable auto-updates via GitHub integration.

## License

Bedrock is open-sourced software licensed under the [MIT license](LICENSE).
