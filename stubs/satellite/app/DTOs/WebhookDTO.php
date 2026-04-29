<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Abonnement webhook retourné par POST /api/v1/webhooks.
 */
final readonly class WebhookDTO
{
    /** @param list<string> $events */
    public function __construct(
        public int $id,
        public ?int $clientId,
        public string $url,
        public array $events,
        public bool $isActive,
        public ?string $lastTriggeredAt,
        public string $createdAt,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            clientId: isset($data['client_id']) ? (int) $data['client_id'] : null,
            url: (string) $data['url'],
            events: array_values((array) ($data['events'] ?? [])),
            isActive: (bool) ($data['is_active'] ?? true),
            lastTriggeredAt: isset($data['last_triggered_at'])
                ? (string) $data['last_triggered_at']
                : null,
            createdAt: (string) ($data['created_at'] ?? ''),
        );
    }
}
