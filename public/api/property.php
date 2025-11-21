<?php
/**
 * Property API Endpoint
 * GET /api/property.php?id={id}
 * Returns property details with notes as JSON
 */

// Load autoloader
require_once __DIR__ . '/../../src/Config/Autoloader.php';

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

// Get ID parameter
$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    View::json([
        'success' => false,
        'message' => 'Invalid or missing id parameter'
    ], 400);
    exit;
}

try {
    // Get controller from container
    $container = Container::getInstance();
    $controller = $container->get(PropertyController::class);
    
    // Get property data
    $result = $controller->show((int) $id);
    
    // Return appropriate response
    if ($result['success']) {
        View::json([
            'success' => true,
            'property' => $result['property']
        ]);
    } else {
        $statusCode = $result['error'] === 'NOT_FOUND' ? 404 : 500;
        View::json([
            'success' => false,
            'message' => $result['message']
        ], $statusCode);
    }

} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    View::json([
        'success' => false,
        'message' => 'Internal server error'
    ], 500);
}

