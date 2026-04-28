<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * État de santé de l'API SI.
 */
final readonly class HealthDTO
{
    public function __construct(
        public string $status,
        public string $databaseSi,
        public ?int $databaseSiLatency,
        public string $databaseInternal,
        public ?int $databaseInternalLatency,
        public string $redis,
        public ?string $redisError,
        public string $queue,
        public string $timestamp,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        /** @var array<string, array<string, mixed>> $checks */
        $checks = $data['checks'] ?? [];

        return new self(
            status: (string) ($data['status'] ?? 'error'),
            databaseSi: (string) ($checks['database_si']['status'] ?? 'unknown'),
            databaseSiLatency: isset($checks['database_si']['latency_ms'])
                ? (int) $checks['database_si']['latency_ms']
                : null,
            databaseInternal: (string) ($checks['database_internal']['status'] ?? 'unknown'),
            databaseInternalLatency: isset($checks['database_internal']['latency_ms'])
                ? (int) $checks['database_internal']['latency_ms']
                : null,
            redis: (string) ($checks['redis']['status'] ?? 'unknown'),
            redisError: isset($checks['redis']['error'])
                ? (string) $checks['redis']['error']
                : null,
            queue: (string) ($checks['queue']['status'] ?? 'unknown'),
            timestamp: (string) ($data['timestamp'] ?? ''),
        );
    }

    public function isHealthy(): bool
    {
        return $this->status === 'ok';
    }
}
