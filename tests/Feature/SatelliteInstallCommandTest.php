<?php

use App\Console\Commands\SatelliteInstallCommand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

// ---------------------------------------------------------------------------
// assertKerberosInstalled — test d'intégration (vraie DB SQLite in-memory)
// ---------------------------------------------------------------------------

describe('SatelliteInstallCommand::assertKerberosInstalled', function () {
    it('aborts with a clear message when users.kerberos is missing', function () {
        // La migration de base ne crée pas la colonne kerberos, donc Schema::hasColumn retourne false.
        artisan('satellite:install')
            ->expectsOutputToContain('app:install')
            ->assertExitCode(Command::FAILURE);
    });
});

// ---------------------------------------------------------------------------
// configureUserModel
// ---------------------------------------------------------------------------

describe('SatelliteInstallCommand::configureUserModel', function () {
    it('adds SI columns to $fillable and si_synced_at cast', function () {
        $content = "        'role_id',\n    ];\n\n    'status' => UserStatus::class,";
        $written = null;

        File::shouldReceive('exists')->once()->andReturn(true);
        File::shouldReceive('get')->once()->andReturn($content);
        File::shouldReceive('put')->once()->withArgs(function ($path, $newContent) use (&$written) {
            $written = $newContent;

            return true;
        });

        $method = new ReflectionMethod(SatelliteInstallCommand::class, 'configureUserModel');
        $method->invoke(new SatelliteInstallCommand);

        expect($written)
            ->toContain("'matricule'")
            ->toContain("'rank'")
            ->toContain("'phone_number'")
            ->toContain("'room_number'")
            ->toContain("'entity_name'")
            ->toContain("'si_synced_at'")
            ->toContain("'si_synced_at' => 'datetime'");
    });

    it('is idempotent when SI columns are already present', function () {
        $content = "        'matricule',\n        'si_synced_at',";

        File::shouldReceive('exists')->once()->andReturn(true);
        File::shouldReceive('get')->once()->andReturn($content);
        File::shouldReceive('put')->never();

        $method = new ReflectionMethod(SatelliteInstallCommand::class, 'configureUserModel');
        $method->invoke(new SatelliteInstallCommand);

        expect(substr_count($content, "'matricule'"))->toBe(1);
    });

    it('does nothing when User.php does not exist', function () {
        File::shouldReceive('exists')->once()->andReturn(false);
        File::shouldReceive('get')->never();
        File::shouldReceive('put')->never();

        $method = new ReflectionMethod(SatelliteInstallCommand::class, 'configureUserModel');
        $method->invoke(new SatelliteInstallCommand);
    });
});

// ---------------------------------------------------------------------------
// configureAppServiceProvider
// ---------------------------------------------------------------------------

describe('SatelliteInstallCommand::configureAppServiceProvider', function () {
    it('adds use statement and singleton binding', function () {
        $content = "use App\Models\User;\n\n    public function register(): void\n    {\n        //\n    }";
        $written = null;

        File::shouldReceive('get')->once()->andReturn($content);
        File::shouldReceive('put')->once()->withArgs(function ($path, $newContent) use (&$written) {
            $written = $newContent;

            return true;
        });

        $method = new ReflectionMethod(SatelliteInstallCommand::class, 'configureAppServiceProvider');
        $method->invoke(new SatelliteInstallCommand);

        expect($written)
            ->toContain('use App\Services\ApiSiClient;')
            ->toContain('$this->app->singleton(ApiSiClient::class);');
    });

    it('is idempotent when singleton already registered', function () {
        $content = '$this->app->singleton(ApiSiClient::class);';

        File::shouldReceive('get')->once()->andReturn($content);
        File::shouldReceive('put')->never();

        $method = new ReflectionMethod(SatelliteInstallCommand::class, 'configureAppServiceProvider');
        $method->invoke(new SatelliteInstallCommand);

        expect(substr_count($content, 'singleton(ApiSiClient::class)'))->toBe(1);
    });

    it('does not add duplicate use statement when already present', function () {
        $content = "use App\Models\User;\nuse App\Services\ApiSiClient;\n\n    public function register(): void\n    {\n        //\n    }";
        $written = null;

        File::shouldReceive('get')->once()->andReturn($content);
        File::shouldReceive('put')->once()->withArgs(function ($path, $newContent) use (&$written) {
            $written = $newContent;

            return true;
        });

        $method = new ReflectionMethod(SatelliteInstallCommand::class, 'configureAppServiceProvider');
        $method->invoke(new SatelliteInstallCommand);

        expect(substr_count($written, 'use App\Services\ApiSiClient;'))->toBe(1);
    });
});

