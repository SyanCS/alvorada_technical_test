<?php

namespace App\Controllers;

use App\Core\View;
use App\Config\Database;
use App\Exceptions\ValidationException;
use App\Exceptions\GeolocationException;
use App\Exceptions\NotFoundException;
use App\Services\PropertyService;
use Exception;

/**
 * Property Controller
 * Thin controller - delegates business logic to PropertyService
 * Handles HTTP request/response coordination only
 * Uses View layer for presentation
 */
class PropertyController
{
    private PropertyService $propertyService;

    public function __construct(PropertyService $propertyService)
    {
        $this->propertyService = $propertyService;
    }

    /**
     * Show property creation form
     */
    public function showForm(): void
    {
        // Test database connection for status display
        $dbStatus = 'Not Connected';
        $dbColor = 'red';
        
        try {
            $db = Database::getInstance();
            $connection = $db->getConnection();
            $dbStatus = 'Connected Successfully!';
            $dbColor = 'green';
        } catch (Exception $e) {
            $dbStatus = 'Connection Failed: ' . $e->getMessage();
            $dbColor = 'red';
        }

        View::render('property/form', [
            'title' => 'Add Property - Alvorada',
            'dbStatus' => $dbStatus,
            'dbColor' => $dbColor,
            'phpVersion' => phpversion()
        ]);
    }

    /**
     * Create a new property
     * Handles form submission
     */
    public function create(): void
    {
        // Get form data
        $data = [
            'name' => $_POST['name'] ?? '',
            'address' => $_POST['address'] ?? ''
        ];

        try {
            // Delegate business logic to service
            $property = $this->propertyService->createProperty($data);

            // Render success view
            View::render('property/success', [
                'title' => 'Property Created - Alvorada',
                'property' => $property->toArray(),
                'mapUrl' => "/map.html?id={$property->getId()}"
            ]);

        } catch (ValidationException $e) {
            View::render('property/error', [
                'title' => 'Error - Alvorada',
                'message' => 'Validation error',
                'errors' => $e->getErrors(),
                'maxWidth' => '600px'
            ]);
        } catch (GeolocationException $e) {
            View::render('property/error', [
                'title' => 'Error - Alvorada',
                'message' => 'Failed to geocode address',
                'errors' => ['address' => $e->getMessage()],
                'maxWidth' => '600px'
            ]);
        } catch (Exception $e) {
            error_log("PropertyController::create Error: " . $e->getMessage());
            View::render('property/error', [
                'title' => 'Error - Alvorada',
                'message' => 'An error occurred while creating the property',
                'errors' => ['general' => $e->getMessage()],
                'maxWidth' => '600px'
            ]);
        }
    }

