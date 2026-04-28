<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;
use Throwable;

/**
 * Exception levée lors d'une erreur de communication avec l'API SI.
 */
final class ApiSiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $statusCode = 0,
        public readonly string $endpoint = '',
        /** @var array<string, list<string>> */
        public readonly array $errors = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }
}
