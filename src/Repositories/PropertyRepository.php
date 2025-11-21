<?php

namespace App\Repositories;

use App\Config\Database;
use App\Models\Property;
use PDOException;

/**
 * Property Repository
 * Handles all database operations for properties
 * Implements Repository Pattern for data access
 */
class PropertyRepository
{
    private Database $db;
    private NoteRepository $noteRepository;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->noteRepository = new NoteRepository();
    }

    /**
     * Find property by ID
     */
    public function findById(int $id): ?Property
    {
        try {
            $query = "SELECT * FROM properties WHERE id = :id LIMIT 1";
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
     * Find all properties
     */
    public function findAll(int $limit = 100, int $offset = 0): array
    {
        try {
            $query = "SELECT * FROM properties ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
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
     * Create new property
     */
    public function create(Property $property): Property
    {
        try {
            $query = "INSERT INTO properties (name, address, latitude, longitude, extra_field) 
                      VALUES (:name, :address, :latitude, :longitude, :extra_field)";
            
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
     * Update existing property
     */
    public function update(Property $property): bool
    {
        try {
            $query = "UPDATE properties 
                      SET name = :name, 
                          address = :address, 
                          latitude = :latitude, 
                          longitude = :longitude, 
                          extra_field = :extra_field 
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
}


