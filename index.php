<?php
/**
 * Root Entry Point - Property Intake Form
 * As per requirements: index.php hosting the form
 */

// Load autoloader
require_once __DIR__ . '/src/Config/Autoloader.php';

use App\Config\Container;
use App\Controllers\PropertyController;

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize container
$container = Container::getInstance();

// Get controller from container
$propertyController = $container->get(PropertyController::class);

// Simple routing
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Route: /property?id=X - Show property details
if ($requestUri === '/property' && isset($_GET['id'])) {
    $propertyController->showProperty();
    exit;
}

// Route: POST / - Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $propertyController->create();
    exit;
}

// Route: GET / - Show the form
$propertyController->showForm();

