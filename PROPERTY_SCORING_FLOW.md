# Property Scoring Flow - Complete Guide

## Overview

The property scoring system intelligently matches properties to client requirements by assigning a score from 0 to 10. This document explains the complete flow from input to output with practical examples.

### 🚀 Enhanced Feature-Based Scoring (NEW)

The system now uses an **enhanced structured features approach** for more accurate, transparent scoring:

✅ **Weighted Feature Analysis** - Features categorized by importance (High/Medium/Lower)  
✅ **Specific Matching Logic** - Direct feature-to-requirement matching with point values  
✅ **Feature Completeness Score** - Know how much data is available (0.0-1.0)  
✅ **Adaptive Confidence** - Confidence adjusted based on feature availability  
✅ **Enhanced Transparency** - Explanations cite specific features with ✓/✗ indicators  
✅ **Structured Feature Summary** - API responses include key feature data  

**Key Benefits:**
- More accurate scores when features are extracted
- Consistent scoring across similar properties
- Detailed explanations with concrete feature references
- Better data for broker decision-making

---

## Table of Contents

- [High-Level Flow](#high-level-flow)
- [Detailed Technical Flow](#detailed-technical-flow)
- [Step-by-Step Process](#step-by-step-process)
- [Real-World Examples](#real-world-examples)
- [Scoring Logic Explained](#scoring-logic-explained)
- [Integration with Feature Extraction](#integration-with-feature-extraction)
- [Error Handling](#error-handling)

---

## High-Level Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                     Property Scoring Flow                        │
└─────────────────────────────────────────────────────────────────┘

1. Broker inputs client requirements (free text)
   - Via Web UI at http://localhost:8080/score.html
   - Or via API call to /api/score_properties.php
   ↓
2. System retrieves all properties from database
   ↓
3. For each property:
   a. Load property details (name, address, location)
   b. Load extracted features (if available)
   c. Build AI prompt with property info + requirements
   d. Send to Gemini AI for scoring
   e. Parse response (score, explanation, strengths, weaknesses)
   ↓
4. Sort properties by score (highest first)
   ↓
5. Return ranked list with explanations
   - Displayed in beautiful UI with color-coded scores
   - Or returned as JSON for API consumers
```

---

## Detailed Technical Flow

### Architecture Components

```
┌─────────────────┐
│   API Client    │  POST /api/score_properties.php
│   (Browser/CLI) │  { "requirements": "...", "limit": 10 }
└────────┬────────┘
         │
         ↓
┌─────────────────────────────────────────────────────────────────┐
│                      AIController.php                            │
│  • Validates input                                               │
│  • Calls PropertyScoringService                                  │
│  • Returns JSON response                                         │
└────────┬────────────────────────────────────────────────────────┘
         │
         ↓
┌─────────────────────────────────────────────────────────────────┐
│                  PropertyScoringService.php                      │
│  • scoreAllProperties() - orchestrates scoring                   │
│  • scoreProperty() - scores individual property                  │
│  • buildScoringSystemPrompt() - creates AI instructions         │
│  • buildScoringUserPrompt() - formats property data             │
│  • parseScoreResponse() - extracts structured data              │
└────────┬────────────────────────────────────────────────────────┘
         │
         ├──→ PropertyRepository (get all properties)
         │
         ├──→ PropertyFeatureRepository (get extracted features)
         │
         └──→ GeminiService (AI scoring)
                    │
                    ↓
              ┌─────────────┐
              │  Gemini AI  │  Returns structured JSON
              │   (Google)  │  with score + explanation
              └─────────────┘
```

---

## Step-by-Step Process

### Step 1: Client Request

Brokers can input requirements in two ways:

#### Option A: Web UI (Recommended for Brokers)

**Access:** Navigate to `http://localhost:8080/score.html`

**Features:**
- User-friendly text area for entering requirements
- Pre-filled example requirements (click to use)
- Limit results option
- Beautiful visual display of scored properties
- Color-coded scores (green for excellent, red for poor)
- Interactive cards showing strengths and weaknesses

#### Option B: API Call (For Developers/Integrations)

**Input Example:**
```bash
POST /api/score_properties.php
Content-Type: application/json

{
  "requirements": "Client is looking for an office near the subway, budget up to $50k/month, for 15–20 people, preferably in a central area.",
  "limit": 5
}
```

**What happens:**
- Request hits `api/score_properties.php`
- Routed to `AIController::scoreProperties()`
- Input validated (requirements cannot be empty)
- Limit parameter is optional (default: all properties)

---

### Step 2: Load Properties

```php
// In PropertyScoringService::scoreAllProperties()
$properties = $this->propertyRepository->findAll(1000, 0);
```

**Example Data Retrieved:**
```php
[
  Property {
    id: 1,
    name: "Downtown Office Tower",
    address: "123 Main St, New York, NY",
    latitude: 40.7128,
    longitude: -74.0060,
    extra_field: { "city": "New York", "state": "NY" }
  },
  Property {
    id: 2,
    name: "Tech Hub Plaza",
    address: "456 Tech Ave, San Francisco, CA",
    latitude: 37.7749,
    longitude: -122.4194,
    extra_field: { "city": "San Francisco", "state": "CA" }
  },
  // ... more properties
]
```

---

### Step 3: Score Each Property

For each property, the system:

#### 3a. Load Extracted Features

```php
$features = $this->featureRepository->findByPropertyId($property->getId());
```

**Example Features:**
```php
PropertyFeature {
  property_id: 1,
  near_subway: true,
  needs_renovation: false,
  parking_available: true,
  has_elevator: true,
  estimated_capacity_people: 25,
  floor_level: 5,
  condition_rating: 4,
  recommended_use: "office",
  amenities: ["conference room", "kitchen", "fiber internet"],
  confidence_score: 0.92
}
```

#### 3b. Build AI Prompt (Enhanced with Structured Features)

**System Prompt (Instructions):**
```
You are an expert commercial real estate broker assistant with deep knowledge of 
property evaluation and client-property matching. Your task is to score how well 
a property matches a client's requirements using both basic property information 
and AI-extracted structured features.

SCORING SCALE:
- 0-3: Poor match (major misalignment)
- 4-5: Fair match (some alignment, significant gaps)
- 6-7: Good match (solid alignment, minor gaps)
- 8-9: Excellent match (strong alignment)
- 10: Perfect match (meets all requirements)

FEATURE IMPORTANCE WEIGHTS:
HIGH IMPORTANCE (30-40% of score):
- Location & Transit Access (near_subway, address, city)
- Property Type & Use Case (recommended_use)
- Capacity/Size (estimated_capacity_people)

MEDIUM IMPORTANCE (20-30% of score):
- Condition & Readiness (condition_rating, needs_renovation)
- Core Amenities (parking_available, has_elevator, amenities)

LOWER IMPORTANCE (10-20% of score):
- Nice-to-have features (floor_level, additional amenities)

FEATURE MATCHING LOGIC:
- near_subway=true + "near transit" requirement = +1.5 to +2.5 points
- capacity matches exactly = +1.5 to +2.0 points
- capacity exceeds by 20-50% = +2.0 to +2.5 points
- recommended_use matches need = +2.0 to +3.0 points
- condition_rating 4-5 + "move-in ready" = +1.0 to +1.5 points
- Wrong property type = max score 3.0
- Capacity too small by >50% = max score 4.0

Return your analysis in JSON format with specific feature references.
```

**User Prompt (Property + Requirements) - Enhanced Structured Format:**
```
========================================
PROPERTY DETAILS
========================================
PROPERTY NAME: Downtown Office Tower
ADDRESS: 123 Main St, New York, NY
CITY: New York
STATE: NY

========================================
AI-EXTRACTED FEATURES (Structured Data)
========================================
Feature Extraction Confidence: 92%

--- HIGH IMPORTANCE FEATURES ---
Near Subway/Transit: ✓ YES (within 5-10 min walk)
Recommended Use: OFFICE
Estimated Capacity: 25 people

--- MEDIUM IMPORTANCE FEATURES ---
Condition Rating: 4/5 (Very Good/Recently Updated)
Needs Renovation: ✓ NO (ready to use)
Parking: ✓ AVAILABLE
Elevator: ✓ YES
Amenities (3): Conference room, Kitchen, Fiber internet

--- LOWER IMPORTANCE FEATURES ---
Floor Level: Floor 5

Extracted from 2 property note(s)
========================================

========================================
CLIENT REQUIREMENTS
========================================
Client is looking for an office near the subway, budget up to $50k/month, 
for 15–20 people, preferably in a central area.

========================================
SCORING TASK
========================================
Analyze the property features against the client requirements and provide a 
scored assessment. Weight features according to importance (High/Medium/Low).
Match specific features to specific requirements. Be specific in explanations.
```

#### 3c. Send to Gemini AI

```php
$result = $this->geminiService->extractStructuredData(
    $systemPrompt,
    $userPrompt,
    ['temperature' => 0.4, 'max_tokens' => 600]
);
```

**AI Response Example:**
```json
{
  "score": 8.5,
  "explanation": "Excellent match for the client's needs. The property is located near subway access in a central area and can comfortably accommodate 15-20 people with capacity for 25. The condition is good and includes modern amenities like fiber internet. The main consideration is confirming the rental rate fits within the $50k/month budget.",
  "strengths": [
    "Prime location near subway in central New York",
    "Perfect capacity (25 people) exceeds requirement of 15-20",
    "No renovation needed - move-in ready",
    "Modern amenities including conference room and fiber internet",
    "Good condition rating (4/5)"
  ],
  "weaknesses": [
    "Budget not explicitly confirmed - needs verification against $50k/month limit",
    "5th floor location may be concern if elevator maintenance is an issue"
  ],
  "confidence": 0.87
}
```

#### 3d. Parse and Format Response (Enhanced)

```php
return [
    'property_id' => 1,
    'property_name' => 'Downtown Office Tower',
    'address' => '123 Main St, New York, NY',
    'score' => 8.5,
    'explanation' => 'Excellent match for the client\'s needs...',
    'strengths' => [...],
    'weaknesses' => [...],
    'confidence' => 0.87,
    'latitude' => 40.7128,
    'longitude' => -74.0060,
    'feature_completeness' => 0.89,  // NEW: How complete the feature data is
    'features' => [                   // NEW: Key feature summary
        'near_subway' => true,
        'recommended_use' => 'office',
        'capacity_people' => 25,
        'condition_rating' => 4,
        'parking' => true,
        'amenities_count' => 3,
        'extraction_confidence' => 0.92
    ]
];
```

---

### Step 4: Sort Results

```php
// Sort by score descending (highest first)
usort($scoredProperties, function($a, $b) {
    return $b['score'] <=> $a['score'];
});

// Apply limit if specified
if ($limit !== null && $limit > 0) {
    $scoredProperties = array_slice($scoredProperties, 0, $limit);
}
```

---

### Step 5: Return Response

**Final API Response (Enhanced):**
```json
{
  "success": true,
  "message": "Properties scored successfully",
  "scored_properties": [
    {
      "property_id": 1,
      "property_name": "Downtown Office Tower",
      "address": "123 Main St, New York, NY",
      "score": 8.5,
      "explanation": "Excellent match for the client's needs. The property is located near subway access (✓ within 10 min walk) in central New York and can comfortably accommodate 15-20 people with capacity for 25. Condition rating of 4/5 means it's move-in ready with no renovation needed. Modern amenities including fiber internet. The main consideration is confirming the rental rate fits within the $50k/month budget.",
      "strengths": [
        "✓ Near subway - within 5-10 minute walk (HIGH IMPORTANCE)",
        "✓ Office use - matches requirement perfectly (HIGH IMPORTANCE)",
        "✓ Capacity of 25 people exceeds requirement of 15-20 by 25% (HIGH IMPORTANCE)",
        "✓ Condition rating 4/5 - move-in ready, no renovation needed",
        "✓ Modern amenities: conference room, kitchen, fiber internet",
        "✓ Has elevator (5th floor location)"
      ],
      "weaknesses": [
        "Budget not explicitly confirmed - needs verification against $50k/month limit",
        "Parking availability not mentioned in requirements but is available if needed"
      ],
      "confidence": 0.87,
      "latitude": 40.7128,
      "longitude": -74.0060,
      "feature_completeness": 0.89,
      "features": {
        "near_subway": true,
        "recommended_use": "office",
        "capacity_people": 25,
        "condition_rating": 4,
        "parking": true,
        "amenities_count": 3,
        "extraction_confidence": 0.92
      }
    },
    {
      "property_id": 5,
      "property_name": "Midtown Business Center",
      "address": "789 Park Ave, New York, NY",
      "score": 7.8,
      "explanation": "Good match with strong location and capacity. Near subway access and suitable office space for 20 people. However, condition rating of 3/5 indicates it's functional but may need some updates soon.",
      "strengths": [
        "✓ Near subway access (HIGH IMPORTANCE)",
        "✓ Office type matches requirement (HIGH IMPORTANCE)",
        "✓ Capacity of 20 people matches requirement exactly"
      ],
      "weaknesses": [
        "✗ Condition rating 3/5 - functional but not recently updated",
        "✗ No parking available - may be a concern",
        "Limited amenities listed (only 1)"
      ],
      "confidence": 0.82,
      "latitude": 40.7580,
      "longitude": -73.9855,
      "feature_completeness": 0.78,
      "features": {
        "near_subway": true,
        "recommended_use": "office",
        "capacity_people": 20,
        "condition_rating": 3,
        "parking": false,
        "amenities_count": 1,
        "extraction_confidence": 0.85
      }
    }
    // ... more properties up to the limit
  ],
  "total_properties": 10,
  "results_shown": 5,
  "client_requirements": "Client is looking for an office near the subway, budget up to $50k/month, for 15–20 people, preferably in a central area."
}
```

**Note the Enhanced Features:**
- Explanations now include checkmarks (✓/✗) and specific feature references
- Strengths cite importance levels (HIGH/MEDIUM/LOWER)
- New `feature_completeness` field (0.0-1.0)
- New `features` object with key feature summary
- More specific, actionable information for brokers

---

## Real-World Examples

### Example 1: Tech Startup Office

**Client Requirements:**
```
"Looking for modern office space for a tech startup, 20-30 employees, 
must have high-speed internet, near public transit, parking for 10 cars, 
prefer open floor plan with natural light. Budget: $40k-60k/month."
```

**Property 1 - High Score (9.2):**
```json
{
  "property_name": "Silicon Valley Tech Hub",
  "score": 9.2,
  "explanation": "Outstanding match for tech startup needs. Features 1Gbps fiber internet, modern open floor plan with floor-to-ceiling windows, close to Caltrain station. Capacity of 35 exceeds needs and includes 15 parking spots. Rent at $55k/month fits budget perfectly.",
  "strengths": [
    "Fiber internet (1Gbps) perfect for tech company",
    "Open floor plan with natural light from floor-to-ceiling windows",
    "2 blocks from Caltrain station",
    "Parking for 15 cars exceeds requirement",
    "Modern amenities: kitchen, shower room, bike storage",
    "Within budget at $55k/month"
  ],
  "weaknesses": [
    "Shared conference rooms (not dedicated)",
    "No nearby restaurants within walking distance"
  ],
  "confidence": 0.91
}
```

**Property 2 - Medium Score (5.5):**
```json
{
  "property_name": "Classic Business Building",
  "score": 5.5,
  "explanation": "Fair match but with significant gaps. While the location and capacity work, the traditional office layout and basic internet (100Mbps) don't align well with tech startup needs. Would require upgrades and renovation to meet modern standards.",
  "strengths": [
    "Good capacity (28 people)",
    "Near bus stop (15 min to train station)",
    "Within budget at $42k/month"
  ],
  "weaknesses": [
    "Traditional cubicle layout, not open plan",
    "Basic internet (100Mbps), would need upgrade",
    "Limited parking (only 5 spots)",
    "Older building needs modernization",
    "Fluorescent lighting, minimal natural light"
  ],
  "confidence": 0.78
}
```

---

### Example 2: Retail Store

**Client Requirements:**
```
"Retail space for a boutique clothing store, high foot traffic area, 
ground floor required, 1500-2000 sq ft, large windows for display, 
central shopping district. Budget: $15k-25k/month."
```

**Property 1 - High Score (8.9):**
```json
{
  "property_name": "Madison Avenue Storefront",
  "score": 8.9,
  "explanation": "Excellent retail location in prime shopping district. Ground floor with large display windows on busy Madison Avenue. 1,800 sq ft meets size requirement perfectly. High foot traffic area with neighboring luxury brands. Rent at $23k/month is within budget.",
  "strengths": [
    "Prime Madison Avenue location - very high foot traffic",
    "Ground floor with 3 large display windows (12ft wide each)",
    "Perfect size at 1,800 sq ft",
    "Neighboring luxury brands drive quality traffic",
    "Recently renovated with modern fixtures",
    "Within budget at $23k/month"
  ],
  "weaknesses": [
    "No dedicated loading area (street loading only)",
    "Limited back-of-house storage space"
  ],
  "confidence": 0.93
}
```

---

### Example 3: Warehouse / Logistics

**Client Requirements:**
```
"Warehouse space for e-commerce fulfillment, minimum 10,000 sq ft, 
loading dock required, 20ft ceiling clearance, near highway access, 
forklift operation approved. Budget: $8k-12k/month."
```

**Property 1 - High Score (9.5):**
```json
{
  "property_name": "Industrial Park Warehouse Unit 7",
  "score": 9.5,
  "explanation": "Near-perfect match for e-commerce fulfillment needs. 12,500 sq ft with 24ft ceilings provides excellent storage capacity. Three loading docks with hydraulic lifts, direct access to I-95, and fully approved for forklift operations. Rent at $10k/month is ideal for budget.",
  "strengths": [
    "Exceeds size requirement at 12,500 sq ft",
    "24ft ceiling clearance (exceeds 20ft requirement)",
    "3 loading docks with hydraulic lifts",
    "Direct access to I-95 (2 min drive)",
    "Approved for 24/7 operations",
    "Forklift operation permitted",
    "LED lighting throughout",
    "Within budget at $10k/month"
  ],
  "weaknesses": [
    "No climate control (would need to install if needed)",
    "Office space is basic (300 sq ft only)"
  ],
  "confidence": 0.95
}
```

**Property 2 - Low Score (3.2):**
```json
{
  "property_name": "Suburban Light Industrial Space",
  "score": 3.2,
  "explanation": "Poor match for e-commerce fulfillment. While price is attractive at $7k/month, the space fails on most critical requirements: only 6,000 sq ft (too small), 12ft ceilings (inadequate), no proper loading dock, and 20 minutes from nearest highway. Would require significant compromises.",
  "strengths": [
    "Below budget at $7k/month",
    "Well-maintained and clean",
    "Adequate parking for staff"
  ],
  "weaknesses": [
    "Only 6,000 sq ft (40% below minimum requirement)",
    "12ft ceilings inadequate for racking systems",
    "No proper loading dock (only roll-up door)",
    "20 minutes from highway - poor logistics access",
    "Limited hours (6am-8pm only)",
    "Shared building with noise restrictions"
  ],
  "confidence": 0.88
}
```

---

## Scoring Logic Explained

### Score Ranges and Meaning

| Score | Quality | Description | Example Scenario |
|-------|---------|-------------|------------------|
| **9.0-10.0** | Excellent | Meets all or nearly all requirements. Minor considerations only. | Perfect location, size, amenities, and price. Maybe missing one nice-to-have. |
| **7.0-8.9** | Good | Strong match with some gaps. Would work well with minor compromises. | Great location and size, but slightly over budget or needs minor updates. |
| **5.0-6.9** | Fair | Partial match. Some requirements met, but significant gaps exist. | Right location but wrong size, or right size but poor location. |
| **3.0-4.9** | Poor | Major misalignment. Would require significant compromises. | Missing multiple key requirements. Might work in desperate situation. |
| **0.0-2.9** | Very Poor | Fundamental misalignment. Not a viable option. | Wrong property type entirely or fails on all critical requirements. |

### Factors Considered by AI

The Gemini AI considers these factors when scoring:

1. **Location Match** (High Weight)
   - Proximity to subway/transit
   - Central vs. suburban
   - Specific neighborhood requirements
   - Highway access (for warehouses)

2. **Capacity/Size Match** (High Weight)
   - Number of people (offices)
   - Square footage
   - Room configuration
   - Ceiling height (warehouses)

3. **Budget Alignment** (High Weight)
   - Within stated range
   - Value for money
   - Operating costs

4. **Property Type** (Critical)
   - Office vs. retail vs. warehouse
   - Floor level requirements
   - Layout type (open plan, cubicles, etc.)

5. **Amenities and Features** (Medium Weight)
   - Parking availability
   - Internet speed
   - Elevator access
   - Loading docks
   - Climate control

6. **Condition** (Medium Weight)
   - Renovation needs
   - Move-in readiness
   - Modern vs. dated

7. **Special Requirements** (Variable Weight)
   - Display windows (retail)
   - Natural light (offices)
   - 24/7 access
   - Forklift operations

---

## Integration with Feature Extraction (Enhanced Approach)

The scoring system uses a **structured, weighted feature approach** for maximum accuracy:

### Without Feature Extraction

If features haven't been extracted yet:

```
========================================
PROPERTY DETAILS
========================================
PROPERTY NAME: Downtown Office Tower
ADDRESS: 123 Main St, New York, NY
CITY: New York
STATE: NY

========================================
⚠️  NO EXTRACTED FEATURES AVAILABLE
========================================
Scoring will be based on property name and address only.
Confidence will be lower. Run feature extraction for better results.
========================================
```

**Impact:**
- AI scores conservatively (typically 4-6 range)
- Based only on name/address inference
- Lower confidence (~0.4-0.6)
- Generic explanations without specific feature references
- Feature completeness score: 0.0
- Confidence automatically capped at 0.6

### With Feature Extraction (Enhanced)

After running feature extraction, features are presented in **structured tiers**:

```
========================================
AI-EXTRACTED FEATURES (Structured Data)
========================================
Feature Extraction Confidence: 92%

--- HIGH IMPORTANCE FEATURES ---
Near Subway/Transit: ✓ YES (within 5-10 min walk)
Recommended Use: OFFICE
Estimated Capacity: 25 people

--- MEDIUM IMPORTANCE FEATURES ---
Condition Rating: 4/5 (Very Good/Recently Updated)
Needs Renovation: ✓ NO (ready to use)
Parking: ✓ AVAILABLE
Elevator: ✓ YES
Amenities (3): Conference room, Kitchen, Fiber internet

--- LOWER IMPORTANCE FEATURES ---
Floor Level: Floor 5
========================================
```

**Impact:**
- **Weighted scoring** based on feature importance tiers
- **Specific feature matching** (e.g., "near_subway=true matches 'near transit' requirement")
- **Higher accuracy** (confidence ~0.8-0.95)
- **Detailed explanations** with concrete feature references
- **Feature completeness** score (0.0-1.0) indicates data quality
- **Extraction confidence** guides overall assessment confidence
- Strengths/weaknesses cite specific features (e.g., "✓ Near subway (5 min walk)")

### Feature Completeness Score

New in enhanced scoring: properties now include a `feature_completeness` score:

- **0.0-0.3**: Low data (score conservatively, confidence capped at 0.6)
- **0.4-0.6**: Moderate data (reasonable scoring accuracy)
- **0.7-0.9**: Good data (high accuracy)
- **1.0**: Complete data (all 9 key features extracted)

### Enhanced Scoring Benefits

The new structured approach provides:

1. **Weighted Feature Analysis**
   - High-importance features (location, type, capacity) carry 30-40% of score
   - Medium-importance (condition, amenities) carry 20-30%
   - Lower-importance (nice-to-haves) carry 10-20%

2. **Specific Feature Matching**
   - Direct matching logic (e.g., "near_subway=true" + "near transit" requirement = +2 points)
   - Concrete scoring rules reduce AI subjectivity
   - More consistent scores across similar properties

3. **Enhanced Transparency**
   - Explanations cite specific features with checkmarks (✓/✗)
   - Strengths/weaknesses reference actual extracted data
   - Feature completeness score shows data quality

4. **Adaptive Confidence**
   - Confidence adjusted based on feature availability
   - Low feature data = capped confidence (max 0.6)
   - Extraction confidence factored into final confidence

5. **Better Response Data**
   - Responses include `feature_completeness` score
   - Responses include `features` object with key feature summary
   - More actionable data for brokers

### Recommended Workflow

```
1. Add property to system
   ↓
2. Add notes with details
   ↓
3. Run feature extraction (POST /api/extract_features.php)
   ↓
4. Verify feature extraction quality (check confidence_score)
   ↓
5. Run property scoring (POST /api/score_properties.php)
   ↓
6. Get highly accurate, weighted, explainable scores
   ↓
7. Review feature_completeness and extraction_confidence
   ↓
8. Present matched properties to clients with confidence
```

---

## Error Handling

### Common Scenarios

#### 1. Empty Requirements

**Request:**
```json
{
  "requirements": "",
  "limit": 5
}
```

**Response:**
```json
{
  "success": false,
  "error": "validation_error",
  "message": "Missing or empty required field: requirements",
  "errors": []
}
```

#### 2. No Properties in Database

**Response:**
```json
{
  "success": true,
  "message": "Properties scored successfully",
  "scored_properties": [],
  "total_properties": 0,
  "results_shown": 0,
  "client_requirements": "office space near subway"
}
```

#### 3. Gemini API Not Configured

**Error:**
```json
{
  "success": false,
  "error": "scoring_failed",
  "message": "Gemini API key is not configured. Please add GEMINI_API_KEY to your .env file."
}
```

**Solution:** Add to `.env` file:
```bash
GEMINI_API_KEY=your-api-key-here
GEMINI_MODEL=gemini-1.5-flash
```

#### 4. Individual Property Scoring Fails

If one property fails to score:
- Error is logged: `"Error scoring property 5: API timeout"`
- System continues with other properties
- Failed property is excluded from results
- No impact on overall scoring operation

---

## Performance Considerations

### API Calls

Each property requires one API call to Gemini:
- **10 properties** = 10 API calls
- **100 properties** = 100 API calls

**Typical Response Time:**
- Per property: 1-3 seconds
- 10 properties: 10-30 seconds
- 100 properties: 2-5 minutes

### Cost Estimates

Using Gemini 1.5 Flash (recommended):
- **Cost per property:** ~$0.0005
- **10 properties:** ~$0.005
- **100 properties:** ~$0.05

Very cost-effective compared to OpenAI GPT-4.

### Optimization Tips

1. **Use limit parameter** - Only score top N properties
```json
{ "requirements": "...", "limit": 10 }
```

2. **Pre-extract features** - Better accuracy, same cost
```bash
# Extract features first for better results
POST /api/extract_features.php
```

3. **Cache results** - Store scores if requirements don't change

4. **Filter properties first** - Score only relevant property types
```php
// In future enhancement
$properties = $this->propertyRepository
    ->findByType('office')
    ->inCity('New York');
```

---

## Testing Examples

### Test with Web UI (Easiest)

1. **Open the scoring page:**
   ```
   http://localhost:8080/score.html
   ```

2. **Enter client requirements** or click one of the example chips:
   - "Office space, 20-30 people, near subway"
   - "Retail storefront, ground floor, high foot traffic"
   - "Warehouse, 10,000+ sqft, loading dock, highway access"

3. **Click "Score Properties"** button

4. **View beautiful results:**
   - Ranked list of properties with scores
   - Detailed explanations for each score
   - Color-coded cards (green = excellent match, red = poor match)
   - Strengths and weaknesses breakdown
   - AI confidence indicators

### Test with cURL

```bash
# Example 1: Office space
curl -X POST http://localhost:8080/api/score_properties.php \
  -H "Content-Type: application/json" \
  -d '{
    "requirements": "Office for tech startup, 25-30 people, near subway, parking for 10 cars, high-speed internet, modern open plan. Budget $40k-60k/month.",
    "limit": 5
  }'

# Example 2: Retail space
curl -X POST http://localhost:8080/api/score_properties.php \
  -H "Content-Type: application/json" \
  -d '{
    "requirements": "Retail storefront, ground floor, high foot traffic, 1500-2000 sqft, large display windows, central shopping area. Budget $15k-25k/month.",
    "limit": 3
  }'

# Example 3: Warehouse
curl -X POST http://localhost:8080/api/score_properties.php \
  -H "Content-Type: application/json" \
  -d '{
    "requirements": "Warehouse for e-commerce fulfillment, minimum 10,000 sqft, loading dock required, 20ft ceiling, near highway, forklift approved. Budget $8k-12k/month.",
    "limit": 5
  }'
```

### Test with PHP Script

```php
<?php
// test_scoring.php

$data = [
    'requirements' => 'Office near subway, 15-20 people, parking available',
    'limit' => 5
];

$ch = curl_init('http://localhost:8080/api/score_properties.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$result = json_decode($response, true);

echo "Scored Properties:\n";
foreach ($result['scored_properties'] as $property) {
    echo sprintf(
        "\n%s (Score: %.1f)\n%s\n",
        $property['property_name'],
        $property['score'],
        $property['explanation']
    );
}
```

---

## Summary

The property scoring flow provides:

✅ **Intelligent Matching** - AI understands context and nuance  
✅ **Clear Scores** - 0-10 scale with defined ranges  
✅ **Explainability** - Detailed explanation for each score  
✅ **Transparency** - Lists specific strengths and weaknesses  
✅ **Confidence Scores** - Shows AI's confidence level  
✅ **Ranked Results** - Automatically sorted best-to-worst  
✅ **Flexibility** - Works with any type of requirement text  
✅ **Integration** - Leverages extracted features for better accuracy  

The system makes it easy for brokers to quickly identify the best property matches for their clients, saving time and improving client satisfaction.

---

## Next Steps

1. **Add properties** to your database
2. **Add notes** with details about each property
3. **Extract features** using `/api/extract_features.php`
4. **Score properties** using `/api/score_properties.php`
5. **Review results** and present to clients

For more information, see:
- [AI Usage Guide](docs/AI_USAGE.md)
- [API Documentation](API_DOCUMENTATION.md)
- [Implementation Summary](AI_IMPLEMENTATION_SUMMARY.md)
