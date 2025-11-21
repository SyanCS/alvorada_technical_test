<?php

namespace App\Repositories;

use App\Config\Database;
use App\Models\Note;
use PDOException;

/**
 * Note Repository
 * Handles all database operations for notes
 * Implements Repository Pattern for data access
 */
class NoteRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find note by ID
     */
    public function findById(int $id): ?Note
    {
        try {
            $query = "SELECT * FROM notes WHERE id = :id LIMIT 1";
            $result = $this->db->fetchOne($query, ['id' => $id]);

            return $result ? new Note($result) : null;
        } catch (PDOException $e) {
            error_log("NoteRepository::findById Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Find all notes for a property
     */
    public function findByPropertyId(int $propertyId): array
    {
        try {
            $query = "SELECT * FROM notes WHERE property_id = :property_id ORDER BY created_at DESC";
            $results = $this->db->fetchAll($query, ['property_id' => $propertyId]);

            $notes = [];
            foreach ($results as $result) {
                $notes[] = new Note($result);
            }

            return $notes;
        } catch (PDOException $e) {
            error_log("NoteRepository::findByPropertyId Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create new note
     */
    public function create(Note $note): Note
    {
        try {
            $query = "INSERT INTO notes (property_id, note) VALUES (:property_id, :note)";
            
            $params = [
                'property_id' => $note->getPropertyId(),
                'note' => $note->getNote()
            ];

            $id = $this->db->insert($query, $params);
            $note->setId($id);

            return $note;
        } catch (PDOException $e) {
            error_log("NoteRepository::create Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update existing note
     */
    public function update(Note $note): bool
    {
        try {
            $query = "UPDATE notes SET note = :note WHERE id = :id";
            
            $params = [
                'id' => $note->getId(),
                'note' => $note->getNote()
            ];

            $affectedRows = $this->db->execute($query, $params);
            return $affectedRows > 0;
        } catch (PDOException $e) {
            error_log("NoteRepository::update Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete note
     */
    public function delete(int $id): bool
    {
        try {
            $query = "DELETE FROM notes WHERE id = :id";
            $affectedRows = $this->db->execute($query, ['id' => $id]);
            return $affectedRows > 0;
        } catch (PDOException $e) {
            error_log("NoteRepository::delete Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete all notes for a property
     */
    public function deleteByPropertyId(int $propertyId): bool
    {
        try {
            $query = "DELETE FROM notes WHERE property_id = :property_id";
            $this->db->execute($query, ['property_id' => $propertyId]);
            return true;
        } catch (PDOException $e) {
            error_log("NoteRepository::deleteByPropertyId Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Count notes for a property
     */
    public function countByPropertyId(int $propertyId): int
    {
        try {
            $query = "SELECT COUNT(*) as total FROM notes WHERE property_id = :property_id";
            $result = $this->db->fetchOne($query, ['property_id' => $propertyId]);
            return (int) $result['total'];
        } catch (PDOException $e) {
            error_log("NoteRepository::countByPropertyId Error: " . $e->getMessage());
            throw $e;
        }
    }
}


