<?php

namespace App\Contracts;

use App\Models\Note;

/**
 * Note Repository Interface
 * Contract for note repository implementations
 */
interface NoteRepositoryInterface extends RepositoryInterface
{
    /**
     * Find all notes for a property
     */
    public function findByPropertyId(int $propertyId): array;

    /**
     * Create new note
     */
    public function create(Note $note): Note;

    /**
     * Update existing note
     */
    public function update(Note $note): bool;

    /**
     * Delete all notes for a property
     */
    public function deleteByPropertyId(int $propertyId): bool;

    /**
     * Count notes for a property
     */
    public function countByPropertyId(int $propertyId): int;
}

