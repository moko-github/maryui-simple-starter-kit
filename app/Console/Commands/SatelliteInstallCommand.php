<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;

/**
 * Programme d'installation interactif d'un satellite client de l'API SI.
 *
 * Reproduit en automatisé les sections 1 à 6 du guide
 * `docs/CREER-UN-SATELLITE.md` (admin-console) :
 *   - publication des stubs `stubs/satellite/`
 *   - patch User model, AppServiceProvider, bootstrap/app.php
 *   - ajout des routes webhook + scheduler
 *   - canal de log dédié + variables d'env
 *   - migration des colonnes SI
 *
 * Prérequis : `php artisan app:install` doit avoir été lancé (colonne
 * `users.kerberos`, table `roles`, modèle `App\Models\Role`).
 */
class SatelliteInstallCommand extends Command
{
    protected $signature = 'satellite:install
                            {--force : Écrase les fichiers existants lors de la publication des stubs}';

    protected $description = "Programme d'installation interactif d'un satellite client de l'API SI";

    public function handle(): int
    {
        intro('Installation du satellite API SI');

        if (! $this->assertKerberosInstalled()) {
            return self::FAILURE;
        }

        $confirm = confirm(
            label: 'Installer le client API SI (sections 1 à 6 du guide CREER-UN-SATELLITE.md) ?',
            default: true,
            hint: 'Publie les stubs satellite/, patche bootstrap/app.php, routes/, config/logging.php et .env.',
        );

        if (! $confirm) {
            note('Installation annulée.');

            return self::SUCCESS;
        }

        $this->publishStubs();
        $this->configureUserModel();
        $this->configureAppServiceProvider();
        $this->configureBootstrap();
        $this->configureWebhookRoute();
        $this->configureScheduler();
        $this->configureLoggingChannel();
        $this->appendEnvVariables();
        $this->runMigration();

        $this->info('✓ Satellite API SI installé.');

        outro(
            "Prochaines étapes :\n".
            "  1. Renseigne API_SI_URL et API_SI_TOKEN dans ton fichier .env\n".
            "  2. Demande à l'équipe API SI un client + token avec les abilities :\n".
            "     users:read users:sync entities:read entities:sync webhooks:manage\n".
            "  3. (Post-déploiement) Lance: php artisan webhooks:register\n".
            '  4. Le scheduler exécute automatiquement sync:si-users + admin:sync-users toutes les 15 min.'
        );

        return self::SUCCESS;
    }

    protected function assertKerberosInstalled(): bool
    {
        if (! Schema::hasColumn('users', 'kerberos')) {
            error(
                "La colonne 'users.kerberos' est absente.\n".
                "Lance d'abord:\n".
                "  php artisan app:install\n".
                "  (et active l'authentification Kerberos)\n".
                'puis ré-exécute satellite:install.'
            );

            return false;
        }

        return true;
    }

    protected function publishStubs(): void
    {
        $stubsPath = base_path('stubs/satellite');

        if (! File::isDirectory($stubsPath)) {
            $this->warn("Répertoire de stubs introuvable : {$stubsPath}");

            return;
        }

        $force = (bool) $this->option('force');
        $copied = 0;
        $skipped = 0;

        foreach (File::allFiles($stubsPath) as $file) {
            $relativePath = $file->getRelativePathname();
            $destination = base_path($relativePath);

            if (File::exists($destination) && ! $force) {
                $skipped++;

                continue;
            }

            File::ensureDirectoryExists(dirname($destination));
            File::copy($file->getPathname(), $destination);
            $copied++;
        }

        note("Stubs satellite : {$copied} publiés, {$skipped} ignorés (déjà présents).");
    }

    protected function configureUserModel(): void
    {
        $userFile = base_path('app/Models/User.php');

        if (! File::exists($userFile)) {
            return;
        }

        $content = File::get($userFile);

        if (str_contains($content, "'matricule'")) {
            return;
        }

        // Kerberos a déjà ajouté kerberos et role_id à $fillable ; on ajoute les colonnes SI.
        $content = str_replace(
            "'role_id',\n    ];",
            "'role_id',\n        'matricule',\n        'rank',\n        'phone_number',\n        'room_number',\n        'entity_name',\n        'si_synced_at',\n    ];",
            $content
        );

        // Cast si_synced_at => datetime
        $content = str_replace(
            "'status' => UserStatus::class,",
            "'status' => UserStatus::class,\n            'si_synced_at' => 'datetime',",
            $content
        );

        File::put($userFile, $content);
    }

    protected function configureAppServiceProvider(): void
    {
        $file = base_path('app/Providers/AppServiceProvider.php');
        $content = File::get($file);

        if (str_contains($content, 'singleton(ApiSiClient::class)')) {
            return;
        }

        if (! str_contains($content, 'use App\Services\ApiSiClient;')) {
            $content = str_replace(
                'use App\Models\User;',
                "use App\Models\User;\nuse App\Services\ApiSiClient;",
                $content
            );
        }

        $content = str_replace(
            "    public function register(): void\n    {\n        //\n    }",
            "    public function register(): void\n    {\n        \$this->app->singleton(ApiSiClient::class);\n    }",
            $content
        );

        File::put($file, $content);
    }

