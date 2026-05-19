<?php

declare(strict_types=1);

namespace Moko\ApiSi\Services;

use Illuminate\Support\Collection;
use Moko\ApiSi\DTOs\HealthDTO;
use Moko\ApiSi\DTOs\SiUserDTO;
use Moko\ApiSi\DTOs\SyncStatusDTO;
use Moko\ApiSi\DTOs\WebhookDTO;
use Moko\Satellite\Services\SatelliteClient;
use Moko\Satellite\Services\SatelliteException;

/**
 * Client HTTP vers l'API SI.
 * Étend SatelliteClient en ajoutant les méthodes typées avec leurs DTOs.
 */
final class ApiSiClient extends SatelliteClient
{
    public function __construct()
    {
        parent::__construct(
            baseUrl:    (string) config('api-si.url'),
            token:      (string) config('api-si.token'),
            timeout:    (int)    config('api-si.timeout', 10),
            logChannel: 'api-si',
        );
    }

    // -------------------------------------------------------------------------
    // Health
    // -------------------------------------------------------------------------

    /** @throws SatelliteException */
    public function health(): HealthDTO
    {
        return HealthDTO::fromArray($this->get('/api/v1/health'));
    }

    // -------------------------------------------------------------------------
    // Users
    // -------------------------------------------------------------------------

    /**
     * @return array{users: list<SiUserDTO>, nextCursor: string|null}
     * @throws SatelliteException
     */
    public function listUsers(?string $cursor = null, bool $withRoles = true, bool $withEntity = true): array
    {
        $query   = [];
        $include = array_values(array_filter([
            $withRoles  ? 'roles'  : null,
            $withEntity ? 'entity' : null,
        ]));

        if ($cursor !== null) {
            $query['cursor'] = $cursor;
        }

        if ($include !== []) {
            $query['include'] = implode(',', $include);
        }

        $data = $this->get('/api/v1/users', $query);

        /** @var array<int, array<string, mixed>> $items */
        $items = $data['data'] ?? [];
        $users = array_map(static fn (array $item) => SiUserDTO::fromArray($item), $items);

        $nextCursor = $data['meta']['next_cursor'] ?? $data['meta']['nextCursor'] ?? null;

        if (is_string($nextCursor) && str_starts_with($nextCursor, 'http')) {
            parse_str((string) parse_url($nextCursor, PHP_URL_QUERY), $params);
            $nextCursor = $params['cursor'] ?? null;
        }

        return [
            'users'      => $users,
            'nextCursor' => is_string($nextCursor) && $nextCursor !== '' ? $nextCursor : null,
        ];
    }

    /** @throws SatelliteException */
    public function getUser(string $kerberos): SiUserDTO
    {
        $data = $this->get("/api/v1/users/{$kerberos}");
        /** @var array<string, mixed> $item */
        $item = $data['data'] ?? $data;

        return SiUserDTO::fromArray($item);
    }

    // -------------------------------------------------------------------------
    // Sync status
    // -------------------------------------------------------------------------

    /**
     * @return Collection<int, SyncStatusDTO>
     * @throws SatelliteException
     */
    public function syncStatus(): Collection
    {
        $data = $this->get('/api/v1/sync/status');
        /** @var array<int, array<string, mixed>> $items */
        $items = $data['data'] ?? [];

        return collect($items)->map(fn (array $item) => SyncStatusDTO::fromArray($item));
    }

    // -------------------------------------------------------------------------
    // Webhooks
    // -------------------------------------------------------------------------

    /**
     * @param  list<string>  $events
     * @throws SatelliteException
     */
    public function subscribeWebhook(string $url, array $events, string $secret): WebhookDTO
    {
        $data = $this->post('/api/v1/webhooks', [
            'url'    => $url,
            'events' => $events,
            'secret' => $secret,
        ]);

        return WebhookDTO::fromArray($data['data'] ?? $data);
    }
}
