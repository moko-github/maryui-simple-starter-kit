<?php

namespace App\Console\Commands;

use App\Models\KerberosAttempt;
use Illuminate\Console\Command;

class PurgeKerberosAttempts extends Command
{
    protected $signature = 'kerberos:purge-attempts {--days=30 : Number of days to retain}';

    protected $description = 'Purge Kerberos login attempts older than the specified number of days';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $count = KerberosAttempt::purgeOld($days)->count();

        if ($count > 0) {
            KerberosAttempt::purgeOld($days)->delete();
            $this->info("Purged {$count} Kerberos attempt(s) older than {$days} days.");
        } else {
            $this->info('No old Kerberos attempts to purge.');
        }

        return Command::SUCCESS;
    }
}
