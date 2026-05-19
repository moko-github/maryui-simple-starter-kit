<?php

declare(strict_types=1);

namespace Moko\ApiSi\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Moko\ApiSi\Services\ApiSiClient;
use Moko\ApiSi\Services\ApiSiException;

/**
 * À la première requête Kerberos d'un utilisateur dans la journée, récupère
 * son profil à jour depuis l'API SI et fait un upsert local (cache 24 h).
 */
final class SyncUserOnAuth
{
    public function __construct(private readonly ApiSiClient $api) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $kerberos = (string) $request->server('REMOTE_USER');

        if ($kerberos === '') {
            return $next($request);
        }

        $cacheKey = "user_synced:{$kerberos}";

        if (Cache::has($cacheKey)) {
            return $next($request);
        }

        /** @var class-string $userModel */
        $userModel = config('auth.providers.users.model', \App\Models\User::class);

        try {
            $dto = $this->api->getUser($kerberos);
        } catch (ApiSiException $e) {
            Log::channel('api-si')->warning('[SyncUserOnAuth] API SI indisponible', [
                'kerberos' => $kerberos,
                'message'  => $e->getMessage(),
                'status'   => $e->statusCode,
            ]);

            if (! $userModel::where('kerberos', $kerberos)->exists()) {
                abort(503, 'API SI indisponible et utilisateur inconnu localement.');
            }

            return $next($request);
        }

        $userModel::updateOrCreate(
            ['kerberos' => $dto->kerberos],
            [
                'name'         => $dto->name,
                'email'        => $dto->email,
                'matricule'    => $dto->matricule,
                'rank'         => $dto->rank,
                'phone_number' => $dto->phoneNumber,
                'room_number'  => $dto->roomNumber,
                'entity_name'  => $dto->entityName,
                'si_synced_at' => now(),
            ]
        );

        Cache::put($cacheKey, true, now()->addHours(24));

        return $next($request);
    }
}
