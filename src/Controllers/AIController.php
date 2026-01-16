<?php

namespace App\Controllers;

use App\Core\View;
use App\Services\FeatureExtractionService;
use App\Services\PropertyScoringService;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use Exception;

/**
 * AI Controller
 * Handles AI-related HTTP requests (feature extraction, property scoring)
 */
class AIController
{
    private FeatureExtractionService $featureExtractionService;
    private PropertyScoringService $scoringService;

    public function __construct(
        FeatureExtractionService $featureExtractionService,
        PropertyScoringService $scoringService
    ) {
        $this->featureExtractionService = $featureExtractionService;
        $this->scoringService = $scoringService;
    }

    /**
     * Extract features from property notes
     * POST /api/extract_features.php
     * Body: { "property_id": 1, "force_refresh": false }
     */
    public function extractFeatures(): void
    {
        try {
            // Get JSON input
            $input = $this->getJsonInput();

            // Validate property_id
            if (!isset($input['property_id'])) {
                throw new ValidationException('Missing required field: property_id');
            }

            $propertyId = (int) $input['property_id'];
            $forceRefresh = isset($input['force_refresh']) && $input['force_refresh'] === true;

            if ($propertyId <= 0) {
                throw new ValidationException('Invalid property_id');
            }

            // Extract features
            $features = $this->featureExtractionService->extractFeaturesFromProperty($propertyId, $forceRefresh);

            // Return success response
            View::json([
                'success' => true,
                'message' => 'Features extracted successfully',
                'property_id' => $propertyId,
                'features' => $features->toArray(),
                'summary' => $features->getSummary()
            ]);

        } catch (NotFoundException $e) {
            View::json([
                'success' => false,
                'error' => 'not_found',
                'message' => $e->getMessage()
            ], 404);

        } catch (ValidationException $e) {
            View::json([
                'success' => false,
                'error' => 'validation_error',
                'message' => $e->getMessage(),
                'errors' => $e->getErrors()
            ], 400);

        } catch (Exception $e) {
            error_log("Feature extraction error: " . $e->getMessage());
            View::json([
                'success' => false,
                'error' => 'extraction_failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Score properties based on client requirements
     * POST /api/score_properties.php
     * Body: { "requirements": "office near subway, 20 people, parking needed", "limit": 10 }
     */
    public function scoreProperties(): void
    {
        try {
            // Get JSON input
            $input = $this->getJsonInput();

            // Validate requirements
            if (!isset($input['requirements']) || empty(trim($input['requirements']))) {
                throw new ValidationException('Missing or empty required field: requirements');
            }

            $requirements = trim($input['requirements']);
            $limit = isset($input['limit']) && is_numeric($input['limit']) 
                ? (int) $input['limit'] 
                : null;

            // Score all properties
            $result = $this->scoringService->scoreAllProperties($requirements, $limit);

            // Return success response
            View::json([
                'success' => true,
                'message' => 'Properties scored successfully',
                'scored_properties' => $result['scored_properties'],
                'total_properties' => $result['total_properties'],
                'results_shown' => $result['results_shown'],
                'client_requirements' => $result['requirements']
            ]);

        } catch (ValidationException $e) {
            View::json([
                'success' => false,
                'error' => 'validation_error',
                'message' => $e->getMessage(),
                'errors' => $e->getErrors()
            ], 400);

        } catch (Exception $e) {
            error_log("Property scoring error: " . $e->getMessage());
            View::json([
                'success' => false,
                'error' => 'scoring_failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get extracted features for a property
     * GET /api/property_features.php?property_id=1
     */
    public function getPropertyFeatures(): void
    {
        try {
            // Get property_id from query string
            if (!isset($_GET['property_id'])) {
                throw new ValidationException('Missing required parameter: property_id');
            }

            $propertyId = (int) $_GET['property_id'];

            if ($propertyId <= 0) {
                throw new ValidationException('Invalid property_id');
            }

            // Get features
            $features = $this->featureExtractionService->getFeatures($propertyId);

            if ($features === null) {
                View::json([
                    'success' => true,
                    'message' => 'No features found for this property',
                    'property_id' => $propertyId,
                    'features' => null,
                    'has_features' => false
                ]);
                return;
            }

            // Return features
            View::json([
                'success' => true,
                'message' => 'Features retrieved successfully',
                'property_id' => $propertyId,
                'features' => $features->toArray(),
                'summary' => $features->getSummary(),
                'has_features' => true
            ]);

        } catch (ValidationException $e) {
            View::json([
                'success' => false,
                'error' => 'validation_error',
                'message' => $e->getMessage(),
                'errors' => $e->getErrors()
            ], 400);

        } catch (Exception $e) {
            error_log("Get features error: " . $e->getMessage());
            View::json([
                'success' => false,
                'error' => 'retrieval_failed',
                'message' => 'Failed to retrieve features'
            ], 500);
        }
    }

    /**
     * Get and decode JSON input from request body
     */
    private function getJsonInput(): array
    {
        $json = file_get_contents('php://input');
        
        if (empty($json)) {
            throw new ValidationException('Empty request body');
        }

        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ValidationException('Invalid JSON: ' . json_last_error_msg());
        }

        if (!is_array($data)) {
            throw new ValidationException('Request body must be a JSON object');
        }

        return $data;
    }
}
