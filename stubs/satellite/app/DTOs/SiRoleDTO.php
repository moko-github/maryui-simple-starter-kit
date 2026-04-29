<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * DTO représentant un rôle retourné par l'API SI.
 */
final readonly class SiRoleDTO
{
    public function __construct(
        public int $id,
        public string $name,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            id:   (int)    $data['id'],
            name: (string) $data['name'],
        );
    }
}