// ---------------------------------------------------------------------------
// configureBootstrap
// ---------------------------------------------------------------------------

describe('SatelliteInstallCommand::configureBootstrap', function () {
    it('inserts SyncUserOnAuth after Kerberos alias line when Kerberos is installed', function () {
        $kerberosAlias = "\$middleware->alias(['kerberos.simulation' => \\App\\Http\\Middleware\\EnsureKerberosSimulationAllowed::class]);";
        $content = "->withMiddleware(function (Middleware \$middleware): void {\n        {$kerberosAlias}\n    })";
        $written = null;

        File::shouldReceive('get')->once()->andReturn($content);
        File::shouldReceive('put')->once()->withArgs(function ($path, $newContent) use (&$written) {
            $written = $newContent;

            return true;
        });

        $method = new ReflectionMethod(SatelliteInstallCommand::class, 'configureBootstrap');
        $method->invoke(new SatelliteInstallCommand);

        expect($written)
            ->toContain('SyncUserOnAuth::class')
            ->toContain("validateCsrfTokens(except: ['webhooks/api-si'])");
    });

    it('uses fallback insertion when Kerberos is not installed', function () {
        $content = "->withMiddleware(function (Middleware \$middleware): void {\n        //\n    })";
        $written = null;

        File::shouldReceive('get')->once()->andReturn($content);
        File::shouldReceive('put')->once()->withArgs(function ($path, $newContent) use (&$written) {
            $written = $newContent;

            return true;
        });

        $method = new ReflectionMethod(SatelliteInstallCommand::class, 'configureBootstrap');
        $method->invoke(new SatelliteInstallCommand);

        expect($written)
            ->toContain('SyncUserOnAuth::class')
            ->toContain("validateCsrfTokens(except: ['webhooks/api-si'])");
    });

    it('is idempotent when SyncUserOnAuth already present', function () {
        $content = 'SyncUserOnAuth::class';

        File::shouldReceive('get')->once()->andReturn($content);
        File::shouldReceive('put')->never();

        $method = new ReflectionMethod(SatelliteInstallCommand::class, 'configureBootstrap');
        $method->invoke(new SatelliteInstallCommand);

        expect(substr_count($content, 'SyncUserOnAuth'))->toBe(1);
    });
});

// ---------------------------------------------------------------------------
// configureWebhookRoute
// ---------------------------------------------------------------------------

describe('SatelliteInstallCommand::configureWebhookRoute', function () {
    it('appends the webhook route to routes/web.php', function () {
        $content = "require __DIR__.'/auth.php';\n";
        $appended = null;

        File::shouldReceive('get')->once()->andReturn($content);
        File::shouldReceive('append')->once()->withArgs(function ($path, $block) use (&$appended) {
            $appended = $block;

            return true;
        });

        $method = new ReflectionMethod(SatelliteInstallCommand::class, 'configureWebhookRoute');
        $method->invoke(new SatelliteInstallCommand);

        expect($appended)
            ->toContain('WebhookController')
            ->toContain('VerifyWebhookSignature')
            ->toContain('webhooks.api-si');
    });

    it('is idempotent when webhook route already present', function () {
        $content = "->name('webhooks.api-si');";

        File::shouldReceive('get')->once()->andReturn($content);
        File::shouldReceive('append')->never();

        $method = new ReflectionMethod(SatelliteInstallCommand::class, 'configureWebhookRoute');
        $method->invoke(new SatelliteInstallCommand);

        expect(substr_count($content, 'webhooks.api-si'))->toBe(1);
    });
});

// ---------------------------------------------------------------------------
// configureScheduler
// ---------------------------------------------------------------------------

describe('SatelliteInstallCommand::configureScheduler', function () {
    it('appends both sync jobs to routes/console.php', function () {
        $content = "Artisan::command('inspire', function () {})->purpose('Display an inspiring quote');\n";
        $appended = null;

        File::shouldReceive('get')->once()->andReturn($content);
        File::shouldReceive('append')->once()->withArgs(function ($path, $block) use (&$appended) {
            $appended = $block;

            return true;
        });

        $method = new ReflectionMethod(SatelliteInstallCommand::class, 'configureScheduler');
        $method->invoke(new SatelliteInstallCommand);

        expect($appended)
            ->toContain('SyncSiUsersJob')
            ->toContain('SyncAdminUsersJob')
            ->toContain('everyFifteenMinutes')
            ->toContain('withoutOverlapping');
    });

    it('is idempotent when SyncSiUsersJob already scheduled', function () {
        $content = 'Schedule::job(new \App\Jobs\SyncSiUsersJob)';

        File::shouldReceive('get')->once()->andReturn($content);
        File::shouldReceive('append')->never();

        $method = new ReflectionMethod(SatelliteInstallCommand::class, 'configureScheduler');
        $method->invoke(new SatelliteInstallCommand);

        expect(substr_count($content, 'SyncSiUsersJob'))->toBe(1);
    });
});

