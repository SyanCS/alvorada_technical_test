# 🧠 Feature Extraction AI Flow

## Overview

This document provides a comprehensive guide to the AI-powered feature extraction system that transforms unstructured property notes into structured, queryable data using Google Gemini AI.

---

## Table of Contents

- [Architecture Overview](#architecture-overview)
- [Complete Flow Diagram](#complete-flow-diagram)
- [Components](#components)
- [Step-by-Step Process](#step-by-step-process)
- [Data Structures](#data-structures)
- [AI Prompt Engineering](#ai-prompt-engineering)
- [API Endpoints](#api-endpoints)
- [CLI Script Usage](#cli-script-usage)
- [Error Handling](#error-handling)
- [Performance Considerations](#performance-considerations)

---

## Architecture Overview

The feature extraction system follows a clean, layered architecture:

```
┌─────────────────────────────────────────────────────────────┐
│                     Entry Points                             │
│  • HTTP API (POST /api/extract_features.php)                │
│  • CLI Script (scripts/extract_features.php)                │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│                   AIController                               │
│  • Validates input (property_id, force_refresh)             │
│  • Handles HTTP request/response                            │
│  • Error handling & JSON formatting                         │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│             FeatureExtractionService                         │
│  • Orchestrates extraction process                          │
│  • Builds AI prompts (system + user)                        │
│  • Parses AI responses                                      │
│  • Manages feature caching                                  │
└──────────────────────┬──────────────────────────────────────┘
                       │
          ┌────────────┴────────────┐
          ▼                         ▼
┌──────────────────┐      ┌──────────────────┐
│  GeminiService   │      │  Repositories    │
│  • API calls     │      │  • Property      │
│  • JSON mode     │      │  • Note          │
│  • Retries       │      │  • Feature       │
└──────────────────┘      └──────────────────┘
          │                         │
          ▼                         ▼
┌──────────────────┐      ┌──────────────────┐
│  Gemini API      │      │  PostgreSQL DB   │
│  (gemini-2.0)    │      │  + property_     │
│                  │      │    features      │
└──────────────────┘      └──────────────────┘
```

---

## Complete Flow Diagram

### Full Extraction Process

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. REQUEST INITIATION                                           │
│    User/Script → POST /api/extract_features.php                 │
│    Body: { "property_id": 5, "force_refresh": false }          │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│ 2. INPUT VALIDATION (AIController)                              │
│    ✓ property_id exists and is valid integer                   │
│    ✓ property_id > 0                                            │
│    ✓ force_refresh is boolean (optional)                       │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│ 3. PROPERTY LOOKUP (FeatureExtractionService)                   │
│    → PropertyRepository::findById(property_id)                  │
│    ✓ Property exists? → Continue                                │
│    ✗ Not found? → Throw NotFoundException                       │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│ 4. CHECK EXISTING FEATURES                                      │
│    IF force_refresh = false:                                    │
│      → PropertyFeatureRepository::exists(property_id)           │
│      IF features exist:                                         │
│        → Return cached features (skip AI call)                  │
│      ELSE:                                                      │
│        → Continue to extraction                                 │
│    IF force_refresh = true:                                     │
│      → Skip cache, proceed to extraction                        │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│ 5. FETCH PROPERTY NOTES                                         │
│    → NoteRepository::findByPropertyId(property_id)              │
│    ✓ Notes found? → Continue                                    │
│    ✗ No notes? → Throw Exception("No notes found")             │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│ 6. VERIFY GEMINI CONFIGURATION                                  │
│    → GeminiService::isConfigured()                              │
│    ✓ API key set? → Continue                                    │
│    ✗ Not configured? → Throw Exception                          │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│ 7. BUILD AI PROMPTS                                             │
│    A. System Prompt (buildSystemPrompt):                        │
│       • Role definition (real estate analyst)                   │
│       • Task description                                        │
│       • Output format (JSON schema)                             │
│       • Field definitions (11 fields)                           │
│       • Extraction guidelines                                   │
│       • Examples (3 scenarios)                                  │
│                                                                 │
│    B. User Prompt (buildUserPrompt):                            │
│       • Property name                                           │
│       • Property address                                        │
│       • Total notes count                                       │
│       • All note contents (numbered)                            │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│ 8. CALL GEMINI API (GeminiService)                              │
│    → extractStructuredData(systemPrompt, userPrompt, options)  │
│                                                                 │
│    Options:                                                     │
│      • temperature: 0.3 (deterministic)                         │
│      • max_tokens: 800                                          │
│      • response_format: 'json' (JSON mode)                      │
│                                                                 │
│    Retry Logic:                                                 │
│      • Max retries: 3                                           │
│      • Exponential backoff: 1s, 2s, 4s                          │
│      • No retry on auth errors                                  │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│ 9. PARSE AI RESPONSE (FeatureExtractionService)                 │
│    → parseAIResponse(data, property, notes)                     │
│                                                                 │
│    Extract & Validate:                                          │
│      • near_subway (boolean or null)                            │
│      • needs_renovation (boolean or null)                       │
│      • parking_available (boolean or null)                      │
│      • has_elevator (boolean or null)                           │
│      • estimated_capacity_people (int or null)                  │
│      • floor_level (int or null)                                │
│      • condition_rating (1-5 or null)                           │
│      • recommended_use (string or null)                         │
│      • amenities (array or null)                                │
│      • confidence_score (0.0-1.0)                               │
│                                                                 │
│    Create PropertyFeature model with extracted data             │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│ 10. SAVE TO DATABASE                                            │
│     IF features exist for property:                             │
│       → PropertyFeatureRepository::update(feature)              │
│     ELSE:                                                       │
│       → PropertyFeatureRepository::create(feature)              │
│                                                                 │
│     Stored data:                                                │
│       • All extracted features                                  │
│       • source_notes_count                                      │
│       • raw_ai_response (JSONB)                                 │
│       • confidence_score                                        │
│       • extracted_at (timestamp)                                │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│ 11. RETURN RESPONSE                                             │
│     HTTP 200 OK:                                                │
│     {                                                           │
│       "success": true,                                          │
│       "message": "Features extracted successfully",             │
│       "property_id": 5,                                         │
│       "features": { ... },                                      │
│       "summary": ["Near subway", "Capacity: 25 people", ...]   │
│     }                                                           │
└─────────────────────────────────────────────────────────────────┘
```

---

## Components

### 1. AIController

**Location:** `src/Controllers/AIController.php`

**Responsibilities:**
- HTTP request handling
- Input validation (property_id, force_refresh)
- JSON request/response formatting
- Error handling with appropriate HTTP status codes

**Key Methods:**
- `extractFeatures()` - Main endpoint handler
- `getJsonInput()` - Parse and validate JSON body

**Error Responses:**
- `400 Bad Request` - Invalid/missing property_id
- `404 Not Found` - Property doesn't exist
- `500 Internal Server Error` - Extraction failed

---

### 2. FeatureExtractionService

**Location:** `src/Services/FeatureExtractionService.php`

**Responsibilities:**
- Orchestrate the entire extraction process
- Build AI prompts (system and user)
- Parse AI responses into structured models
- Manage feature caching logic
- Coordinate with repositories

**Key Methods:**

#### `extractFeaturesFromProperty(int $propertyId, bool $forceRefresh = false): PropertyFeature`

Main extraction method. Returns cached features if available (unless force_refresh=true).

**Process:**
1. Fetch property from database
2. Check for existing features (unless force_refresh)
3. Fetch all notes for property
4. Verify Gemini API is configured
5. Build prompts
6. Call Gemini API
7. Parse response
8. Save/update in database
9. Return PropertyFeature model

#### `buildSystemPrompt(): string`

Creates comprehensive system prompt with:
- Role definition (commercial real estate analyst)
- Task description (extract structured data)
- JSON output format specification
- Field definitions (11 fields with descriptions)
- Extraction constraints (evidence-based, conservative)
- Extraction guidelines (keywords to look for)
- 3 detailed examples (clear, vague, mixed notes)

#### `buildUserPrompt(Property $property, array $notes): string`

Creates user prompt with:
- Property name and address
- Total notes count
- All note contents (numbered sequentially)

#### `parseAIResponse(array $data, Property $property, array $notes): PropertyFeature`

Parses and validates AI JSON response:
- Type checking (bool, int, string, array)
- Range validation (condition_rating: 1-5, confidence: 0-1)
- Null handling for missing/uncertain data
- Creates PropertyFeature model

#### `getFeatures(int $propertyId): ?PropertyFeature`

Retrieves cached features without extraction.

#### `hasFeatures(int $propertyId): bool`

Checks if features exist for a property.

---

### 3. GeminiService

**Location:** `src/Services/GeminiService.php`

**Responsibilities:**
- Direct communication with Google Gemini API
- Request formatting (OpenAI-style → Gemini format)
- JSON mode enforcement
- Retry logic with exponential backoff
- Response parsing

**Key Methods:**

#### `extractStructuredData(string $systemPrompt, string $userPrompt, array $options = []): array`

Main method for structured extraction:
- Combines system + user prompts
- Forces JSON response format
- Returns parsed JSON data

**Options:**
- `temperature` (0.0-1.0, default: 0.3)
- `max_tokens` (default: 2048)
- `model` (default: gemini-2.0-flash)

**Returns:**
```php
[
    'success' => true,
    'data' => [...],           // Parsed JSON
    'raw_response' => '...',   // Raw text
    'usage' => [...]           // Token usage metadata
]
```

#### `chat(array $messages, array $options = []): array`

Lower-level chat completion method.

#### `makeRequestWithRetry(string $model, array $payload): array`

HTTP request with retry logic:
- Max 3 attempts
- Exponential backoff (1s, 2s, 4s)
- No retry on authentication errors
- Logs all attempts

#### `isConfigured(): bool`

Checks if GEMINI_API_KEY is set.

---

### 4. PropertyFeatureRepository

**Location:** `src/Repositories/PropertyFeatureRepository.php`

**Responsibilities:**
- CRUD operations for property_features table
- Database queries and data mapping

**Key Methods:**
- `findByPropertyId(int $propertyId): ?PropertyFeature`
- `create(PropertyFeature $feature): PropertyFeature`
- `update(PropertyFeature $feature): bool`
- `exists(int $propertyId): bool`
- `delete(int $id): bool`

---

### 5. PropertyFeature Model

**Location:** `src/Models/PropertyFeature.php`

**Properties:**
- `id` (int) - Primary key
- `property_id` (int) - Foreign key to properties
- `near_subway` (bool|null) - Within 5-10 min walk to transit
- `needs_renovation` (bool|null) - Requires significant repairs
- `parking_available` (bool|null) - Has parking spaces
- `has_elevator` (bool|null) - Building has elevator
- `estimated_capacity_people` (int|null) - Max occupancy
- `floor_level` (int|null) - Floor number (0=ground)
- `condition_rating` (int|null) - 1-5 scale (1=poor, 5=excellent)
- `recommended_use` (string|null) - office, retail, warehouse, etc.
- `amenities` (array|null) - List of features
- `confidence_score` (float|null) - AI confidence (0.0-1.0)
- `source_notes_count` (int) - Number of notes analyzed
- `raw_ai_response` (array|null) - Full AI response
- `extracted_at` (timestamp) - Extraction time
- `updated_at` (timestamp) - Last update time

**Methods:**
- `toArray()` - JSON serialization
- `getSummary()` - Human-readable feature list

---

## Step-by-Step Process

### Step 1: Request Initiation

**HTTP API:**
```bash
curl -X POST http://localhost:8080/api/extract_features.php \
  -H "Content-Type: application/json" \
  -d '{
    "property_id": 5,
    "force_refresh": false
  }'
```

**CLI Script:**
```bash
# Extract all properties
php scripts/extract_features.php

# Extract specific property
php scripts/extract_features.php --property-id=5

# Force re-extraction
php scripts/extract_features.php --force-refresh

# Limit to 10 properties
php scripts/extract_features.php --limit=10
```

---

### Step 2: Validation

**AIController validates:**
- `property_id` is present
- `property_id` is a positive integer
- `force_refresh` is boolean (if provided)

**Errors thrown:**
- `ValidationException` - Invalid input
- Returns HTTP 400

---

### Step 3: Property Lookup

**FeatureExtractionService:**
```php
$property = $this->propertyRepository->findById($propertyId);
if (!$property) {
    throw new NotFoundException("Property with ID {$propertyId} not found");
}
```

**Errors:**
- `NotFoundException` - Property doesn't exist
- Returns HTTP 404

---

### Step 4: Cache Check

**Logic:**
```php
if (!$forceRefresh && $this->featureRepository->exists($propertyId)) {
    $existing = $this->featureRepository->findByPropertyId($propertyId);
    if ($existing) {
        return $existing; // Return cached features
    }
}
```

**Benefits:**
- Saves API calls and costs
- Faster response times
- Can be bypassed with `force_refresh: true`

---

### Step 5: Fetch Notes

**FeatureExtractionService:**
```php
$notes = $this->noteRepository->findByPropertyId($propertyId);

if (empty($notes)) {
    throw new Exception("No notes found for property. Add some notes before extracting features.");
}
```

**Requirements:**
- At least 1 note must exist
- More notes = better extraction quality

---

### Step 6: Verify API Configuration

**Check:**
```php
if (!$this->geminiService->isConfigured()) {
    throw new Exception("Gemini API key is not configured. Please add GEMINI_API_KEY to your .env file.");
}
```

**Configuration:**
```bash
# .env file
GEMINI_API_KEY=your-api-key-here
GEMINI_MODEL=gemini-2.0-flash
GEMINI_TEMPERATURE=0.3
GEMINI_MAX_TOKENS=2048
```

---

### Step 7: Build Prompts

#### System Prompt Structure

```
## ROLE
You are an expert commercial real estate analyst...

## TASK
Analyze the provided property research notes and extract structured information...

## OUTPUT FORMAT
{
  "near_subway": boolean or null,
  "needs_renovation": boolean or null,
  ...
}

## FIELD DEFINITIONS
- near_subway: Property is within 5-10 minutes walking distance to subway...
- needs_renovation: Property requires significant repairs...
...

## CONSTRAINTS
1. Evidence-based extraction: Only set a field if there is clear evidence
2. Use null for missing data
3. Conservative boolean logic
4. No assumptions
5. JSON only
6. Reasonable ranges

## EXTRACTION GUIDELINES
- near_subway: Look for phrases like "close to metro", "2 blocks from subway"
- needs_renovation: Keywords like "needs repair", "outdated"
...

## EXAMPLES
[3 detailed examples with input/output]
```

#### User Prompt Structure

```
Property Information:
- Name: Downtown Office Center
- Address: 123 Main St, New York, NY
- Total Notes: 3

Research Notes:
Note 1: Property is well located, close to subway entrance
Note 2: Needs minor renovation - painting and fixtures
Note 3: Good for office with up to 25 people, has elevator
```

---

### Step 8: Call Gemini API

**Request:**
```php
$result = $this->geminiService->extractStructuredData(
    $systemPrompt,
    $userPrompt,
    [
        'temperature' => 0.3,    // Deterministic
        'max_tokens' => 800,     // Enough for detailed response
        'response_format' => 'json'  // Force JSON mode
    ]
);
```

**Gemini API Endpoint:**
```
POST https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=API_KEY
```

**Retry Logic:**
- Attempt 1: Immediate
- Attempt 2: Wait 1 second
- Attempt 3: Wait 2 seconds
- Attempt 4: Wait 4 seconds (if max_retries=4)

**No retry on:**
- Invalid API key
- Authentication errors
- API key errors

---

### Step 9: Parse Response

**AI Response Example:**
```json
{
  "near_subway": true,
  "needs_renovation": true,
  "parking_available": false,
  "has_elevator": true,
  "estimated_capacity_people": 25,
  "floor_level": null,
  "condition_rating": 3,
  "recommended_use": "office",
  "amenities": ["elevator", "natural light"],
  "confidence_score": 0.85,
  "summary": "Well-located office space near subway with elevator access. Requires minor cosmetic updates. Suitable for 25 employees."
}
```

**Parsing Logic:**
```php
// Boolean fields
if (isset($data['near_subway']) && is_bool($data['near_subway'])) {
    $feature->setNearSubway($data['near_subway']);
}

// Numeric fields with validation
if (isset($data['condition_rating']) && is_numeric($data['condition_rating'])) {
    $rating = (int) $data['condition_rating'];
    if ($rating >= 1 && $rating <= 5) {
        $feature->setConditionRating($rating);
    }
}

// Confidence score clamping
if (isset($data['confidence_score']) && is_numeric($data['confidence_score'])) {
    $confidence = (float) $data['confidence_score'];
    $feature->setConfidenceScore(min(1.0, max(0.0, $confidence)));
}
```

**Validation:**
- Type checking (bool, int, string, array)
- Range validation (condition: 1-5, confidence: 0-1)
- Null handling for missing data
- No exceptions on invalid fields (graceful degradation)

---

### Step 10: Save to Database

**SQL Schema:**
```sql
CREATE TABLE property_features (
    id SERIAL PRIMARY KEY,
    property_id INTEGER UNIQUE NOT NULL REFERENCES properties(id),
    near_subway BOOLEAN,
    needs_renovation BOOLEAN,
    parking_available BOOLEAN,
    has_elevator BOOLEAN,
    estimated_capacity_people INTEGER,
    floor_level INTEGER,
    condition_rating INTEGER CHECK (condition_rating >= 1 AND condition_rating <= 5),
    recommended_use VARCHAR(100),
    amenities JSONB,
    confidence_score DECIMAL(3,2) CHECK (confidence_score >= 0 AND confidence_score <= 1),
    source_notes_count INTEGER NOT NULL,
    raw_ai_response JSONB,
    extracted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Save Logic:**
```php
if ($this->featureRepository->exists($propertyId)) {
    $this->featureRepository->update($feature);
} else {
    $feature = $this->featureRepository->create($feature);
}
```

**Indexes:**
- Primary key on `id`
- Unique constraint on `property_id`
- B-tree indexes on boolean fields
- GIN indexes on JSONB fields (amenities, raw_ai_response)

---

### Step 11: Return Response

**Success Response (HTTP 200):**
```json
{
  "success": true,
  "message": "Features extracted successfully",
  "property_id": 5,
  "features": {
    "id": 12,
    "property_id": 5,
    "near_subway": true,
    "needs_renovation": true,
    "parking_available": false,
    "has_elevator": true,
    "estimated_capacity_people": 25,
    "floor_level": null,
    "condition_rating": 3,
    "recommended_use": "office",
    "amenities": ["elevator", "natural light"],
    "confidence_score": 0.85,
    "source_notes_count": 3,
    "extracted_at": "2026-01-19T10:30:00Z",
    "updated_at": "2026-01-19T10:30:00Z"
  },
  "summary": [
    "Near subway",
    "Needs renovation",
    "No parking",
    "Has elevator",
    "Capacity: 25 people",
    "Best for: office",
    "Condition: 3/5"
  ]
}
```

---

## Data Structures

### PropertyFeature Model

```php
class PropertyFeature
{
    private ?int $id = null;
    private int $propertyId;
    private ?bool $nearSubway = null;
    private ?bool $needsRenovation = null;
    private ?bool $parkingAvailable = null;
    private ?bool $hasElevator = null;
    private ?int $estimatedCapacityPeople = null;
    private ?int $floorLevel = null;
    private ?int $conditionRating = null;
    private ?string $recommendedUse = null;
    private ?array $amenities = null;
    private ?float $confidenceScore = null;
    private int $sourceNotesCount = 0;
    private ?array $rawAiResponse = null;
    private ?string $extractedAt = null;
    private ?string $updatedAt = null;
}
```

### Field Descriptions

| Field | Type | Description | Example |
|-------|------|-------------|---------|
| `near_subway` | bool\|null | Within 5-10 min walk to transit | true |
| `needs_renovation` | bool\|null | Requires significant repairs | false |
| `parking_available` | bool\|null | Has parking spaces | true |
| `has_elevator` | bool\|null | Building has elevator | true |
| `estimated_capacity_people` | int\|null | Max comfortable occupancy | 25 |
| `floor_level` | int\|null | Floor number (0=ground) | 3 |
| `condition_rating` | int\|null | 1=poor, 5=excellent | 4 |
| `recommended_use` | string\|null | office, retail, warehouse, etc. | "office" |
| `amenities` | array\|null | List of features | ["kitchen", "gym"] |
| `confidence_score` | float\|null | AI confidence (0.0-1.0) | 0.85 |
| `source_notes_count` | int | Number of notes analyzed | 3 |
| `raw_ai_response` | array\|null | Full AI response | {...} |

---

## AI Prompt Engineering

### System Prompt Design Principles

1. **Clear Role Definition**
   - Establishes expertise (commercial real estate analyst)
   - Sets expectations for output quality

2. **Explicit Output Format**
   - JSON schema with exact field names
   - Type specifications (boolean, integer, string, array, float)
   - Null handling instructions

3. **Field Definitions**
   - Detailed description for each field
   - Clear criteria (e.g., "within 5-10 minutes walking distance")
   - Value ranges (condition: 1-5, confidence: 0-1)

4. **Extraction Constraints**
   - Evidence-based only (no assumptions)
   - Conservative approach (use null when uncertain)
   - Reasonable value ranges

5. **Extraction Guidelines**
   - Keywords to look for per field
   - Phrases that indicate features
   - How to assess confidence

6. **Examples**
   - 3 scenarios: clear, vague, mixed
   - Shows expected behavior
   - Demonstrates null usage

### User Prompt Design

**Simple and structured:**
- Property metadata (name, address)
- Notes count
- All notes numbered sequentially
- Clear instruction at the end

**Benefits:**
- Easy for AI to parse
- Consistent format
- All context provided upfront

---

## API Endpoints

### POST /api/extract_features.php

Extract features from property notes.

**Request:**
```json
{
  "property_id": 5,
  "force_refresh": false
}
```

**Parameters:**
- `property_id` (required, integer) - Property ID
- `force_refresh` (optional, boolean) - Re-extract even if cached (default: false)

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Features extracted successfully",
  "property_id": 5,
  "features": { ... },
  "summary": [ ... ]
}
```

**Error Responses:**

**400 Bad Request:**
```json
{
  "success": false,
  "error": "validation_error",
  "message": "Invalid property_id"
}
```

**404 Not Found:**
```json
{
  "success": false,
  "error": "not_found",
  "message": "Property with ID 5 not found"
}
```

**500 Internal Server Error:**
```json
{
  "success": false,
  "error": "extraction_failed",
  "message": "No notes found for property. Add some notes before extracting features."
}
```

---

### GET /api/property_features.php

Get cached features without extraction.

**Request:**
```bash
GET /api/property_features.php?property_id=5
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Features retrieved successfully",
  "property_id": 5,
  "features": { ... },
  "summary": [ ... ],
  "has_features": true
}
```

**No Features Response (200 OK):**
```json
{
  "success": true,
  "message": "No features found for this property",
  "property_id": 5,
  "features": null,
  "has_features": false
}
```

---

## CLI Script Usage

### Basic Usage

**Location:** `scripts/extract_features.php`

```bash
# Extract all properties with notes
php scripts/extract_features.php

# Extract first 10 properties
php scripts/extract_features.php --limit=10

# Extract specific property
php scripts/extract_features.php --property-id=5

# Force re-extraction (ignore cache)
php scripts/extract_features.php --force-refresh

# Combine options
php scripts/extract_features.php --limit=5 --force-refresh

# Show help
php scripts/extract_features.php --help
```

### Output Example

```
🤖 Starting Feature Extraction
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✓ Gemini API configured
Properties to process: 10
Force refresh: No

⏳ Processing...

[1/10] Processing: Downtown Office Center (ID: 5)... ✓ Success (1.2s) [Confidence: 85%]
    🚇 Near subway | 🅿️ Parking | 🛗 Elevator | Condition: ⭐⭐⭐ | Use: Office

[2/10] Processing: Warehouse District (ID: 8)... ✓ Success (0.9s) [Confidence: 78%]
    No subway | 🅿️ Parking | No elevator | Condition: ⭐⭐ | Use: Warehouse

[3/10] Processing: Retail Plaza (ID: 12)... ⊘ Already extracted (use --force-refresh to re-extract)

...

✅ Extraction Complete!
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Extracted: 7
Skipped:   3 (already extracted)
Failed:    0
Total time: 12.5s
Avg time per extraction: 1.1s
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

🎯 Next Steps:
  1. View extracted features in the property details pages
  2. Run property scoring: php scripts/score_properties.php
  3. View API results: curl http://localhost:8080/api/properties.php
```

### Features

- **Color-coded output** - Green (success), Yellow (skip), Red (error)
- **Progress tracking** - [current/total] for each property
- **Timing information** - Per-property and total duration
- **Confidence display** - Shows AI confidence percentage
- **Feature preview** - Key features shown inline
- **Summary statistics** - Extracted, skipped, failed counts
- **Error reporting** - Detailed error messages for failures
- **Rate limiting** - 0.5s delay between requests

---

## Error Handling

### Error Types

#### 1. Validation Errors (HTTP 400)

**Causes:**
- Missing property_id
- Invalid property_id (not integer or <= 0)
- Invalid JSON body
- Empty request body

**Response:**
```json
{
  "success": false,
  "error": "validation_error",
  "message": "Invalid property_id",
  "errors": {}
}
```

#### 2. Not Found Errors (HTTP 404)

**Causes:**
- Property doesn't exist in database

**Response:**
```json
{
  "success": false,
  "error": "not_found",
  "message": "Property with ID 5 not found"
}
```

#### 3. Extraction Errors (HTTP 500)

**Causes:**
- No notes found for property
- Gemini API key not configured
- API call failed (network, rate limit, etc.)
- Invalid AI response format
- JSON parsing error

**Response:**
```json
{
  "success": false,
  "error": "extraction_failed",
  "message": "No notes found for property. Add some notes before extracting features."
}
```

### Retry Logic

**Gemini API calls automatically retry:**
- Max attempts: 3
- Exponential backoff: 1s, 2s, 4s
- Logs each attempt
- No retry on authentication errors

**Example log:**
```
Gemini API attempt 1/3 failed: Connection timeout
Gemini API attempt 2/3 failed: Connection timeout
Gemini API attempt 3/3 succeeded
```

### Graceful Degradation

**Invalid AI responses:**
- Missing fields → Set to null
- Invalid types → Skip field
- Out of range values → Skip field
- Malformed JSON → Throw exception

**Example:**
```php
// If AI returns condition_rating: 10 (invalid, should be 1-5)
// Field is skipped, set to null
if ($rating >= 1 && $rating <= 5) {
    $feature->setConditionRating($rating);
}
```

---

## Performance Considerations

### API Call Costs

**Gemini 2.0 Flash Pricing (as of 2026):**
- Input: ~$0.075 per 1M tokens
- Output: ~$0.30 per 1M tokens

**Typical Extraction:**
- System prompt: ~1,200 tokens
- User prompt (3 notes): ~300 tokens
- AI response: ~200 tokens
- **Total: ~1,700 tokens per extraction**
- **Cost: ~$0.0003 per property**

**For 1000 properties:**
- Total tokens: ~1.7M
- Total cost: ~$0.30

### Caching Strategy

**Features are cached by default:**
- Stored in `property_features` table
- Unique constraint on `property_id`
- Retrieved without AI call if exists
- Use `force_refresh: true` to bypass

**Benefits:**
- Instant response (no API call)
- Zero cost for cached data
- Consistent results

**When to force refresh:**
- Notes have been updated
- Extraction quality was poor
- Testing prompt changes

### Rate Limiting

**CLI Script:**
- 0.5s delay between requests
- Prevents API rate limit errors
- ~120 properties per minute

**API Endpoint:**
- No built-in rate limiting
- Client should implement delays
- Consider queueing for bulk operations

### Optimization Tips

1. **Batch Processing**
   - Use CLI script for bulk extraction
   - Process during off-peak hours
   - Monitor API quotas

2. **Note Quality**
   - More detailed notes = better extraction
   - Fewer, well-written notes > many vague notes
   - Aim for 2-5 notes per property

3. **Prompt Tuning**
   - Adjust temperature for consistency
   - Increase max_tokens for complex properties
   - Test with sample properties first

4. **Database Optimization**
   - Indexes on commonly queried fields
   - JSONB indexes for amenities
   - Regular VACUUM for performance

---

## Best Practices

### 1. Note Writing Guidelines

**Good Notes:**
```
✓ "Property is on 3rd floor with elevator access. 
   Located 2 blocks from Red Line subway station. 
   Has dedicated parking for 10 cars. Recently 
   renovated, excellent condition. Can accommodate 
   25-30 employees comfortably."
```

**Poor Notes:**
```
✗ "Nice place. Checked it out yesterday. Seems okay."
```

**Tips:**
- Be specific (numbers, distances, features)
- Mention location details (transit, parking)
- Describe condition explicitly
- Note capacity/size
- List amenities

### 2. Extraction Workflow

**Recommended Process:**
1. Add property to system
2. Add 2-5 detailed notes
3. Run extraction (API or CLI)
4. Review extracted features
5. Add more notes if needed
6. Re-extract with `force_refresh: true`

### 3. Quality Assurance

**Check Confidence Scores:**
- High (0.8-1.0): Good quality, trust results
- Medium (0.6-0.8): Review for accuracy
- Low (0.0-0.6): Add more notes, re-extract

**Validate Critical Fields:**
- Verify `recommended_use` matches property type
- Check `estimated_capacity_people` is reasonable
- Confirm `condition_rating` aligns with notes

### 4. Error Recovery

**If extraction fails:**
1. Check Gemini API key is configured
2. Verify property has notes
3. Check API quota/rate limits
4. Review error logs
5. Try again after delay

**If results are poor:**
1. Add more detailed notes
2. Use `force_refresh: true`
3. Check confidence score
4. Consider adjusting temperature

---

## Troubleshooting

### Common Issues

#### "Gemini API key not configured"

**Solution:**
```bash
# Add to .env file
echo "GEMINI_API_KEY=your-key-here" >> .env

# Verify
grep GEMINI_API_KEY .env
```

#### "No notes found for property"

**Solution:**
```bash
# Add notes via API
curl -X POST http://localhost:8080/api/add_note.php \
  -H "Content-Type: application/json" \
  -d '{
    "property_id": 5,
    "note": "Property is well-located near subway with parking"
  }'
```

#### "Failed to parse JSON response"

**Causes:**
- API returned non-JSON
- Network error
- Rate limit exceeded

**Solution:**
- Check API key is valid
- Verify API quota
- Wait and retry
- Check error logs

#### Low Confidence Scores

**Causes:**
- Vague notes
- Insufficient information
- Contradictory information

**Solution:**
- Add more detailed notes
- Be specific with measurements
- Clarify ambiguous points
- Re-extract with `force_refresh`

---

## Summary

The feature extraction system provides:

✅ **Automated Data Structuring** - Transforms free-text notes into queryable fields  
✅ **AI-Powered Intelligence** - Uses Gemini 2.0 Flash for accurate extraction  
✅ **Robust Error Handling** - Graceful degradation and retry logic  
✅ **Caching Strategy** - Avoids unnecessary API calls  
✅ **Flexible Access** - HTTP API and CLI script  
✅ **Quality Metrics** - Confidence scores and completeness tracking  
✅ **Cost Effective** - ~$0.0003 per property extraction  
✅ **Production Ready** - Clean architecture, validation, logging  

**Next Steps:**
- [Property Scoring Flow](PROPERTY_SCORING_FLOW.md) - Use extracted features for scoring
- [API Documentation](API_DOCUMENTATION.md) - Complete API reference
- [AI Usage Guide](docs/AI_USAGE.md) - Practical examples and tips

---

**Built with ❤️ using Google Gemini AI and clean PHP architecture**
