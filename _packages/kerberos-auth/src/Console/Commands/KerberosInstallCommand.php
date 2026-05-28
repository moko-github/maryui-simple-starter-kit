<?php

namespace MokoGithub\KerberosAuth\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;

class KerberosInstallCommand extends Command
{
    protected $signature = 'kerberos:install';

    protected $description = "Installe l'authentification Kerberos SSO dans l'application Laravel";

    public function handle(): int
    {
        intro("Installation de l'authentification Kerberos...");

        $this->configureMiddleware();
        $this->configureUserModel();
        $this->configureRoutes();
        $this->configureScheduler();
        $this->appendEnvVariables();
        $this->runKerberosMigrations();

        $this->info('✓ Authentification Kerberos installée avec succès.');

        note(
            "Prochaines étapes :\n".
            "  1. Définissez KERBEROS_ENABLED=true dans votre fichier .env\n".
            "  2. Configurez KERBEROS_ADMIN_EMAILS avec les adresses email des administrateurs\n".
            "  3. Configurez votre serveur web (Apache/Nginx) avec le module Kerberos\n".
            '  4. Pour les tests en local, définissez KERBEROS_SIMULATION_MODE=true'
        );

        outro('Installation terminée !');

        return self::SUCCESS;
    }

    protected function configureMiddleware(): void
    {
        $appFile = base_path('bootstrap/app.php');
        $content = File::get($appFile);

        $search = "->withMiddleware(function (Middleware \$middleware): void {\n        //\n    })";
        $replace = "->withMiddleware(function (Middleware \$middleware): void {\n        \$middleware->appendToGroup('web', \\MokoGithub\\KerberosAuth\\Http\\Middleware\\KerberosAuthentication::class);\n        \$middleware->alias(['kerberos.simulation' => \\MokoGithub\\KerberosAuth\\Http\\Middleware\\EnsureKerberosSimulationAllowed::class]);\n    })";

        File::put($appFile, str_replace($search, $replace, $content));
    }

    protected function configureUserModel(): void
    {
        $userFile = base_path('app/Models/User.php');
        $content = File::get($userFile);

        $content = str_replace(
            "'password',\n    ];",
            "'password',\n        'kerberos',\n        'role_id',\n    ];",
            $content
        );

        $roleMethod = "\n    public function role(): \\Illuminate\\Database\\Eloquent\\Relations\\BelongsTo\n    {\n        return \$this->belongsTo(\\MokoGithub\\KerberosAuth\\Models\\Role::class);\n    }\n";

        $lastBrace = strrpos($content, '}');
        $content = substr($content, 0, $lastBrace).$roleMethod.'}'.substr($content, $lastBrace + 1);

        File::put($userFile, $content);
    }

    protected function configureRoutes(): void
    {
        $routesFile = base_path('routes/web.php');

        $kerberosRoutes = "\n\n// Routes d'authentification Kerberos\nRoute::middleware('guest')->group(function (): void {\n    Route::get('/demande-acces', \\MokoGithub\\KerberosAuth\\Livewire\\Auth\\RequestAccess::class)->name('access-request.create');\n    Route::get('/acces-refuse', \\MokoGithub\\KerberosAuth\\Livewire\\Auth\\AccessDenied::class)->name('access-denied');\n});\n";

        File::append($routesFile, $kerberosRoutes);
    }

    protected function configureScheduler(): void
    {
        $consoleFile = base_path('routes/console.php');
        $schedulerEntry = "\n\\Illuminate\\Support\\Facades\\Schedule::command('kerberos:purge-attempts')->dailyAt('03:00');\n";
        File::append($consoleFile, $schedulerEntry);
    }

    protected function appendEnvVariables(): void
    {
        $envFile = base_path('.env');

        if (! File::exists($envFile)) {
            return;
        }

        $envBlock = "\n# Kerberos Authentication\n".
            "KERBEROS_ENABLED=false\n".
            "KERBEROS_SERVER_VAR=REMOTE_USER\n".
            "KERBEROS_FALLBACK_AUTH=true\n".
            "KERBEROS_SIMULATION_MODE=false\n".
            "KERBEROS_ADMIN_EMAILS=\n".
            "KERBEROS_ADMIN_NOTIFICATION_MODE=immediate\n".
            "KERBEROS_AUTO_CLEANUP_DAYS=30\n".
            "KERBEROS_ALLOWED_DOMAINS=\n";

        File::append($envFile, $envBlock);
    }

    protected function runKerberosMigrations(): void
    {
        $this->call('migrate', ['--force' => true]);
        $this->call('db:seed', ['--class' => 'Database\\Seeders\\RolesSeeder', '--force' => true]);
        $this->call('db:seed', ['--class' => 'Database\\Seeders\\KerberosSetupSeeder', '--force' => true]);
    }
}
