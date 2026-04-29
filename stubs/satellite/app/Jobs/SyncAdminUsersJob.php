<?php

declare(strict_types=1);

namespace App\Jobs;

use App\DTOs\SiUserDTO;
use App\Models\Role;
use App\Models\User;
use App\Services\ApiSiClient;
use App\Services\ApiSiException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Synchronise les utilisateurs ayant le rôle "Administrator" depuis l'API SI
 * dans la table locale `users` (upsert sur la colonne `kerberos`).
 *
 * Variante stricte du SyncSiUsersJob : ne traite QUE les administrateurs,
 * les assigne au rôle local "Admin". Utile quand on veut sync uniquement
 * la cohorte admin (curseur dédié).
 */
class SyncAdminUsersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const CACHE_KEY_LAST_ID = 'admin_users_sync_last_id';

    private const CACHE_KEY_LAST_RUN = 'admin_users_sync_last_run_at';

    private const ADMIN_ROLE_NAME = 'Admin';

    public function __construct(public readonly bool $fullSync = false) {}

    public function handle(ApiSiClient $client): void
    {
        $adminRole = Role::firstOrCreate(['name' => self::ADMIN_ROLE_NAME]);

        $lastId = $this->fullSync ? 0 : (int) Cache::get(self::CACHE_KEY_LAST_ID, 0);

        $maxId    = $lastId;
        $upserted = 0;

        try {
            $cursor = $lastId > 0 ? (string) $lastId : null;

            do {
                ['users' => $users, 'nextCursor' => $nextCursor] = $client->listUsers(
                    cursor: $cursor,
                    withRoles: true,
                    withEntity: true,
                );

                foreach ($users as $dto) {
                    /** @var SiUserDTO $dto */
                    if ($dto->isAdministrator()) {
                        $this->upsertUser($dto, $adminRole->id);
                        $upserted++;
                    }

                    if ($dto->id > $maxId) {
                        $maxId = $dto->id;
                    }
                }

                $cursor = $nextCursor;

            } while ($cursor !== null);

        } catch (ApiSiException $e) {
            Log::channel('api-si')->error('[SyncAdminUsersJob] API SI error', [
                'message'  => $e->getMessage(),
                'endpoint' => $e->endpoint,
                'status'   => $e->statusCode,
            ]);

            return;
        }

        if ($maxId > $lastId) {
            Cache::forever(self::CACHE_KEY_LAST_ID, $maxId);
        }

        Cache::forever(self::CACHE_KEY_LAST_RUN, now()->toIso8601String());

        Log::channel('api-si')->info("[SyncAdminUsersJob] Done. Upserted={$upserted}, lastId={$maxId}, fullSync={$this->fullSync}");
    }

    private function upsertUser(SiUserDTO $dto, int $roleId): void
    {
        User::updateOrCreate(
            ['kerberos' => $dto->kerberos],
            [
                'name'          => $dto->name,
                'email'         => $dto->email,
                'matricule'     => $dto->matricule,
                'rank'          => $dto->rank,
                'phone_number'  => $dto->phoneNumber,
                'room_number'   => $dto->roomNumber,
                'entity_name'   => $dto->entityName,
                'role_id'       => $roleId,
                'si_synced_at'  => now(),
                'password'      => $this->resolvePassword($dto->kerberos),
            ]
        );
    }

    private function resolvePassword(string $kerberos): string
    {
        $existing = User::where('kerberos', $kerberos)->value('password');

        return $existing ?? bcrypt(Str::random(64));
    }
}
