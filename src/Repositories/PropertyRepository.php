<?php

namespace App\Repositories;

use App\Contracts\DatabaseInterface;
use App\Contracts\NoteRepositoryInterface;
use App\Contracts\PropertyRepositoryInterface;
use App\Models\Property;
use PDOException;

/**
 * Property Repository
 * Handles all database operations for properties
 * Implements Repository Pattern with Dependency Injection
 */
class PropertyRepository implements PropertyRepositoryInterface
{
    private DatabaseInterface $db;
    private NoteRepositoryInterface $noteRepository;

    /**
     * Constructor with Dependency Injection
     * 
     * @param DatabaseInterface $db
     * @param NoteRepositoryInterface $noteRepository
     */
    public function __construct(
        DatabaseInterface $db,
        NoteRepositoryInterface $noteRepository
    ) {
        $this->db = $db;
        $this->noteRepository = $noteRepository;
    }

    /**
     * Find property by ID (with PostGIS location)
     */
    public function findById(int $id): ?Property
    {
        try {
            // Extract lat/lon from PostGIS geography type
            $query = "SELECT 
                        id, name, address, 
                        ST_Y(location::geometry) as latitude,
                        ST_X(location::geometry) as longitude,
                        extra_field, created_at, updated_at
                      FROM properties WHERE id = :id LIMIT 1";
            $result = $this->db->fetchOne($query, ['id' => $id]);

            if ($result) {
                $property = new Property($result);
                
                // Load associated notes
                $notes = $this->noteRepository->findByPropertyId($id);
                $property->setNotes($notes);
                
                return $property;
            }

            return null;
        } catch (PDOException $e) {
            error_log("PropertyRepository::findById Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Find all properties (with PostGIS location)
     */
    public function findAll(int $limit = 100, int $offset = 0): array
    {
        try {
            $query = "SELECT 
                        id, name, address,
                        ST_Y(location::geometry) as latitude,
                        ST_X(location::geometry) as longitude,
                        extra_field, created_at, updated_at
                      FROM properties 
                      ORDER BY created_at DESC 
                      LIMIT :limit OFFSET :offset";
            $results = $this->db->fetchAll($query, [
                'limit' => $limit,
                'offset' => $offset
            ]);

            $properties = [];
            foreach ($results as $result) {
                $property = new Property($result);
                $notes = $this->noteRepository->findByPropertyId($property->getId());
                $property->setNotes($notes);
                $properties[] = $property;
            }

            return $properties;
        } catch (PDOException $e) {
            error_log("PropertyRepository::findAll Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create new property with PostGIS location
     */
    public function create(Property $property): Property
    {
        try {
            // Use PostGIS ST_SetSRID and ST_MakePoint for proper geospatial storage
            $query = "INSERT INTO properties (name, address, location, extra_field) 
                      VALUES (:name, :address, ST_SetSRID(ST_MakePoint(:longitude, :latitude), 4326)::geography, :extra_field::jsonb)";
            
            $params = [
                'name' => $property->getName(),
                'address' => $property->getAddress(),
                'latitude' => $property->getLatitude(),
                'longitude' => $property->getLongitude(),
                'extra_field' => $property->getExtraField()
            ];

            $id = $this->db->insert($query, $params);
            $property->setId($id);

            return $property;
        } catch (PDOException $e) {
            error_log("PropertyRepository::create Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update existing property (with PostGIS location)
     */
    public function update(Property $property): bool
    {
        try {
            $query = "UPDATE properties 
                      SET name = :name, 
                          address = :address, 
                          location = ST_SetSRID(ST_MakePoint(:longitude, :latitude), 4326)::geography,
                          extra_field = :extra_field::jsonb
                      WHERE id = :id";
            
            $params = [
                'id' => $property->getId(),
                'name' => $property->getName(),
                'address' => $property->getAddress(),
                'latitude' => $property->getLatitude(),
                'longitude' => $property->getLongitude(),
                'extra_field' => $property->getExtraField()
            ];

            $affectedRows = $this->db->execute($query, $params);
            return $affectedRows > 0;
        } catch (PDOException $e) {
            error_log("PropertyRepository::update Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete property
     */
    public function delete(int $id): bool
    {
        try {
            $query = "DELETE FROM properties WHERE id = :id";
            $affectedRows = $this->db->execute($query, ['id' => $id]);
            return $affectedRows > 0;
        } catch (PDOException $e) {
            error_log("PropertyRepository::delete Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Search properties by address
     */
    public function searchByAddress(string $address): array
    {
        try {
            $query = "SELECT * FROM properties WHERE address LIKE :address ORDER BY created_at DESC";
            $results = $this->db->fetchAll($query, ['address' => "%{$address}%"]);

            $properties = [];
            foreach ($results as $result) {
                $properties[] = new Property($result);
            }

            return $properties;
        } catch (PDOException $e) {
            error_log("PropertyRepository::searchByAddress Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Count total properties
     */
    public function count(): int
    {
        try {
            $query = "SELECT COUNT(*) as total FROM properties";
            $result = $this->db->fetchOne($query);
            return (int) $result['total'];
        } catch (PDOException $e) {
            error_log("PropertyRepository::count Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Find properties within radius (PostGIS spatial query)
     * 
     * @param float $latitude Center latitude
     * @param float $longitude Center longitude
     * @param int $radiusMeters Radius in meters
     * @return array Properties within radius with distance
     */
    public function findWithinRadius(float $latitude, float $longitude, int $radiusMeters): array
    {
        try {
            $query = "SELECT 
                        id, name, address,
                        ST_Y(location::geometry) as latitude,
                        ST_X(location::geometry) as longitude,
                        extra_field, created_at, updated_at,
                        ST_Distance(
                            location,
                            ST_SetSRID(ST_MakePoint(:longitude, :latitude), 4326)::geography
                        ) as distance_meters
                      FROM properties
                      WHERE ST_DWithin(
                        location,
                        ST_SetSRID(ST_MakePoint(:longitude, :latitude), 4326)::geography,
                        :radius
                      )
                      ORDER BY distance_meters";
            
            $results = $this->db->fetchAll($query, [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'radius' => $radiusMeters
            ]);

            $properties = [];
            foreach ($results as $result) {
                $property = new Property($result);
                $properties[] = [
                    'property' => $property,
                    'distance_meters' => (float) $result['distance_meters'],
                    'distance_km' => round((float) $result['distance_meters'] / 1000, 2)
                ];
            }

            return $properties;
        } catch (PDOException $e) {
            error_log("PropertyRepository::findWithinRadius Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Find nearest properties to a location
     * 
     * @param float $latitude
     * @param float $longitude
     * @param int $limit Number of properties to return
     * @return array
     */
    public function findNearest(float $latitude, float $longitude, int $limit = 10): array
    {
        try {
            $query = "SELECT 
                        id, name, address,
                        ST_Y(location::geometry) as latitude,
                        ST_X(location::geometry) as longitude,
                        extra_field, created_at, updated_at,
                        ST_Distance(
                            location,
                            ST_SetSRID(ST_MakePoint(:longitude, :latitude), 4326)::geography
                        ) as distance_meters
                      FROM properties
                      ORDER BY location <-> ST_SetSRID(ST_MakePoint(:longitude, :latitude), 4326)::geography
                      LIMIT :limit";
            
            $results = $this->db->fetchAll($query, [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'limit' => $limit
            ]);

            $properties = [];
            foreach ($results as $result) {
                $property = new Property($result);
                $properties[] = [
                    'property' => $property,
                    'distance_meters' => (float) $result['distance_meters'],
                    'distance_km' => round((float) $result['distance_meters'] / 1000, 2)
                ];
            }

            return $properties;
        } catch (PDOException $e) {
            error_log("PropertyRepository::findNearest Error: " . $e->getMessage());
            throw $e;
        }
    }
}


