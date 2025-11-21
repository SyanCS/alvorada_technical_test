<?php

namespace App\Contracts;

use App\Models\Property;

/**
 * Property Repository Interface
 * Defines contract for property data access
 */
interface PropertyRepositoryInterface extends RepositoryInterface
{
    /**
     * Find properties near a location
     * 
     * @param float $latitude
     * @param float $longitude
     * @param int $radiusMeters Search radius in meters
     * @return Property[]
     */
    public function findNearby(float $latitude, float $longitude, int $radiusMeters = 1000): array;
    
    /**
     * Check if property name exists
     * 
     * @param string $name Property name
     * @return bool
     */
    public function existsByName(string $name): bool;
    
    /**
     * Check if property address exists
     * 
     * @param string $address Property address
     * @return bool
     */
    public function existsByAddress(string $address): bool;
    
    /**
     * Find property by location (within radius)
     * 
     * @param float $latitude
     * @param float $longitude
     * @param int $radiusMeters Search radius in meters
     * @return Property|null
     */
    public function findByLocation(float $latitude, float $longitude, int $radiusMeters = 10): ?Property;
}
