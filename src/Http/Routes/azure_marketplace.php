<?php

use Illuminate\Support\Facades\Route;
use Ziptied\Bedrock\Http\Controllers\LandingController;
use Ziptied\Bedrock\Http\Controllers\WebhookController;

Route::get('/landing', [LandingController::class, 'landing'])->name('azure-marketplace.landing');

Route::middleware(config('azure_marketplace.route.webhook_middleware', ['api']))
    ->post('/webhook', [WebhookController::class, 'handle'])
    ->name('azure-marketplace.webhook');

Route::get('/health', [LandingController::class, 'health'])->name('azure-marketplace.health');
