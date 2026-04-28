<?php

declare(strict_types=1);

namespace App\Events\Si;

use Illuminate\Foundation\Events\Dispatchable;

final class UserEnabled
{
    use Dispatchable;

    /** @param array<string, mixed> $data */
    public function __construct(public readonly array $data) {}
}
