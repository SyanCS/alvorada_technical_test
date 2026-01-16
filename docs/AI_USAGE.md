# AI Features Usage Guide

This guide explains how to use the AI-powered features in the Alvorada Property Research System.

---

## Table of Contents

- [Overview](#overview)
- [Getting Started](#getting-started)
- [Feature 1: Extracting Structured Data from Notes](#feature-1-extracting-structured-data-from-notes)
- [Feature 2: Property Scoring](#feature-2-property-scoring)
- [API Reference](#api-reference)
- [Configuration](#configuration)
- [Testing](#testing)
- [Troubleshooting](#troubleshooting)
- [Cost Management](#cost-management)

---

## Overview

The system includes two AI-powered features:

1. **Feature Extraction**: Transforms unstructured property notes into structured data
2. **Property Scoring**: Ranks properties (0-10) based on client requirements

Both features use OpenAI's GPT models for intelligent text analysis.

---

## Getting Started

### 1. Get an OpenAI API Key

1. Visit [OpenAI Platform](https://platform.openai.com/api-keys)
2. Sign up or log in
3. Navigate to API Keys section
4. Click "Create new secret key"
5. Copy the key (starts with `sk-`)

### 2. Configure the API Key

Add your API key to the `.env` file:

```bash
# Copy example environment file
cp env.example .env

# Edit .env and add your API key
OPENAI_API_KEY=sk-your-api-key-here
OPENAI_MODEL=gpt-3.5-turbo
OPENAI_TEMPERATURE=0.3
OPENAI_MAX_TOKENS=1000
```

### 3. Run Database Migration

The AI features require a new table for storing extracted features:

```bash
# Connect to database container
docker exec -i alvorada_db psql -U alvorada_user -d alvorada_db < sql/migrations/001_add_property_features.sql

# Verify table was created
docker exec -it alvorada_db psql -U alvorada_user -d alvorada_db -c "\d property_features"
```

### 4. Test the Features

Run the test script to verify everything is working:

```bash
php scripts/test_ai_features.php
```

---

## Feature 1: Extracting Structured Data from Notes

### Purpose

Converts free-text property notes into structured, queryable data:

**Input** (unstructured notes):
- "Property is well located, close to subway entrance"
- "Needs minor renovation - painting and fixtures"
- "Good for office with up to 25 people, has elevator"

**Output** (structured features):
```json
{
  "near_subway": true,
  "needs_renovation": true,
  "parking_available": false,
  "has_elevator": true,
  "estimated_capacity_people": 25,
  "recommended_use": "office",
  "confidence_score": 0.92
}
```

### Using the API

**Extract features for a property:**

```bash
curl -X POST http://localhost:8080/api/extract_features.php \
  -H "Content-Type: application/json" \
  -d '{
    "property_id": 1,
    "force_refresh": false
  }'
```

**Response:**

```json
{
  "success": true,
  "message": "Features extracted successfully",
  "property_id": 1,
  "features": {
    "near_subway": true,
    "needs_renovation": true,
    "parking_available": false,
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
  "summary": [
    "Near subway",
    "Needs renovation",
    "Has elevator",
    "Capacity: 25 people",
    "Best for: office",
    "Condition: 4/5"
  ]
}
```

### Retrieving Extracted Features

**Get features without re-extraction:**

```bash
curl http://localhost:8080/api/property_features.php?property_id=1
```

### What Gets Extracted

| Field | Type | Description |
|-------|------|-------------|
| `near_subway` | boolean | Near public transit |
| `needs_renovation` | boolean | Requires renovation work |
| `parking_available` | boolean | Parking availability |
| `has_elevator` | boolean | Has elevator access |
| `estimated_capacity_people` | integer | Number of people it can accommodate |
| `floor_level` | integer | Floor number |
| `condition_rating` | integer | Condition (1=poor, 5=excellent) |
| `recommended_use` | string | Best use (office, retail, warehouse, etc.) |
| `amenities` | array | List of amenities |
| `confidence_score` | float | AI confidence (0.0-1.0) |

---

## Feature 2: Property Scoring

### Purpose

Scores and ranks properties (0-10) based on how well they match client requirements.

### Using the API

**Score all properties:**

```bash
curl -X POST http://localhost:8080/api/score_properties.php \
  -H "Content-Type: application/json" \
  -d '{
    "requirements": "Looking for office near subway, 20-30 people, parking available, good condition",
    "limit": 5
  }'
```

**Response:**

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
      "explanation": "Excellent match for office space. Near subway with good capacity and parking. Minor renovation needs.",
      "strengths": [
        "Near subway (2 blocks)",
        "Perfect capacity (25 people)",
        "Parking garage available",
        "Good condition (4/5)"
      ],
      "weaknesses": [
        "Needs minor renovation",
        "5th floor walkup if elevator breaks"
      ],
      "confidence": 0.89,
      "latitude": 40.7128,
      "longitude": -74.0060
    },
    {
      "property_id": 3,
      "property_name": "Tech Hub Plaza",
      "address": "456 Tech Ave, San Francisco, CA",
      "score": 7.2,
      "explanation": "Good office space but parking is limited...",
      "strengths": [...],
      "weaknesses": [...],
      "confidence": 0.82
    }
  ],
  "total_properties": 10,
  "results_shown": 5,
  "client_requirements": "Looking for office near subway..."
}
```

### Score Interpretation

| Score Range | Match Quality | Description |
|-------------|---------------|-------------|
| 9.0 - 10.0 | Excellent | Meets all or nearly all requirements |
| 7.0 - 8.9 | Good | Strong match with minor gaps |
| 5.0 - 6.9 | Fair | Partial match, some misalignment |
| 3.0 - 4.9 | Poor | Significant gaps in requirements |
| 0.0 - 2.9 | Very Poor | Major misalignment |

### Writing Effective Requirements

**Good examples:**

✅ "Office space for 20-30 people near subway, parking needed, modern condition, $40-50k budget"

✅ "Retail location in high-traffic area, ground floor, 2000+ sqft, central district"

✅ "Warehouse with loading dock, 10,000 sqft minimum, industrial area, truck access"

**Less effective:**

❌ "Nice office" (too vague)

❌ "Something cheap" (no specific criteria)

---

## API Reference

### POST /api/extract_features.php

Extract structured features from property notes.

**Request:**
```json
{
  "property_id": 1,
  "force_refresh": false  // optional, default: false
}
```

**Response:** 200 OK
```json
{
  "success": true,
  "message": "Features extracted successfully",
  "property_id": 1,
  "features": { ... },
  "summary": [ ... ]
}
```

**Errors:**
- `400` - Invalid property_id or missing parameter
- `404` - Property not found
- `500` - Extraction failed

---

### POST /api/score_properties.php

Score properties based on client requirements.

**Request:**
```json
{
  "requirements": "office near subway, 20 people, parking",
  "limit": 10  // optional, default: all properties
}
```

**Response:** 200 OK
```json
{
  "success": true,
  "scored_properties": [ ... ],
  "total_properties": 10,
  "results_shown": 10,
  "client_requirements": "..."
}
```

**Errors:**
- `400` - Missing or empty requirements
- `500` - Scoring failed

---

### GET /api/property_features.php

Get extracted features for a property.

**Query Parameters:**
- `property_id` (required): Property ID

**Response:** 200 OK
```json
{
  "success": true,
  "property_id": 1,
  "features": { ... },
  "summary": [ ... ],
  "has_features": true
}
```

---

## Configuration

### Model Selection

Edit `.env` to change the AI model:

```bash
# Fast and cheap (recommended for testing)
OPENAI_MODEL=gpt-3.5-turbo

# More accurate but slower and more expensive
OPENAI_MODEL=gpt-4-turbo-preview
```

### Temperature Setting

Controls randomness (0.0 = deterministic, 1.0 = creative):

```bash
# Recommended for factual extraction
OPENAI_TEMPERATURE=0.3

# For more creative descriptions
OPENAI_TEMPERATURE=0.7
```

### Token Limits

Maximum tokens per API call:

```bash
# Default (sufficient for most cases)
OPENAI_MAX_TOKENS=1000

# For longer responses
OPENAI_MAX_TOKENS=2000
```

---

## Testing

### Test Script

Run the comprehensive test:

```bash
php scripts/test_ai_features.php
```

This will:
1. Check for properties with notes
2. Extract features from a test property
3. Score all properties against sample requirements
4. Display results with color-coded output

### Manual API Testing

**1. Add a property with notes:**

```bash
# Add property via web interface at http://localhost:8080
# Then add notes via property details page
```

**2. Extract features:**

```bash
curl -X POST http://localhost:8080/api/extract_features.php \
  -H "Content-Type: application/json" \
  -d '{"property_id": 1}'
```

**3. Score properties:**

```bash
curl -X POST http://localhost:8080/api/score_properties.php \
  -H "Content-Type: application/json" \
  -d '{"requirements": "office space for 20 people near subway"}'
```

---

## Troubleshooting

### "OpenAI API key not configured"

**Solution:** Add your API key to `.env`:
```bash
OPENAI_API_KEY=sk-your-key-here
```

### "No notes found for property"

**Solution:** Add notes to the property first:
```bash
# Via API:
curl -X POST http://localhost:8080/api/add_note.php \
  -H "Content-Type: application/json" \
  -d '{"property_id": 1, "note": "Near subway station"}'

# Or via web interface at property details page
```

### "Failed to parse AI response as JSON"

**Causes:**
- API key invalid or expired
- Rate limit exceeded
- Model returned invalid format

**Solution:**
- Verify API key is correct
- Check OpenAI account has credits
- Try again after a few seconds

### Mock Data Being Used

If you see lower confidence scores (0.5-0.6) and "mock" in responses:

**Cause:** OpenAI API key not configured

**Solution:** System falls back to keyword-based extraction. Configure API key for full AI features.

---

## Cost Management

### Estimated Costs (OpenAI Pricing)

**GPT-3.5-turbo:**
- Feature extraction: ~$0.002 per property
- Property scoring: ~$0.001 per query
- **100 properties + 100 queries ≈ $0.30**

**GPT-4-turbo:**
- Feature extraction: ~$0.02 per property
- Property scoring: ~$0.01 per query
- **100 properties + 100 queries ≈ $3.00**

### Cost Optimization Tips

1. **Cache extracted features** - Don't re-extract unless notes change
2. **Use gpt-3.5-turbo** for most tasks (10x cheaper than GPT-4)
3. **Batch processing** - Extract features for multiple properties at once
4. **Set reasonable limits** - Use the `limit` parameter in scoring

### Monitoring Usage

Check your OpenAI dashboard:
- https://platform.openai.com/usage

Set billing alerts to avoid surprises.

---

## Best Practices

### For Feature Extraction

1. **Add descriptive notes** - More detail = better extraction
2. **Be specific** - "Near 34th St subway" better than "good location"
3. **Include numbers** - "20 people" better than "medium size"
4. **Mention amenities** - "Conference room, kitchen" helps AI extract features

### For Property Scoring

1. **Be specific** - Include capacity, location, budget, features
2. **Use measurable criteria** - "20-30 people" better than "medium office"
3. **Prioritize requirements** - Mention most important factors first
4. **Include context** - "Tech startup" vs "law firm" affects scoring

---

## Advanced Usage

### Integration with Existing Workflow

**Automatic extraction on note creation:**

Modify `api/add_note.php` to trigger extraction:

```php
// After note is created
$note = $noteService->addNote($data);

// Optionally trigger feature extraction
if ($shouldExtractFeatures) {
    $featureService->extractFeaturesFromProperty(
        $note->getPropertyId(), 
        true // force refresh
    );
}
```

### Custom Scoring Logic

Extend `PropertyScoringService` to add business-specific logic:

```php
// Weight certain features higher
if ($features->getNearSubway() && $clientNeedsTransit) {
    $score += 2.0; // Bonus for subway access
}
```

---

## Support

For issues or questions:

1. Check logs: `docker compose logs -f`
2. Review this documentation
3. Test with the provided script: `php scripts/test_ai_features.php`
4. Check OpenAI status: https://status.openai.com

---

## Next Steps

- [ ] Configure OpenAI API key
- [ ] Run database migration
- [ ] Test with sample data
- [ ] Extract features for existing properties
- [ ] Try property scoring with different requirements
- [ ] Integrate into your workflow

**Happy analyzing!** 🚀
