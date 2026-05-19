<?php

declare(strict_types=1);

namespace Moko\ApiSi\Console\Commands;

use Illuminate\Console\Command;
use Moko\ApiSi\Jobs\SyncAdminUsersJob;

class SyncAdminUsers extends Command
{
    protected $signature = 'admin:sync-users {--full : Ignore le curseur et resynchronise tout}';

    protected $description = "Synchronise les utilisateurs Administrator depuis l'API SI.";

    public function handle(): int
    {
        $fullSync = (bool) $this->option('full');

        SyncAdminUsersJob::dispatchSync($fullSync);

        $this->info($fullSync ? 'Sync admin complet terminé.' : 'Sync admin incrémental terminé.');

        return self::SUCCESS;
    }
}
