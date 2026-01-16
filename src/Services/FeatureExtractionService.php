<?php

namespace App\Services;

use App\Contracts\NoteRepositoryInterface;
use App\Contracts\PropertyRepositoryInterface;
use App\Contracts\PropertyFeatureRepositoryInterface;
use App\Models\PropertyFeature;
use App\Models\Property;
use App\Exceptions\NotFoundException;
use Exception;

/**
 * Feature Extraction Service
 * Uses AI to extract structured features from unstructured property notes
 */
class FeatureExtractionService
{
    private GeminiService $geminiService;
    private PropertyRepositoryInterface $propertyRepository;
    private NoteRepositoryInterface $noteRepository;
    private PropertyFeatureRepositoryInterface $featureRepository;

    public function __construct(
        GeminiService $geminiService,
        PropertyRepositoryInterface $propertyRepository,
        NoteRepositoryInterface $noteRepository,
        PropertyFeatureRepositoryInterface $featureRepository
    ) {
        $this->geminiService = $geminiService;
        $this->propertyRepository = $propertyRepository;
        $this->noteRepository = $noteRepository;
        $this->featureRepository = $featureRepository;
    }

    /**
     * Extract features from all notes for a property
     * 
     * @param int $propertyId Property ID
     * @param bool $forceRefresh Force re-extraction even if features exist
     * @return PropertyFeature Extracted features
     * @throws NotFoundException If property not found
     * @throws Exception If extraction fails
     */
    public function extractFeaturesFromProperty(int $propertyId, bool $forceRefresh = false): PropertyFeature
    {
        // Get property
        $property = $this->propertyRepository->findById($propertyId);
        if (!$property) {
            throw new NotFoundException("Property with ID {$propertyId} not found");
        }

        // Check if features already exist and not forcing refresh
        if (!$forceRefresh && $this->featureRepository->exists($propertyId)) {
            $existing = $this->featureRepository->findByPropertyId($propertyId);
            if ($existing) {
                return $existing;
            }
        }

        // Get all notes for the property
        $notes = $this->noteRepository->findByPropertyId($propertyId);

        if (empty($notes)) {
            throw new Exception("No notes found for property. Add some notes before extracting features.");
        }

        // Check if Gemini is configured
        if (!$this->geminiService->isConfigured()) {
            throw new Exception("Gemini API key is not configured. Please add GEMINI_API_KEY to your .env file.");
        }

        // Build prompt for AI
        $systemPrompt = $this->buildSystemPrompt();
        $userPrompt = $this->buildUserPrompt($property, $notes);

        try {
            // Call Gemini API with JSON mode
            $result = $this->geminiService->extractStructuredData(
                $systemPrompt,
                $userPrompt,
                ['temperature' => 0.3, 'max_tokens' => 800]
            );

            // Parse AI response into PropertyFeature model
            // Handle if AI returns array or object
            $data = $result['data'];
            if (isset($data[0]) && is_array($data[0])) {
                // AI returned an array, take the first element
                $data = $data[0];
            }
            
            $feature = $this->parseAIResponse($data, $property, $notes);
            $feature->setRawAiResponse($data);

            // Save or update in database
            if ($this->featureRepository->exists($propertyId)) {
                $this->featureRepository->update($feature);
            } else {
                $feature = $this->featureRepository->create($feature);
            }

            return $feature;

        } catch (Exception $e) {
            error_log("Feature extraction error for property {$propertyId}: " . $e->getMessage());
            throw new Exception("Failed to extract features: " . $e->getMessage());
        }
    }

