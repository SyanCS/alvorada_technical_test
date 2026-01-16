<?php
/**
 * Score Properties API Endpoint
 * POST /api/score_properties.php
 * 
 * Scores properties based on client requirements using AI
 * 
 * Request Body:
 * {
 *   "requirements": "office near subway, 15-20 people, parking needed",
 *   "limit": 10 (optional)
 * }
 * 
 * Response:
 * {
 *   "success": true,
 *   "message": "Properties scored successfully",
 *   "scored_properties": [
 *     {
 *       "property_id": 1,
 *       "property_name": "...",
 *       "address": "...",
 *       "score": 8.5,
 *       "explanation": "...",
 *       "strengths": [...],
 *       "weaknesses": [...],
 *       "confidence": 0.87
 *     },
 *     ...
 *   ],
 *   "total_properties": 10,
 *   "results_shown": 10,
 *   "client_requirements": "..."
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
    $controller->scoreProperties();

} catch (Exception $e) {
    error_log("API Error (score_properties): " . $e->getMessage());
    View::json([
        'success' => false,
        'error' => 'internal_error',
        'message' => 'Internal server error'
    ], 500);
}
