<?php

use App\Console\Commands\InstallCommand;
use Illuminate\Support\Facades\File;

describe('InstallCommand::configureUsersIndex', function () {
    it('adds kerberos column to users index headers', function () {
        $content = "['key' => 'email', 'label' => 'Email', 'sortable' => false]\n        ];";
        $written = null;

        File::shouldReceive('get')->once()->andReturn($content);
        File::shouldReceive('put')->once()->withArgs(function ($path, $newContent) use (&$written) {
            $written = $newContent;

            return true;
        });

        $method = new ReflectionMethod(InstallCommand::class, 'configureUsersIndex');
        $method->invoke(new InstallCommand);

        expect($written)->toContain("'kerberos'");
    });

    it('does not modify users index when kerberos column already exists', function () {
        $content = "['key' => 'email', 'label' => 'Email', 'sortable' => false],\n            ['key' => 'kerberos', 'label' => 'Kerberos', 'sortable' => false]\n        ];";

        File::shouldReceive('get')->once()->andReturn($content);
        File::shouldReceive('put')->never();

        $method = new ReflectionMethod(InstallCommand::class, 'configureUsersIndex');
        $method->invoke(new InstallCommand);

        expect(substr_count($content, "'kerberos'"))->toBe(1);
    });
});

describe('InstallCommand::configureUsersCreate', function () {
    it('adds kerberos property and input to users create', function () {
        $content = "#[Validate('required|email|max:50|unique:users')]\n    public string \$email = '';\n<x-mary-input :label=\"__('Email')\" wire:model=\"email\"/>";
        $written = null;

        File::shouldReceive('get')->once()->andReturn($content);
        File::shouldReceive('put')->once()->withArgs(function ($path, $newContent) use (&$written) {
            $written = $newContent;

            return true;
        });

        $method = new ReflectionMethod(InstallCommand::class, 'configureUsersCreate');
        $method->invoke(new InstallCommand);

        expect($written)
            ->toContain('public ?string $kerberos = null')
            ->toContain('wire:model="kerberos"');
    });

    it('does not modify users create when kerberos already exists', function () {
        $content = 'public ?string $kerberos = null;';

        File::shouldReceive('get')->once()->andReturn($content);
        File::shouldReceive('put')->never();

        $method = new ReflectionMethod(InstallCommand::class, 'configureUsersCreate');
        $method->invoke(new InstallCommand);

        expect(substr_count($content, 'kerberos'))->toBe(1);
    });
});

describe('InstallCommand::configureUsersEdit', function () {
    it('adds kerberos property and input to users edit', function () {
        $content = "public string \$email = '';\n<x-mary-input :disabled=\"auth()->user()->cannot('manageStatus', \$user)\" :label=\"__('Email')\" wire:model=\"email\"/>";
        $written = null;

        File::shouldReceive('get')->once()->andReturn($content);
        File::shouldReceive('put')->once()->withArgs(function ($path, $newContent) use (&$written) {
            $written = $newContent;

            return true;
        });

        $method = new ReflectionMethod(InstallCommand::class, 'configureUsersEdit');
        $method->invoke(new InstallCommand);

        expect($written)
            ->toContain('public ?string $kerberos = null')
            ->toContain('wire:model="kerberos"');
    });

    it('does not modify users edit when kerberos already exists', function () {
        $content = 'public ?string $kerberos = null;';

        File::shouldReceive('get')->once()->andReturn($content);
        File::shouldReceive('put')->never();

        $method = new ReflectionMethod(InstallCommand::class, 'configureUsersEdit');
        $method->invoke(new InstallCommand);

        expect(substr_count($content, 'kerberos'))->toBe(1);
    });
});

