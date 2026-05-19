<?php

declare(strict_types=1);

namespace Moko\ApiSi\Console\Commands;

use Illuminate\Console\Command;
use Moko\ApiSi\Jobs\SyncSiUsersJob;

class SyncSiUsers extends Command
{
    protected $signature = 'sync:si-users {--full : Ignore le curseur et resynchronise tout}';

    protected $description = "Synchronise les utilisateurs depuis l'API SI vers la base locale.";

    public function handle(): int
    {
        $fullSync = (bool) $this->option('full');

        SyncSiUsersJob::dispatchSync($fullSync);

        $this->info($fullSync ? 'Sync complet terminé.' : 'Sync incrémental terminé.');

        return self::SUCCESS;
    }
}
