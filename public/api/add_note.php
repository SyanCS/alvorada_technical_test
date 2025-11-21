<?php
/**
 * Add Note API Endpoint
 * POST /api/add_note.php
 * Adds a note to a property
 */

// Load autoloader
require_once __DIR__ . '/../../src/Config/Autoloader.php';

use App\Config\Container;
use App\Controllers\NoteController;
use App\Core\View;

// Set JSON header
header('Content-Type: application/json');

// Enable CORS (adjust for production)
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
        'message' => 'Method not allowed. Use POST.'
    ], 405);
    exit;
}

try {
    // Get controller from container
    $container = Container::getInstance();
    $controller = $container->get(NoteController::class);
    
    // Add note (controller handles response)
    $controller->addNote();

} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    View::json([
        'success' => false,
        'message' => 'Internal server error'
    ], 500);
}