// ---------------------------------------------------------------------------
// configureLoggingChannel
// ---------------------------------------------------------------------------

describe('SatelliteInstallCommand::configureLoggingChannel', function () {
    it("inserts the api-si channel before 'emergency'", function () {
        $content = "        'emergency' => [\n            'path' => storage_path('logs/laravel.log'),\n        ],\n\n    ],\n\n];\n";
        $written = null;

        File::shouldReceive('get')->once()->andReturn($content);
        File::shouldReceive('put')->once()->withArgs(function ($path, $newContent) use (&$written) {
            $written = $newContent;

            return true;
        });

        $method = new ReflectionMethod(SatelliteInstallCommand::class, 'configureLoggingChannel');
        $method->invoke(new SatelliteInstallCommand);

        expect($written)
            ->toContain("'api-si' =>")
            ->toContain("'driver' => 'daily'")
            ->toContain("logs/api-si.log")
            ->toContain("LOG_API_SI_LEVEL");

        // L'entrée 'emergency' doit toujours être présente après l'insertion.
        expect(str_contains($written, "'emergency' =>"))->toBeTrue();
    });

    it("is idempotent when api-si channel already present", function () {
        $content = "        'api-si' => [\n            'driver' => 'daily',\n        ],";

        File::shouldReceive('get')->once()->andReturn($content);
        File::shouldReceive('put')->never();

        $method = new ReflectionMethod(SatelliteInstallCommand::class, 'configureLoggingChannel');
        $method->invoke(new SatelliteInstallCommand);

        expect(substr_count($content, "'api-si' =>"))->toBe(1);
    });
});

// ---------------------------------------------------------------------------
// appendEnvVariables
// ---------------------------------------------------------------------------

describe('SatelliteInstallCommand::appendEnvVariables', function () {
    it('appends API SI variables to .env and .env.example', function () {
        $envContent = "APP_NAME=Laravel\nAPP_ENV=local\n";
        $exampleContent = "APP_NAME=Laravel\n";
        $appendedEnv = null;
        $appendedExample = null;

        File::shouldReceive('exists')->twice()->andReturn(true);
        File::shouldReceive('get')->twice()->andReturn($envContent, $exampleContent);
        File::shouldReceive('append')->twice()->withArgs(function ($path, $block) use (&$appendedEnv, &$appendedExample) {
            if (str_ends_with($path, '.env.example')) {
                $appendedExample = $block;
            } else {
                $appendedEnv = $block;
            }

            return true;
        });

        $method = new ReflectionMethod(SatelliteInstallCommand::class, 'appendEnvVariables');
        $method->invoke(new SatelliteInstallCommand);

        expect($appendedEnv)
            ->toContain('API_SI_URL')
            ->toContain('API_SI_TOKEN')
            ->toContain('API_SI_WEBHOOK_SECRET')
            ->toContain('LOG_API_SI_LEVEL');

        expect($appendedExample)
            ->toContain('API_SI_URL')
            ->not->toContain('API_SI_WEBHOOK_SECRET='); // le secret est vide dans l'example

        // Le secret dans .env doit être non-vide (généré aléatoirement).
        expect(preg_match('/API_SI_WEBHOOK_SECRET=\S+/', $appendedEnv))->toBe(1);
    });

    it('is idempotent when API_SI_URL already present in .env', function () {
        $envContent = "API_SI_URL=https://api-si.justice.gouv.fr\n";

        File::shouldReceive('exists')->once()->andReturn(true);
        File::shouldReceive('get')->once()->andReturn($envContent);
        File::shouldReceive('append')->never();

        // On simule l'absence de .env.example pour simplifier.
        File::shouldReceive('exists')->once()->andReturn(false);

        $method = new ReflectionMethod(SatelliteInstallCommand::class, 'appendEnvVariables');
        $method->invoke(new SatelliteInstallCommand);

        expect(substr_count($envContent, 'API_SI_URL'))->toBe(1);
    });
});
