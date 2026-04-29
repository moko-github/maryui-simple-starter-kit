<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\HealthDTO;
use App\DTOs\SiUserDTO;
use App\DTOs\SyncStatusDTO;
use App\DTOs\WebhookDTO;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client HTTP vers l'API SI pour un satellite.
 *
 * Version allégée : ne contient que les méthodes utiles à un satellite
 * basique (lecture utilisateurs, health, self-service webhooks). Les
 * méthodes admin (clients, tokens, audit, deliveries) sont volontairement
 * absentes : utilise admin-console pour ça.
 */
final class ApiSiClient
{
    private PendingRequest $http;

    public function __construct()
    {
        $this->http = Http::baseUrl((string) config('api-si.url'))
            ->withToken((string) config('api-si.token'))
            ->timeout((int) config('api-si.timeout'))
            ->acceptJson();
    }

    // -------------------------------------------------------------------------
    // Health
    // -------------------------------------------------------------------------

    /**
     * @throws ApiSiException
     */
    public function health(): HealthDTO
    {
        $data = $this->get('/api/v1/health');

        return HealthDTO::fromArray($data);
    }

    // -------------------------------------------------------------------------
    // Users
    // -------------------------------------------------------------------------

    /**
     * @return array{users: list<SiUserDTO>, nextCursor: string|null}
     *
     * @throws ApiSiException
     */
    public function listUsers(
        ?string $cursor = null,
        bool $withRoles = true,
        bool $withEntity = true,
    ): array {
        $query = [];

        if ($cursor !== null) {
            $query['cursor'] = $cursor;
        }

        $include = array_values(array_filter([
            $withRoles  ? 'roles'  : null,
            $withEntity ? 'entity' : null,
        ]));

        if ($include !== []) {
            $query['include'] = implode(',', $include);
        }

        $data = $this->get('/api/v1/users', $query);

        /** @var array<int, array<string, mixed>> $items */
        $items = $data['data'] ?? [];

        $users = array_map(
            static fn (array $item) => SiUserDTO::fromArray($item),
            $items
        );

        $nextCursor = $data['meta']['next_cursor']
            ?? $data['meta']['nextCursor']
            ?? null;

        if (is_string($nextCursor) && str_starts_with($nextCursor, 'http')) {
            parse_str((string) parse_url($nextCursor, PHP_URL_QUERY), $params);
            $nextCursor = $params['cursor'] ?? null;
        }

        return [
            'users'      => $users,
            'nextCursor' => is_string($nextCursor) && $nextCursor !== '' ? $nextCursor : null,
        ];
    }

    /**
     * @throws ApiSiException
     */
    public function getUser(string $kerberos): SiUserDTO
    {
        $data = $this->get("/api/v1/users/{$kerberos}");

        /** @var array<string, mixed> $item */
        $item = $data['data'] ?? $data;

        return SiUserDTO::fromArray($item);
    }

    // -------------------------------------------------------------------------
    // Sync (debug / monitoring)
    // -------------------------------------------------------------------------

    /**
     * @return Collection<int, SyncStatusDTO>
     *
     * @throws ApiSiException
     */
    public function syncStatus(): Collection
    {
        $data = $this->get('/api/v1/sync/status');

        /** @var array<int, array<string, mixed>> $items */
        $items = $data['data'] ?? [];

        return collect($items)
            ->map(fn (array $item) => SyncStatusDTO::fromArray($item));
    }

    // -------------------------------------------------------------------------
    // Webhooks self-service
    // -------------------------------------------------------------------------

    /**
     * Abonne ce satellite aux events de l'API SI.
     *
     * @param  list<string>  $events
     *
     * @throws ApiSiException
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

    // -------------------------------------------------------------------------
    // HTTP helpers privés
    // -------------------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     *
     * @throws ApiSiException
     */
    private function get(string $endpoint, array $query = []): array
    {
        Log::channel('api-si')->debug('[ApiSiClient] GET', [
            'endpoint' => $endpoint,
            'query'    => $query,
        ]);

        $response = $this->http->get($endpoint, $query);

        Log::channel('api-si')->debug('[ApiSiClient] GET response', [
            'endpoint' => $endpoint,
            'status'   => $response->status(),
            'body'     => $response->json(),
        ]);

        if ($response->failed()) {
            throw new ApiSiException(
                message: "API SI error on GET {$endpoint}: {$response->status()}",
                statusCode: $response->status(),
                endpoint: $endpoint,
                errors: $response->json('errors') ?? [],
            );
        }

        return $response->json() ?? [];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws ApiSiException
     */
    private function post(string $endpoint, array $payload = []): array
    {
        Log::channel('api-si')->debug('[ApiSiClient] POST', [
            'endpoint' => $endpoint,
            'payload'  => $payload,
        ]);

        $response = $this->http->post($endpoint, $payload);

        Log::channel('api-si')->debug('[ApiSiClient] POST response', [
            'endpoint' => $endpoint,
            'status'   => $response->status(),
            'body'     => $response->json(),
        ]);

        if ($response->failed()) {
            throw new ApiSiException(
                message: "API SI error on POST {$endpoint}: {$response->status()}",
                statusCode: $response->status(),
                endpoint: $endpoint,
                errors: $response->json('errors') ?? [],
            );
        }

        return $response->json() ?? [];
    }
}
