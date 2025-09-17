<?php

return [
    'billable_model' => env('AZURE_MP_BILLABLE_MODEL', App\Models\User::class),

    'route' => [
        'prefix' => env('AZURE_MP_ROUTE_PREFIX', 'marketplace'),
        'middleware' => ['web'],
        'webhook_middleware' => ['api', \Ziptied\Bedrock\Http\Middleware\VerifyMarketplaceSignature::class],
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
        'shared_secret' => env('AZURE_MP_WEBHOOK_SECRET'),
        'ip_allowlist' => array_filter(array_map('trim', explode(',', env('AZURE_MP_WEBHOOK_IPS', '')))),
        'enforce_tls_client_cert' => (bool) env('AZURE_MP_WEBHOOK_MTLS', false),
        'signature_header' => env('AZURE_MP_SIGNATURE_HEADER', 'X-Marketplace-Signature'),
    ],

    'cashier_compat' => [
        'use_cashier_tables' => true,
        'provider_column' => 'provider',
    ],

    'plans' => [
        // 'gold' => ['seats' => 100, 'features' => ['feature-a', 'feature-b']],
    ],

    'metering' => [
        'enabled' => env('AZURE_MP_METERING_ENABLED', false),
        'dimension' => env('AZURE_MP_DIMENSION'),
        'batch_size' => 25,
    ],

    'logging' => [
        'channel' => env('AZURE_MP_LOG_CHANNEL'),
    ],

    'landing_redirect' => env('AZURE_MP_LANDING_REDIRECT', '/dashboard'),
];