    protected function configureBootstrap(): void
    {
        $file = base_path('bootstrap/app.php');
        $content = File::get($file);

        if (str_contains($content, 'SyncUserOnAuth')) {
            return;
        }

        $kerberosAlias = "\$middleware->alias(['kerberos.simulation' => \\App\\Http\\Middleware\\EnsureKerberosSimulationAllowed::class]);";

        if (str_contains($content, $kerberosAlias)) {
            $insertion = $kerberosAlias.
                "\n        \$middleware->appendToGroup('web', \\App\\Http\\Middleware\\SyncUserOnAuth::class);".
                "\n        \$middleware->validateCsrfTokens(except: ['webhooks/api-si']);";
            $content = str_replace($kerberosAlias, $insertion, $content);
        } else {
            $search = "->withMiddleware(function (Middleware \$middleware): void {\n        //\n    })";
            $replace = "->withMiddleware(function (Middleware \$middleware): void {\n        \$middleware->appendToGroup('web', \\App\\Http\\Middleware\\SyncUserOnAuth::class);\n        \$middleware->validateCsrfTokens(except: ['webhooks/api-si']);\n    })";
            $content = str_replace($search, $replace, $content);
        }

        File::put($file, $content);
    }

    protected function configureWebhookRoute(): void
    {
        $file = base_path('routes/web.php');
        $content = File::get($file);

        if (str_contains($content, 'webhooks.api-si')) {
            return;
        }

        $route = "\n\n// Récepteur webhook API SI (signé HMAC, hors auth, exclu CSRF dans bootstrap/app.php)\n".
            "Route::post('/webhooks/api-si', [\\App\\Http\\Controllers\\WebhookController::class, 'handle'])\n".
            "    ->middleware(\\App\\Http\\Middleware\\VerifyWebhookSignature::class)\n".
            "    ->name('webhooks.api-si');\n";

        File::append($file, $route);
    }

    protected function configureScheduler(): void
    {
        $file = base_path('routes/console.php');
        $content = File::get($file);

        if (str_contains($content, 'SyncSiUsersJob')) {
            return;
        }

        $entry = "\n\\Illuminate\\Support\\Facades\\Schedule::job(new \\App\\Jobs\\SyncSiUsersJob)->everyFifteenMinutes()->withoutOverlapping();\n".
            "\\Illuminate\\Support\\Facades\\Schedule::job(new \\App\\Jobs\\SyncAdminUsersJob)->everyFifteenMinutes()->withoutOverlapping();\n";

        File::append($file, $entry);
    }

    protected function configureLoggingChannel(): void
    {
        $file = base_path('config/logging.php');
        $content = File::get($file);

        if (str_contains($content, "'api-si' =>")) {
            return;
        }

        $marker = "        'emergency' => [";
        $insertion = "        'api-si' => [\n".
            "            'driver' => 'daily',\n".
            "            'path'   => storage_path('logs/api-si.log'),\n".
            "            'level'  => env('LOG_API_SI_LEVEL', 'debug'),\n".
            "            'days'   => 14,\n".
            "        ],\n\n".
            "        'emergency' => [";

        $content = str_replace($marker, $insertion, $content);

        File::put($file, $content);
    }

    protected function appendEnvVariables(): void
    {
        $secret = Str::random(64);

        $block = "\n# API SI\n".
            "API_SI_URL=https://api-si.justice.gouv.fr\n".
            "API_SI_TOKEN=\n".
            "API_SI_TIMEOUT=10\n".
            "API_SI_SCRAMBLE_URL=https://api-si.justice.gouv.fr/docs/api\n".
            "LOG_API_SI_LEVEL=debug\n".
            "API_SI_WEBHOOK_SECRET={$secret}\n";

        $envFile = base_path('.env');
        if (File::exists($envFile)) {
            $envContent = File::get($envFile);
            if (! str_contains($envContent, 'API_SI_URL')) {
                File::append($envFile, $block);
            }
        }

        $exampleFile = base_path('.env.example');
        if (File::exists($exampleFile)) {
            $exampleContent = File::get($exampleFile);
            if (! str_contains($exampleContent, 'API_SI_URL')) {
                $exampleBlock = "\n# API SI\n".
                    "API_SI_URL=https://api-si.justice.gouv.fr\n".
                    "API_SI_TOKEN=\n".
                    "API_SI_TIMEOUT=10\n".
                    "API_SI_SCRAMBLE_URL=https://api-si.justice.gouv.fr/docs/api\n".
                    "LOG_API_SI_LEVEL=debug\n".
                    "API_SI_WEBHOOK_SECRET=\n";
                File::append($exampleFile, $exampleBlock);
            }
        }
    }

    protected function runMigration(): void
    {
        $this->call('migrate', ['--force' => true]);
    }
}
