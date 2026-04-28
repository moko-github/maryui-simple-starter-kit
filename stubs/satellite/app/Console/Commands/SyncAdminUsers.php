<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SyncAdminUsersJob;
use Illuminate\Console\Command;

/**
 * Lance une synchronisation des administrateurs API SI dans la table locale.
 *
 *   php artisan admin:sync-users           # full sync
 *   php artisan admin:sync-users --since   # incrémental depuis le dernier curseur
 */
class SyncAdminUsers extends Command
{
    protected $signature = 'admin:sync-users
                            {--since : Sync incrémental depuis le dernier curseur mémorisé}';

    protected $description = 'Synchronise les utilisateurs Administrator depuis l\'API SI dans la table users locale.';

    public function handle(): int
    {
        $fullSync = ! $this->option('since');

        $this->info($fullSync
            ? 'Lancement d\'un full sync des administrateurs API SI...'
            : 'Lancement d\'un sync incrémental des administrateurs API SI...'
        );

        SyncAdminUsersJob::dispatchSync(fullSync: $fullSync);

        $this->info('Synchronisation terminée.');

        return self::SUCCESS;
    }
}
