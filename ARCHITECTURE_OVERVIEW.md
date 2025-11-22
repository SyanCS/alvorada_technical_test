# Alvorada Property Research System - Architecture Overview

## Table of Contents
1. [Architecture Patterns](#architecture-patterns)
2. [Folder Structure](#folder-structure)
3. [Complete Request Flow](#complete-request-flow)
4. [Layer-by-Layer Breakdown](#layer-by-layer-breakdown)
5. [Data Flow Diagram](#data-flow-diagram)
6. [Key Design Decisions](#key-design-decisions)

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
- Root entry point (`index.php`) for web routes
- Separate API endpoints in `public/api/` for JSON responses
- Hybrid approach: MVC for pages, standalone APIs for AJAX

### 7. **Interface Segregation**
- Contracts define behavior
- Implementations can be swapped without changing dependents

---

## Folder Structure

```
alvorada_technical_test/
│
├── index.php                        # Root entry point - handles form and property routes
│
├── api/                             # API endpoints (JSON responses)
│   ├── property.php                 # GET single property
│   ├── properties.php               # GET all properties
│   ├── add_note.php                 # POST add note
│   └── notes.php                    # GET notes for property
│
├── public/                          # Static assets
│   ├── map.html                     # Interactive map interface (SPA)
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
├── views/                           # View templates (server-side rendered)
│   ├── layouts/
│   │   └── main.php                 # Master layout
│   └── property/
│       ├── form.php                 # Property creation form
│       ├── show.php                 # Property details page (MVC)
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
1. Apache routes to `index.php` (Root Entry Point)
2. `Autoloader.php` is loaded - registers PSR-4 autoloader
3. `Container::getInstance()` - initializes DI container
4. Container registers all services and dependencies
5. Simple routing logic checks request path:
   - `GET /` → `PropertyController->showForm()`
   - `GET /property?id=X` → `PropertyController->showProperty()`
   - `POST /` → `PropertyController->create()`
6. Calls `PropertyController->showForm()`

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
HTTP POST http://localhost/
Body: name=Downtown Office&address=123 Main St, New York, NY
```

**Flow:**
1. `index.php` detects POST request
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
    
    /**
     * Show property creation form
     */
    public function showForm(): void
    {
        View::render('property/form', [
            'title' => 'Add Property - Alvorada',
            'dbStatus' => 'Connected Successfully!'
        ]);
    }
    
    /**
     * Show property details page (server-side rendered)
     */
    public function showProperty(): void
    {
        $id = $_GET['id'] ?? null;
        
        if (!$id || !is_numeric($id)) {
            View::render('property/error', [
                'message' => 'Invalid property ID'
            ]);
            return;
        }
        
        try {
            $property = $this->propertyService->getProperty((int)$id);
            
            View::render('property/show', [
                'title' => $property->getName() . ' - Alvorada',
                'property' => $property->toArray()
            ]);
        } catch (NotFoundException $e) {
            View::render('property/error', [
                'message' => 'Property not found'
            ]);
        }
    }
    
    /**
     * Create new property
     */
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

<form method="POST" action="/index.php">
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

#### **views/property/show.php** (NEW - Server-Side Rendered)
```php
<?php
$propertyData = $property;
$notes = $propertyData['notes'] ?? [];
?>

<div class="property-details">
    <div class="property-header">
        <div class="property-name"><?php echo htmlspecialchars($propertyData['name']); ?></div>
        <div class="property-address">📍 <?php echo htmlspecialchars($propertyData['address']); ?></div>
    </div>

    <div class="property-meta">
        <div class="meta-item">
            <div class="meta-label">Latitude</div>
            <div class="meta-value"><?php echo number_format($propertyData['latitude'], 6); ?>°</div>
        </div>
        <div class="meta-item">
            <div class="meta-label">Longitude</div>
            <div class="meta-value"><?php echo number_format($propertyData['longitude'], 6); ?>°</div>
        </div>
        <div class="meta-item">
            <div class="meta-label">Total Notes</div>
            <div class="meta-value"><?php echo count($notes); ?></div>
        </div>
    </div>
</div>

<div class="notes-section">
    <h3>📝 Notes</h3>
    
    <!-- Add note form with AJAX submission -->
    <form id="addNoteForm">
        <textarea name="note" required minlength="3"></textarea>
        <button type="submit">✍️ Add Note</button>
    </form>
    
    <!-- Display existing notes -->
    <div class="notes-list">
        <?php foreach ($notes as $note): ?>
            <div class="note-item">
                <div class="note-content"><?php echo htmlspecialchars($note['note']); ?></div>
                <div class="note-meta">
                    Added on <?php echo date('M j, Y \a\t g:i A', strtotime($note['created_at'])); ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
// AJAX note submission
document.getElementById('addNoteForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const noteText = e.target.note.value.trim();
    
    const response = await fetch('/api/add_note.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            property_id: <?php echo $propertyData['id']; ?>,
            note: noteText
        })
    });
    
    if (response.ok) {
        window.location.reload(); // Refresh to show new note
    }
});
</script>
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
│                       INDEX.PHP                                  │
│                   (Root Entry Point)                             │
│  • Loads Autoloader                                              │
│  • Initializes Container                                         │
│  • Simple routing logic:                                         │
│    - GET /              → showForm()                             │
│    - GET /property?id=X → showProperty()                         │
│    - POST /             → create()                               │
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
- **Decision**: Separate files for API endpoints in root-level `/api/` directory
- **Rationale**: 
  - Different response formats (JSON vs HTML)
  - Different error handling
  - Can add API-specific middleware
  - Requirements specify API at root level, not in public/
- **Example**: `/api/property.php` returns JSON, `/index.php` returns HTML

### 13. **MVC for Property Details vs Static SPA for Map**
- **Decision**: 
  - Property details page: Server-side rendered MVC (`/property?id=X` → `views/property/show.php`)
  - Map interface: Static HTML SPA (`/map.html`)
- **Rationale**:
  - **Property Details (MVC):**
    - Single property view - data available on server
    - Better SEO - content indexed by search engines
    - Faster initial load - one request instead of two
    - Progressive enhancement - works without JavaScript
    - Consistent with form architecture
  - **Map Interface (Static SPA):**
    - Multiple properties with heavy interactivity (zoom, pan, cluster)
    - Requires JavaScript anyway (Leaflet.js)
    - Real-time filtering and search
    - Requirements explicitly specify "map.html"
- **Example**: 
  - `/property?id=1` → Server renders HTML with property data
  - `/map.html` → Client fetches all properties via `/api/properties.php`

### 14. **Root-Level index.php vs public/ Directory**
- **Decision**: `index.php` at project root, not in `public/`
- **Rationale**:
  - Requirements explicitly state "index.php at root level"
  - Simpler URL structure (`/` instead of `/public/`)
  - Static assets still organized in `public/` directory
  - `.htaccess` protects sensitive directories (`src/`, `views/`, `sql/`)
- **Security**: Directory protection via `.htaccess` rules
```apache
# Protect sensitive directories
RewriteRule ^(src|views|sql|scripts)/ - [F,L]
```

---

## URL Routing Structure

### Web Routes (HTML Responses)

| Method | URL | Handler | Response |
|--------|-----|---------|----------|
| GET | `/` | `PropertyController::showForm()` | Property intake form |
| POST | `/` | `PropertyController::create()` | Success/error page |
| GET | `/property?id=1` | `PropertyController::showProperty()` | Property details page |
| GET | `/map.html` | Static file | Interactive map (SPA) |

### API Routes (JSON Responses)

| Method | URL | File | Response |
|--------|-----|------|----------|
| GET | `/api/property.php?id=1` | `api/property.php` | Single property JSON |
| GET | `/api/properties.php` | `api/properties.php` | All properties JSON |
| POST | `/api/add_note.php` | `api/add_note.php` | Note creation result |
| GET | `/api/notes.php?property_id=1` | `api/notes.php` | Property notes JSON |

### Static Assets

| URL Pattern | Location | Purpose |
|-------------|----------|---------|
| `/css/*` | `public/css/` | Stylesheets |
| `/js/*` | `public/js/` | JavaScript files |
| `/map.html` | `public/map.html` | Map interface |

### Routing Implementation

**index.php (Root Entry Point):**
```php
<?php
require_once __DIR__ . '/src/Config/Autoloader.php';

use App\Config\Container;
use App\Controllers\PropertyController;

$container = Container::getInstance();
$propertyController = $container->get(PropertyController::class);

// Get request path
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Route: /property?id=X - Show property details (MVC)
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
```

**.htaccess (URL Rewriting):**
```apache
RewriteEngine On

# Protect sensitive directories
RewriteRule ^(src|views|sql|scripts)/ - [F,L]

# Static assets from public directory
RewriteRule ^(css|js)/(.*)$ public/$1/$2 [L]

# Map HTML page from public directory
RewriteRule ^(map\.html)$ public/$1 [L]

# Route all non-file/non-directory requests to index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Security headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>

# Prevent directory listing
Options -Indexes
```

**How it works:**
- Static files (CSS, JS, map.html) are served directly from `public/`
- API files in `/api/` are accessed directly (PHP files exist)
- All other requests (like `/property?id=X`) are routed to `index.php`
- `index.php` handles routing logic for web pages

### User Flow Examples

**1. Adding a Property:**
```
User → GET / 
     → index.php 
     → PropertyController::showForm() 
     → views/property/form.php
     → User sees form

User → POST / (with form data)
     → index.php
     → PropertyController::create()
     → PropertyService::createProperty()
     → GeolocationService::geocodeAddress()
     → PropertyRepository::create()
     → views/property/success.php
     → User sees success page with "View on Map" link
```

**2. Viewing Property Details:**
```
User → GET /property?id=1
     → index.php
     → PropertyController::showProperty()
     → PropertyService::getProperty()
     → PropertyRepository::findById()
     → views/property/show.php (server-rendered)
     → User sees property details with notes

User → Submits note form (AJAX)
     → POST /api/add_note.php
     → Standalone API endpoint
     → Returns JSON response
     → Page reloads to show new note
```

**3. Viewing Map:**
```
User → GET /map.html
     → Static HTML file served
     → JavaScript loads
     → AJAX GET /api/properties.php
     → Returns all properties as JSON
     → Leaflet.js renders markers on map
     → User clicks marker
     → Popup shows "View Details" link
     → Links to /property?id=X
```

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

