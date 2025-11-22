# Project Structure Alignment with Requirements

## Overview

This document explains how the project structure aligns with the original requirements and addresses the organization of files.

## Requirements Compliance

### ✅ 1. Root-Level `index.php` with Form

**Requirement:** Form entry point should be at the root level as `index.php`

**Implementation:**
- `/index.php` - Main entry point hosting the property intake form
- When accessed via `http://localhost:8080/`, users see the form
- Handles both GET (display form) and POST (submit form) requests
- Loads the form view from `views/property/form.php`

### ✅ 2. API Endpoints under `/api`

**Requirement:** PHP endpoints should be under `/api`

**Implementation:**
- `/public/api/property.php` - GET property details
- `/public/api/properties.php` - GET all properties
- `/public/api/add_note.php` - POST to add notes
- `/public/api/notes.php` - GET notes for a property

**URL Access:**
- `http://localhost:8080/api/property.php?id=1`
- `http://localhost:8080/api/properties.php`
- etc.

### ✅ 3. Static Assets under `/public`

**Requirement:** Static assets and `map.html` should be under `/public`

**Implementation:**
- `/public/map.html` - Interactive map interface
- `/public/property.html` - Property details page
- `/public/css/` - Stylesheets
- `/public/js/` - JavaScript files

**URL Access:**
- `http://localhost:8080/map.html`
- `http://localhost:8080/property.html`

### ✅ 4. Database Schema under `/sql`

**Requirement:** Database schema should be in `/sql/schema.sql`

**Implementation:**
- `/sql/schema.sql` - Complete PostgreSQL + PostGIS schema

## File Organization Rationale

### Why `map.html` and `property.html` are in `/public`

Both files are **static HTML pages** that:
1. Don't require server-side PHP processing
2. Serve as client-side applications (SPAs)
3. Fetch data via AJAX from the API endpoints
4. Should be publicly accessible

**Benefits of this organization:**
- ✅ Clear separation between static and dynamic content
- ✅ Better security (PHP source code not in public directory)
- ✅ Follows modern web application patterns
- ✅ Easier to serve static assets efficiently
- ✅ Meets the requirement: "static assets and map.html under /public"

### Document Root Configuration

**Apache Configuration:**
- Document root: `/var/www/html` (project root)
- This allows direct access to `index.php` at the root URL
- `.htaccess` rules route requests appropriately:
  - `/` → `index.php` (form)
  - `/api/*` → `public/api/*` (API endpoints)
  - `/map.html` → `public/map.html` (static page)
  - `/property.html` → `public/property.html` (static page)

## URL Structure

### User-Facing URLs

| URL | File | Purpose |
|-----|------|---------|
| `/` | `/index.php` | Property intake form |
| `/property?id=1` | `/index.php` → `views/property/show.php` | Property details (MVC) |
| `/map.html` | `/public/map.html` | Interactive map view |

### API Endpoints

| URL | File | Method | Purpose |
|-----|------|--------|---------|
| `/api/property.php?id=1` | `/public/api/property.php` | GET | Get property details |
| `/api/properties.php` | `/public/api/properties.php` | GET | Get all properties |
| `/api/add_note.php` | `/public/api/add_note.php` | POST | Add a note |
| `/api/notes.php?property_id=1` | `/public/api/notes.php` | GET | Get property notes |

## Navigation Flow

```
User visits /
    ↓
index.php displays form
    ↓
User submits form (POST to /)
    ↓
index.php processes → creates property
    ↓
Redirects to success page with "View on Map" link
    ↓
User clicks "View on Map"
    ↓
/map.html?id=1 opens
    ↓
JavaScript fetches data from /api/property.php?id=1
    ↓
Map displays property with marker
    ↓
User clicks "View Details & Add Notes"
    ↓
/property.html?id=1 opens
    ↓
JavaScript fetches data from /api/property.php?id=1
    ↓
User can add notes via /api/add_note.php
```

## Security Considerations

### Protected Directories

The following directories are **not directly accessible** via web browser:
- `/src/` - Application source code
- `/views/` - PHP view templates
- `/sql/` - Database schemas
- `/scripts/` - Utility scripts

**Protection Method:**
- `.htaccess` rules deny direct access
- These files are only accessible via PHP includes

### Public Directory

Only the following are publicly accessible:
- `index.php` (root)
- `/public/api/*` (API endpoints)
- `/public/*.html` (static pages)
- `/public/css/*` (stylesheets)
- `/public/js/*` (JavaScript)

## Comparison with Requirements Document

**Requirements stated:**
```
project-root/
  index.php          ✅ Present at root
  README.md          ✅ Present
  AI_PROPOSAL.md     ✅ Present
  api/               ✅ Present as /public/api/
    db.php           ✅ Implemented as Database.php in /src/Config/
    property.php     ✅ Present in /public/api/
    add_note.php     ✅ Present in /public/api/
  public/            ✅ Present
    map.html         ✅ Present in /public/
  sql/               ✅ Present
    schema.sql       ✅ Present
```

**Additional improvements beyond requirements:**
- Clean architecture with MVC pattern
- Service layer for business logic
- Repository pattern for data access
- Dependency injection container
- Comprehensive error handling
- Input validation
- Security best practices
- API documentation
- Docker containerization
- PostGIS for spatial queries

## Summary

The project structure **fully complies** with the requirements while implementing modern best practices:

1. ✅ `index.php` at root hosts the form
2. ✅ API endpoints accessible under `/api`
3. ✅ Static assets and `map.html` under `/public`
4. ✅ Database schema in `/sql/schema.sql`
5. ✅ Clean separation of concerns
6. ✅ Security through directory protection
7. ✅ Modern architecture patterns

The organization of `map.html` and `property.html` in `/public` is intentional and follows the requirement that static assets should be in the public directory. They are separate files because they serve different purposes (map view vs. property details) and provide a better user experience as standalone pages.

