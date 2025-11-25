<?php
/**
 * Properties List API Endpoint
 * GET /api/properties.php
 * Returns all properties as JSON
 */

// Load autoloader
require_once __DIR__ . '/../src/Config/Autoloader.php';

use App\Config\Container;
use App\Controllers\PropertyController;
use App\Core\View;

// Set JSON header
header('Content-Type: application/json');

// Enable CORS (adjust for production)
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
        'message' => 'Method not allowed. Use GET.'
    ], 405);
    exit;
}

try {
    // Get controller from container
    $container = Container::getInstance();
    $controller = $container->get(PropertyController::class);
    
    // Get all properties (controller handles response)
    $controller->indexJson();

} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    View::json([
        'success' => false,
        'message' => 'Internal server error'
    ], 500);
}

