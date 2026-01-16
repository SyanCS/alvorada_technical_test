<?php

namespace App\Models;

/**
 * PropertyFeature Model
 * Represents AI-extracted structured features from property notes
 */
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

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPropertyId(): int
    {
        return $this->propertyId;
    }

    public function getNearSubway(): ?bool
    {
        return $this->nearSubway;
    }

    public function getNeedsRenovation(): ?bool
    {
        return $this->needsRenovation;
    }

    public function getParkingAvailable(): ?bool
    {
        return $this->parkingAvailable;
    }

    public function getHasElevator(): ?bool
    {
        return $this->hasElevator;
    }

    public function getEstimatedCapacityPeople(): ?int
    {
        return $this->estimatedCapacityPeople;
    }

    public function getFloorLevel(): ?int
    {
        return $this->floorLevel;
    }

    public function getConditionRating(): ?int
    {
        return $this->conditionRating;
    }

    public function getRecommendedUse(): ?string
    {
        return $this->recommendedUse;
    }

    public function getAmenities(): ?array
    {
        return $this->amenities;
    }

    public function getConfidenceScore(): ?float
    {
        return $this->confidenceScore;
    }

    public function getSourceNotesCount(): int
    {
        return $this->sourceNotesCount;
    }

    public function getRawAiResponse(): ?array
    {
        return $this->rawAiResponse;
    }

    public function getExtractedAt(): ?string
    {
        return $this->extractedAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    // Setters
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setPropertyId(int $propertyId): self
    {
        $this->propertyId = $propertyId;
        return $this;
    }

    public function setNearSubway(?bool $nearSubway): self
    {
        $this->nearSubway = $nearSubway;
        return $this;
    }

    public function setNeedsRenovation(?bool $needsRenovation): self
    {
        $this->needsRenovation = $needsRenovation;
        return $this;
    }

    public function setParkingAvailable(?bool $parkingAvailable): self
    {
        $this->parkingAvailable = $parkingAvailable;
        return $this;
    }

    public function setHasElevator(?bool $hasElevator): self
    {
        $this->hasElevator = $hasElevator;
        return $this;
    }

    public function setEstimatedCapacityPeople(?int $estimatedCapacityPeople): self
    {
        $this->estimatedCapacityPeople = $estimatedCapacityPeople;
        return $this;
    }

    public function setFloorLevel(?int $floorLevel): self
    {
        $this->floorLevel = $floorLevel;
        return $this;
    }

    public function setConditionRating(?int $conditionRating): self
    {
        $this->conditionRating = $conditionRating;
        return $this;
    }

    public function setRecommendedUse(?string $recommendedUse): self
    {
        $this->recommendedUse = $recommendedUse;
        return $this;
    }

    public function setAmenities(?array $amenities): self
    {
        $this->amenities = $amenities;
        return $this;
    }

    public function setConfidenceScore(?float $confidenceScore): self
    {
        $this->confidenceScore = $confidenceScore;
        return $this;
    }

    public function setSourceNotesCount(int $sourceNotesCount): self
    {
        $this->sourceNotesCount = $sourceNotesCount;
        return $this;
    }

    public function setRawAiResponse(?array $rawAiResponse): self
    {
        $this->rawAiResponse = $rawAiResponse;
        return $this;
    }

    public function setExtractedAt(string $extractedAt): self
    {
        $this->extractedAt = $extractedAt;
        return $this;
    }

    public function setUpdatedAt(string $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Convert model to array for JSON serialization
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'property_id' => $this->propertyId,
            'near_subway' => $this->nearSubway,
            'needs_renovation' => $this->needsRenovation,
            'parking_available' => $this->parkingAvailable,
            'has_elevator' => $this->hasElevator,
            'estimated_capacity_people' => $this->estimatedCapacityPeople,
            'floor_level' => $this->floorLevel,
            'condition_rating' => $this->conditionRating,
            'recommended_use' => $this->recommendedUse,
            'amenities' => $this->amenities,
            'confidence_score' => $this->confidenceScore,
            'source_notes_count' => $this->sourceNotesCount,
            'extracted_at' => $this->extractedAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    /**
     * Get features as a readable summary
     */
    public function getSummary(): array
    {
        $features = [];

        if ($this->nearSubway !== null) {
            $features[] = $this->nearSubway ? 'Near subway' : 'Not near subway';
        }

        if ($this->needsRenovation !== null) {
            $features[] = $this->needsRenovation ? 'Needs renovation' : 'No renovation needed';
        }

        if ($this->parkingAvailable !== null) {
            $features[] = $this->parkingAvailable ? 'Parking available' : 'No parking';
        }

        if ($this->hasElevator !== null) {
            $features[] = $this->hasElevator ? 'Has elevator' : 'No elevator';
        }

        if ($this->estimatedCapacityPeople !== null) {
            $features[] = "Capacity: {$this->estimatedCapacityPeople} people";
        }

        if ($this->recommendedUse !== null) {
            $features[] = "Best for: {$this->recommendedUse}";
        }

        if ($this->conditionRating !== null) {
            $features[] = "Condition: {$this->conditionRating}/5";
        }

        return $features;
    }
}