describe('InstallCommand::configureMfcUsersIndex', function () {
    it('adds kerberos column to mfc-users index headers', function () {
        $content = "['key' => 'email', 'label' => 'Email', 'sortable' => false],\n        ];";
        $written = null;

        File::shouldReceive('get')->once()->andReturn($content);
        File::shouldReceive('put')->once()->withArgs(function ($path, $newContent) use (&$written) {
            $written = $newContent;

            return true;
        });

        $method = new ReflectionMethod(InstallCommand::class, 'configureMfcUsersIndex');
        $method->invoke(new InstallCommand);

        expect($written)->toContain("'kerberos'");
    });

    it('does not modify mfc-users index when kerberos column already exists', function () {
        $content = "['key' => 'kerberos', 'label' => 'Kerberos', 'sortable' => false],";

        File::shouldReceive('get')->once()->andReturn($content);
        File::shouldReceive('put')->never();

        $method = new ReflectionMethod(InstallCommand::class, 'configureMfcUsersIndex');
        $method->invoke(new InstallCommand);

        expect(substr_count($content, "'kerberos'"))->toBe(1);
    });
});

describe('InstallCommand::configureMfcUsersCreate', function () {
    it('adds kerberos property and input to mfc-users create', function () {
        $phpContent = "#[Validate('required|email|max:50|unique:users')]\n    public string \$email = '';";
        $bladeContent = "<x-mary-input :label=\"__('Email')\" wire:model=\"email\"/>";
        $writtenPhp = null;
        $writtenBlade = null;

        File::shouldReceive('get')->twice()->andReturn($phpContent, $bladeContent);
        File::shouldReceive('put')->twice()->withArgs(function ($path, $newContent) use (&$writtenPhp, &$writtenBlade) {
            if (str_ends_with($path, '.blade.php')) {
                $writtenBlade = $newContent;
            } else {
                $writtenPhp = $newContent;
            }

            return true;
        });

        $method = new ReflectionMethod(InstallCommand::class, 'configureMfcUsersCreate');
        $method->invoke(new InstallCommand);

        expect($writtenPhp)->toContain('public ?string $kerberos = null')
            ->and($writtenBlade)->toContain('wire:model="kerberos"');
    });

    it('does not modify mfc-users create when kerberos already exists', function () {
        $phpContent = 'public ?string $kerberos = null;';
        $bladeContent = 'wire:model="kerberos"';

        File::shouldReceive('get')->twice()->andReturn($phpContent, $bladeContent);
        File::shouldReceive('put')->never();

        $method = new ReflectionMethod(InstallCommand::class, 'configureMfcUsersCreate');
        $method->invoke(new InstallCommand);

        expect(substr_count($phpContent, 'kerberos') + substr_count($bladeContent, 'kerberos'))->toBe(2);
    });
});

describe('InstallCommand::configureMfcUsersEdit', function () {
    it('adds kerberos property and input to mfc-users edit', function () {
        $phpContent = "public string \$email = '';";
        $bladeContent = "<x-mary-input :disabled=\"auth()->user()->cannot('manageStatus', \$user)\" :label=\"__('Email')\" wire:model=\"email\"/>";
        $writtenPhp = null;
        $writtenBlade = null;

        File::shouldReceive('get')->twice()->andReturn($phpContent, $bladeContent);
        File::shouldReceive('put')->twice()->withArgs(function ($path, $newContent) use (&$writtenPhp, &$writtenBlade) {
            if (str_ends_with($path, '.blade.php')) {
                $writtenBlade = $newContent;
            } else {
                $writtenPhp = $newContent;
            }

            return true;
        });

        $method = new ReflectionMethod(InstallCommand::class, 'configureMfcUsersEdit');
        $method->invoke(new InstallCommand);

        expect($writtenPhp)->toContain('public ?string $kerberos = null')
            ->and($writtenBlade)->toContain('wire:model="kerberos"');
    });

    it('does not modify mfc-users edit when kerberos already exists', function () {
        $phpContent = 'public ?string $kerberos = null;';
        $bladeContent = 'wire:model="kerberos"';

        File::shouldReceive('get')->twice()->andReturn($phpContent, $bladeContent);
        File::shouldReceive('put')->never();

        $method = new ReflectionMethod(InstallCommand::class, 'configureMfcUsersEdit');
        $method->invoke(new InstallCommand);

        expect(substr_count($phpContent, 'kerberos') + substr_count($bladeContent, 'kerberos'))->toBe(2);
    });
});
