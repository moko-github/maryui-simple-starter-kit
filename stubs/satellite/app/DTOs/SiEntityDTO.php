<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * DTO représentant une entité retournée par l'API SI.
 */
final readonly class SiEntityDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $code,
        public ?string $type,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            id:   (int)    $data['id'],
            name: (string) $data['name'],
            code: isset($data['code']) ? (string) $data['code'] : null,
            type: isset($data['type']) ? (string) $data['type'] : null,
        );
    }
}
