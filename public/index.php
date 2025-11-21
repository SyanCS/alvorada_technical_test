<?php
/**
 * Front Controller
 * Single entry point for all requests (MVC Pattern)
 */

// Load autoloader
require_once __DIR__ . '/../src/Config/Autoloader.php';

use App\Config\Container;
use App\Controllers\PropertyController;
use App\Core\Router;

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize container
$container = Container::getInstance();

// Initialize router
$router = new Router();

// Get controller from container
$propertyController = $container->get(PropertyController::class);

// Define routes
$router->get('/', [$propertyController, 'showForm']);
$router->post('/property/create', [$propertyController, 'create']);

// Dispatch request
$router->dispatch();

