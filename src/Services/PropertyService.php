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

        // 3. Check for duplicate name
        if ($this->checkDuplicateName($data['name'])) {
            throw new ValidationException(
                "Duplicate property found",
                ['name' => 'A property with this name already exists']
            );
        }

        // 4. Geocode address using external API
        try {
            $geoData = $this->geolocationService->geocodeAddress($data['address']);
        } catch (Exception $e) {
            throw new GeolocationException(
                "Failed to geocode address: " . $e->getMessage(),
                0,
                $e
            );
        }

        // 5. Check for duplicate address (after geocoding for normalized address)
        $normalizedAddress = $geoData['display_name'] ?? $data['address'];
        if ($this->checkDuplicateAddress($normalizedAddress)) {
            throw new ValidationException(
                "Duplicate property found",
                ['address' => 'A property with this address already exists']
            );
        }

        // 6. Create and populate property model
        $property = new Property();
        $property->setName($data['name']);
        $property->setAddress($normalizedAddress);
        $property->setLatitude($geoData['latitude']);
        $property->setLongitude($geoData['longitude']);
        $property->setExtraField($geoData['extra_field']);

        // 7. Check for duplicate location (within 10 meters)
        $duplicateByLocation = $this->checkDuplicateLocation(
            $property->getLatitude(),
            $property->getLongitude()
        );
        
        if ($duplicateByLocation) {
            throw new ValidationException(
                "Duplicate property found",
                ['location' => sprintf(
                    'A property already exists at this location: "%s"',
                    $duplicateByLocation->getName()
                )]
            );
        }

        // 8. Persist to database
        try {
            return $this->propertyRepository->create($property);
        } catch (Exception $e) {
            error_log("PropertyService::createProperty - Database error: " . $e->getMessage());
            throw new Exception("Failed to save property to database", 0, $e);
        }
    }
    
    /**
     * Check if property name already exists
     * 
     * @param string $name Property name
     * @return bool True if duplicate exists
     */
    private function checkDuplicateName(string $name): bool
    {
        try {
            return $this->propertyRepository->existsByName($name);
        } catch (Exception $e) {
            // If check fails, log and continue (don't block creation)
            error_log("PropertyService::checkDuplicateName Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if property address already exists
     * 
     * @param string $address Property address
     * @return bool True if duplicate exists
     */
    private function checkDuplicateAddress(string $address): bool
    {
        try {
            return $this->propertyRepository->existsByAddress($address);
        } catch (Exception $e) {
            // If check fails, log and continue (don't block creation)
            error_log("PropertyService::checkDuplicateAddress Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if property location already exists (within 10 meters)
     * 
     * @param float $latitude Latitude
     * @param float $longitude Longitude
     * @return Property|null Existing property if found
     */
    private function checkDuplicateLocation(float $latitude, float $longitude): ?Property
    {
        try {
            return $this->propertyRepository->findByLocation($latitude, $longitude, 10);
        } catch (Exception $e) {
            // If check fails, log and continue (don't block creation)
            error_log("PropertyService::checkDuplicateLocation Error: " . $e->getMessage());
            return null;
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

