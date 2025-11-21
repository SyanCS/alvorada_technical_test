<?php

namespace App\Services;

use App\Contracts\PropertyRepositoryInterface;
use App\Exceptions\ValidationException;
use App\Exceptions\GeolocationException;
use App\Exceptions\NotFoundException;
use App\Models\Property;
use App\Validators\PropertyValidator;
use Exception;

/**
 * Property Service
 * Contains business logic for property operations
 * This is where the "business rules" live
 */
class PropertyService
{
    private PropertyRepositoryInterface $propertyRepository;
    private GeolocationService $geolocationService;
    private PropertyValidator $validator;

    public function __construct(
        PropertyRepositoryInterface $propertyRepository,
        GeolocationService $geolocationService,
        PropertyValidator $validator
    ) {
        $this->propertyRepository = $propertyRepository;
        $this->geolocationService = $geolocationService;
        $this->validator = $validator;
    }

    /**
     * Create a new property with address enrichment
     * Business logic for property creation
     * 
     * @param array $data Raw input data
     * @return Property Created property with all enriched data
     * @throws ValidationException
     * @throws GeolocationException
     */
    public function createProperty(array $data): Property
    {
        // 1. Sanitize input
        $data = $this->validator->sanitize($data);

        // 2. Validate input
        if (!$this->validator->validate($data)) {
            throw new ValidationException(
                "Validation failed",
                $this->validator->getErrors()
            );
        }

        // 3. Geocode address using external API
        try {
            $geoData = $this->geolocationService->geocodeAddress($data['address']);
        } catch (Exception $e) {
            throw new GeolocationException(
                "Failed to geocode address: " . $e->getMessage(),
                0,
                $e
            );
        }

        // 4. Create and populate property model
        $property = new Property();
        $property->setName($data['name']);
        $property->setAddress($geoData['display_name'] ?? $data['address']);
        $property->setLatitude($geoData['latitude']);
        $property->setLongitude($geoData['longitude']);
        $property->setExtraField($geoData['extra_field']);

        // 5. Persist to database
        try {
            return $this->propertyRepository->create($property);
        } catch (Exception $e) {
            error_log("PropertyService::createProperty - Database error: " . $e->getMessage());
            throw new Exception("Failed to save property to database", 0, $e);
        }
    }

    /**
     * Get property by ID with all related data
     * 
     * @param int $id
     * @return Property
     * @throws NotFoundException
     */
    public function getProperty(int $id): Property
    {
        $property = $this->propertyRepository->findById($id);

        if ($property === null) {
            throw new NotFoundException("Property with ID {$id} not found");
        }

        return $property;
    }

    /**
     * Get all properties with pagination
     * 
     * @param int $page
     * @param int $perPage
     * @return array ['properties' => Property[], 'pagination' => array]
     */
    public function listProperties(int $page = 1, int $perPage = 20): array
    {
        // Business rule: ensure valid pagination
        $page = max(1, $page);
        $perPage = min(100, max(1, $perPage)); // Max 100 per page

        $offset = ($page - 1) * $perPage;
        $properties = $this->propertyRepository->findAll($perPage, $offset);
        $total = $this->propertyRepository->count();

        return [
            'properties' => $properties,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage)
            ]
        ];
    }

    /**
     * Search properties by address
     * 
     * @param string $query
     * @return Property[]
     */
    public function searchProperties(string $query): array
    {
        // Business rule: sanitize search query
        $query = trim($query);
        
        if (strlen($query) < 2) {
            return [];
        }

        return $this->propertyRepository->searchByAddress($query);
    }

    /**
     * Find properties within radius
     * Business logic for spatial queries
     * 
     * @param float $latitude
     * @param float $longitude
     * @param int $radiusMeters
     * @return array
     */
    public function findNearbyProperties(
        float $latitude, 
        float $longitude, 
        int $radiusMeters = 5000
    ): array {
        // Business rule: validate coordinates
        if (!$this->geolocationService->validateCoordinates($latitude, $longitude)) {
            throw new ValidationException("Invalid coordinates provided");
        }

        // Business rule: limit radius to reasonable distance (100km max)
        $radiusMeters = min(100000, max(100, $radiusMeters));

        return $this->propertyRepository->findWithinRadius(
            $latitude, 
            $longitude, 
            $radiusMeters
        );
    }

    /**
     * Update property
     * 
     * @param int $id
     * @param array $data
     * @return Property
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function updateProperty(int $id, array $data): Property
    {
        // Get existing property
        $property = $this->getProperty($id);

        // Sanitize and validate
        $data = $this->validator->sanitize($data);
        if (!$this->validator->validate($data)) {
            throw new ValidationException(
                "Validation failed",
                $this->validator->getErrors()
            );
        }

        // If address changed, re-geocode
        if (isset($data['address']) && $data['address'] !== $property->getAddress()) {
            try {
                $geoData = $this->geolocationService->geocodeAddress($data['address']);
                $property->setAddress($geoData['display_name'] ?? $data['address']);
                $property->setLatitude($geoData['latitude']);
                $property->setLongitude($geoData['longitude']);
                $property->setExtraField($geoData['extra_field']);
            } catch (Exception $e) {
                throw new GeolocationException(
                    "Failed to geocode new address: " . $e->getMessage(),
                    0,
                    $e
                );
            }
        }

        // Update name if provided
        if (isset($data['name'])) {
            $property->setName($data['name']);
        }

        // Persist changes
        $this->propertyRepository->update($property);

        return $property;
    }

    /**
     * Delete property
     * 
     * @param int $id
     * @return bool
     * @throws NotFoundException
     */
    public function deleteProperty(int $id): bool
    {
        // Verify property exists
        $this->getProperty($id);

        return $this->propertyRepository->delete($id);
    }

    /**
     * Get property statistics
     * Business logic for analytics
     * 
     * @return array
     */
    public function getStatistics(): array
    {
        $total = $this->propertyRepository->count();

        return [
            'total_properties' => $total,
            'has_properties' => $total > 0,
        ];
    }
}

