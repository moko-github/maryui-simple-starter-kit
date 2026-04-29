<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * DTO représentant un utilisateur retourné par GET /api/v1/users de l'API SI.
 */
final readonly class SiUserDTO
{
    public function __construct(
        public int $id,
        public string $kerberos,
        public string $name,
        public string $email,
        public ?string $matricule,
        public ?string $rank,
        public ?string $phoneNumber,
        public ?string $roomNumber,
        public ?string $entityName,
        /** @var list<SiRoleDTO> */
        public array $roles,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $roles = array_map(
            fn (array $r) => SiRoleDTO::fromArray($r),
            (array) ($data['roles'] ?? [])
        );

        $entityName = null;
        if (isset($data['entity']) && is_array($data['entity'])) {
            $entityName = (string) ($data['entity']['name'] ?? '');
        }

        return new self(
            id:          (int)    $data['id'],
            kerberos:    (string) $data['kerberos'],
            name:        (string) $data['name'],
            email:       (string) $data['email'],
            matricule:   isset($data['matricule'])    ? (string) $data['matricule']    : null,
            rank:        isset($data['rank'])         ? (string) $data['rank']         : null,
            phoneNumber: isset($data['phone_number']) ? (string) $data['phone_number'] : null,
            roomNumber:  isset($data['room_number'])  ? (string) $data['room_number']  : null,
            entityName:  $entityName,
            roles:       $roles,
        );
    }

    public function hasRole(string $roleName): bool
    {
        $needle = strtolower($roleName);

        foreach ($this->roles as $role) {
            if (strtolower($role->name) === $needle) {
                return true;
            }
        }

        return false;
    }

    public function isAdministrator(): bool
    {
        return $this->hasRole('administrator');
    }
}