    /**
     * Build system prompt for AI
     */
    private function buildSystemPrompt(): string
    {
        return <<<PROMPT
You are an expert commercial real estate analyst. Your task is to analyze property research notes and extract structured information.

Analyze the provided property notes and extract the following information in JSON format:

{
  "near_subway": boolean or null (Is the property near subway/public transit?),
  "needs_renovation": boolean or null (Does the property need renovation?),
  "parking_available": boolean or null (Is parking available?),
  "has_elevator": boolean or null (Does the property have an elevator?),
  "estimated_capacity_people": integer or null (How many people can the property accommodate?),
  "floor_level": integer or null (What floor is the property on?),
  "condition_rating": integer or null (Property condition: 1=poor, 2=fair, 3=good, 4=very good, 5=excellent),
  "recommended_use": string or null (Best use: "office", "retail", "warehouse", "logistics", "mixed", etc.),
  "amenities": array or null (List of amenities mentioned: ["kitchen", "conference room", "gym", etc.]),
  "confidence_score": float (Your confidence in the extraction: 0.0 to 1.0),
  "summary": string (Brief 2-3 sentence summary of key findings)
}

Rules:
- Only set a field if there's clear evidence in the notes
- Use null for uncertain or missing information
- Be conservative with boolean values - only set true/false if clearly stated
- For capacity, look for phrases like "fits 20 people", "suitable for 15-30 employees"
- For recommended_use, consider the context and explicit mentions
- Confidence score should reflect how clear the information is in the notes
- Extract all relevant amenities mentioned
- Return valid JSON only, no additional text

PROMPT;
    }

    /**
     * Build user prompt with property and notes data
     */
    private function buildUserPrompt(Property $property, array $notes): string
    {
        $notesText = "";
        foreach ($notes as $index => $note) {
            $noteNumber = $index + 1;
            $notesText .= "Note {$noteNumber}: {$note->getNote()}\n";
        }

        $notesCount = count($notes);
        $propertyName = $property->getName();
        $propertyAddress = $property->getAddress();

        return <<<PROMPT
Property Information:
- Name: {$propertyName}
- Address: {$propertyAddress}
- Total Notes: {$notesCount}

Research Notes:
{$notesText}

Please analyze these notes and extract structured features in JSON format.
PROMPT;
    }

    /**
     * Parse AI response into PropertyFeature model
     */
    private function parseAIResponse(array $data, Property $property, array $notes): PropertyFeature
    {
        $feature = new PropertyFeature();
        $feature->setPropertyId($property->getId());
        $feature->setSourceNotesCount(count($notes));

        // Extract boolean fields
        if (isset($data['near_subway']) && is_bool($data['near_subway'])) {
            $feature->setNearSubway($data['near_subway']);
        }

        if (isset($data['needs_renovation']) && is_bool($data['needs_renovation'])) {
            $feature->setNeedsRenovation($data['needs_renovation']);
        }

        if (isset($data['parking_available']) && is_bool($data['parking_available'])) {
            $feature->setParkingAvailable($data['parking_available']);
        }

        if (isset($data['has_elevator']) && is_bool($data['has_elevator'])) {
            $feature->setHasElevator($data['has_elevator']);
        }

        // Extract numeric fields
        if (isset($data['estimated_capacity_people']) && is_numeric($data['estimated_capacity_people'])) {
            $feature->setEstimatedCapacityPeople((int) $data['estimated_capacity_people']);
        }

        if (isset($data['floor_level']) && is_numeric($data['floor_level'])) {
            $feature->setFloorLevel((int) $data['floor_level']);
        }

        if (isset($data['condition_rating']) && is_numeric($data['condition_rating'])) {
            $rating = (int) $data['condition_rating'];
            if ($rating >= 1 && $rating <= 5) {
                $feature->setConditionRating($rating);
            }
        }

        // Extract text fields
        if (isset($data['recommended_use']) && is_string($data['recommended_use'])) {
            $feature->setRecommendedUse($data['recommended_use']);
        }

        // Extract amenities array
        if (isset($data['amenities']) && is_array($data['amenities'])) {
            $feature->setAmenities($data['amenities']);
        }

        // Extract confidence score
        if (isset($data['confidence_score']) && is_numeric($data['confidence_score'])) {
            $confidence = (float) $data['confidence_score'];
            $feature->setConfidenceScore(min(1.0, max(0.0, $confidence)));
        }

        return $feature;
    }

    /**
     * Get extracted features for a property (without extraction)
     */
    public function getFeatures(int $propertyId): ?PropertyFeature
    {
        return $this->featureRepository->findByPropertyId($propertyId);
    }

    /**
     * Check if property has features extracted
     */
    public function hasFeatures(int $propertyId): bool
    {
        return $this->featureRepository->exists($propertyId);
    }
}
