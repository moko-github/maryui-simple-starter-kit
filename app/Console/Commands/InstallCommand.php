<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;

class InstallCommand extends Command
{
    protected $signature = 'app:install';

    protected $description = 'Interactive installer for the application';

    public function handle(): int
    {
        intro('Welcome to the Application Installer');

        $this->installKerberos();

        outro('Installation complete! Your application is ready.');

        return self::SUCCESS;
    }

    protected function installKerberos(): void
    {
        $install = confirm(
            label: 'Do you want Kerberos authentication (SSO) support?',
            default: false,
            hint: 'Enables Single Sign-On via the REMOTE_USER server variable (requires Apache/Nginx Kerberos module).'
        );

        if (! $install) {
            return;
        }

        note('Installing Kerberos authentication module...');

        $this->publishStubs();
        $this->configureMiddleware();
        $this->configureUserModel();
        $this->configureLoginView();
        $this->configureRoutes();
        $this->configureScheduler();
        $this->appendEnvVariables();
        $this->runKerberosMigrations();

        $this->info('✓ Kerberos authentication installed successfully.');

        note(
            "Next steps:\n".
            "  1. Set KERBEROS_ENABLED=true in your .env file\n".
            "  2. Configure KERBEROS_ADMIN_EMAILS with admin email addresses\n".
            "  3. Configure your web server (Apache/Nginx) with the Kerberos module\n".
            "  4. For local testing, set KERBEROS_SIMULATION_MODE=true"
        );
    }

    protected function publishStubs(): void
    {
        $stubsPath = base_path('stubs/kerberos');

        foreach (File::allFiles($stubsPath) as $file) {
            $relativePath = $file->getRelativePathname();
            $destination = base_path($relativePath);

            File::ensureDirectoryExists(dirname($destination));
            File::copy($file->getPathname(), $destination);
        }
    }

    protected function configureMiddleware(): void
    {
        $appFile = base_path('bootstrap/app.php');
        $content = File::get($appFile);

        $search = "->withMiddleware(function (Middleware \$middleware): void {\n        //\n    })";
        $replace = "->withMiddleware(function (Middleware \$middleware): void {\n        \$middleware->appendToGroup('web', \\App\\Http\\Middleware\\KerberosAuthentication::class);\n        \$middleware->alias(['kerberos.simulation' => \\App\\Http\\Middleware\\EnsureKerberosSimulationAllowed::class]);\n    })";

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

        $roleMethod = "\n    public function role(): \\Illuminate\\Database\\Eloquent\\Relations\\BelongsTo\n    {\n        return \$this->belongsTo(Role::class);\n    }\n";

        $lastBrace = strrpos($content, '}');
        $content = substr($content, 0, $lastBrace).$roleMethod.'}'.substr($content, $lastBrace + 1);

        File::put($userFile, $content);
    }

    protected function configureLoginView(): void
    {
        $loginFile = base_path('resources/views/livewire/auth/login.blade.php');
        $content = File::get($loginFile);

        $content = str_replace(
            "\n</x-layouts::auth>",
            "\n\n    @livewire('auth.simulate-kerberos')\n</x-layouts::auth>",
            $content
        );

        File::put($loginFile, $content);
    }

    protected function configureRoutes(): void
    {
        $routesFile = base_path('routes/web.php');

        $kerberosRoutes = "\n\n// Kerberos authentication routes\nRoute::middleware('guest')->group(function (): void {\n    Route::get('/access-request', \\App\\Livewire\\Auth\\RequestAccess::class)->name('access-request.create');\n    Route::get('/access-denied', \\App\\Livewire\\Auth\\AccessDenied::class)->name('access-denied');\n});\n";

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
    }
}
