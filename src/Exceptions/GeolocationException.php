<?php

namespace App\Exceptions;

use Exception;

/**
 * Geolocation Exception
 * Thrown when geolocation services fail
 */
class GeolocationException extends Exception
{
    public function __construct(string $message = "Geolocation service failed", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

