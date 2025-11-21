# API Documentation

## Overview
REST API endpoints for property and note management.

## Base URL
```
http://localhost:8080/api
```

## Endpoints

### 1. Get Property by ID
Retrieve property details with associated notes.

**Endpoint:** `GET /api/property.php?id={id}`

**Parameters:**
- `id` (required, integer): Property ID

**Success Response (200):**
```json
{
  "success": true,
  "property": {
    "id": 1,
    "address": "123 Main St",
    "city": "New York",
    "state": "NY",
    "postal_code": "10001",
    "country": "USA",
    "location": {
      "lat": 40.7128,
      "lon": -74.0060
    },
    "extra_field": {"type": "residential"},
    "created_at": "2025-11-21 18:00:00",
    "notes": [
      {
        "id": 1,
        "note": "Test note",
        "created_at": "2025-11-21 18:01:00"
      }
    ]
  }
}
```

**Error Responses:**
- `400 Bad Request`: Invalid or missing id parameter
```json
{
  "success": false,
  "message": "Invalid or missing id parameter"
}
```

- `404 Not Found`: Property not found
```json
{
  "success": false,
  "message": "Property not found"
}
```

- `500 Internal Server Error`: Server error
```json
{
  "success": false,
  "message": "Internal server error"
}
```

**Example:**
```bash
curl -X GET "http://localhost:8080/api/property.php?id=1"
```

---

### 2. Add Note to Property
Add a note to an existing property.

**Endpoint:** `POST /api/add_note.php`

**Content-Type:** `application/json` or `application/x-www-form-urlencoded`

**Request Body:**
```json
{
  "property_id": 1,
  "note": "This is a test note"
}
```

**Success Response (201):**
```json
{
  "success": true,
  "message": "Note added successfully",
  "note": {
    "id": 1,
    "property_id": 1,
    "note": "This is a test note",
    "created_at": "2025-11-21 18:01:00"
  }
}
```

**Error Responses:**
- `404 Not Found`: Property not found
```json
{
  "success": false,
  "message": "Property not found"
}
```

- `422 Unprocessable Entity`: Validation error
```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "property_id": ["Property ID is required"],
    "note": ["Note must be at least 10 characters"]
  }
}
```

- `500 Internal Server Error`: Server error
```json
{
  "success": false,
  "message": "Failed to add note",
  "error": "Error details..."
}
```

**Examples:**

JSON:
```bash
curl -X POST http://localhost:8080/api/add_note.php \
  -H "Content-Type: application/json" \
  -d '{"property_id": 1, "note": "This is a test note"}'
```

Form Data:
```bash
curl -X POST http://localhost:8080/api/add_note.php \
  -d "property_id=1&note=This is a test note"
```

---

### 3. Get Notes by Property
Retrieve all notes for a specific property.

**Endpoint:** `GET /api/notes.php?property_id={id}`

**Parameters:**
- `property_id` (required, integer): Property ID

**Success Response (200):**
```json
{
  "success": true,
  "notes": [
    {
      "id": 1,
      "property_id": 1,
      "note": "First note",
      "created_at": "2025-11-21 18:01:00"
    },
    {
      "id": 2,
      "property_id": 1,
      "note": "Second note",
      "created_at": "2025-11-21 18:02:00"
    }
  ],
  "count": 2
}
```

**Error Responses:**
- `400 Bad Request`: Invalid property_id parameter
```json
{
  "success": false,
  "message": "Invalid property_id parameter"
}
```

- `500 Internal Server Error`: Server error
```json
{
  "success": false,
  "message": "Failed to retrieve notes",
  "error": "Error details..."
}
```

**Example:**
```bash
curl -X GET "http://localhost:8080/api/notes.php?property_id=1"
```

---

## CORS Headers
All API endpoints include CORS headers for cross-origin requests:
- `Access-Control-Allow-Origin: *`
- `Access-Control-Allow-Methods: GET, POST, OPTIONS`
- `Access-Control-Allow-Headers: Content-Type`

---

## Error Handling
All endpoints return JSON responses with consistent structure:
- `success`: boolean indicating success or failure
- `message`: human-readable message
- `data/errors`: additional data or error details

---

## Architecture
- **Controllers**: Thin controllers handle HTTP request/response
- **Services**: Business logic layer (NoteService, PropertyService)
- **Repositories**: Data access layer
- **Validators**: Input validation
- **Dependency Injection**: All dependencies injected via Container

