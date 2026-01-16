# Property Scoring Flow - Complete Guide

## Overview

The property scoring system intelligently matches properties to client requirements by assigning a score from 0 to 10. This document explains the complete flow from input to output with practical examples.

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

#### 3b. Build AI Prompt

**System Prompt (Instructions):**
```
You are an expert commercial real estate broker assistant. Your task is to score 
how well a property matches a client's requirements.

Analyze the property information and client requirements, then provide a score 
from 0 to 10 where:
- 0-3: Poor match (major misalignment)
- 4-5: Fair match (some alignment, significant gaps)
- 6-7: Good match (solid alignment, minor gaps)
- 8-9: Excellent match (strong alignment)
- 10: Perfect match (meets all requirements)

Return your analysis in JSON format:
{
  "score": float (0.0 to 10.0),
  "explanation": string (2-3 sentences explaining the score),
  "strengths": array of strings (what matches well),
  "weaknesses": array of strings (what doesn't match or is missing),
  "confidence": float (0.0 to 1.0, your confidence in this assessment)
}

Consider:
- Location requirements (near subway, specific area, etc.)
- Capacity requirements (number of people)
- Property type requirements (office, retail, warehouse)
- Budget constraints
- Amenities and features needed
- Condition and renovation requirements
```

**User Prompt (Property + Requirements):**
```
Property Information:
Property Name: Downtown Office Tower
Address: 123 Main St, New York, NY
City: New York
State: NY

Extracted Features:
- Near Subway: Yes
- Needs Renovation: No
- Parking: Available
- Elevator: Yes
- Estimated Capacity: 25 people
- Recommended Use: office
- Condition Rating: 4/5
- Amenities: conference room, kitchen, fiber internet

Client Requirements:
Client is looking for an office near the subway, budget up to $50k/month, 
for 15–20 people, preferably in a central area.

Please score this property (0-10) based on how well it matches the client's 
requirements and provide a detailed explanation in JSON format.
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

#### 3d. Parse and Format Response

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
    'longitude' => -74.0060
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

**Final API Response:**
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
      "confidence": 0.87,
      "latitude": 40.7128,
      "longitude": -74.0060
    },
    {
      "property_id": 5,
      "property_name": "Midtown Business Center",
      "address": "789 Park Ave, New York, NY",
      "score": 7.8,
      "explanation": "Good match with strong location and capacity...",
      "strengths": [...],
      "weaknesses": [...],
      "confidence": 0.82
    }
    // ... more properties up to the limit
  ],
  "total_properties": 10,
  "results_shown": 5,
  "client_requirements": "Client is looking for an office near the subway, budget up to $50k/month, for 15–20 people, preferably in a central area."
}
```

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

## Integration with Feature Extraction

The scoring system is more powerful when combined with feature extraction:

### Without Feature Extraction

If features haven't been extracted yet:

```
Property Information:
Property Name: Downtown Office Tower
Address: 123 Main St, New York, NY
City: New York
State: NY

Note: No AI-extracted features available for this property yet.
```

**Impact:**
- AI can only score based on name and address
- Less accurate scoring (confidence ~0.5-0.6)
- More generic explanations

### With Feature Extraction

After running feature extraction:

```
Property Information:
Property Name: Downtown Office Tower
Address: 123 Main St, New York, NY
City: New York
State: NY

Extracted Features:
- Near Subway: Yes
- Needs Renovation: No
- Parking: Available
- Elevator: Yes
- Estimated Capacity: 25 people
- Recommended Use: office
- Condition Rating: 4/5
- Amenities: conference room, kitchen, fiber internet
```

**Impact:**
- Much more accurate scoring (confidence ~0.8-0.95)
- Specific, detailed explanations
- Better matching of requirements to features

### Recommended Workflow

```
1. Add property to system
   ↓
2. Add notes with details
   ↓
3. Run feature extraction (POST /api/extract_features.php)
   ↓
4. Run property scoring (POST /api/score_properties.php)
   ↓
5. Get highly accurate, explainable scores
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
