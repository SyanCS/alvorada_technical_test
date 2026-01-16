<?php
/**
 * Extract Features API Endpoint
 * POST /api/extract_features.php
 * 
 * Extracts structured features from property notes using AI
 * 
 * Request Body:
 * {
 *   "property_id": 1,
 *   "force_refresh": false (optional)
 * }
 * 
 * Response:
 * {
 *   "success": true,
 *   "message": "Features extracted successfully",
 *   "property_id": 1,
 *   "features": { ... },
 *   "summary": [ ... ]
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
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    View::json([
        'success' => false,
        'error' => 'method_not_allowed',
        'message' => 'Only POST requests are allowed'
    ], 405);
    exit;
}

try {
    // Get controller from container
    $container = Container::getInstance();
    $controller = $container->get(AIController::class);
    
    // Handle request
    $controller->extractFeatures();

} catch (Exception $e) {
    error_log("API Error (extract_features): " . $e->getMessage());
    View::json([
        'success' => false,
        'error' => 'internal_error',
        'message' => 'Internal server error'
    ], 500);
}
