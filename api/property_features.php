<?php
/**
 * Property Features API Endpoint
 * GET /api/property_features.php?property_id={id}
 * 
 * Retrieves extracted features for a specific property
 * 
 * Query Parameters:
 * - property_id: integer (required)
 * 
 * Response:
 * {
 *   "success": true,
 *   "message": "Features retrieved successfully",
 *   "property_id": 1,
 *   "features": {
 *     "near_subway": true,
 *     "needs_renovation": false,
 *     "estimated_capacity_people": 20,
 *     ...
 *   },
 *   "summary": [...],
 *   "has_features": true
 * }
 */

// Load autoloader
require_once __DIR__ . '/../src/Config/Autoloader.php';

use App\Config\Container;
use App\Controllers\AIController;
use App\Core\View;

// Set JSON header
header('Content-Type: application/json');

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    View::json([
        'success' => false,
        'error' => 'method_not_allowed',
        'message' => 'Only GET requests are allowed'
    ], 405);
    exit;
}

try {
    // Get controller from container
    $container = Container::getInstance();
    $controller = $container->get(AIController::class);
    
    // Handle request
    $controller->getPropertyFeatures();

} catch (Exception $e) {
    error_log("API Error (property_features): " . $e->getMessage());
    View::json([
        'success' => false,
        'error' => 'internal_error',
        'message' => 'Internal server error'
    ], 500);
}
