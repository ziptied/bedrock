<?php

namespace Ziptied\Bedrock\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;
use Ziptied\Bedrock\AzureMarketplaceServiceProvider;
use Ziptied\Bedrock\Tests\Fakes\User;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('azure_marketplace.billable_model', User::class);
        config()->set('azure_marketplace.metering.enabled', true);
        config()->set('azure_marketplace.metering.dimension', 'emails_processed');
        config()->set('azure_marketplace.webhook.shared_secret', null);
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(dirname(__DIR__) . '/src/Database/migrations');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }

    protected function getPackageProviders($app)
    {
        return [AzureMarketplaceServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
