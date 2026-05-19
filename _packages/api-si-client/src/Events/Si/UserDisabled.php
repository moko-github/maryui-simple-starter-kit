<?php

declare(strict_types=1);

namespace Moko\ApiSi\Events\Si;

final class UserDisabled
{
    /** @param array<string, mixed> $data */
    public function __construct(public readonly array $data) {}
}
