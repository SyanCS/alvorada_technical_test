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
            // Call Gemini API
            $result = $this->geminiService->extractStructuredData(
                $systemPrompt,
                $userPrompt,
                ['temperature' => 0.4, 'max_tokens' => 600]
            );

            return $this->parseScoreResponse($result['data'], $property);

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
You are an expert commercial real estate broker assistant. Your task is to score how well a property matches a client's requirements.

Analyze the property information and client requirements, then provide a score from 0 to 10 where:
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

Be practical and business-focused. A property doesn't need to be perfect to score well.
Return valid JSON only.
PROMPT;
    }

    /**
     * Build user prompt with property and requirements
     */
    private function buildScoringUserPrompt(Property $property, ?PropertyFeature $features, string $clientRequirements): string
    {
        $propertyInfo = "Property Name: {$property->getName()}\n";
        $propertyInfo .= "Address: {$property->getAddress()}\n";

        // Add location info if available
        $extraField = $property->getExtraField();
        if (is_array($extraField)) {
            if (isset($extraField['city'])) {
                $propertyInfo .= "City: {$extraField['city']}\n";
            }
            if (isset($extraField['state'])) {
                $propertyInfo .= "State: {$extraField['state']}\n";
            }
        }

        // Add extracted features if available
        if ($features) {
            $propertyInfo .= "\nExtracted Features:\n";
            
            if ($features->getNearSubway() !== null) {
                $propertyInfo .= "- Near Subway: " . ($features->getNearSubway() ? "Yes" : "No") . "\n";
            }
            
            if ($features->getNeedsRenovation() !== null) {
                $propertyInfo .= "- Needs Renovation: " . ($features->getNeedsRenovation() ? "Yes" : "No") . "\n";
            }
            
            if ($features->getParkingAvailable() !== null) {
                $propertyInfo .= "- Parking: " . ($features->getParkingAvailable() ? "Available" : "Not available") . "\n";
            }
            
            if ($features->getHasElevator() !== null) {
                $propertyInfo .= "- Elevator: " . ($features->getHasElevator() ? "Yes" : "No") . "\n";
            }
            
            if ($features->getEstimatedCapacityPeople() !== null) {
                $propertyInfo .= "- Estimated Capacity: {$features->getEstimatedCapacityPeople()} people\n";
            }
            
            if ($features->getRecommendedUse() !== null) {
                $propertyInfo .= "- Recommended Use: {$features->getRecommendedUse()}\n";
            }
            
            if ($features->getConditionRating() !== null) {
                $propertyInfo .= "- Condition Rating: {$features->getConditionRating()}/5\n";
            }
            
            if ($features->getAmenities() !== null && !empty($features->getAmenities())) {
                $propertyInfo .= "- Amenities: " . implode(", ", $features->getAmenities()) . "\n";
            }
        } else {
            $propertyInfo .= "\nNote: No AI-extracted features available for this property yet.\n";
        }

        return <<<PROMPT
Property Information:
{$propertyInfo}

Client Requirements:
{$clientRequirements}

Please score this property (0-10) based on how well it matches the client's requirements and provide a detailed explanation in JSON format.
PROMPT;
    }

    /**
     * Parse AI score response
     */
    private function parseScoreResponse(array $data, Property $property): array
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

        return [
            'property_id' => $property->getId(),
            'property_name' => $property->getName(),
            'address' => $property->getAddress(),
            'score' => round($score, 1),
            'explanation' => $explanation,
            'strengths' => $strengths,
            'weaknesses' => $weaknesses,
            'confidence' => round($confidence, 2),
            'latitude' => $property->getLatitude(),
            'longitude' => $property->getLongitude()
        ];
    }
}
