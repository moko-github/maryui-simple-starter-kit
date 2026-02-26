<?php

use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\KerberosSetupSeeder;

describe('KerberosSetupSeeder', function () {
    it('assigns user role to existing users without a role', function () {
        $userRole = Role::create(['name' => 'User']);
        Role::create(['name' => 'Admin']);

        $userWithoutRole = User::factory()->create(['role_id' => null]);
        $userWithRole = User::factory()->create(['role_id' => $userRole->id]);

        (new KerberosSetupSeeder)->run();

        expect($userWithoutRole->fresh()->role_id)->toBe($userRole->id)
            ->and($userWithRole->fresh()->role_id)->toBe($userRole->id);
    });

    it('does not assign user role when the role does not exist', function () {
        $user = User::factory()->create(['role_id' => null]);

        (new KerberosSetupSeeder)->run();

        expect($user->fresh()->role_id)->toBeNull();
    });

    it('creates admin test account when it does not exist', function () {
        Role::create(['name' => 'User']);
        $adminRole = Role::create(['name' => 'Admin']);

        (new KerberosSetupSeeder)->run();

        $admin = User::where('email', 'admin@example.com')->first();

        expect($admin)->not->toBeNull()
            ->and($admin->name)->toBe('Test User')
            ->and($admin->kerberos)->toBe('admin@krb.example.com')
            ->and($admin->role_id)->toBe($adminRole->id)
            ->and($admin->status)->toBe(UserStatus::ACTIVE);
    });

    it('always assigns admin role to existing admin@example.com', function () {
        Role::create(['name' => 'User']);
        $adminRole = Role::create(['name' => 'Admin']);

        User::factory()->create(['email' => 'admin@example.com', 'role_id' => null]);

        (new KerberosSetupSeeder)->run();

        $admin = User::where('email', 'admin@example.com')->first();

        expect($admin->role_id)->toBe($adminRole->id);
    });

    it('is idempotent when run multiple times', function () {
        Role::create(['name' => 'User']);
        Role::create(['name' => 'Admin']);

        (new KerberosSetupSeeder)->run();
        (new KerberosSetupSeeder)->run();

        expect(User::where('email', 'admin@example.com')->count())->toBe(1);
    });
});
