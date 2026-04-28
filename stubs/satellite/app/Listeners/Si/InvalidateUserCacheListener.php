<?php

declare(strict_types=1);

namespace App\Listeners\Si;

use App\Events\Si\UserServiceChanged;
use App\Events\Si\UserUpdated;
use Illuminate\Support\Facades\Cache;

/**
 * Réaction générique à user.updated et user.service_changed : invalidation
 * du cache de sync-on-auth.
 *
 * Enregistre cet écouteur sur les deux events depuis ton EventServiceProvider
 * (ou découverte automatique en Laravel 11+).
 */
final class InvalidateUserCacheListener
{
    public function handle(UserUpdated|UserServiceChanged $event): void
    {
        $kerberos = (string) ($event->data['kerberos'] ?? '');

        if ($kerberos === '') {
            return;
        }

        Cache::forget("user_synced:{$kerberos}");
    }
}
