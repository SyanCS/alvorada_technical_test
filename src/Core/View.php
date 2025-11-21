<?php

namespace App\Core;

/**
 * View
 * Simple view rendering system
 * Separates presentation from business logic
 */
class View
{
    private static string $viewPath = __DIR__ . '/../../views';
    private static string $layoutPath = __DIR__ . '/../../views/layouts';

    /**
     * Render a view
     * 
     * @param string $view View name (e.g., 'property/success')
     * @param array $data Data to pass to view
     * @param string|null $layout Layout to use (default: 'main')
     */
    public static function render(string $view, array $data = [], ?string $layout = 'main'): void
    {
        // Extract data to variables
        extract($data);

        // Start output buffering
        ob_start();

        // Include the view file
        $viewFile = self::$viewPath . '/' . $view . '.php';
        
        if (!file_exists($viewFile)) {
            throw new \Exception("View not found: {$view}");
        }

        include $viewFile;

        // Get the view content
        $content = ob_get_clean();

        // Render with layout if specified
        if ($layout !== null) {
            self::renderLayout($layout, $content, $data);
        } else {
            echo $content;
        }
    }

    /**
     * Render layout
     */
    private static function renderLayout(string $layout, string $content, array $data): void
    {
        extract($data);
        
        $layoutFile = self::$layoutPath . '/' . $layout . '.php';
        
        if (!file_exists($layoutFile)) {
            // No layout found, just output content
            echo $content;
            return;
        }

        include $layoutFile;
    }

    /**
     * Render JSON response
     */
    public static function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    /**
     * Redirect to a URL
     */
    public static function redirect(string $url, int $statusCode = 302): void
    {
        http_response_code($statusCode);
        header("Location: {$url}");
        exit;
    }
}

