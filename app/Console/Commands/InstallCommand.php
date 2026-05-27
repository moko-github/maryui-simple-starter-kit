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

    protected $description = "Programme d'installation interactif de l'application";

    public function handle(): int
    {
        intro("Bienvenue dans le programme d'installation");

        $this->installKerberos();

        outro('Installation terminée ! Votre application est prête.');

        return self::SUCCESS;
    }

    protected function installKerberos(): void
    {
        $install = confirm(
            label: "Voulez-vous activer l'authentification Kerberos (SSO) ?",
            default: false,
            hint: 'Active la connexion unique via la variable serveur REMOTE_USER (nécessite le module Kerberos Apache/Nginx).'
        );

        if (! $install) {
            return;
        }

        note("Installation du module d'authentification Kerberos...");

        // Délégation au package : middleware, User model, routes, env, migrations, seeders
        $this->call('kerberos:install');

        // Publier la config et forcer le layout du starter kit
        $this->call('vendor:publish', ['--tag' => 'kerberos-config', '--force' => false]);
        $this->setKerberosLayout();

        // Injections spécifiques aux vues du starter kit
        $this->configureLoginView();
        $this->configureSidebar();
        $this->configureCrudViews();

        $this->info('✓ Authentification Kerberos installée avec succès.');

        note(
            "Prochaines étapes :\n".
            "  1. Définissez KERBEROS_ENABLED=true dans votre fichier .env\n".
            "  2. Configurez KERBEROS_ADMIN_EMAILS avec les adresses email des administrateurs\n".
            "  3. Configurez votre serveur web (Apache/Nginx) avec le module Kerberos\n".
            '  4. Pour les tests en local, définissez KERBEROS_SIMULATION_MODE=true'
        );
    }

    protected function setKerberosLayout(): void
    {
        $configFile = config_path('kerberos.php');

        if (! File::exists($configFile)) {
            return;
        }

        $content = File::get($configFile);
        $content = str_replace("'layout' => null,", "'layout' => 'layouts.auth',", $content);
        File::put($configFile, $content);
        $this->line('  Layout Kerberos configuré sur layouts.auth.');
    }

    protected function configureLoginView(): void
    {
        $loginFile = base_path('resources/views/pages/auth/⚡login.blade.php');
        $content   = File::get($loginFile);

        if (str_contains($content, 'simulate-kerberos')) {
            return;
        }

        $content = str_replace(
            "\n    </form>",
            "\n    </form>\n\n    @livewire('auth.simulate-kerberos')",
            $content
        );

        File::put($loginFile, $content);
        $this->line('  Composant simulate-kerberos ajouté à la page login.');
    }

    protected function configureSidebar(): void
    {
        $file    = base_path('resources/views/layouts/app/sidebar.blade.php');
        $content = File::get($file);

        if (str_contains($content, 'simulation-banner')) {
            return;
        }

        $content = str_replace(
            "<x-mary-theme-toggle />\n            </div>",
            "<x-mary-theme-toggle />\n            </div>\n            @livewire('auth.simulation-banner')",
            $content
        );

        File::put($file, $content);
        $this->line('  Composant simulation-banner ajouté à la sidebar.');
    }

    protected function configureCrudViews(): void
    {
        $this->configureUsersIndex();
        $this->configureUsersCreate();
        $this->configureUsersEdit();
        $this->configureMfcUsersIndex();
        $this->configureMfcUsersCreate();
        $this->configureMfcUsersEdit();
    }

    protected function configureUsersIndex(): void
    {
        $file    = base_path('resources/views/pages/users/⚡index.blade.php');
        $content = File::get($file);

        if (str_contains($content, "'kerberos'")) {
            return;
        }

        $content = str_replace(
            "['key' => 'email', 'label' => 'Email', 'sortable' => false]\n        ];",
            "['key' => 'email', 'label' => 'Email', 'sortable' => false],\n            ['key' => 'kerberos', 'label' => 'Kerberos', 'sortable' => false]\n        ];",
            $content
        );

        File::put($file, $content);
    }

    protected function configureUsersCreate(): void
    {
        $file    = base_path('resources/views/pages/users/⚡create.blade.php');
        $content = File::get($file);

        if (str_contains($content, '$kerberos')) {
            return;
        }

        $content = str_replace(
            "#[Validate('required|email|max:50|unique:users')]\n    public string \$email = '';",
            "#[Validate('required|email|max:50|unique:users')]\n    public string \$email = '';\n\n    #[Validate('nullable|string|max:255')]\n    public ?string \$kerberos = null;",
            $content
        );

        $content = str_replace(
            "<x-mary-input :label=\"__('Email')\" wire:model=\"email\"/>",
            "<x-mary-input :label=\"__('Email')\" wire:model=\"email\"/>\n                <x-mary-input :label=\"__('Kerberos')\" wire:model=\"kerberos\"/>",
            $content
        );

        File::put($file, $content);
    }

    protected function configureUsersEdit(): void
    {
        $file    = base_path('resources/views/pages/users/⚡edit.blade.php');
        $content = File::get($file);

        if (str_contains($content, '$kerberos')) {
            return;
        }

        $content = str_replace(
            "public string \$email = '';",
            "public string \$email = '';\n\n    #[Validate('nullable|string|max:255')]\n    public ?string \$kerberos = null;",
            $content
        );

        $content = str_replace(
            "<x-mary-input :disabled=\"auth()->user()->cannot('manageStatus', \$user)\" :label=\"__('Email')\" wire:model=\"email\"/>",
            "<x-mary-input :disabled=\"auth()->user()->cannot('manageStatus', \$user)\" :label=\"__('Email')\" wire:model=\"email\"/>\n                <x-mary-input :disabled=\"auth()->user()->cannot('manageStatus', \$user)\" :label=\"__('Kerberos')\" wire:model=\"kerberos\"/>",
            $content
        );

        File::put($file, $content);
    }

    protected function configureMfcUsersIndex(): void
    {
        $file    = base_path('resources/views/pages/mfc-users/⚡index/index.php');
        $content = File::get($file);

        if (str_contains($content, "'kerberos'")) {
            return;
        }

        $content = str_replace(
            "['key' => 'email', 'label' => 'Email', 'sortable' => false],\n        ];",
            "['key' => 'email', 'label' => 'Email', 'sortable' => false],\n            ['key' => 'kerberos', 'label' => 'Kerberos', 'sortable' => false],\n        ];",
            $content
        );

        File::put($file, $content);
    }

    protected function configureMfcUsersCreate(): void
    {
        $phpFile    = base_path('resources/views/pages/mfc-users/⚡create/create.php');
        $phpContent = File::get($phpFile);

        if (! str_contains($phpContent, '$kerberos')) {
            $phpContent = str_replace(
                "#[Validate('required|email|max:50|unique:users')]\n    public string \$email = '';",
                "#[Validate('required|email|max:50|unique:users')]\n    public string \$email = '';\n\n    #[Validate('nullable|string|max:255')]\n    public ?string \$kerberos = null;",
                $phpContent
            );

            File::put($phpFile, $phpContent);
        }

        $bladeFile    = base_path('resources/views/pages/mfc-users/⚡create/create.blade.php');
        $bladeContent = File::get($bladeFile);

        if (! str_contains($bladeContent, 'wire:model="kerberos"')) {
            $bladeContent = str_replace(
                "<x-mary-input :label=\"__('Email')\" wire:model=\"email\"/>",
                "<x-mary-input :label=\"__('Email')\" wire:model=\"email\"/>\n                <x-mary-input :label=\"__('Kerberos')\" wire:model=\"kerberos\"/>",
                $bladeContent
            );

            File::put($bladeFile, $bladeContent);
        }
    }

    protected function configureMfcUsersEdit(): void
    {
        $phpFile    = base_path('resources/views/pages/mfc-users/⚡edit/edit.php');
        $phpContent = File::get($phpFile);

        if (! str_contains($phpContent, '$kerberos')) {
            $phpContent = str_replace(
                "public string \$email = '';",
                "public string \$email = '';\n\n    #[Validate('nullable|string|max:255')]\n    public ?string \$kerberos = null;",
                $phpContent
            );

            File::put($phpFile, $phpContent);
        }

        $bladeFile    = base_path('resources/views/pages/mfc-users/⚡edit/edit.blade.php');
        $bladeContent = File::get($bladeFile);

        if (! str_contains($bladeContent, 'wire:model="kerberos"')) {
            $bladeContent = str_replace(
                "<x-mary-input :disabled=\"auth()->user()->cannot('manageStatus', \$user)\" :label=\"__('Email')\" wire:model=\"email\"/>",
                "<x-mary-input :disabled=\"auth()->user()->cannot('manageStatus', \$user)\" :label=\"__('Email')\" wire:model=\"email\"/>\n                <x-mary-input :disabled=\"auth()->user()->cannot('manageStatus', \$user)\" :label=\"__('Kerberos')\" wire:model=\"kerberos\"/>",
                $bladeContent
            );

            File::put($bladeFile, $bladeContent);
        }
    }
}
