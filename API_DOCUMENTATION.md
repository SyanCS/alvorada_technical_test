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

### 5. Extract Features (AI)
Extract structured features from property notes using AI.

**Endpoint:** `POST /api/extract_features.php`

**Content-Type:** `application/json`

**Request Body:**
```json
{
  "property_id": 1,
  "force_refresh": false
}
```

**Parameters:**
- `property_id` (required, integer): Property ID
- `force_refresh` (optional, boolean): Force re-extraction even if features exist (default: false)

**Success Response (200):**
```json
{
  "success": true,
  "message": "Features extracted successfully",
  "property_id": 1,
  "features": {
    "near_subway": true,
    "needs_renovation": false,
    "parking_available": true,
    "has_elevator": true,
    "estimated_capacity_people": 25,
    "floor_level": 5,
    "condition_rating": 4,
    "recommended_use": "office",
    "amenities": ["conference room", "kitchen", "gym"],
    "confidence_score": 0.92,
    "source_notes_count": 5,
    "extracted_at": "2026-01-15T10:30:00Z"
  },
  "summary": [
    "Near subway",
    "Has elevator",
    "Capacity: 25 people",
    "Best for: office",
    "Condition: 4/5"
  ]
}
```

**Error Responses:**
- `400 Bad Request`: Invalid property_id
```json
{
  "success": false,
  "error": "validation_error",
  "message": "Invalid property_id"
}
```

- `404 Not Found`: Property not found
```json
{
  "success": false,
  "error": "not_found",
  "message": "Property with ID 1 not found"
}
```

- `500 Internal Server Error`: Extraction failed
```json
{
  "success": false,
  "error": "extraction_failed",
  "message": "No notes found for property. Add some notes before extracting features."
}
```

**Example:**
```bash
curl -X POST http://localhost:8080/api/extract_features.php \
  -H "Content-Type: application/json" \
  -d '{"property_id": 1, "force_refresh": false}'
```

---

### 6. Score Properties (AI)
Score and rank properties based on client requirements.

**Endpoint:** `POST /api/score_properties.php`

**Content-Type:** `application/json`

**Request Body:**
```json
{
  "requirements": "Looking for office space near subway, 20-30 people, parking available",
  "limit": 10
}
```

**Parameters:**
- `requirements` (required, string): Free-text description of client requirements
- `limit` (optional, integer): Maximum number of results to return

**Success Response (200):**
```json
{
  "success": true,
  "message": "Properties scored successfully",
  "scored_properties": [
    {
      "property_id": 5,
      "property_name": "Downtown Office Center",
      "address": "123 Main St, New York, NY",
      "score": 8.7,
      "explanation": "Excellent match for office space. Near subway with good capacity and parking available.",
      "strengths": [
        "Near subway (2 blocks)",
        "Perfect capacity (25 people)",
        "Parking garage available",
        "Good condition (4/5)"
      ],
      "weaknesses": [
        "Needs minor renovation",
        "5th floor (elevator dependent)"
      ],
      "confidence": 0.89,
      "latitude": 40.7128,
      "longitude": -74.0060
    }
  ],
  "total_properties": 10,
  "results_shown": 1,
  "client_requirements": "Looking for office space near subway..."
}
```

**Score Scale:**
- `9.0-10.0`: Excellent match (meets all or nearly all requirements)
- `7.0-8.9`: Good match (strong alignment with minor gaps)
- `5.0-6.9`: Fair match (partial alignment)
- `3.0-4.9`: Poor match (significant gaps)
- `0.0-2.9`: Very poor match (major misalignment)

**Error Responses:**
- `400 Bad Request`: Missing requirements
```json
{
  "success": false,
  "error": "validation_error",
  "message": "Missing or empty required field: requirements"
}
```

- `500 Internal Server Error`: Scoring failed
```json
{
  "success": false,
  "error": "scoring_failed",
  "message": "Failed to score properties"
}
```

**Example:**
```bash
curl -X POST http://localhost:8080/api/score_properties.php \
  -H "Content-Type: application/json" \
  -d '{"requirements": "office near subway, 20-30 people", "limit": 5}'
```

---

### 7. Get Property Features (AI)
Retrieve extracted features for a property.

**Endpoint:** `GET /api/property_features.php?property_id={id}`

**Parameters:**
- `property_id` (required, integer): Property ID

**Success Response (200) - Features Found:**
```json
{
  "success": true,
  "message": "Features retrieved successfully",
  "property_id": 1,
  "features": {
    "near_subway": true,
    "needs_renovation": false,
    "parking_available": true,
    "has_elevator": true,
    "estimated_capacity_people": 25,
    "floor_level": 5,
    "condition_rating": 4,
    "recommended_use": "office",
    "amenities": ["conference room", "kitchen"],
    "confidence_score": 0.92,
    "source_notes_count": 5,
    "extracted_at": "2026-01-15T10:30:00Z"
  },
  "summary": ["Near subway", "Has elevator", "Capacity: 25 people"],
  "has_features": true
}
```

**Success Response (200) - No Features:**
```json
{
  "success": true,
  "message": "No features found for this property",
  "property_id": 1,
  "features": null,
  "has_features": false
}
```

**Error Responses:**
- `400 Bad Request`: Invalid property_id
```json
{
  "success": false,
  "error": "validation_error",
  "message": "Invalid property_id"
}
```

**Example:**
```bash
curl -X GET "http://localhost:8080/api/property_features.php?property_id=1"
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
- **Services**: Business logic layer (PropertyService, NoteService, FeatureExtractionService, PropertyScoringService)
- **Repositories**: Data access layer (PropertyRepository, NoteRepository, PropertyFeatureRepository)
- **Validators**: Input validation
- **AI Integration**: OpenAIService for GPT-powered features
- **Dependency Injection**: All dependencies injected via Container

---

## AI Features Configuration

To use AI-powered endpoints, configure OpenAI API key:

```bash
# In .env file
OPENAI_API_KEY=sk-your-api-key-here
OPENAI_MODEL=gpt-3.5-turbo
OPENAI_TEMPERATURE=0.3
OPENAI_MAX_TOKENS=1000
```

**Note:** Without API key configured, the system will use fallback keyword-based extraction with lower accuracy.

For detailed AI features documentation, see [docs/AI_USAGE.md](docs/AI_USAGE.md).

