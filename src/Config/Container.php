<?php

namespace App\Config;

use App\Contracts\DatabaseInterface;
use App\Contracts\NoteRepositoryInterface;
use App\Contracts\PropertyRepositoryInterface;
use App\Controllers\NoteController;
use App\Controllers\PropertyController;
use App\Repositories\NoteRepository;
use App\Repositories\PropertyRepository;
use App\Services\GeolocationService;
use App\Services\HttpClient;
use App\Services\NoteService;
use App\Services\PropertyService;
use App\Validators\PropertyValidator;

/**
 * Dependency Injection Container
 * Simple service container for managing dependencies
 * Implements Service Locator pattern for the application
 */
class Container
{
    private static ?Container $instance = null;
    private array $services = [];
    private array $singletons = [];

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct()
    {
        $this->registerServices();
    }

    /**
     * Get container instance (Singleton)
     */
    public static function getInstance(): Container
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register all services
     */
    private function registerServices(): void
    {
        // Register Database as singleton
        $this->singleton(DatabaseInterface::class, function () {
            return Database::getInstance();
        });

        // Register NoteRepository
        $this->bind(NoteRepositoryInterface::class, function () {
            return new NoteRepository(
                $this->get(DatabaseInterface::class)
            );
        });

        // Register PropertyRepository
        $this->bind(PropertyRepositoryInterface::class, function () {
            return new PropertyRepository(
                $this->get(DatabaseInterface::class),
                $this->get(NoteRepositoryInterface::class)
            );
        });

        // Register GeolocationService
        $this->singleton(GeolocationService::class, function () {
            return new GeolocationService();
        });

        // Register PropertyValidator
        $this->bind(PropertyValidator::class, function () {
            return new PropertyValidator();
        });

        // Register HttpClient
        $this->bind(HttpClient::class, function () {
            return new HttpClient();
        });

        // Register PropertyService (business logic layer)
        $this->bind(PropertyService::class, function () {
            return new PropertyService(
                $this->get(PropertyRepositoryInterface::class),
                $this->get(GeolocationService::class),
                $this->get(PropertyValidator::class)
            );
        });

        // Register NoteService (business logic layer)
        $this->bind(NoteService::class, function () {
            return new NoteService(
                $this->get(NoteRepositoryInterface::class),
                $this->get(PropertyRepositoryInterface::class),
                $this->get(PropertyValidator::class)
            );
        });

        // Register PropertyController (thin controller)
        $this->bind(PropertyController::class, function () {
            return new PropertyController(
                $this->get(PropertyService::class)
            );
        });

        // Register NoteController (thin controller)
        $this->bind(NoteController::class, function () {
            return new NoteController(
                $this->get(NoteService::class)
            );
        });
    }

    /**
     * Bind a service to the container
     * 
     * @param string $abstract Class name or interface
     * @param callable $concrete Factory function
     */
    public function bind(string $abstract, callable $concrete): void
    {
        $this->services[$abstract] = $concrete;
    }

    /**
     * Register a singleton service
     * 
     * @param string $abstract Class name or interface
     * @param callable $concrete Factory function
     */
    public function singleton(string $abstract, callable $concrete): void
    {
        $this->services[$abstract] = $concrete;
        $this->singletons[$abstract] = null;
    }

    /**
     * Get service from container
     * 
     * @param string $abstract Class name or interface
     * @return mixed
     * @throws \Exception
     */
    public function get(string $abstract): mixed
    {
        // Check if it's a singleton and already instantiated
        if (isset($this->singletons[$abstract])) {
            if ($this->singletons[$abstract] === null) {
                $this->singletons[$abstract] = $this->services[$abstract]();
            }
            return $this->singletons[$abstract];
        }

        // Check if service is registered
        if (!isset($this->services[$abstract])) {
            throw new \Exception("Service not found: {$abstract}");
        }

        // Create and return new instance
        return $this->services[$abstract]();
    }

    /**
     * Check if service is registered
     * 
     * @param string $abstract
     * @return bool
     */
    public function has(string $abstract): bool
    {
        return isset($this->services[$abstract]);
    }

    /**
     * Make an instance with automatic dependency resolution
     * 
     * @param string $class
     * @return mixed
     * @throws \ReflectionException
     */
    public function make(string $class): mixed
    {
        // If already bound, use that
        if ($this->has($class)) {
            return $this->get($class);
        }

        // Auto-resolve dependencies
        $reflector = new \ReflectionClass($class);
        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $dependencies = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            
            if ($type === null) {
                throw new \Exception("Cannot resolve parameter: {$parameter->getName()}");
            }

            $typeName = $type->getName();
            $dependencies[] = $this->get($typeName);
        }

        return $reflector->newInstanceArgs($dependencies);
    }
}

