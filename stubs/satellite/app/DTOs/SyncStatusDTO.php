<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Curseur de synchronisation retourné par GET /api/v1/sync/status.
 */
final readonly class SyncStatusDTO
{
    public function __construct(
        public string $resource,
        public int $lastId,
        public ?string $lastSyncedAt,
        public ?string $updatedAt,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            resource:     (string) ($data['resource'] ?? ''),
            lastId:       (int)    ($data['last_id'] ?? 0),
            lastSyncedAt: isset($data['last_synced_at']) ? (string) $data['last_synced_at'] : null,
            updatedAt:    isset($data['updated_at'])     ? (string) $data['updated_at']     : null,
        );
    }
}
