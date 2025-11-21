<?php

namespace App\Validators;

/**
 * Property Validator
 * Validates property data before processing
 */
class PropertyValidator
{
    private array $errors = [];

    /**
     * Validate property data
     * 
     * @param array $data
     * @return bool
     */
    public function validate(array $data): bool
    {
        $this->errors = [];

        // Validate name
        if (empty($data['name'])) {
            $this->errors['name'] = 'Property name is required';
        } elseif (strlen($data['name']) < 2) {
            $this->errors['name'] = 'Property name must be at least 2 characters';
        } elseif (strlen($data['name']) > 255) {
            $this->errors['name'] = 'Property name cannot exceed 255 characters';
        }

        // Validate address
        if (empty($data['address'])) {
            $this->errors['address'] = 'Address is required';
        } elseif (strlen($data['address']) < 5) {
            $this->errors['address'] = 'Address must be at least 5 characters';
        } elseif (strlen($data['address']) > 500) {
            $this->errors['address'] = 'Address cannot exceed 500 characters';
        }

        // Validate latitude (if provided)
        if (isset($data['latitude'])) {
            $lat = (float) $data['latitude'];
            if ($lat < -90 || $lat > 90) {
                $this->errors['latitude'] = 'Latitude must be between -90 and 90';
            }
        }

        // Validate longitude (if provided)
        if (isset($data['longitude'])) {
            $lon = (float) $data['longitude'];
            if ($lon < -180 || $lon > 180) {
                $this->errors['longitude'] = 'Longitude must be between -180 and 180';
            }
        }

        return empty($this->errors);
    }

    /**
     * Validate note data
     * 
     * @param array $data
     * @return bool
     */
    public function validateNote(array $data): bool
    {
        $this->errors = [];

        // Validate property_id
        if (empty($data['property_id'])) {
            $this->errors['property_id'] = 'Property ID is required';
        } elseif (!is_numeric($data['property_id']) || (int)$data['property_id'] <= 0) {
            $this->errors['property_id'] = 'Property ID must be a positive integer';
        }

        // Validate note
        if (empty($data['note'])) {
            $this->errors['note'] = 'Note content is required';
        } elseif (strlen($data['note']) < 3) {
            $this->errors['note'] = 'Note must be at least 3 characters';
        } elseif (strlen($data['note']) > 5000) {
            $this->errors['note'] = 'Note cannot exceed 5000 characters';
        }

        return empty($this->errors);
    }

    /**
     * Get validation errors
     * 
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error message
     * 
     * @return string|null
     */
    public function getFirstError(): ?string
    {
        if (empty($this->errors)) {
            return null;
        }

        return reset($this->errors);
    }

    /**
     * Sanitize input data
     * 
     * @param array $data
     * @return array
     */
    public function sanitize(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // Trim whitespace
                $value = trim($value);
                // Remove null bytes
                $value = str_replace("\0", '', $value);
                // Basic XSS prevention
                $sanitized[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}

