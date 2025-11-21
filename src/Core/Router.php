<?php

namespace App\Core;

use Exception;

/**
 * Router
 * Handles routing for the application
 * Maps HTTP requests to controller actions
 */
class Router
{
    private array $routes = [];
    private string $basePath;

    public function __construct(string $basePath = '')
    {
        $this->basePath = rtrim($basePath, '/');
    }

    /**
     * Register a GET route
     */
    public function get(string $path, callable|array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    /**
     * Register a POST route
     */
    public function post(string $path, callable|array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    /**
     * Register a route
     */
    private function addRoute(string $method, string $path, callable|array $handler): void
    {
        $path = $this->basePath . $path;
        $this->routes[$method][$path] = $handler;
    }

    /**
     * Dispatch the current request
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Check if route exists
        if (!isset($this->routes[$method][$path])) {
            $this->handleNotFound();
            return;
        }

        $handler = $this->routes[$method][$path];

        try {
            // Call the handler
            if (is_array($handler)) {
                [$controller, $action] = $handler;
                $controller->$action();
            } else {
                $handler();
            }
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    /**
     * Handle 404 Not Found
     */
    private function handleNotFound(): void
    {
        http_response_code(404);
        echo "404 - Page Not Found";
    }

    /**
     * Handle errors
     */
    private function handleError(Exception $e): void
    {
        http_response_code(500);
        error_log("Router Error: " . $e->getMessage());
        
        if (getenv('APP_DEBUG') === 'true') {
            echo "Error: " . $e->getMessage();
        } else {
            echo "Internal Server Error";
        }
    }
}

