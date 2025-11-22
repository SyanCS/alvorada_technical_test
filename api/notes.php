<?php
/**
 * Notes API Endpoint
 * GET /api/notes.php?property_id={id}
 * Returns all notes for a property
 */

// Load autoloader
require_once __DIR__ . '/../src/Config/Autoloader.php';

use App\Config\Container;
use App\Controllers\NoteController;
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
    $controller = $container->get(NoteController::class);
    
    // Get notes (controller handles response)
    $controller->getNotesByProperty();

} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    View::json([
        'success' => false,
        'message' => 'Internal server error'
    ], 500);
}

