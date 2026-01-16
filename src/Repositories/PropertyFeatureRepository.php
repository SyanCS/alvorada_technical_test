<?php

namespace App\Repositories;

use App\Contracts\DatabaseInterface;
use App\Contracts\PropertyFeatureRepositoryInterface;
use App\Models\PropertyFeature;
use PDO;
use Exception;

/**
 * PropertyFeature Repository
 * Handles data access for property features
 */
class PropertyFeatureRepository implements PropertyFeatureRepositoryInterface
{
    private DatabaseInterface $database;

    public function __construct(DatabaseInterface $database)
    {
        $this->database = $database;
    }

    /**
     * Find property features by property ID
     */
    public function findByPropertyId(int $propertyId): ?PropertyFeature
    {
        $sql = "SELECT * FROM property_features WHERE property_id = :property_id LIMIT 1";
        
        $row = $this->database->fetchOne($sql, ['property_id' => $propertyId]);
        
        if (!$row) {
            return null;
        }
        
        return $this->mapRowToModel($row);
    }

    /**
     * Find by ID
     */
    public function findById(int $id): ?PropertyFeature
    {
        $sql = "SELECT * FROM property_features WHERE id = :id LIMIT 1";
        
        $row = $this->database->fetchOne($sql, ['id' => $id]);
        
        if (!$row) {
            return null;
        }
        
        return $this->mapRowToModel($row);
    }

    /**
     * Get all property features
     */
    public function findAll(int $limit = 100, int $offset = 0): array
    {
        $sql = "SELECT * FROM property_features 
                ORDER BY extracted_at DESC 
                LIMIT $limit OFFSET $offset";
        
        $rows = $this->database->fetchAll($sql);
        
        $features = [];
        foreach ($rows as $row) {
            $features[] = $this->mapRowToModel($row);
        }
        
        return $features;
    }

    /**
     * Get all property features with filters
     */
    public function findAllWithFilters(array $filters = []): array
    {
        $sql = "SELECT * FROM property_features WHERE 1=1";
        $params = [];

        // Add filters dynamically
        if (isset($filters['near_subway'])) {
            $sql .= " AND near_subway = :near_subway";
            $params['near_subway'] = $filters['near_subway'];
        }

        if (isset($filters['needs_renovation'])) {
            $sql .= " AND needs_renovation = :needs_renovation";
            $params['needs_renovation'] = $filters['needs_renovation'];
        }

        if (isset($filters['parking_available'])) {
            $sql .= " AND parking_available = :parking_available";
            $params['parking_available'] = $filters['parking_available'];
        }

        if (isset($filters['has_elevator'])) {
            $sql .= " AND has_elevator = :has_elevator";
            $params['has_elevator'] = $filters['has_elevator'];
        }

        if (isset($filters['recommended_use'])) {
            $sql .= " AND recommended_use = :recommended_use";
            $params['recommended_use'] = $filters['recommended_use'];
        }

        if (isset($filters['min_capacity'])) {
            $sql .= " AND estimated_capacity_people >= :min_capacity";
            $params['min_capacity'] = $filters['min_capacity'];
        }

        if (isset($filters['max_capacity'])) {
            $sql .= " AND estimated_capacity_people <= :max_capacity";
            $params['max_capacity'] = $filters['max_capacity'];
        }

        $sql .= " ORDER BY extracted_at DESC";

        $rows = $this->database->fetchAll($sql, $params);
        
        $features = [];
        foreach ($rows as $row) {
            $features[] = $this->mapRowToModel($row);
        }
        
        return $features;
    }

    /**
     * Create new property features
     */
    public function create(PropertyFeature $feature): PropertyFeature
    {
        $sql = "INSERT INTO property_features (
                    property_id, near_subway, needs_renovation, parking_available,
                    has_elevator, estimated_capacity_people, floor_level,
                    condition_rating, recommended_use, amenities,
                    confidence_score, source_notes_count, raw_ai_response
                ) VALUES (
                    :property_id, :near_subway, :needs_renovation, :parking_available,
                    :has_elevator, :estimated_capacity_people, :floor_level,
                    :condition_rating, :recommended_use, :amenities,
                    :confidence_score, :source_notes_count, :raw_ai_response
                ) RETURNING id, extracted_at, updated_at";

        $params = [
            'property_id' => $feature->getPropertyId(),
            'near_subway' => $this->boolToDb($feature->getNearSubway()),
            'needs_renovation' => $this->boolToDb($feature->getNeedsRenovation()),
            'parking_available' => $this->boolToDb($feature->getParkingAvailable()),
            'has_elevator' => $this->boolToDb($feature->getHasElevator()),
            'estimated_capacity_people' => $feature->getEstimatedCapacityPeople(),
            'floor_level' => $feature->getFloorLevel(),
            'condition_rating' => $feature->getConditionRating(),
            'recommended_use' => $feature->getRecommendedUse(),
            'amenities' => $this->arrayToJson($feature->getAmenities()),
            'confidence_score' => $feature->getConfidenceScore(),
            'source_notes_count' => $feature->getSourceNotesCount(),
            'raw_ai_response' => $this->arrayToJson($feature->getRawAiResponse()),
        ];
        