    /**
     * Get property data as JSON (for API)
     */
    public function createJson(): void
    {
        $data = [
            'name' => $_POST['name'] ?? '',
            'address' => $_POST['address'] ?? ''
        ];

        try {
            $property = $this->propertyService->createProperty($data);

            View::json([
                'success' => true,
                'message' => 'Property created successfully!',
                'property' => $property->toArray(),
                'redirect' => "/map.html?id={$property->getId()}"
            ]);

        } catch (ValidationException $e) {
            View::json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->getErrors()
            ], 422);
        } catch (GeolocationException $e) {
            View::json([
                'success' => false,
                'message' => 'Failed to geocode address',
                'errors' => ['address' => $e->getMessage()]
            ], 400);
        } catch (Exception $e) {
            error_log("PropertyController::createJson Error: " . $e->getMessage());
            View::json([
                'success' => false,
                'message' => 'An error occurred while creating the property',
                'errors' => ['general' => $e->getMessage()]
            ], 500);
        }
    }

    /**
     * Get property by ID
     * Returns array for API use
     * 
     * @param int $id
     * @return array Response data
     */
    public function show(int $id): array
    {
        try {
            $property = $this->propertyService->getProperty($id);

            return [
                'success' => true,
                'property' => $property->toArray()
            ];

        } catch (NotFoundException $e) {
            return [
                'success' => false,
                'message' => 'Property not found',
                'error' => 'NOT_FOUND'
            ];
        } catch (Exception $e) {
            error_log("PropertyController::show Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to retrieve property',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Show property details page (HTML view)
     * Renders the property details with notes
     */
    public function showProperty(): void
    {
        $id = $_GET['id'] ?? null;

        if (!$id || !is_numeric($id)) {
            View::render('property/error', [
                'title' => 'Error - Alvorada',
                'message' => 'Invalid or missing property ID',
                'backUrl' => '/'
            ]);
            return;
        }

        try {
            $property = $this->propertyService->getProperty((int)$id);

            View::render('property/show', [
                'title' => htmlspecialchars($property->getName()) . ' - Alvorada',
                'property' => $property->toArray(),
                'maxWidth' => '900px'
            ]);

        } catch (NotFoundException $e) {
            View::render('property/error', [
                'title' => 'Property Not Found - Alvorada',
                'message' => 'The requested property could not be found.',
                'backUrl' => '/'
            ]);
        } catch (Exception $e) {
            error_log("PropertyController::showProperty Error: " . $e->getMessage());
            View::render('property/error', [
                'title' => 'Error - Alvorada',
                'message' => 'An error occurred while loading the property.',
                'backUrl' => '/'
            ]);
        }
    }

    /**
     * Get property by ID and render as JSON
     * For API endpoints - handles validation and response
     */
    public function showJson(): void
    {
        // Get and validate ID parameter
        $id = $_GET['id'] ?? null;

        if (!$id || !is_numeric($id)) {
            View::json([
                'success' => false,
                'message' => 'Invalid or missing id parameter'
            ], 400);
            return;
        }

        try {
            $property = $this->propertyService->getProperty((int)$id);

            View::json([
                'success' => true,
                'property' => $property->toArray()
            ]);

        } catch (NotFoundException $e) {
            View::json([
                'success' => false,
                'message' => 'Property not found'
            ], 404);
        } catch (Exception $e) {
            error_log("PropertyController::showJson Error: " . $e->getMessage());
            View::json([
                'success' => false,
                'message' => 'Failed to retrieve property'
            ], 500);
        }
    }

    /**
     * List all properties with pagination
     * 
     * @param int $page
     * @param int $perPage
     * @return array HTTP response
     */
    public function index(int $page = 1, int $perPage = 20): array
    {
        try {
            $result = $this->propertyService->listProperties($page, $perPage);

            return [
                'success' => true,
                'properties' => array_map(fn($p) => $p->toArray(), $result['properties']),
                'pagination' => $result['pagination']
            ];

        } catch (Exception $e) {
            error_log("PropertyController::index Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to retrieve properties',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * List all properties as JSON (for API)
     * GET /api/properties.php
     */
    public function indexJson(): void
    {
        try {
            $result = $this->propertyService->listProperties();

            View::json([
                'success' => true,
                'properties' => array_map(fn($p) => $p->toArray(), $result['properties']),
                'count' => count($result['properties'])
            ]);

        } catch (Exception $e) {
            error_log("PropertyController::indexJson Error: " . $e->getMessage());
            View::json([
                'success' => false,
                'message' => 'Failed to retrieve properties'
            ], 500);
        }
    }

    /**
     * Search properties by address
     * 
     * @param string $query
     * @return array HTTP response
     */
    public function search(string $query): array
    {
        try {
            $properties = $this->propertyService->searchProperties($query);

            return [
                'success' => true,
                'properties' => array_map(fn($p) => $p->toArray(), $properties),
                'count' => count($properties)
            ];

        } catch (Exception $e) {
            error_log("PropertyController::search Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Search failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Find properties within radius
     * 
     * @param float $latitude
     * @param float $longitude
     * @param int $radiusMeters
     * @return array HTTP response
     */
    public function findNearby(float $latitude, float $longitude, int $radiusMeters = 5000): array
    {
        try {
            $properties = $this->propertyService->findNearbyProperties(
                $latitude, 
                $longitude, 
                $radiusMeters
            );

            return [
                'success' => true,
                'properties' => $properties,
                'count' => count($properties),
                'center' => [
                    'latitude' => $latitude,
                    'longitude' => $longitude
                ],
                'radius_meters' => $radiusMeters
            ];

        } catch (ValidationException $e) {
            return [
                'success' => false,
                'message' => 'Invalid coordinates',
                'error' => $e->getMessage()
            ];
        } catch (Exception $e) {
            error_log("PropertyController::findNearby Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to find nearby properties',
                'error' => $e->getMessage()
            ];
        }
    }
}

