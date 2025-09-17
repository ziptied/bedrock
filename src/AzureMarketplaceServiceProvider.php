<?php

namespace Ziptied\Bedrock;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Ziptied\Bedrock\Clients\DefaultMarketplaceHttpClient;
use Ziptied\Bedrock\Console\Commands\EmitUsageCommand;
use Ziptied\Bedrock\Console\Commands\SyncOperationsCommand;
use Ziptied\Bedrock\Domain\Contracts\MarketplaceHttpClient;
use Ziptied\Bedrock\Domain\Contracts\Provisioner;
use Ziptied\Bedrock\Domain\Support\DefaultProvisioner;

class AzureMarketplaceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/Config/azure_marketplace.php', 'azure_marketplace');

        $this->app->bind(MarketplaceHttpClient::class, DefaultMarketplaceHttpClient::class);
        $this->app->singleton(Provisioner::class, function (Application $app) {
            return new DefaultProvisioner($app['config']['azure_marketplace']['billable_model']);
        });
    }

    public function boot(Dispatcher $events): void
    {
        $configPath = function_exists('config_path') ? config_path('azure_marketplace.php') : base_path('config/azure_marketplace.php');
        $migrationPath = function_exists('database_path') ? database_path('migrations') : base_path('database/migrations');

        $this->publishes([
            __DIR__ . '/Config/azure_marketplace.php' => $configPath,
        ], 'azure-marketplace-config');

        $this->publishes([
            __DIR__ . '/Database/migrations/' => $migrationPath,
        ], 'azure-marketplace-migrations');

        $this->registerRoutes();
        $this->registerCommands();
    }

    protected function registerRoutes(): void
    {
        $routeConfig = config('azure_marketplace.route');

        Route::middleware($routeConfig['middleware'] ?? ['web'])
            ->prefix($routeConfig['prefix'] ?? 'marketplace')
            ->group(__DIR__ . '/Http/Routes/azure_marketplace.php');
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                EmitUsageCommand::class,
                SyncOperationsCommand::class,
            ]);
        }
    }
}
