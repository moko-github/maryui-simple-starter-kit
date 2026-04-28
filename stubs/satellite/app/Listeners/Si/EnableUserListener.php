<?php

declare(strict_types=1);

namespace App\Listeners\Si;

use App\Events\Si\UserEnabled;
use Illuminate\Support\Facades\Cache;

/**
 * Réaction à user.enabled : on invalide le cache de sync-on-auth pour forcer
 * un re-fetch à la prochaine requête.
 */
final class EnableUserListener
{
    public function handle(UserEnabled $event): void
    {
        $kerberos = (string) ($event->data['kerberos'] ?? '');

        if ($kerberos === '') {
            return;
        }

        Cache::forget("user_synced:{$kerberos}");
    }
}
