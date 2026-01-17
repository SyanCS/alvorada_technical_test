<?php

namespace App\Services;

use App\Contracts\PropertyRepositoryInterface;
use App\Contracts\PropertyFeatureRepositoryInterface;
use App\Models\Property;
use App\Models\PropertyFeature;
use Exception;

/**
 * Property Scoring Service
 * Scores properties based on client requirements using AI
 */
class PropertyScoringService
{
    private GeminiService $geminiService;
    private PropertyRepositoryInterface $propertyRepository;
    private PropertyFeatureRepositoryInterface $featureRepository;

    public function __construct(
        GeminiService $geminiService,
        PropertyRepositoryInterface $propertyRepository,
        PropertyFeatureRepositoryInterface $featureRepository
    ) {
        $this->geminiService = $geminiService;
        $this->propertyRepository = $propertyRepository;
        $this->featureRepository = $featureRepository;
    }

    /**
     * Score all properties based on client requirements
     * 
     * @param string $clientRequirements Free-text client requirements
     * @param int|null $limit Limit number of results
     * @return array Scored and ranked properties
     * @throws Exception If scoring fails
     */
    public function scoreAllProperties(string $clientRequirements, ?int $limit = null): array
    {
        if (empty(trim($clientRequirements))) {
            throw new Exception("Client requirements cannot be empty");
        }

        // Get all properties
        $properties = $this->propertyRepository->findAll(1000, 0);

        if (empty($properties)) {
            return [
                'scored_properties' => [],
                'total_properties' => 0,
                'requirements' => $clientRequirements,
                'message' => 'No properties found in database'
            ];
        }

        // Score each property
        $scoredProperties = [];
        foreach ($properties as $property) {
            try {
                $score = $this->scoreProperty($property, $clientRequirements);
                $scoredProperties[] = $score;
            } catch (Exception $e) {
                error_log("Error scoring property {$property->getId()}: " . $e->getMessage());
                // Continue with other properties
            }
        }

        // Sort by score descending
        usort($scoredProperties, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // Apply limit if specified
        if ($limit !== null && $limit > 0) {
            $scoredProperties = array_slice($scoredProperties, 0, $limit);
        }

        return [
            'scored_properties' => $scoredProperties,
            'total_properties' => count($properties),
            'results_shown' => count($scoredProperties),
            'requirements' => $clientRequirements
        ];
    }

    /**
     * Score a single property against client requirements
     * 
     * @param Property $property Property to score
     * @param string $clientRequirements Client requirements
     * @return array Score data with explanation
     * @throws Exception If scoring fails
     */
    public function scoreProperty(Property $property, string $clientRequirements): array
    {
        // Get extracted features if available
        $features = $this->featureRepository->findByPropertyId($property->getId());

        // Check if Gemini is configured
        if (!$this->geminiService->isConfigured()) {
            throw new Exception("Gemini API key is not configured. Please add GEMINI_API_KEY to your .env file.");
        }

        // Build prompt for AI scoring
        $systemPrompt = $this->buildScoringSystemPrompt();
        $userPrompt = $this->buildScoringUserPrompt($property, $features, $clientRequirements);

        try {
            // Call Gemini API with increased token limit for better explanations
            $result = $this->geminiService->extractStructuredData(
                $systemPrompt,
                $userPrompt,
                ['temperature' => 0.4, 'max_tokens' => 800]
            );

            return $this->parseScoreResponse($result['data'], $property, $features);

        } catch (Exception $e) {
            error_log("Scoring error for property {$property->getId()}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Build system prompt for scoring
     */
    private function buildScoringSystemPrompt(): string
    {
        return <<<PROMPT
You are an expert commercial real estate broker assistant with deep knowledge of property evaluation and client-property matching. Your task is to score how well a property matches a client's requirements using both basic property information and AI-extracted structured features.

## SCORING SCALE
Provide a score from 0 to 10:
- 0-3: Poor match (major misalignment, multiple critical requirements not met)
- 4-5: Fair match (some alignment, significant gaps in key requirements)
- 6-7: Good match (solid alignment, minor gaps or compromises needed)
- 8-9: Excellent match (strong alignment, meets most/all key requirements)
- 10: Perfect match (exceeds or perfectly meets all requirements)

## FEATURE IMPORTANCE WEIGHTS
When extracted features are available, weight them according to relevance:

**HIGH IMPORTANCE (30-40% of score):**
- Location & Transit Access (near_subway, address, city)
- Property Type & Use Case (recommended_use)
- Capacity/Size (estimated_capacity_people)

**MEDIUM IMPORTANCE (20-30% of score):**
- Condition & Readiness (condition_rating, needs_renovation)
- Core Amenities (parking_available, has_elevator, amenities)

**LOWER IMPORTANCE (10-20% of score):**
- Nice-to-have features (floor_level, additional amenities)
- Budget considerations (if mentioned)

## SCORING GUIDELINES

1. **With Extracted Features Available:**
   - Prioritize structured features over just property name/address
   - Use specific feature matches to justify score
   - High confidence features (confidence_score > 0.8) should carry more weight
   - Match recommended_use to client needs very carefully
   - Consider feature completeness (more features = more confident scoring)

2. **Without Extracted Features:**
   - Score conservatively (typically 4-6 range)
   - Base score on property name, address, and general location info
   - Indicate lower confidence (typically 0.4-0.6)
   - Note that feature extraction would improve accuracy

3. **Feature Matching Logic:**
   - near_subway=true + "near transit" requirement = strong match (+1.5 to +2.5 points)
   - capacity matches requirement exactly = strong match (+1.5 to +2.0 points)
   - capacity exceeds requirement by 20-50% = excellent (+2.0 to +2.5 points)
   - recommended_use matches need (office for office, retail for retail) = strong match (+2.0 to +3.0 points)
   - condition_rating 4-5 + "move-in ready" need = strong match (+1.0 to +1.5 points)
   - condition_rating 1-2 or needs_renovation=true + "ready to use" need = major penalty (-2.0 to -3.0 points)

4. **Deal Breakers:**
   - Wrong property type (office vs retail vs warehouse) = max score 3.0
   - Capacity too small by >50% = max score 4.0
   - Critical feature missing (e.g., needs parking, parking=false) = -2.0 to -3.0 points

## OUTPUT FORMAT
Return a valid JSON object (no markdown, no additional text):
{
  "score": float (0.0 to 10.0),
  "explanation": string (2-3 sentences explaining the score with specific feature references),
  "strengths": array of strings (specific features that match well),
  "weaknesses": array of strings (specific features that don't match or are missing),
  "confidence": float (0.0 to 1.0, your confidence in this assessment)
}

## IMPORTANT
- Be specific in explanations - reference actual features when available
- Strengths/weaknesses should cite concrete features (e.g., "Near subway (10 min walk)" not just "good location")
- Confidence should reflect both feature availability and match clarity
- Be practical - partial matches can still score 7-8 if core needs are met
- Return valid JSON only, no markdown formatting

PROMPT;
    }

    /**
     * Build user prompt with property and requirements
     */
    private function buildScoringUserPrompt(Property $property, ?PropertyFeature $features, string $clientRequirements): string
    {
        // Build basic property information
        $propertyInfo = "PROPERTY NAME: {$property->getName()}\n";
        $propertyInfo .= "ADDRESS: {$property->getAddress()}\n";

        // Add location info if available
        $extraField = $property->getExtraField();
        if (is_array($extraField)) {
            if (isset($extraField['city'])) {
                $propertyInfo .= "CITY: {$extraField['city']}\n";
            }
            if (isset($extraField['state'])) {
                $propertyInfo .= "STATE: {$extraField['state']}\n";
            }
        }

        // Add structured extracted features if available
        if ($features) {
            $propertyInfo .= "\n========================================\n";
            $propertyInfo .= "AI-EXTRACTED FEATURES (Structured Data)\n";
            $propertyInfo .= "========================================\n";
            
            // Feature confidence
            if ($features->getConfidenceScore() !== null) {
                $confidence = round($features->getConfidenceScore() * 100);
                $propertyInfo .= "Feature Extraction Confidence: {$confidence}%\n";
            }
            
            $propertyInfo .= "\n--- HIGH IMPORTANCE FEATURES ---\n";
            
            // Location & Transit
            if ($features->getNearSubway() !== null) {
                $value = $features->getNearSubway() ? "✓ YES (within 5-10 min walk)" : "✗ NO";
                $propertyInfo .= "Near Subway/Transit: {$value}\n";
            } else {
                $propertyInfo .= "Near Subway/Transit: UNKNOWN\n";
            }
            
            // Property Type
            if ($features->getRecommendedUse() !== null) {
                $propertyInfo .= "Recommended Use: " . strtoupper($features->getRecommendedUse()) . "\n";
            } else {
                $propertyInfo .= "Recommended Use: UNKNOWN\n";
            }
            
            // Capacity
            if ($features->getEstimatedCapacityPeople() !== null) {
                $propertyInfo .= "Estimated Capacity: {$features->getEstimatedCapacityPeople()} people\n";
            } else {
                $propertyInfo .= "Estimated Capacity: UNKNOWN\n";
            }
            
            $propertyInfo .= "\n--- MEDIUM IMPORTANCE FEATURES ---\n";
            
            // Condition
            if ($features->getConditionRating() !== null) {
                $rating = $features->getConditionRating();
                $ratingDesc = [
                    1 => "Poor/Uninhabitable",
                    2 => "Fair/Needs Work",
                    3 => "Good/Move-in Ready",
                    4 => "Very Good/Recently Updated",
                    5 => "Excellent/Newly Built"
                ];
                $desc = $ratingDesc[$rating] ?? "Unknown";
                $propertyInfo .= "Condition Rating: {$rating}/5 ({$desc})\n";
            } else {
                $propertyInfo .= "Condition Rating: UNKNOWN\n";
            }
            
            // Renovation
            if ($features->getNeedsRenovation() !== null) {
                $value = $features->getNeedsRenovation() ? "✗ YES (requires significant work)" : "✓ NO (ready to use)";
                $propertyInfo .= "Needs Renovation: {$value}\n";
            } else {
                $propertyInfo .= "Needs Renovation: UNKNOWN\n";
            }
            
            // Parking
            if ($features->getParkingAvailable() !== null) {
                $value = $features->getParkingAvailable() ? "✓ AVAILABLE" : "✗ NOT AVAILABLE";
                $propertyInfo .= "Parking: {$value}\n";
            } else {
                $propertyInfo .= "Parking: UNKNOWN\n";
            }
            
            // Elevator
            if ($features->getHasElevator() !== null) {
                $value = $features->getHasElevator() ? "✓ YES" : "✗ NO";
                $propertyInfo .= "Elevator: {$value}\n";
            } else {
                $propertyInfo .= "Elevator: UNKNOWN\n";
            }
            
            // Amenities
            if ($features->getAmenities() !== null && !empty($features->getAmenities())) {
                $amenitiesList = implode(", ", array_map('ucfirst', $features->getAmenities()));
                $amenitiesCount = count($features->getAmenities());
                $propertyInfo .= "Amenities ({$amenitiesCount}): {$amenitiesList}\n";
            } else {
                $propertyInfo .= "Amenities: NONE LISTED\n";
            }
            
            $propertyInfo .= "\n--- LOWER IMPORTANCE FEATURES ---\n";
            
            // Floor Level
            if ($features->getFloorLevel() !== null) {
                $floor = $features->getFloorLevel();
                $floorDesc = $floor == 0 ? "Ground Floor" : "Floor {$floor}";
                $propertyInfo .= "Floor Level: {$floorDesc}\n";
            } else {
                $propertyInfo .= "Floor Level: UNKNOWN\n";
            }
            
            // Total notes analyzed
            if ($features->getSourceNotesCount() !== null) {
                $propertyInfo .= "\nExtracted from {$features->getSourceNotesCount()} property note(s)\n";
            }
            
            $propertyInfo .= "========================================\n";
        } else {
            $propertyInfo .= "\n========================================\n";
            $propertyInfo .= "⚠️  NO EXTRACTED FEATURES AVAILABLE\n";
            $propertyInfo .= "========================================\n";
            $propertyInfo .= "Scoring will be based on property name and address only.\n";
            $propertyInfo .= "Confidence will be lower. Run feature extraction for better results.\n";
            $propertyInfo .= "========================================\n";
        }

        return <<<PROMPT
========================================
PROPERTY DETAILS
========================================
{$propertyInfo}

========================================
CLIENT REQUIREMENTS
========================================
{$clientRequirements}

========================================
SCORING TASK
========================================
Analyze the property features against the client requirements and provide a scored assessment.

Key Instructions:
1. If features are available, weight them according to importance (High/Medium/Low)
2. Match specific features to specific requirements
3. Consider feature extraction confidence in your assessment
4. Be specific in your explanations - cite actual features
5. If no features available, score conservatively (4-6 range) with lower confidence

Return your analysis in JSON format as specified in the system prompt.
PROMPT;
    }

    /**
     * Calculate feature completeness score
     * Returns a score (0-1) indicating how much feature data is available
     */
    private function calculateFeatureCompleteness(?PropertyFeature $features): float
    {
        if (!$features) {
            return 0.0;
        }

        $totalFields = 9; // Total important feature fields
        $filledFields = 0;

        if ($features->getNearSubway() !== null) $filledFields++;
        if ($features->getNeedsRenovation() !== null) $filledFields++;
        if ($features->getParkingAvailable() !== null) $filledFields++;
        if ($features->getHasElevator() !== null) $filledFields++;
        if ($features->getEstimatedCapacityPeople() !== null) $filledFields++;
        if ($features->getFloorLevel() !== null) $filledFields++;
        if ($features->getConditionRating() !== null) $filledFields++;
        if ($features->getRecommendedUse() !== null) $filledFields++;
        if ($features->getAmenities() !== null && !empty($features->getAmenities())) $filledFields++;

        return round($filledFields / $totalFields, 2);
    }

    /**
     * Generate feature summary for response
     */
    private function generateFeatureSummary(?PropertyFeature $features): ?array
    {
        if (!$features) {
            return null;
        }

        $summary = [];
        
        if ($features->getNearSubway() !== null) {
            $summary['near_subway'] = $features->getNearSubway();
        }
        if ($features->getRecommendedUse() !== null) {
            $summary['recommended_use'] = $features->getRecommendedUse();
        }
        if ($features->getEstimatedCapacityPeople() !== null) {
            $summary['capacity_people'] = $features->getEstimatedCapacityPeople();
        }
        if ($features->getConditionRating() !== null) {
            $summary['condition_rating'] = $features->getConditionRating();
        }
        if ($features->getParkingAvailable() !== null) {
            $summary['parking'] = $features->getParkingAvailable();
        }
        if ($features->getAmenities() !== null && !empty($features->getAmenities())) {
            $summary['amenities_count'] = count($features->getAmenities());
        }
        if ($features->getConfidenceScore() !== null) {
            $summary['extraction_confidence'] = round($features->getConfidenceScore(), 2);
        }

        return $summary;
    }

    /**
     * Parse AI score response
     */
    private function parseScoreResponse(array $data, Property $property, ?PropertyFeature $features = null): array
    {
        $score = isset($data['score']) && is_numeric($data['score']) 
            ? (float) $data['score'] 
            : 5.0;

        // Clamp score between 0 and 10
        $score = min(10.0, max(0.0, $score));

        $explanation = isset($data['explanation']) && is_string($data['explanation'])
            ? $data['explanation']
            : 'No explanation provided';

        $strengths = isset($data['strengths']) && is_array($data['strengths'])
            ? $data['strengths']
            : [];

        $weaknesses = isset($data['weaknesses']) && is_array($data['weaknesses'])
            ? $data['weaknesses']
            : [];

        $confidence = isset($data['confidence']) && is_numeric($data['confidence'])
            ? min(1.0, max(0.0, (float) $data['confidence']))
            : 0.7;

        // Calculate feature completeness
        $featureCompleteness = $this->calculateFeatureCompleteness($features);
        
        // Adjust confidence based on feature availability
        if ($featureCompleteness < 0.3) {
            // Low feature data, reduce confidence
            $confidence = min($confidence, 0.6);
        }

        $result = [
            'property_id' => $property->getId(),
            'property_name' => $property->getName(),
            'address' => $property->getAddress(),
            'score' => round($score, 1),
            'explanation' => $explanation,
            'strengths' => $strengths,
            'weaknesses' => $weaknesses,
            'confidence' => round($confidence, 2),
            'latitude' => $property->getLatitude(),
            'longitude' => $property->getLongitude(),
            'feature_completeness' => $featureCompleteness
        ];

        // Add feature summary if available
        $featureSummary = $this->generateFeatureSummary($features);
        if ($featureSummary !== null) {
            $result['features'] = $featureSummary;
        }

        return $result;
    }
}
