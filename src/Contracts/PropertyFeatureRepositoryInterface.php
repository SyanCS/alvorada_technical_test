<?php

namespace App\Contracts;

use App\Models\PropertyFeature;

/**
 * PropertyFeature Repository Interface
 * Defines contract for property features data access
 */
interface PropertyFeatureRepositoryInterface extends RepositoryInterface
{
    /**
     * Find property features by property ID
     * 
     * @param int $propertyId Property ID
     * @return PropertyFeature|null
     */
    public function findByPropertyId(int $propertyId): ?PropertyFeature;

    /**
     * Create new property features
     * 
     * @param PropertyFeature $feature
     * @return PropertyFeature Created feature with ID
     */
    public function create(PropertyFeature $feature): PropertyFeature;

    /**
     * Update existing property features
     * 
     * @param PropertyFeature $feature
     * @return bool Success status
     */
    public function update(PropertyFeature $feature): bool;

    /**
     * Delete property features by property ID
     * 
     * @param int $propertyId Property ID
     * @return bool Success status
     */
    public function deleteByPropertyId(int $propertyId): bool;

    /**
     * Check if property has features extracted
     * 
     * @param int $propertyId Property ID
     * @return bool
     */
    public function exists(int $propertyId): bool;

    /**
     * Get all property features with optional filters
     * 
     * @param array $filters Optional filters (e.g., ['near_subway' => true])
     * @return PropertyFeature[]
     */
    public function findAllWithFilters(array $filters = []): array;
}
