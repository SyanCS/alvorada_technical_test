<?php

namespace App\Exceptions;

use Exception;

/**
 * Validation Exception
 * Thrown when validation fails
 */
class ValidationException extends Exception
{
    private array $errors = [];

    public function __construct(string $message = "Validation failed", array $errors = [], int $code = 0)
    {
        parent::__construct($message, $code);
        $this->errors = $errors;
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error message
     */
    public function getFirstError(): ?string
    {
        if (empty($this->errors)) {
            return null;
        }
        return reset($this->errors);
    }
}

