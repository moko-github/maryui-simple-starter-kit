<?php

declare(strict_types=1);

namespace Moko\ApiSi;

use Illuminate\Support\ServiceProvider;
use Moko\ApiSi\Console\Commands\RegisterApiSiWebhook;
use Moko\ApiSi\Console\Commands\SyncAdminUsers;
use Moko\ApiSi\Console\Commands\SyncSiUsers;
use Moko\ApiSi\Services\ApiSiClient;

class ApiSiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/api-si.php', 'api-si');

        $this->app->singleton(ApiSiClient::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/api-si.php' => config_path('api-si.php'),
            ], 'api-si-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'api-si-migrations');

            $this->commands([
                RegisterApiSiWebhook::class,
                SyncSiUsers::class,
                SyncAdminUsers::class,
            ]);
        }
    }
}
