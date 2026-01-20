# 🏢 Alvorada Property Research System

A full-stack property research and management platform built with vanilla PHP, PostgreSQL + PostGIS, and Leaflet.js. This system enables users to add properties, automatically enrich them with geolocation data, manage research notes, and visualize properties on an interactive map.

[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=flat&logo=php)](https://www.php.net/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15-336791?style=flat&logo=postgresql)](https://www.postgresql.org/)
[![PostGIS](https://img.shields.io/badge/PostGIS-3.3-4169E1?style=flat)](https://postgis.net/)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=flat&logo=docker)](https://www.docker.com/)

---

## 📋 Table of Contents

- [Features](#features)
- [Prerequisites](#prerequisites)
- [Quick Start](#quick-start)
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

### 🤖 AI-Powered Features (ENHANCED!)
- **🧠 Feature Extraction** - Automatically extract structured data from unstructured property notes
- **📊 Enhanced Property Scoring** - Score and rank properties (0-10) using weighted, structured features
- **🎯 Smart Matching** - AI-powered property-to-requirement matching with feature importance tiers
- **💡 Intelligent Insights** - Extract amenities, capacity, condition ratings, and more from text
- **⚖️ Weighted Analysis** - High/Medium/Lower importance feature categorization
- **📈 Feature Completeness** - Track data quality with completeness scores (0.0-1.0)
- **✓ Transparent Scoring** - Detailed explanations with specific feature references and checkmarks

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
git clone
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

### Seed Sample Data (Optional)

Populate the database with 36 sample US properties:

```bash
# Seed all properties (takes ~1 minute due to rate limiting)
php scripts/seed_properties.php

# Seed only 10 properties
php scripts/seed_properties.php --count=10

# Seed 5 properties with sample notes
php scripts/seed_properties.php --count=5 --with-notes

# View help
php scripts/seed_properties.php --help
```

**Sample Properties Include:**
- Famous landmarks (Empire State Building, Statue of Liberty, Golden Gate Bridge)
- Tech company HQs (Apple, Google, Microsoft, Meta, Amazon, Tesla)
- Government buildings (White House, US Capitol, Pentagon)
- Universities (Harvard, MIT, Stanford, Yale)
- Sports venues (Yankee Stadium, Wrigley Field, Fenway Park)
- Entertainment (Disneyland, Universal Studios, Las Vegas casinos)
- Airports (LAX, JFK, O'Hare)
- Historic sites (Alamo, Mount Rushmore, Independence Hall)

### Access the Application

Open your web browser and navigate to:

- **Main Application:** http://localhost:8080/
- **Property Scoring:** http://localhost:8080/score.html
- **Interactive Map:** http://localhost:8080/map.html

---

### 🤖 Setup AI Features (Optional)

To enable AI-powered feature extraction and property scoring:

```bash
# 1. Run the database migration
docker exec -i alvorada_db psql -U alvorada_user -d alvorada_db < sql/migrations/001_add_property_features.sql

# 2. Get Gemini API key from https://aistudio.google.com/app/apikey

# 3. Add to .env file
echo "GEMINI_API_KEY=your-key-here" >> .env

# 4. Test AI features
php scripts/test_ai_features.php
```

**AI Features Include:**
- 🧠 Feature extraction from notes
- 📊 Property scoring and ranking (0-10 scale)
- ⚖️ Weighted feature analysis (High/Medium/Lower importance)
- 📈 Feature completeness tracking
- 💡 Intelligent insights from unstructured text

**See [docs/AI_USAGE.md](docs/AI_USAGE.md) for complete AI features documentation.**

---


## 📖 Usage Guide

### 🎥 Video Demo

Watch a complete walkthrough of the system in action:

**[📹 System Usage Demo Video](https://drive.google.com/file/d/1_lMHlL4UP-EfphI6N-dRDQFrkAxP8Chl/view?usp=sharing)**

**[📹 Extract Notes Feature](https://drive.google.com/file/d/1EPWpQwuq7YUJemUB8aCndundTVd4Qhf6/view)**

**[📹 Rank Properties Feature](https://drive.google.com/file/d/1gI6iKLXDDu6-LMCtrOY3ZLlhJ2EK75CY/view)**

---

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

### 🎯 Scoring Properties Against Client Requirements (Enhanced)

1. **Navigate to:** http://localhost:8080/score.html

2. **Enter client requirements:**
   - Type free-text description of what your client is looking for
   - Example: "Office space for 20-30 people near subway, parking needed, modern condition, $40-50k/month budget"
   - Or click one of the quick example chips to auto-fill

3. **Click "🎯 Score Properties"**

4. **View ranked results with enhanced features:**
   - Properties scored from 0 to 10 using **weighted feature analysis**
   - Color-coded cards (green = excellent match, red = poor match)
   - Detailed explanations with **specific feature references** (✓/✗ indicators)
   - Strengths cite **importance levels** (HIGH/MEDIUM/LOWER)
   - Weaknesses show specific feature gaps
   - **Feature completeness score** (0.0-1.0) indicates data quality
   - AI confidence indicators adjusted by feature availability
   - Key features summary in API responses

5. **Enhanced Scoring Benefits:**
   - **Weighted Analysis:** High-importance features (location, type, capacity) weighted 30-40%
   - **Specific Matching:** Direct feature-to-requirement matching with point values
   - **Adaptive Confidence:** Lower confidence when feature data is incomplete
   - **Transparent Results:** See exactly which features match or miss requirements

6. **Filter results:**
   - Use the "Limit Results" field to show only top N matches
   - Default shows top 10 properties

**Example Requirements:**
- "Client is looking for an office near the subway, budget up to $50k/month, for 15–20 people, preferably in a central area"
- "Retail storefront, ground floor, high foot traffic, 1500-2000 sqft, large display windows"
- "Warehouse with loading dock, 10,000+ sqft, 20ft ceiling, near highway access"

**Best Practice Workflow:**
1. Add properties to system
2. Add detailed notes about each property
3. Run feature extraction: `POST /api/extract_features.php`
4. Run property scoring: `POST /api/score_properties.php`
5. Review results with high confidence and feature completeness

**Note:** Properties with extracted features receive more accurate scores (confidence ~0.8-0.95) compared to those without features (confidence capped at ~0.6). See [PROPERTY_SCORING_FLOW.md](PROPERTY_SCORING_FLOW.md) for complete details.

---

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
- **[API_DOCUMENTATION.md](API_DOCUMENTATION.md)** - Complete API reference including AI endpoints
- **[PROPERTY_SCORING_FLOW.md](PROPERTY_SCORING_FLOW.md)** - 🚀 **Enhanced property scoring guide** with weighted features
- **[docs/AI_USAGE.md](docs/AI_USAGE.md)** - AI features usage guide and examples
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

**Syan Cordeiro de Souza**  
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

# Seed sample data
php scripts/seed_properties.php --count=10

# Test AI features
php scripts/test_ai_features.php

# Access database CLI
docker exec -it alvorada_db psql -U alvorada_user -d alvorada_db

# Run SQL file
docker exec -i alvorada_db psql -U alvorada_user -d alvorada_db < sql/schema.sql

# Check container status
docker compose ps

# Rebuild containers
docker compose up -d --build

# Clear all data (⚠️ destructive)
docker exec alvorada_db psql -U alvorada_user -d alvorada_db -c "TRUNCATE properties, notes CASCADE;"

# View application
open http://localhost:8080  # Mac
start http://localhost:8080  # Windows
xdg-open http://localhost:8080  # Linux
```

---

**Need help?** Check the [Troubleshooting](#troubleshooting) section or review the logs with `docker compose logs -f`.

**Happy property researching!** 🏢🗺️

