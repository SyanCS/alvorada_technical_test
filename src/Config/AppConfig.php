<?php

namespace App\Config;

/**
 * Application Configuration
 * Central configuration management with defaults
 * Single source of truth for all app settings
 */
class AppConfig
{
    private static ?AppConfig $instance = null;
    private array $config = [];

    /**
     * Private constructor - loads all configuration
     */
    private function __construct()
    {
        $this->loadConfiguration();
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(): AppConfig
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Load all configuration with defaults
     */
    private function loadConfiguration(): void
    {
        $this->config = [
            // Application
            'app' => [
                'name' => $this->env('APP_NAME', 'AlvoradaPropertyResearchSystem'),
                'env' => $this->env('APP_ENV', 'development'),
                'debug' => $this->env('APP_DEBUG', 'true') === 'true',
            ],

            // Database
            'database' => [
                'host' => $this->env('DB_HOST', 'db'),
                'name' => $this->env('DB_NAME', 'alvorada_db'),
                'user' => $this->env('DB_USER', 'alvorada_user'),
                'password' => $this->env('DB_PASSWORD', 'alvorada_password'),
                'charset' => $this->env('DB_CHARSET', 'utf8mb4'),
            ],

            // Geolocation Service
            'geolocation' => [
                'provider' => $this->env('GEOLOCATION_PROVIDER', 'nominatim'),
                'base_url' => $this->env('GEOLOCATION_BASE_URL', 'https://nominatim.openstreetmap.org/search'),
                'reverse_url' => $this->env('GEOLOCATION_REVERSE_URL', 'https://nominatim.openstreetmap.org/reverse'),
                'user_agent' => $this->env('GEOLOCATION_USER_AGENT', 'AlvoradaPropertyResearchSystem/1.0'),
                'timeout' => (int) $this->env('GEOLOCATION_TIMEOUT', '10'),
                'api_key' => $this->env('GEOLOCATION_API_KEY', ''),
            ],

            // HTTP Client
            'http' => [
                'user_agent' => $this->env('HTTP_CLIENT_USER_AGENT', 'AlvoradaPropertyResearchSystem/1.0'),
                'timeout' => (int) $this->env('HTTP_CLIENT_TIMEOUT', '10'),
                'follow_redirects' => $this->env('HTTP_CLIENT_FOLLOW_REDIRECTS', 'true') === 'true',
                'verify_ssl' => $this->env('HTTP_CLIENT_VERIFY_SSL', 'true') === 'true',
            ],

            // Google Gemini Service
            'gemini' => [
                'api_base_url' => $this->env('GEMINI_API_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
                'api_key' => $this->env('GEMINI_API_KEY', ''),
                'model' => $this->env('GEMINI_MODEL', 'gemini-2.0-flash'),
                'temperature' => (float) $this->env('GEMINI_TEMPERATURE', '0.3'),
                'max_tokens' => (int) $this->env('GEMINI_MAX_TOKENS', '2048'),
                'timeout' => (int) $this->env('GEMINI_TIMEOUT', '30'),
                'max_retries' => (int) $this->env('GEMINI_MAX_RETRIES', '3'),
            ],
        ];
    }

    /**
     * Get environment variable with fallback
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    private function env(string $key, $default = null): mixed
    {
        $value = getenv($key);
        return $value !== false ? $value : $default;
    }

    /**
     * Get configuration value using dot notation
     * 
     * @param string $key Dot notation key (e.g., 'database.host')
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    public function get(string $key, $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $segment) {
            if (!isset($value[$segment])) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Get all configuration for a section
     * 
     * @param string $section
     * @return array
     */
    public function getSection(string $section): array
    {
        return $this->config[$section] ?? [];
    }

    /**
     * Check if configuration key exists
     * 
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $segment) {
            if (!isset($value[$segment])) {
                return false;
            }
            $value = $value[$segment];
        }

        return true;
    }

    /**
     * Get all configuration
     * 
     * @return array
     */
    public function all(): array
    {
        return $this->config;
    }

    // Convenience methods for common configs
    
    public function getAppName(): string
    {
        return $this->get('app.name');
    }

    public function isDebug(): bool
    {
        return $this->get('app.debug');
    }

    public function isDevelopment(): bool
    {
        return $this->get('app.env') === 'development';
    }

    public function isProduction(): bool
    {
        return $this->get('app.env') === 'production';
    }

    /**
     * Get database configuration
     * 
     * @return array
     */
    public function getDatabaseConfig(): array
    {
        return $this->getSection('database');
    }

    /**
     * Get Gemini configuration
     * 
     * @return array
     */
    public function getGeminiConfig(): array
    {
        return $this->getSection('gemini');
    }

    /**
     * Get geolocation configuration
     * 
     * @return array
     */
    public function getGeolocationConfig(): array
    {
        return $this->getSection('geolocation');
    }

    /**
     * Get HTTP client configuration
     * 
     * @return array
     */
    public function getHttpConfig(): array
    {
        return $this->getSection('http');
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
}