        $stmt = $this->database->query($sql, $params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $feature->setId($row['id']);
        $feature->setExtractedAt($row['extracted_at']);
        $feature->setUpdatedAt($row['updated_at']);

        return $feature;
    }

    /**
     * Update existing property features
     */
    public function update(PropertyFeature $feature): bool
    {
        $sql = "UPDATE property_features SET
                    near_subway = :near_subway,
                    needs_renovation = :needs_renovation,
                    parking_available = :parking_available,
                    has_elevator = :has_elevator,
                    estimated_capacity_people = :estimated_capacity_people,
                    floor_level = :floor_level,
                    condition_rating = :condition_rating,
                    recommended_use = :recommended_use,
                    amenities = :amenities,
                    confidence_score = :confidence_score,
                    source_notes_count = :source_notes_count,
                    raw_ai_response = :raw_ai_response
                WHERE property_id = :property_id";

        $params = [
            'property_id' => $feature->getPropertyId(),
            'near_subway' => $this->boolToDb($feature->getNearSubway()),
            'needs_renovation' => $this->boolToDb($feature->getNeedsRenovation()),
            'parking_available' => $this->boolToDb($feature->getParkingAvailable()),
            'has_elevator' => $this->boolToDb($feature->getHasElevator()),
            'estimated_capacity_people' => $feature->getEstimatedCapacityPeople(),
            'floor_level' => $feature->getFloorLevel(),
            'condition_rating' => $feature->getConditionRating(),
            'recommended_use' => $feature->getRecommendedUse(),
            'amenities' => $this->arrayToJson($feature->getAmenities()),
            'confidence_score' => $feature->getConfidenceScore(),
            'source_notes_count' => $feature->getSourceNotesCount(),
            'raw_ai_response' => $this->arrayToJson($feature->getRawAiResponse()),
        ];
        
        $affectedRows = $this->database->execute($sql, $params);
        return $affectedRows > 0;
    }

    /**
     * Delete property features by ID
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM property_features WHERE id = :id";
        $affectedRows = $this->database->execute($sql, ['id' => $id]);
        return $affectedRows > 0;
    }

    /**
     * Delete property features by property ID
     */
    public function deleteByPropertyId(int $propertyId): bool
    {
        $sql = "DELETE FROM property_features WHERE property_id = :property_id";
        $affectedRows = $this->database->execute($sql, ['property_id' => $propertyId]);
        return $affectedRows > 0;
    }

    /**
     * Check if property has features extracted
     */
    public function exists(int $propertyId): bool
    {
        $sql = "SELECT COUNT(*) as count FROM property_features WHERE property_id = :property_id";
        $row = $this->database->fetchOne($sql, ['property_id' => $propertyId]);
        return $row && $row['count'] > 0;
    }

    /**
     * Count total property features
     */
    public function count(): int
    {
        $sql = "SELECT COUNT(*) as count FROM property_features";
        $row = $this->database->fetchOne($sql);
        return $row ? (int) $row['count'] : 0;
    }

    /**
     * Map database row to PropertyFeature model
     */
    private function mapRowToModel(array $row): PropertyFeature
    {
        $feature = new PropertyFeature();
        
        $feature->setId((int) $row['id']);
        $feature->setPropertyId((int) $row['property_id']);
        $feature->setNearSubway($this->dbToBool($row['near_subway']));
        $feature->setNeedsRenovation($this->dbToBool($row['needs_renovation']));
        $feature->setParkingAvailable($this->dbToBool($row['parking_available']));
        $feature->setHasElevator($this->dbToBool($row['has_elevator']));
        $feature->setEstimatedCapacityPeople($row['estimated_capacity_people'] ? (int) $row['estimated_capacity_people'] : null);
        $feature->setFloorLevel($row['floor_level'] ? (int) $row['floor_level'] : null);
        $feature->setConditionRating($row['condition_rating'] ? (int) $row['condition_rating'] : null);
        $feature->setRecommendedUse($row['recommended_use']);
        $feature->setAmenities($this->jsonToArray($row['amenities']));
        $feature->setConfidenceScore($row['confidence_score'] ? (float) $row['confidence_score'] : null);
        $feature->setSourceNotesCount((int) $row['source_notes_count']);
        $feature->setRawAiResponse($this->jsonToArray($row['raw_ai_response']));
        $feature->setExtractedAt($row['extracted_at']);
        $feature->setUpdatedAt($row['updated_at']);

        return $feature;
    }

    /**
     * Convert boolean to database format
     */
    private function boolToDb(?bool $value): ?string
    {
        if ($value === null) {
            return null;
        }
        return $value ? 'true' : 'false';
    }

    /**
     * Convert database value to boolean
     */
    private function dbToBool($value): ?bool
    {
        if ($value === null) {
            return null;
        }
        return $value === 't' || $value === 'true' || $value === true;
    }

    /**
     * Convert array to JSON string for database
     */
    private function arrayToJson(?array $value): ?string
    {
        if ($value === null) {
            return null;
        }
        return json_encode($value);
    }

    /**
     * Convert JSON string to array
     */
    private function jsonToArray(?string $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : null;
    }
}
