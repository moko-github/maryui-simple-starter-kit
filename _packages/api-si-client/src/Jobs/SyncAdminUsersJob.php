<?php

declare(strict_types=1);

namespace Moko\ApiSi\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Moko\ApiSi\DTOs\SiUserDTO;
use Moko\ApiSi\Services\ApiSiClient;
use Moko\ApiSi\Services\ApiSiException;

/** Synchronise uniquement les utilisateurs Administrator depuis l'API SI. */
class SyncAdminUsersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const CACHE_KEY_LAST_ID  = 'admin_users_sync_last_id';
    private const CACHE_KEY_LAST_RUN = 'admin_users_sync_last_run_at';

    public function __construct(public readonly bool $fullSync = false) {}

    public function handle(ApiSiClient $client): void
    {
        /** @var class-string $userModel */
        $userModel = config('auth.providers.users.model', \App\Models\User::class);
        $adminRole = \App\Models\Role::firstOrCreate(['name' => 'Admin']);

        $lastId   = $this->fullSync ? 0 : (int) Cache::get(self::CACHE_KEY_LAST_ID, 0);
        $maxId    = $lastId;
        $upserted = 0;

        try {
            $cursor = $lastId > 0 ? (string) $lastId : null;

            do {
                ['users' => $users, 'nextCursor' => $nextCursor] = $client->listUsers(
                    cursor: $cursor, withRoles: true, withEntity: true,
                );

                foreach ($users as $dto) {
                    /** @var SiUserDTO $dto */
                    if ($dto->isAdministrator()) {
                        $this->upsertUser($userModel, $dto, $adminRole->id);
                        $upserted++;
                    }

                    if ($dto->id > $maxId) {
                        $maxId = $dto->id;
                    }
                }

                $cursor = $nextCursor;
            } while ($cursor !== null);

        } catch (ApiSiException $e) {
            Log::channel('api-si')->error('[SyncAdminUsersJob] error', [
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
        Log::channel('api-si')->info("[SyncAdminUsersJob] Done. upserted={$upserted}, lastId={$maxId}");
    }

    /** @param class-string $userModel */
    private function upsertUser(string $userModel, SiUserDTO $dto, int $roleId): void
    {
        $existing = $userModel::where('kerberos', $dto->kerberos)->value('password');

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
                'role_id'      => $roleId,
                'si_synced_at' => now(),
                'password'     => $existing ?? bcrypt(\Illuminate\Support\Str::random(64)),
            ]
        );
    }
}
