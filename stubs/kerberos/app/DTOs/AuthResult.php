<?php

namespace App\DTOs;

use App\Models\User;

class AuthResult
{
    public const SUCCESS = 'success';

    public const NO_KERBEROS = 'no_kerberos';

    public const NO_ROLE = 'no_role';

    public const UNKNOWN_USER = 'unknown_user';

    public function __construct(
        public readonly string $status,
        public readonly ?User $user = null,
        public readonly ?string $kerberos = null,
        public readonly ?string $message = null,
    ) {
        if (! in_array($status, [self::SUCCESS, self::NO_KERBEROS, self::NO_ROLE, self::UNKNOWN_USER])) {
            throw new \InvalidArgumentException("Invalid AuthResult status: {$status}");
        }
    }

    public function isSuccess(): bool
    {
        return $this->status === self::SUCCESS;
    }

    public function needsAccessRequest(): bool
    {
        return $this->status === self::NO_ROLE;
    }

    public function isBlocked(): bool
    {
        return $this->status === self::UNKNOWN_USER;
    }

    public function needsFallbackAuth(): bool
    {
        return $this->status === self::NO_KERBEROS;
    }

    public function getMessage(): string
    {
        return $this->message ?? match ($this->status) {
            self::SUCCESS => 'Authentication successful.',
            self::NO_KERBEROS => 'Kerberos not available. Please use the login form.',
            self::NO_ROLE => 'Your account has no role assigned. Please request access.',
            self::UNKNOWN_USER => 'Kerberos identifier not recognised. Administrators have been notified.',
            default => 'Unknown status.',
        };
    }

    public static function success(User $user, string $kerberos): self
    {
        return new self(status: self::SUCCESS, user: $user, kerberos: $kerberos);
    }

    public static function noKerberos(): self
    {
        return new self(status: self::NO_KERBEROS);
    }

    public static function noRole(User $user, string $kerberos): self
    {
        return new self(status: self::NO_ROLE, user: $user, kerberos: $kerberos);
    }

    public static function unknownUser(string $kerberos): self
    {
        return new self(status: self::UNKNOWN_USER, kerberos: $kerberos);
    }
}
