<?php

namespace App\Contracts;

use App\Models\Property;

/**
 * Property Repository Interface
 * Contract for property repository implementations
 */
interface PropertyRepositoryInterface extends RepositoryInterface
{
    /**
     * Create new property
     */
    public function create(Property $property): Property;

    /**
     * Update existing property
     */
    public function update(Property $property): bool;

    /**
     * Search properties by address
     */
    public function searchByAddress(string $address): array;

    /**
     * Count total properties
     */
    public function count(): int;
}

