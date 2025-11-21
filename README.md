# 🏢 Alvorada Property Research System

A full-stack property research and management platform built with vanilla PHP, PostgreSQL + PostGIS, and Leaflet.js. This system enables users to add properties, automatically enrich them with geolocation data, manage research notes, and visualize properties on an interactive map.

[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=flat&logo=php)](https://www.php.net/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15-336791?style=flat&logo=postgresql)](https://www.postgresql.org/)
[![PostGIS](https://img.shields.io/badge/PostGIS-3.3-4169E1?style=flat)](https://postgis.net/)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=flat&logo=docker)](https://www.docker.com/)

---

## 📋 Table of Contents

- [Features](#features)
- [Architecture](#architecture)
- [Prerequisites](#prerequisites)
- [Quick Start](#quick-start)
- [Detailed Setup](#detailed-setup)
- [Project Structure](#project-structure)
- [API Documentation](#api-documentation)
- [Usage Guide](#usage-guide)
- [Development](#development)
- [Troubleshooting](#troubleshooting)
- [Tech Stack](#tech-stack)

---

## ✨ Features

### Core Functionality
- **🏠 Property Management** - Add and manage property records with automatic geocoding
- **🌍 Geolocation Enrichment** - Automatic address geocoding via OpenStreetMap Nominatim API
- **📍 PostGIS Integration** - Advanced spatial data storage and querying
- **📝 Notes System** - Add, view, and manage research notes for each property
- **🗺️ Interactive Map** - Leaflet.js-powered map with marker clustering
- **🔍 Property Search** - Real-time search and filtering on the map
- **📊 REST API** - Complete RESTful API for programmatic access

### Technical Highlights
- **✅ Clean Architecture** - MVC pattern with Service Layer
- **✅ Dependency Injection** - Custom DI Container for loose coupling
- **✅ Repository Pattern** - Abstracted data access layer
- **✅ SOLID Principles** - Well-structured, maintainable codebase
- **✅ Input Validation** - Comprehensive validation and sanitization
- **✅ Error Handling** - Custom exceptions and graceful error handling
- **✅ Security** - Prepared statements, input sanitization, XSS protection
- **✅ No Frameworks** - Pure PHP implementation showcasing core skills

---

## 🏗️ Architecture

### Design Patterns Implemented
- **MVC (Model-View-Controller)** - Separation of concerns
- **Service Layer** - Business logic isolation
- **Repository Pattern** - Data access abstraction
- **Dependency Injection** - Loose coupling between components
- **Front Controller** - Single entry point (`public/index.php`)
- **Factory Pattern** - Object creation in DI Container

### Application Layers
```
┌─────────────────────────────────────────────────────────┐
│                    Presentation Layer                    │
│        (Views, HTML, JavaScript, CSS)                    │
└────────────────────┬────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────┐
│                   Controller Layer                       │
│     (PropertyController, NoteController)                 │
└────────────────────┬────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────┐
│                    Service Layer                         │
│   (PropertyService, NoteService, GeolocationService)    │
└────────────────────┬────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────┐
│                   Repository Layer                       │
│     (PropertyRepository, NoteRepository)                 │
└────────────────────┬────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────┐
│                     Data Layer                           │
│          (PostgreSQL + PostGIS Database)                 │
└──────────────────────────────────────────────────────────┘
```

---

## 📦 Prerequisites

Before you begin, ensure you have the following installed:

- **Docker Desktop** (v20.10+)
  - [Windows](https://docs.docker.com/desktop/install/windows-install/)
  - [Mac](https://docs.docker.com/desktop/install/mac-install/)
  - [Linux](https://docs.docker.com/desktop/install/linux-install/)
- **Docker Compose** (v2.0+) - Usually included with Docker Desktop
- **Git** (for cloning the repository)
- **Web Browser** (Chrome, Firefox, Edge, or Safari)

**Optional but Recommended:**
- **Database Client** (DBeaver, pgAdmin, or DataGrip) for database inspection
- **API Client** (Postman or Insomnia) for API testing
- **Code Editor** (VS Code, PhpStorm, or Sublime Text)

---

## 🚀 Quick Start

Get up and running in less than 5 minutes:

```bash
# 1. Clone the repository
git clone git@github.com:YourUsername/alvorada_technical_test.git
cd alvorada_technical_test

# 2. Copy environment configuration
cp env.example .env

# 3. Start Docker containers
docker compose up -d --build

# 4. Wait for services to be ready (about 10-15 seconds)
sleep 15

# 5. Open your browser
# Application: http://localhost:8080
# Map View:    http://localhost:8080/map.html
# pgAdmin:     http://localhost:5050 (optional)
```

That's it! The application is now running. 🎉

---

## 📖 Detailed Setup

### Step 1: Clone the Repository

```bash
git clone git@github.com:YourUsername/alvorada_technical_test.git
cd alvorada_technical_test
```

### Step 2: Configure Environment Variables

Copy the example environment file and customize if needed:

```bash
cp env.example .env
```

**Default Configuration** (`.env`):
```env
# Database Configuration
DB_HOST=db
DB_NAME=alvorada_db
DB_USER=alvorada_user
DB_PASSWORD=secure_password_123
DB_CHARSET=UTF8

# Application Configuration
APP_ENV=development
APP_DEBUG=true

# Geolocation API Configuration
GEOLOCATION_PROVIDER=nominatim
GEOLOCATION_BASE_URL=https://nominatim.openstreetmap.org/search
GEOLOCATION_REVERSE_URL=https://nominatim.openstreetmap.org/reverse
GEOLOCATION_USER_AGENT=AlvoradaPropertySystem/1.0
GEOLOCATION_TIMEOUT=10

# HTTP Client Configuration
HTTP_CLIENT_USER_AGENT=AlvoradaPropertySystem/1.0
HTTP_CLIENT_TIMEOUT=10
HTTP_CLIENT_FOLLOW_REDIRECTS=true
HTTP_CLIENT_VERIFY_SSL=true
```

**Note:** For production, change:
- `DB_PASSWORD` to a strong password
- `APP_ENV` to `production`
- `APP_DEBUG` to `false`

### Step 3: Build and Start Docker Containers

```bash
# Build and start all services in detached mode
docker compose up -d --build
```

This will create and start three containers:
- **alvorada_web** - PHP 8.2 + Apache web server (port 8080)
- **alvorada_db** - PostgreSQL 15 + PostGIS 3.3 (port 5432)
- **alvorada_pgadmin** - pgAdmin 4 web interface (port 5050)

**Expected Output:**
```
[+] Building 45.2s (18/18) FINISHED
[+] Running 3/3
 ✔ Container alvorada_db       Started
 ✔ Container alvorada_pgadmin  Started
 ✔ Container alvorada_web      Started
```

### Step 4: Verify Installation

Check that all containers are running:

```bash
docker compose ps
```

**Expected Output:**
```
NAME                 STATUS              PORTS
alvorada_db          Up 30 seconds      0.0.0.0:5432->5432/tcp
alvorada_pgadmin     Up 30 seconds      0.0.0.0:5050->80/tcp
alvorada_web         Up 30 seconds      0.0.0.0:8080->80/tcp
```

### Step 5: Initialize Database Schema

The database schema is automatically loaded when the container starts. Verify it:

```bash
# Check if tables exist
docker exec alvorada_db psql -U alvorada_user -d alvorada_db -c "\dt"
```

**Expected Output:**
```
              List of relations
 Schema |    Name    | Type  |     Owner
--------+------------+-------+---------------
 public | notes      | table | alvorada_user
 public | properties | table | alvorada_user
```

### Step 6: Access the Application

Open your web browser and navigate to:

- **Main Application:** http://localhost:8080/
- **Interactive Map:** http://localhost:8080/map.html
- **pgAdmin (Database UI):** http://localhost:5050/
  - Email: `admin@alvorada.com`
  - Password: `admin123`

---

## 📂 Project Structure

```
alvorada_technical_test/
│
├── public/                      # Public web root (Document root)
│   ├── index.php               # Front Controller (MVC entry point)
│   ├── map.html                # Interactive Leaflet.js map
│   ├── property.html           # Property details & notes UI
│   ├── .htaccess               # Apache URL rewrite rules
│   └── api/                    # REST API endpoints
│       ├── property.php        # GET /api/property.php?id={id}
│       ├── properties.php      # GET /api/properties.php (all)
│       ├── add_note.php        # POST /api/add_note.php
│       └── notes.php           # GET /api/notes.php?property_id={id}
│
├── src/                        # Application source code
│   ├── Config/                 # Configuration classes
│   │   ├── Autoloader.php      # PSR-4 autoloader
│   │   ├── AppConfig.php       # Centralized config management
│   │   ├── Container.php       # Dependency Injection container
│   │   └── Database.php        # Database singleton
│   │
│   ├── Controllers/            # Controller layer (thin)
│   │   ├── PropertyController.php
│   │   └── NoteController.php
│   │
│   ├── Services/               # Business logic layer
│   │   ├── PropertyService.php
│   │   ├── NoteService.php
│   │   ├── GeolocationService.php
│   │   └── HttpClient.php
│   │
│   ├── Repositories/           # Data access layer
│   │   ├── PropertyRepository.php
│   │   └── NoteRepository.php
│   │
│   ├── Models/                 # Domain models
│   │   ├── Property.php
│   │   └── Note.php
│   │
│   ├── Validators/             # Input validation
│   │   └── PropertyValidator.php
│   │
│   ├── Contracts/              # Interfaces
│   │   ├── DatabaseInterface.php
│   │   ├── RepositoryInterface.php
│   │   ├── PropertyRepositoryInterface.php
│   │   └── NoteRepositoryInterface.php
│   │
│   ├── Exceptions/             # Custom exceptions
│   │   ├── DatabaseException.php
│   │   ├── ValidationException.php
│   │   ├── NotFoundException.php
│   │   └── GeolocationException.php
│   │
│   └── Core/                   # Core framework components
│       ├── Router.php          # URL routing
│       └── View.php            # View rendering
│
├── views/                      # View templates
│   ├── layouts/
│   │   └── main.php           # Main layout template
│   └── property/
│       ├── form.php           # Property creation form
│       ├── success.php        # Success page
│       └── error.php          # Error page
│
├── sql/
│   └── schema.sql             # Database schema (PostgreSQL + PostGIS)
│
├── docker-compose.yml          # Docker services configuration
├── Dockerfile                  # PHP-Apache container definition
├── .env                        # Environment variables (create from env.example)
├── env.example                 # Environment variables template
├── .gitignore                  # Git ignore rules
│
├── README.md                   # This file (setup documentation)
├── REQUIREMENTS.md             # Original project requirements
├── API_DOCUMENTATION.md        # Complete API reference
└── AI_PROPOSAL.md              # AI/LLM enhancement proposal
```

---

## 📡 API Documentation

### Base URL
```
http://localhost:8080/api
```

### Endpoints

#### 1. Get All Properties
```http
GET /api/properties.php
```

**Response:**
```json
{
  "success": true,
  "properties": [
    {
      "id": 1,
      "name": "Empire State Building",
      "address": "20 W 34th St, New York, NY 10001",
      "latitude": 40.748817,
      "longitude": -73.985428,
      "created_at": "2025-11-21 18:00:00",
      "notes": []
    }
  ],
  "count": 1
}
```

#### 2. Get Single Property
```http
GET /api/property.php?id=1
```

**Response:**
```json
{
  "success": true,
  "property": {
    "id": 1,
    "name": "Empire State Building",
    "address": "20 W 34th St, New York, NY 10001",
    "latitude": 40.748817,
    "longitude": -73.985428,
    "extra_field": { /* OSM metadata */ },
    "created_at": "2025-11-21 18:00:00",
    "notes": [
      {
        "id": 1,
        "note": "Great location!",
        "created_at": "2025-11-21 18:05:00"
      }
    ]
  }
}
```

#### 3. Add Note to Property
```http
POST /api/add_note.php
Content-Type: application/json

{
  "property_id": 1,
  "note": "Excellent visibility and foot traffic."
}
```

**Response:**
```json
{
  "success": true,
  "message": "Note added successfully",
  "note": {
    "id": 2,
    "property_id": 1,
    "note": "Excellent visibility and foot traffic.",
    "created_at": "2025-11-21 18:10:00"
  }
}
```

#### 4. Get Notes for Property
```http
GET /api/notes.php?property_id=1
```

For complete API documentation, see [API_DOCUMENTATION.md](API_DOCUMENTATION.md).

---

## 📖 Usage Guide

### Adding a Property

1. **Navigate to the home page:** http://localhost:8080/

2. **Fill in the form:**
   - **Property Name:** e.g., "Empire State Building"
   - **Address:** e.g., "20 W 34th St, New York, NY 10001"

3. **Click "🚀 Add Property & Enrich Data"**

4. **The system will:**
   - Validate your input
   - Call OpenStreetMap Nominatim API to geocode the address
   - Extract latitude, longitude, and additional metadata
   - Store the property in PostgreSQL with PostGIS geometry
   - Show success page with coordinates

5. **Click "📍 View on Map"** to see your property on the interactive map

### Viewing Properties on the Map

1. **Navigate to:** http://localhost:8080/map.html

2. **Features:**
   - All properties displayed as markers
   - Click markers to see property details
   - Marker clustering for nearby properties
   - Search properties by name or address
   - Zoom and pan to explore
   - "Fit All Markers" to see all properties at once

3. **Focusing on a specific property:**
   - Use URL: `http://localhost:8080/map.html?id=1`
   - Map will automatically zoom to that property

### Adding Notes to a Property

1. **From the map:**
   - Click on a property marker
   - Click "View Details & Add Notes" in the popup

2. **On the property details page:**
   - Type your note (minimum 3 characters)
   - Click "✍️ Add Note"
   - Note appears immediately in the list

3. **View all notes:**
   - All notes are displayed chronologically
   - Each note shows timestamp

### Using the API

#### cURL Examples

```bash
# Get all properties
curl http://localhost:8080/api/properties.php

# Get specific property
curl http://localhost:8080/api/property.php?id=1

# Add a note
curl -X POST http://localhost:8080/api/add_note.php \
  -H "Content-Type: application/json" \
  -d '{"property_id": 1, "note": "Great location for office!"}'

# Get notes for a property
curl http://localhost:8080/api/notes.php?property_id=1
```

---

## 🛠️ Development

### Running Commands in Containers

```bash
# Execute command in web container
docker exec -it alvorada_web bash

# Execute command in database container
docker exec -it alvorada_db bash

# Run PHP command
docker exec alvorada_web php -v

# Access PostgreSQL CLI
docker exec -it alvorada_db psql -U alvorada_user -d alvorada_db
```

### Database Management

#### Using psql (PostgreSQL CLI)

```bash
# Connect to database
docker exec -it alvorada_db psql -U alvorada_user -d alvorada_db

# Common commands:
\dt                           # List tables
\d properties                 # Describe properties table
\d+ properties                # Detailed table description
SELECT * FROM properties;     # Query properties
\q                            # Quit
```

#### Using pgAdmin (Web UI)

1. Open http://localhost:5050/
2. Login with:
   - Email: `admin@alvorada.com`
   - Password: `admin123`
3. Add server:
   - Name: `Alvorada DB`
   - Host: `db` (container name)
   - Port: `5432`
   - Username: `alvorada_user`
   - Password: `secure_password_123`

#### Sample SQL Queries

```sql
-- View all properties with coordinates
SELECT 
    id, 
    name, 
    ST_Y(location::geometry) as latitude,
    ST_X(location::geometry) as longitude
FROM properties;

-- Find properties within 10km of a point
SELECT 
    name, 
    address,
    ST_Distance(
        location,
        ST_GeographyFromText('POINT(-73.985428 40.748817)')
    ) / 1000 as distance_km
FROM properties
WHERE ST_DWithin(
    location,
    ST_GeographyFromText('POINT(-73.985428 40.748817)'),
    10000
)
ORDER BY distance_km;

-- Count notes per property
SELECT 
    p.name,
    COUNT(n.id) as note_count
FROM properties p
LEFT JOIN notes n ON p.id = n.property_id
GROUP BY p.id, p.name
ORDER BY note_count DESC;
```

### Viewing Logs

```bash
# View all container logs
docker compose logs

# Follow logs in real-time
docker compose logs -f

# View specific container logs
docker compose logs web
docker compose logs db

# Last 50 lines
docker compose logs --tail=50 web
```

### Restarting Services

```bash
# Restart all containers
docker compose restart

# Restart specific container
docker compose restart web

# Stop all containers
docker compose stop

# Start stopped containers
docker compose start

# Stop and remove containers (data persists in volumes)
docker compose down

# Stop and remove everything including volumes (⚠️ data loss)
docker compose down -v
```

---

## 🐛 Troubleshooting

### Problem: Docker containers won't start

**Solution:**
```bash
# Check if Docker Desktop is running
docker --version

# Check for port conflicts
lsof -i :8080  # Linux/Mac
netstat -ano | findstr :8080  # Windows

# Remove conflicting containers
docker compose down
docker compose up -d --build
```

### Problem: "Connection refused" or "Can't connect to database"

**Solution:**
```bash
# Check container status
docker compose ps

# Check database logs
docker compose logs db

# Verify database is ready
docker exec alvorada_db pg_isready -U alvorada_user

# Restart database container
docker compose restart db
```

### Problem: Map not displaying properly

**Solution:**
1. Hard refresh browser: `Ctrl+Shift+R` (Windows/Linux) or `Cmd+Shift+R` (Mac)
2. Clear browser cache
3. Check browser console for JavaScript errors (F12)
4. Verify API is returning data: `curl http://localhost:8080/api/properties.php`

### Problem: "404 Not Found" errors

**Solution:**
```bash
# Check Apache configuration
docker exec alvorada_web apache2ctl -t

# Verify mod_rewrite is enabled
docker exec alvorada_web apache2ctl -M | grep rewrite

# Check .htaccess file exists
docker exec alvorada_web ls -la /var/www/html/public/.htaccess

# Restart web container
docker compose restart web
```

### Problem: PHP errors or blank pages

**Solution:**
```bash
# Check PHP error logs
docker compose logs web | grep -i error

# Enable error display (development only)
# Already enabled in Dockerfile for dev

# Check PHP version
docker exec alvorada_web php -v

# Verify PHP extensions
docker exec alvorada_web php -m | grep -E 'pdo|pgsql'
```

### Problem: PostGIS functions not working

**Solution:**
```bash
# Verify PostGIS extension is installed
docker exec alvorada_db psql -U alvorada_user -d alvorada_db -c "SELECT PostGIS_version();"

# Reinstall PostGIS extension
docker exec alvorada_db psql -U alvorada_user -d alvorada_db -c "CREATE EXTENSION IF NOT EXISTS postgis;"

# Check spatial_ref_sys table
docker exec alvorada_db psql -U alvorada_user -d alvorada_db -c "SELECT COUNT(*) FROM spatial_ref_sys;"
```

### Problem: Geocoding API not working

**Symptoms:**
- Properties saved without coordinates
- "Failed to geocode address" errors

**Solution:**
1. **Check API availability:**
   ```bash
   curl "https://nominatim.openstreetmap.org/search?format=json&q=New+York"
   ```

2. **Verify User-Agent is set** (required by Nominatim):
   - Check `.env` file has `GEOLOCATION_USER_AGENT`

3. **Check rate limits:**
   - Nominatim: 1 request per second
   - Wait 60 seconds between multiple submissions

4. **Use more specific addresses:**
   - Include city, state, and ZIP code
   - Example: "123 Main St, New York, NY 10001"

---

## 💻 Tech Stack

### Backend
- **PHP 8.2** - Server-side programming
- **PostgreSQL 15** - Relational database
- **PostGIS 3.3** - Spatial database extension
- **Apache 2.4** - Web server
- **PDO** - Database abstraction

### Frontend
- **HTML5 / CSS3** - Modern markup and styling
- **Vanilla JavaScript (ES6+)** - Client-side interactivity
- **Leaflet.js 1.9** - Interactive maps
- **Leaflet.markercluster** - Marker clustering

### DevOps
- **Docker** - Containerization
- **Docker Compose** - Multi-container orchestration

### External APIs
- **OpenStreetMap Nominatim** - Geocoding and reverse geocoding

### Architecture Patterns
- **MVC** - Model-View-Controller
- **Repository Pattern** - Data access abstraction
- **Service Layer** - Business logic separation
- **Dependency Injection** - Loose coupling
- **Front Controller** - Single entry point

---

## 🎯 Key Features Showcase

### 1. Clean Architecture
```
Controllers → Services → Repositories → Database
   (thin)    (business)   (data access)   (storage)
```

### 2. SOLID Principles
- **S** - Single Responsibility (each class has one job)
- **O** - Open/Closed (extensible via interfaces)
- **L** - Liskov Substitution (interfaces properly implemented)
- **I** - Interface Segregation (focused interfaces)
- **D** - Dependency Inversion (depend on abstractions)

### 3. Security
- ✅ Prepared statements (SQL injection prevention)
- ✅ Input validation and sanitization (XSS prevention)
- ✅ Environment variables for secrets
- ✅ CORS headers configured
- ✅ Error messages don't expose internals

### 4. Database Design
- ✅ Normalized schema (3NF)
- ✅ Foreign key constraints
- ✅ Spatial indexes for performance
- ✅ Timestamps for audit trail
- ✅ PostGIS for advanced geospatial queries

---

## 📚 Additional Resources

- **[REQUIREMENTS.md](REQUIREMENTS.md)** - Original project requirements
- **[API_DOCUMENTATION.md](API_DOCUMENTATION.md)** - Complete API reference
- **[AI_PROPOSAL.md](AI_PROPOSAL.md)** - AI/LLM enhancement strategy

### Useful Links
- [PostGIS Documentation](https://postgis.net/documentation/)
- [Leaflet.js Documentation](https://leafletjs.com/)
- [OpenStreetMap Nominatim API](https://nominatim.org/release-docs/latest/api/Search/)
- [Docker Documentation](https://docs.docker.com/)
- [PHP Manual](https://www.php.net/manual/en/)

---

## 📝 License

This project is created as a technical assessment. All rights reserved.

---

## 👤 Author

**Alvorada Technical Assessment**  
Built with ❤️ using vanilla PHP, PostgreSQL, and PostGIS

---

## 🎉 Quick Command Reference

```bash
# Start application
docker compose up -d

# Stop application
docker compose down

# View logs
docker compose logs -f

# Restart application
docker compose restart

# Access database CLI
docker exec -it alvorada_db psql -U alvorada_user -d alvorada_db

# Run SQL file
docker exec -i alvorada_db psql -U alvorada_user -d alvorada_db < sql/schema.sql

# Check container status
docker compose ps

# Rebuild containers
docker compose up -d --build

# View application
open http://localhost:8080  # Mac
start http://localhost:8080  # Windows
xdg-open http://localhost:8080  # Linux
```

---

**Need help?** Check the [Troubleshooting](#troubleshooting) section or review the logs with `docker compose logs -f`.

**Happy property researching!** 🏢🗺️

