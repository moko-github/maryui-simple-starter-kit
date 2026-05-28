<?php

namespace MokoGithub\KerberosAuth;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use MokoGithub\KerberosAuth\Console\Commands\KerberosInstallCommand;
use MokoGithub\KerberosAuth\Console\Commands\PurgeKerberosAttempts;
use MokoGithub\KerberosAuth\Livewire\Auth\AccessDenied;
use MokoGithub\KerberosAuth\Livewire\Auth\RequestAccess;
use MokoGithub\KerberosAuth\Livewire\Auth\SimulateKerberos;
use MokoGithub\KerberosAuth\Livewire\Auth\SimulationBanner;

class KerberosServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/kerberos.php', 'kerberos');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'kerberos-auth');

        Livewire::component('auth.access-denied', AccessDenied::class);
        Livewire::component('auth.request-access', RequestAccess::class);
        Livewire::component('auth.simulate-kerberos', SimulateKerberos::class);
        Livewire::component('auth.simulation-banner', SimulationBanner::class);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/kerberos.php' => config_path('kerberos.php'),
            ], 'kerberos-config');

            $this->publishes([
                __DIR__.'/../database/seeders' => database_path('seeders'),
            ], 'kerberos-seeders');

            $this->commands([
                KerberosInstallCommand::class,
                PurgeKerberosAttempts::class,
            ]);
        }
    }
}
