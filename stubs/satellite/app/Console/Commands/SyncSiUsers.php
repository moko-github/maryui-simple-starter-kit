<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SyncSiUsersJob;
use Illuminate\Console\Command;

/**
 * Lance une synchronisation des utilisateurs API SI dans la table locale.
 *
 *   php artisan sync:si-users          # incrémental (depuis le dernier curseur)
 *   php artisan sync:si-users --full   # full sync (curseur réinitialisé)
 */
class SyncSiUsers extends Command
{
    protected $signature = 'sync:si-users
                            {--full : Full sync (ignore le curseur mémorisé)}';

    protected $description = 'Synchronise les utilisateurs depuis l\'API SI dans la table users locale.';

    public function handle(): int
    {
        $fullSync = (bool) $this->option('full');

        $this->info($fullSync
            ? 'Lancement d\'un full sync des utilisateurs API SI...'
            : 'Lancement d\'un sync incrémental des utilisateurs API SI...'
        );

        SyncSiUsersJob::dispatchSync(fullSync: $fullSync);

        $this->info('Synchronisation terminée.');

        return self::SUCCESS;
    }
}
