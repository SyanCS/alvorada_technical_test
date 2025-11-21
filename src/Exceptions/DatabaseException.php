<?php

namespace App\Exceptions;

use Exception;

/**
 * Database Exception
 * Thrown when database operations fail
 */
class DatabaseException extends Exception
{
    public function __construct(string $message = "Database operation failed", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

