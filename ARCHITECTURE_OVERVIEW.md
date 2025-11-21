# Alvorada Property Research System - Architecture Overview

## Table of Contents
1. [System Overview](#system-overview)
2. [Architecture Patterns](#architecture-patterns)
3. [Folder Structure](#folder-structure)
4. [Complete Request Flow](#complete-request-flow)
5. [Layer-by-Layer Breakdown](#layer-by-layer-breakdown)
6. [Data Flow Diagram](#data-flow-diagram)
7. [Key Design Decisions](#key-design-decisions)

---

## System Overview

The Alvorada Property Research System is a modern PHP application built with clean architecture principles. It manages properties with geolocation features using PostgreSQL with PostGIS extension for spatial queries.

**Technology Stack:**
- **Backend**: PHP 8.2+
- **Database**: PostgreSQL 15 with PostGIS extension
- **Web Server**: Apache with mod_rewrite
- **Frontend**: Vanilla JavaScript with Leaflet.js for maps
- **External API**: OpenStreetMap Nominatim for geocoding
- **Containerization**: Docker with docker-compose

**Core Features:**
- Property creation with automatic address geocoding
- Geospatial data storage using PostGIS geography types
- Address enrichment with metadata (OSM place_id, type, bounding box)
- Duplicate detection (by name, address, and location)
- Notes system for property annotations
- Interactive map visualization
- RESTful API endpoints
- Proximity-based property search

---

## Architecture Patterns

The application implements multiple design patterns for maintainability and scalability:

### 1. **MVC (Model-View-Controller)**
- **Models**: Domain entities (`Property`, `Note`)
- **Views**: PHP templates in `views/` folder
- **Controllers**: Thin controllers that coordinate HTTP requests

### 2. **Repository Pattern**
- Abstracts data access layer
- All database queries isolated in repository classes
- Implements interfaces for loose coupling

### 3. **Service Layer Pattern**
- Business logic separated from controllers
- Services orchestrate multiple repositories and external APIs
- Enforces business rules and validation

### 4. **Dependency Injection**
- Container manages all dependencies
- Constructor injection throughout
- Enables easy testing and swapping implementations

### 5. **Singleton Pattern**
- Used for: `Database`, `Container`, `AppConfig`
- Ensures single instance of critical resources

### 6. **Front Controller Pattern**
- Single entry point (`public/index.php`)
- All requests routed through one file

### 7. **Interface Segregation**
- Contracts define behavior
- Implementations can be swapped without changing dependents

---

## Folder Structure

```
alvorada_technical_test/
│
├── public/                          # Web root (document root)
│   ├── index.php                    # Front controller - entry point
│   ├── map.html                     # Interactive map interface
│   ├── property.html                # Property detail page
│   ├── api/                         # API endpoints
│   │   ├── property.php             # GET single property
│   │   ├── properties.php           # GET all properties
│   │   ├── add_note.php             # POST add note
│   │   └── notes.php                # GET notes for property
│   ├── css/                         # Stylesheets
│   └── js/                          # JavaScript files
│
├── src/                             # Application source code
│   ├── Config/                      # Configuration & Bootstrap
│   │   ├── Autoloader.php           # PSR-4 autoloader
│   │   ├── AppConfig.php            # Centralized configuration (Singleton)
│   │   ├── Database.php             # Database connection (Singleton)
│   │   └── Container.php            # DI Container (Singleton)
│   │
│   ├── Core/                        # Core framework components
│   │   ├── Router.php               # HTTP routing
│   │   └── View.php                 # Template rendering
│   │
│   ├── Contracts/                   # Interfaces (Dependency Inversion)
│   │   ├── DatabaseInterface.php
│   │   ├── PropertyRepositoryInterface.php
│   │   ├── NoteRepositoryInterface.php
│   │   └── RepositoryInterface.php
│   │
│   ├── Models/                      # Domain entities
│   │   ├── Property.php             # Property model with PostGIS support
│   │   └── Note.php                 # Note model
│   │
│   ├── Repositories/                # Data Access Layer
│   │   ├── PropertyRepository.php   # Property database operations
│   │   └── NoteRepository.php       # Note database operations
│   │
│   ├── Services/                    # Business Logic Layer
│   │   ├── PropertyService.php      # Property business logic
│   │   ├── NoteService.php          # Note business logic
│   │   ├── GeolocationService.php   # Address geocoding
│   │   └── HttpClient.php           # HTTP request wrapper
│   │
│   ├── Validators/                  # Input validation
│   │   └── PropertyValidator.php    # Property & note validation
│   │
│   ├── Controllers/                 # HTTP Controllers (thin)
│   │   ├── PropertyController.php   # Property HTTP coordination
│   │   └── NoteController.php       # Note HTTP coordination
│   │
│   └── Exceptions/                  # Custom exceptions
│       ├── ValidationException.php
│       ├── GeolocationException.php
│       ├── NotFoundException.php
│       └── DatabaseException.php
│
├── views/                           # View templates
│   ├── layouts/
│   │   └── main.php                 # Master layout
│   └── property/
│       ├── form.php                 # Property creation form
│       ├── success.php              # Success page
│       └── error.php                # Error page
│
├── sql/
│   └── schema.sql                   # Database schema with PostGIS
│
├── scripts/
│   └── seed_properties.php          # Database seeder
│
├── docker-compose.yml               # Docker orchestration
├── Dockerfile                       # PHP-Apache container
└── .env                             # Environment configuration
```

---

## Complete Request Flow

### Example: User Creates a New Property

#### **Step 1: User Visits Homepage**
```
HTTP GET http://localhost/
```

**Flow:**
1. Apache routes to `public/index.php` (Front Controller)
2. `Autoloader.php` is loaded - registers PSR-4 autoloader
3. `Container::getInstance()` - initializes DI container
4. Container registers all services and dependencies
5. `Router` is created
6. Routes are registered:
   - `GET /` → `PropertyController->showForm()`
   - `POST /property/create` → `PropertyController->create()`
7. `Router->dispatch()` matches current request
8. Calls `PropertyController->showForm()`

**PropertyController->showForm():**
```php
public function showForm(): void
{
    // Test database connection
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    // Render view
    View::render('property/form', [
        'title' => 'Add Property - Alvorada',
        'dbStatus' => 'Connected Successfully!',
        'phpVersion' => phpversion()
    ]);
}
```

**View::render():**
1. Extracts data array to variables
2. Uses output buffering
3. Includes `views/property/form.php`
4. Captures content
5. Wraps in `views/layouts/main.php`
6. Outputs HTML

**Result:** User sees property creation form

---

#### **Step 2: User Submits Form**
```
HTTP POST http://localhost/property/create
Body: name=Downtown Office&address=123 Main St, New York, NY
```

**Flow:**
1. Router matches `POST /property/create`
2. Calls `PropertyController->create()`

**PropertyController->create():**
```php
public function create(): void
{
    // Get form data
    $data = [
        'name' => $_POST['name'],
        'address' => $_POST['address']
    ];
    
    try {
        // Delegate to service
        $property = $this->propertyService->createProperty($data);
        
        // Render success view
        View::render('property/success', [
            'property' => $property->toArray(),
            'mapUrl' => "/map.html?id={$property->getId()}"
        ]);
    } catch (ValidationException $e) {
        View::render('property/error', [
            'message' => 'Validation error',
            'errors' => $e->getErrors()
        ]);
    }
}
```

---

#### **Step 3: PropertyService->createProperty()**

This is where the **business logic** happens:

```php
public function createProperty(array $data): Property
{
    // 1. Sanitize input (XSS prevention)
    $data = $this->validator->sanitize($data);
    
    // 2. Validate input
    if (!$this->validator->validate($data)) {
        throw new ValidationException(
            "Validation failed",
            $this->validator->getErrors()
        );
    }
    
    // 3. Check duplicate name
    if ($this->propertyRepository->existsByName($data['name'])) {
        throw new ValidationException(
            "Duplicate property found",
            ['name' => 'A property with this name already exists']
        );
    }
    
    // 4. Geocode address (external API call)
    $geoData = $this->geolocationService->geocodeAddress($data['address']);
    // Returns: ['latitude' => 40.7128, 'longitude' => -74.0060, 'extra_field' => {...}]
    
    // 5. Check duplicate address (normalized)
    $normalizedAddress = $geoData['display_name'];
    if ($this->propertyRepository->existsByAddress($normalizedAddress)) {
        throw new ValidationException(
            "Duplicate property found",
            ['address' => 'A property with this address already exists']
        );
    }
    
    // 6. Check duplicate location (within 10 meters)
    $duplicate = $this->propertyRepository->findByLocation(
        $geoData['latitude'],
        $geoData['longitude'],
        10
    );
    if ($duplicate) {
        throw new ValidationException(
            "Duplicate property found",
            ['location' => 'A property already exists at this location']
        );
    }
    
    // 7. Create Property model
    $property = new Property();
    $property->setName($data['name']);
    $property->setAddress($normalizedAddress);
    $property->setLatitude($geoData['latitude']);
    $property->setLongitude($geoData['longitude']);
    $property->setExtraField($geoData['extra_field']);
    
    // 8. Persist to database
    return $this->propertyRepository->create($property);
}
```

---

#### **Step 4: GeolocationService->geocodeAddress()**

External API integration:

```php
public function geocodeAddress(string $address): array
{
    // Build query parameters
    $params = [
        'q' => $address,
        'format' => 'json',
        'limit' => 1,
        'addressdetails' => 1,
        'extratags' => 1,
    ];
    
    // Make HTTP GET request to Nominatim
    $response = $this->httpClient->get($this->baseUrl, $params);
    // URL: https://nominatim.openstreetmap.org/search?q=123+Main+St...
    
    $result = $response[0];
    
    // Extract data
    $latitude = (float) $result['lat'];
    $longitude = (float) $result['lon'];
    
    // Prepare extra metadata
    $extraData = [
        'display_name' => $result['display_name'],
        'type' => $result['type'],
        'class' => $result['class'],
        'importance' => $result['importance'],
        'place_id' => $result['place_id'],
        'osm_type' => $result['osm_type'],
        'osm_id' => $result['osm_id'],
        'boundingbox' => $result['boundingbox'],
    ];
    
    return [
        'latitude' => $latitude,
        'longitude' => $longitude,
        'extra_field' => json_encode($extraData),
        'display_name' => $result['display_name']
    ];
}
```

---

#### **Step 5: PropertyRepository->create()**

Database persistence with PostGIS:

```php
public function create(Property $property): Property
{
    // Use PostGIS functions for geospatial storage
    $query = "INSERT INTO properties (name, address, location, extra_field) 
              VALUES (:name, :address, 
                      ST_SetSRID(ST_MakePoint(:longitude, :latitude), 4326)::geography, 
                      :extra_field::jsonb)";
    
    $params = [
        'name' => $property->getName(),
        'address' => $property->getAddress(),
        'latitude' => $property->getLatitude(),
        'longitude' => $property->getLongitude(),
        'extra_field' => $property->getExtraField()
    ];
    
    // Execute query and get inserted ID
    $id = $this->db->insert($query, $params);
    $property->setId($id);
    
    return $property;
}
```

**PostGIS Explanation:**
- `ST_MakePoint(longitude, latitude)` - Creates a point geometry
- `ST_SetSRID(..., 4326)` - Sets coordinate system to WGS84 (GPS standard)
- `::geography` - Casts to geography type for accurate distance calculations
- `extra_field::jsonb` - Stores JSON data in PostgreSQL JSONB format

---

#### **Step 6: Response Rendered**

Controller receives the created Property object and renders success view:

```php
View::render('property/success', [
    'property' => $property->toArray(),
    'mapUrl' => "/map.html?id=123"
]);
```

**Result:** User sees success page with link to view property on map

---

## Layer-by-Layer Breakdown

### **Layer 1: Config (Bootstrap & Infrastructure)**

#### **Autoloader.php**
```php
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../';
    
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});
```

**Purpose:** Automatically loads classes based on namespace
- `App\Controllers\PropertyController` → `src/Controllers/PropertyController.php`

---

#### **AppConfig.php (Singleton)**
```php
class AppConfig
{
    private static ?AppConfig $instance = null;
    private array $config = [];
    
    private function __construct()
    {
        $this->config = [
            'database' => [
                'host' => $this->env('DB_HOST', 'db'),
                'name' => $this->env('DB_NAME', 'alvorada_db'),
                'user' => $this->env('DB_USER', 'alvorada_user'),
                'password' => $this->env('DB_PASSWORD', 'alvorada_password'),
            ],
            'geolocation' => [
                'base_url' => $this->env('GEOLOCATION_BASE_URL', 
                    'https://nominatim.openstreetmap.org/search'),
                'user_agent' => $this->env('GEOLOCATION_USER_AGENT', 
                    'AlvoradaPropertyResearchSystem/1.0'),
            ],
        ];
    }
    
    public static function getInstance(): AppConfig
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function get(string $key, $default = null): mixed
    {
        // Supports dot notation: 'database.host'
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
}
```

**Features:**
- Centralized configuration
- Environment variable support with defaults
- Dot notation access
- Singleton ensures one instance

---

#### **Database.php (Singleton)**
```php
class Database implements DatabaseInterface
{
    private static ?Database $instance = null;
    private ?PDO $connection = null;
    
    private function __construct()
    {
        $config = AppConfig::getInstance();
        $this->host = $config->get('database.host');
        $this->dbName = $config->get('database.name');
        // ...
    }
    
    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }
    
    private function connect(): void
    {
        $dsn = "pgsql:host={$this->host};dbname={$this->dbName}";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $this->connection = new PDO($dsn, $this->username, $this->password, $options);
        
        // Enable PostGIS extension
        $this->connection->exec("CREATE EXTENSION IF NOT EXISTS postgis");
    }
    
    public function query(string $query, array $params = []): \PDOStatement
    {
        $stmt = $this->getConnection()->prepare($query);
        $stmt->execute($params);
        return $stmt;
    }
    
    public function fetchAll(string $query, array $params = []): array
    {
        $stmt = $this->query($query, $params);
        return $stmt->fetchAll();
    }
    
    public function insert(string $query, array $params = []): int
    {
        $this->query($query, $params);
        return (int) $this->getConnection()->lastInsertId();
    }
}
```

**Features:**
- Singleton pattern for single connection
- PDO with prepared statements (SQL injection prevention)
- Helper methods for common operations
- Automatic PostGIS extension enablement

---

#### **Container.php (Dependency Injection Container)**
```php
class Container
{
    private static ?Container $instance = null;
    private array $services = [];
    private array $singletons = [];
    
    private function __construct()
    {
        $this->registerServices();
    }
    
    private function registerServices(): void
    {
        // Register Database as singleton
        $this->singleton(DatabaseInterface::class, function () {
            return Database::getInstance();
        });
        
        // Register PropertyRepository
        $this->bind(PropertyRepositoryInterface::class, function () {
            return new PropertyRepository(
                $this->get(DatabaseInterface::class),
                $this->get(NoteRepositoryInterface::class)
            );
        });
        
        // Register PropertyService
        $this->bind(PropertyService::class, function () {
            return new PropertyService(
                $this->get(PropertyRepositoryInterface::class),
                $this->get(GeolocationService::class),
                $this->get(PropertyValidator::class)
            );
        });
        
        // Register PropertyController
        $this->bind(PropertyController::class, function () {
            return new PropertyController(
                $this->get(PropertyService::class)
            );
        });
    }
    
    public function get(string $abstract): mixed
    {
        // Check if singleton
        if (isset($this->singletons[$abstract])) {
            if ($this->singletons[$abstract] === null) {
                $this->singletons[$abstract] = $this->services[$abstract]();
            }
            return $this->singletons[$abstract];
        }
        
        // Create new instance
        return $this->services[$abstract]();
    }
}
```

**Dependency Graph:**
```
Database (singleton)
    ↓
NoteRepository
    ↓
PropertyRepository (depends on NoteRepository)
    ↓
GeolocationService, PropertyValidator
    ↓
PropertyService (depends on PropertyRepository, GeolocationService, PropertyValidator)
    ↓
PropertyController (depends on PropertyService)
```

---

### **Layer 2: Core (Routing & Views)**

#### **Router.php**
```php
class Router
{
    private array $routes = [];
    
    public function get(string $path, callable|array $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }
    
    public function post(string $path, callable|array $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }
    
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        if (!isset($this->routes[$method][$path])) {
            $this->handleNotFound();
            return;
        }
        
        $handler = $this->routes[$method][$path];
        
        // Call controller method
        if (is_array($handler)) {
            [$controller, $action] = $handler;
            $controller->$action();
        } else {
            $handler();
        }
    }
}
```

**Usage in index.php:**
```php
$router = new Router();
$propertyController = $container->get(PropertyController::class);

$router->get('/', [$propertyController, 'showForm']);
$router->post('/property/create', [$propertyController, 'create']);

$router->dispatch();
```

---

#### **View.php**
```php
class View
{
    private static string $viewPath = __DIR__ . '/../../views';
    
    public static function render(string $view, array $data = [], ?string $layout = 'main'): void
    {
        // Extract data to variables
        extract($data);
        
        // Start output buffering
        ob_start();
        
        // Include view file
        $viewFile = self::$viewPath . '/' . $view . '.php';
        include $viewFile;
        
        // Get view content
        $content = ob_get_clean();
        
        // Render with layout
        if ($layout !== null) {
            self::renderLayout($layout, $content, $data);
        } else {
            echo $content;
        }
    }
    
    public static function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
```

---

### **Layer 3: Models (Domain Entities)**

#### **Property.php**
```php
class Property
{
    private ?int $id = null;
    private string $name;
    private string $address;
    private float $latitude;
    private float $longitude;
    private ?string $extraField = null;
    private array $notes = [];
    
    public function hydrate(array $data): self
    {
        if (isset($data['id'])) {
            $this->id = (int) $data['id'];
        }
        if (isset($data['name'])) {
            $this->name = $data['name'];
        }
        
        // Handle PostGIS location field
        if (isset($data['location'])) {
            $this->parsePostGISLocation($data['location']);
        }
        
        return $this;
    }
    
    private function parsePostGISLocation(string $location): void
    {
        // PostGIS format: POINT(longitude latitude)
        if (preg_match('/POINT\(([0-9.\-]+)\s+([0-9.\-]+)\)/', $location, $matches)) {
            $this->longitude = (float) $matches[1];
            $this->latitude = (float) $matches[2];
        }
    }
    
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'extra_field' => $this->getExtraFieldAsArray(),
            'notes' => array_map(fn($note) => $note->toArray(), $this->notes)
        ];
    }
}
```

---

### **Layer 4: Repositories (Data Access)**

#### **PropertyRepository.php**
```php
class PropertyRepository implements PropertyRepositoryInterface
{
    private DatabaseInterface $db;
    private NoteRepositoryInterface $noteRepository;
    
    public function __construct(
        DatabaseInterface $db,
        NoteRepositoryInterface $noteRepository
    ) {
        $this->db = $db;
        $this->noteRepository = $noteRepository;
    }
    
    public function findById(int $id): ?Property
    {
        $query = "SELECT 
                    id, name, address, 
                    ST_Y(location::geometry) as latitude,
                    ST_X(location::geometry) as longitude,
                    extra_field, created_at, updated_at
                  FROM properties WHERE id = :id";
        
        $result = $this->db->fetchOne($query, ['id' => $id]);
        
        if ($result) {
            $property = new Property($result);
            
            // Load associated notes
            $notes = $this->noteRepository->findByPropertyId($id);
            $property->setNotes($notes);
            
            return $property;
        }
        
        return null;
    }
    
    public function create(Property $property): Property
    {
        $query = "INSERT INTO properties (name, address, location, extra_field) 
                  VALUES (:name, :address, 
                          ST_SetSRID(ST_MakePoint(:longitude, :latitude), 4326)::geography, 
                          :extra_field::jsonb)";
        
        $params = [
            'name' => $property->getName(),
            'address' => $property->getAddress(),
            'latitude' => $property->getLatitude(),
            'longitude' => $property->getLongitude(),
            'extra_field' => $property->getExtraField()
        ];
        
        $id = $this->db->insert($query, $params);
        $property->setId($id);
        
        return $property;
    }
    
    public function findWithinRadius(float $latitude, float $longitude, int $radiusMeters): array
    {
        $query = "SELECT 
                    id, name, address,
                    ST_Y(location::geometry) as latitude,
                    ST_X(location::geometry) as longitude,
                    extra_field,
                    ST_Distance(
                        location,
                        ST_SetSRID(ST_MakePoint(:longitude, :latitude), 4326)::geography
                    ) as distance_meters
                  FROM properties
                  WHERE ST_DWithin(
                    location,
                    ST_SetSRID(ST_MakePoint(:longitude, :latitude), 4326)::geography,
                    :radius
                  )
                  ORDER BY distance_meters";
        
        $results = $this->db->fetchAll($query, [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'radius' => $radiusMeters
        ]);
        
        $properties = [];
        foreach ($results as $result) {
            $property = new Property($result);
            $properties[] = [
                'property' => $property,
                'distance_meters' => (float) $result['distance_meters'],
                'distance_km' => round((float) $result['distance_meters'] / 1000, 2)
            ];
        }
        
        return $properties;
    }
}
```

**PostGIS Functions Used:**
- `ST_MakePoint(lon, lat)` - Creates point geometry
- `ST_SetSRID(geom, 4326)` - Sets coordinate system (WGS84)
- `ST_X()`, `ST_Y()` - Extracts longitude/latitude
- `ST_Distance()` - Calculates distance in meters
- `ST_DWithin()` - Finds points within radius

---

### **Layer 5: Services (Business Logic)**

#### **PropertyService.php**
```php
class PropertyService
{
    private PropertyRepositoryInterface $propertyRepository;
    private GeolocationService $geolocationService;
    private PropertyValidator $validator;
    
    public function createProperty(array $data): Property
    {
        // 1. Sanitize
        $data = $this->validator->sanitize($data);
        
        // 2. Validate
        if (!$this->validator->validate($data)) {
            throw new ValidationException(
                "Validation failed",
                $this->validator->getErrors()
            );
        }
        
        // 3. Check duplicate name
        if ($this->propertyRepository->existsByName($data['name'])) {
            throw new ValidationException(
                "Duplicate property found",
                ['name' => 'A property with this name already exists']
            );
        }
        
        // 4. Geocode address
        $geoData = $this->geolocationService->geocodeAddress($data['address']);
        
        // 5. Check duplicate address
        if ($this->propertyRepository->existsByAddress($geoData['display_name'])) {
            throw new ValidationException(
                "Duplicate property found",
                ['address' => 'A property with this address already exists']
            );
        }
        
        // 6. Check duplicate location (within 10m)
        $duplicate = $this->propertyRepository->findByLocation(
            $geoData['latitude'],
            $geoData['longitude'],
            10
        );
        
        if ($duplicate) {
            throw new ValidationException(
                "Duplicate property found",
                ['location' => 'A property already exists at this location']
            );
        }
        
        // 7. Create model
        $property = new Property();
        $property->setName($data['name']);
        $property->setAddress($geoData['display_name']);
        $property->setLatitude($geoData['latitude']);
        $property->setLongitude($geoData['longitude']);
        $property->setExtraField($geoData['extra_field']);
        
        // 8. Persist
        return $this->propertyRepository->create($property);
    }
    
    public function findNearbyProperties(
        float $latitude, 
        float $longitude, 
        int $radiusMeters = 5000
    ): array {
        // Business rule: validate coordinates
        if (!$this->geolocationService->validateCoordinates($latitude, $longitude)) {
            throw new ValidationException("Invalid coordinates provided");
        }
        
        // Business rule: limit radius to 100km
        $radiusMeters = min(100000, max(100, $radiusMeters));
        
        return $this->propertyRepository->findWithinRadius(
            $latitude, 
            $longitude, 
            $radiusMeters
        );
    }
}
```

---

#### **GeolocationService.php**
```php
class GeolocationService
{
    private string $baseUrl;
    private HttpClient $httpClient;
    
    public function __construct()
    {
        $config = AppConfig::getInstance();
        $this->baseUrl = $config->get('geolocation.base_url');
        $this->httpClient = new HttpClient();
    }
    
    public function geocodeAddress(string $address): array
    {
        $params = [
            'q' => $address,
            'format' => 'json',
            'limit' => 1,
            'addressdetails' => 1,
            'extratags' => 1,
        ];
        
        $response = $this->httpClient->get($this->baseUrl, $params);
        
        if (empty($response)) {
            throw new Exception("No results found for the provided address");
        }
        
        $result = $response[0];
        
        $extraData = [
            'display_name' => $result['display_name'] ?? '',
            'type' => $result['type'] ?? '',
            'class' => $result['class'] ?? '',
            'importance' => $result['importance'] ?? 0,
            'place_id' => $result['place_id'] ?? null,
            'osm_type' => $result['osm_type'] ?? '',
            'osm_id' => $result['osm_id'] ?? null,
            'boundingbox' => $result['boundingbox'] ?? [],
        ];
        
        return [
            'latitude' => (float) $result['lat'],
            'longitude' => (float) $result['lon'],
            'extra_field' => json_encode($extraData),
            'display_name' => $result['display_name'] ?? $address
        ];
    }
}
```

---

### **Layer 6: Validators**

#### **PropertyValidator.php**
```php
class PropertyValidator
{
    private array $errors = [];
    
    public function validate(array $data): bool
    {
        $this->errors = [];
        
        // Validate name
        if (empty($data['name'])) {
            $this->errors['name'] = 'Property name is required';
        } elseif (strlen($data['name']) < 2) {
            $this->errors['name'] = 'Property name must be at least 2 characters';
        } elseif (strlen($data['name']) > 255) {
            $this->errors['name'] = 'Property name cannot exceed 255 characters';
        }
        
        // Validate address
        if (empty($data['address'])) {
            $this->errors['address'] = 'Address is required';
        } elseif (strlen($data['address']) < 5) {
            $this->errors['address'] = 'Address must be at least 5 characters';
        }
        
        return empty($this->errors);
    }
    
    public function sanitize(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $value = trim($value);
                $value = str_replace("\0", '', $value);
                $sanitized[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
}
```

---

### **Layer 7: Controllers (HTTP Coordination)**

#### **PropertyController.php**
```php
class PropertyController
{
    private PropertyService $propertyService;
    
    public function __construct(PropertyService $propertyService)
    {
        $this->propertyService = $propertyService;
    }
    
    public function create(): void
    {
        $data = [
            'name' => $_POST['name'] ?? '',
            'address' => $_POST['address'] ?? ''
        ];
        
        try {
            $property = $this->propertyService->createProperty($data);
            
            View::render('property/success', [
                'title' => 'Property Created',
                'property' => $property->toArray(),
                'mapUrl' => "/map.html?id={$property->getId()}"
            ]);
            
        } catch (ValidationException $e) {
            View::render('property/error', [
                'title' => 'Error',
                'message' => 'Validation error',
                'errors' => $e->getErrors()
            ]);
        } catch (GeolocationException $e) {
            View::render('property/error', [
                'title' => 'Error',
                'message' => 'Failed to geocode address',
                'errors' => ['address' => $e->getMessage()]
            ]);
        }
    }
}
```

**Controller Responsibilities:**
- Extract data from HTTP request
- Call service methods
- Handle exceptions
- Render appropriate response (HTML or JSON)
- **NO business logic** - all delegated to services

---

### **Layer 8: Views (Presentation)**

#### **views/layouts/main.php**
```php
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $title ?? 'Alvorada'; ?></title>
    <style>
        /* Modern gradient background */
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body>
    <div class="header-nav">
        <a href="/">🏠 Add Property</a>
        <a href="/map.html">🗺️ View Map</a>
    </div>
    <div class="container">
        <?php echo $content; ?>
    </div>
</body>
</html>
```

#### **views/property/form.php**
```php
<h1>🏢 Alvorada Property System</h1>

<div class="status-card">
    <div class="status-value"><?php echo htmlspecialchars($dbStatus); ?></div>
</div>

<form method="POST" action="/property/create">
    <div class="form-group">
        <label for="name">Property Name</label>
        <input type="text" name="name" required minlength="2" maxlength="255">
    </div>
    
    <div class="form-group">
        <label for="address">Address</label>
        <input type="text" name="address" required minlength="5" maxlength="500">
    </div>
    
    <button type="submit">🚀 Add Property</button>
</form>
```

---

## Data Flow Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                         HTTP REQUEST                             │
│                    (User submits form)                           │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                    PUBLIC/INDEX.PHP                              │
│                   (Front Controller)                             │
│  • Loads Autoloader                                              │
│  • Initializes Container                                         │
│  • Creates Router                                                │
│  • Registers routes                                              │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                         ROUTER                                   │
│  • Matches HTTP method + path                                    │
│  • Dispatches to controller                                      │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                    CONTROLLER LAYER                              │
│              (PropertyController->create())                      │
│  • Extracts POST data                                            │
│  • Calls service method                                          │
│  • Handles exceptions                                            │
│  • Renders view                                                  │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                     SERVICE LAYER                                │
│           (PropertyService->createProperty())                    │
│  1. Sanitize input (XSS prevention)                              │
│  2. Validate input (business rules)                              │
│  3. Check duplicate name                                         │
│  4. Geocode address (external API) ──────────┐                  │
│  5. Check duplicate address                  │                  │
│  6. Check duplicate location                 │                  │
│  7. Create Property model                    │                  │
│  8. Persist to database                      │                  │
└────────────────────┬────────────────────────┬┘                  │
                     │                        │                   │
                     │                        │                   │
                     ▼                        ▼                   ▼
┌──────────────────────────┐  ┌──────────────────────┐  ┌────────────────┐
│   VALIDATOR LAYER        │  │  REPOSITORY LAYER    │  │  GEOLOCATION   │
│ PropertyValidator        │  │ PropertyRepository   │  │    SERVICE     │
│ • validate()             │  │ • create()           │  │ • geocode()    │
│ • sanitize()             │  │ • findById()         │  │ • API call to  │
│ • getErrors()            │  │ • existsByName()     │  │   Nominatim    │
└──────────────────────────┘  └──────────┬───────────┘  └────────────────┘
                                         │
                                         ▼
                              ┌──────────────────────┐
                              │   DATABASE LAYER     │
                              │  (Database class)    │
                              │  • query()           │
                              │  • insert()          │
                              │  • fetchAll()        │
                              └──────────┬───────────┘
                                         │
                                         ▼
                              ┌──────────────────────┐
                              │   POSTGRESQL DB      │
                              │   + PostGIS          │
                              │  • properties table  │
                              │  • geography type    │
                              │  • spatial indexes   │
                              └──────────────────────┘
                                         │
                                         │ (returns Property model)
                                         ▼
┌─────────────────────────────────────────────────────────────────┐
│                      MODEL LAYER                                 │
│                    (Property object)                             │
│  • id, name, address, lat, lon, extra_field                      │
│  • toArray() method                                              │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                      VIEW LAYER                                  │
│                 (View::render())                                 │
│  • Extracts data to variables                                    │
│  • Includes view template                                        │
│  • Wraps in layout                                               │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                     HTTP RESPONSE                                │
│                 (HTML success page)                              │
└─────────────────────────────────────────────────────────────────┘
```

---

## Key Design Decisions

### 1. **Thin Controllers, Fat Services**
- **Decision**: Controllers only handle HTTP concerns
- **Rationale**: Business logic in services makes testing easier and promotes reusability
- **Example**: PropertyController delegates all logic to PropertyService

### 2. **Repository Pattern**
- **Decision**: All database queries in repository classes
- **Rationale**: Separates data access from business logic, easier to swap database implementations
- **Example**: PropertyRepository handles all SQL queries

### 3. **Dependency Injection via Container**
- **Decision**: Container manages all dependencies
- **Rationale**: Loose coupling, easier testing with mocks, single responsibility
- **Example**: PropertyController receives PropertyService via constructor

### 4. **PostGIS for Geospatial Data**
- **Decision**: Use geography type instead of separate lat/lon columns
- **Rationale**: 
  - Accurate distance calculations (accounts for Earth's curvature)
  - Built-in spatial functions (ST_Distance, ST_DWithin)
  - Spatial indexes for performance
- **Example**: `ST_SetSRID(ST_MakePoint(lon, lat), 4326)::geography`

### 5. **Address Enrichment via External API**
- **Decision**: Geocode addresses using Nominatim
- **Rationale**: 
  - Normalizes addresses
  - Provides lat/lon coordinates
  - Enriches with metadata (place_id, type, bounding box)
- **Example**: GeolocationService calls Nominatim API

### 6. **Duplicate Detection at Multiple Levels**
- **Decision**: Check duplicates by name, address, and location
- **Rationale**: Prevents data redundancy and maintains data quality
- **Example**: 
  - Name: Exact match
  - Address: After normalization
  - Location: Within 10 meters

### 7. **JSONB for Extra Fields**
- **Decision**: Store additional metadata in JSONB column
- **Rationale**: 
  - Flexible schema for varying data
  - PostgreSQL JSONB is queryable and indexed
  - No need for separate tables
- **Example**: OSM metadata stored in extra_field

### 8. **Validation at Multiple Layers**
- **Decision**: Validate in validator class AND enforce business rules in service
- **Rationale**: 
  - Validator handles input validation (format, length)
  - Service enforces business rules (duplicates, constraints)
- **Example**: PropertyValidator checks length, PropertyService checks duplicates

### 9. **Singleton for Shared Resources**
- **Decision**: Database, Container, AppConfig are singletons
- **Rationale**: 
  - Single database connection
  - Single configuration instance
  - Memory efficiency
- **Example**: `Database::getInstance()`

### 10. **Front Controller Pattern**
- **Decision**: All requests go through index.php
- **Rationale**: 
  - Centralized request handling
  - Consistent bootstrapping
  - Easy to add middleware
- **Example**: Apache rewrites all requests to index.php

### 11. **Interface-Based Programming**
- **Decision**: Use interfaces for repositories and database
- **Rationale**: 
  - Dependency Inversion Principle
  - Easy to swap implementations
  - Better for testing (mocking)
- **Example**: PropertyRepositoryInterface, DatabaseInterface

### 12. **Separation of API and Web Routes**
- **Decision**: Separate files for API endpoints (public/api/)
- **Rationale**: 
  - Different response formats (JSON vs HTML)
  - Different error handling
  - Can add API-specific middleware
- **Example**: property.php returns JSON, index.php returns HTML

---

## Security Considerations

### 1. **SQL Injection Prevention**
- **Method**: PDO prepared statements
- **Implementation**: All queries use parameter binding
```php
$stmt = $this->db->prepare("SELECT * FROM properties WHERE id = :id");
$stmt->execute(['id' => $id]);
```

### 2. **XSS Prevention**
- **Method**: Input sanitization and output escaping
- **Implementation**: 
  - `htmlspecialchars()` in validator
  - `htmlspecialchars()` in views
```php
$sanitized[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
```

### 3. **CSRF Protection**
- **Status**: Not implemented (should be added for production)
- **Recommendation**: Add CSRF tokens to forms

### 4. **Input Validation**
- **Method**: Multi-layer validation
- **Implementation**: 
  - Client-side: HTML5 validation attributes
  - Server-side: PropertyValidator class
  - Business rules: PropertyService

### 5. **Error Handling**
- **Method**: Try-catch blocks with logging
- **Implementation**: 
  - Exceptions caught in controllers
  - Errors logged via `error_log()`
  - User-friendly error messages displayed

---

## Performance Optimizations

### 1. **Database Indexes**
- **Implementation**: Spatial indexes on location column
```sql
CREATE INDEX idx_properties_location ON properties USING GIST(location);
```

### 2. **Singleton Pattern**
- **Benefit**: Single database connection reused across requests
- **Implementation**: Database class is singleton

### 3. **Lazy Loading**
- **Implementation**: Database connection created only when needed
```php
public function getConnection(): PDO
{
    if ($this->connection === null) {
        $this->connect();
    }
    return $this->connection;
}
```

### 4. **Pagination**
- **Implementation**: LIMIT and OFFSET in queries
- **Business rule**: Max 100 items per page
```php
$perPage = min(100, max(1, $perPage));
```

### 5. **Efficient Spatial Queries**
- **Method**: PostGIS geography type with spatial indexes
- **Benefit**: Fast proximity searches even with large datasets

---

## Testing Strategy (Recommended)

### 1. **Unit Tests**
- Test individual classes in isolation
- Mock dependencies using interfaces
- Example: Test PropertyValidator independently

### 2. **Integration Tests**
- Test repository methods with test database
- Test service layer with mocked repositories
- Example: Test PropertyService->createProperty()

### 3. **API Tests**
- Test API endpoints with HTTP requests
- Verify JSON responses
- Example: Test POST /api/add_note.php

### 4. **End-to-End Tests**
- Test complete user flows
- Use browser automation (Selenium)
- Example: Test property creation from form submission to map display

---

## Deployment Considerations

### 1. **Environment Configuration**
- Use `.env` file for environment-specific settings
- Never commit `.env` to version control
- Use `env.example` as template

### 2. **Docker Deployment**
- Application containerized with Docker
- `docker-compose.yml` orchestrates services
- Separate containers for PHP-Apache and PostgreSQL

### 3. **Database Migrations**
- Use `sql/schema.sql` for initial setup
- Consider migration tool for production (e.g., Phinx)

### 4. **Logging**
- Application logs errors via `error_log()`
- Configure PHP error logging in production
- Consider centralized logging (e.g., ELK stack)

### 5. **Monitoring**
- Monitor database performance
- Track API response times
- Set up alerts for errors

---

## Future Enhancements

### 1. **Authentication & Authorization**
- Add user login system
- Role-based access control
- JWT tokens for API authentication

### 2. **Caching**
- Redis for frequently accessed data
- Cache geocoding results
- Cache property lists

### 3. **Queue System**
- Async geocoding for bulk imports
- Background jobs for heavy processing
- Use RabbitMQ or Redis Queue

### 4. **API Rate Limiting**
- Protect against abuse
- Implement token bucket algorithm
- Use middleware for rate limiting

### 5. **Full-Text Search**
- PostgreSQL full-text search
- Search across property names and addresses
- Implement search ranking

### 6. **File Uploads**
- Property images
- Document attachments
- Use cloud storage (S3, CloudFlare R2)

### 7. **Advanced Spatial Features**
- Polygon drawing for areas
- Route calculation
- Heatmaps for property density

### 8. **Real-Time Updates**
- WebSocket integration
- Live property updates on map
- Collaborative editing

---

## Conclusion

This architecture provides a solid foundation for a scalable, maintainable property management system. The separation of concerns, use of design patterns, and clean code principles make it easy to extend and test.

**Key Strengths:**
- ✅ Clean separation of layers
- ✅ Dependency injection for loose coupling
- ✅ Repository pattern for data access abstraction
- ✅ Service layer for business logic
- ✅ PostGIS for advanced spatial queries
- ✅ Address enrichment via external API
- ✅ Comprehensive validation
- ✅ Security best practices (prepared statements, XSS prevention)

**Architecture follows SOLID principles:**
- **S**ingle Responsibility: Each class has one job
- **O**pen/Closed: Open for extension, closed for modification
- **L**iskov Substitution: Interfaces allow substitution
- **I**nterface Segregation: Small, focused interfaces
- **D**ependency Inversion: Depend on abstractions, not concretions

This architecture is production-ready and can scale to handle thousands of properties with efficient spatial queries.

