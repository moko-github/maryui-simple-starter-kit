<?php

declare(strict_types=1);

namespace Moko\ApiSi\Services;

use Moko\Satellite\Services\SatelliteException;

/** Exception spécifique à l'API SI — étend SatelliteException pour des catch ciblés. */
final class ApiSiException extends SatelliteException {}
