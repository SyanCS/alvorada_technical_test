<?php

namespace App\Services;

use App\Contracts\NoteRepositoryInterface;
use App\Contracts\PropertyRepositoryInterface;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Note;
use App\Validators\PropertyValidator;

/**
 * Note Service
 * Handles business logic for notes
 */
class NoteService
{
    private NoteRepositoryInterface $noteRepository;
    private PropertyRepositoryInterface $propertyRepository;
    private PropertyValidator $validator;

    public function __construct(
        NoteRepositoryInterface $noteRepository,
        PropertyRepositoryInterface $propertyRepository,
        PropertyValidator $validator
    ) {
        $this->noteRepository = $noteRepository;
        $this->propertyRepository = $propertyRepository;
        $this->validator = $validator;
    }

    /**
     * Add a note to a property
     * 
     * @param array $data Note data (property_id, note)
     * @return Note Created note
     * @throws ValidationException If validation fails
     * @throws NotFoundException If property doesn't exist
     */
    public function addNote(array $data): Note
    {
        // Validate note data
        if (!$this->validator->validateNote($data)) {
            throw new ValidationException(
                "Validation failed",
                $this->validator->getErrors()
            );
        }

        // Check if property exists
        $property = $this->propertyRepository->findById((int) $data['property_id']);
        if ($property === null) {
            throw new NotFoundException("Property not found");
        }

        // Create note model
        $note = new Note();
        $note->setPropertyId((int) $data['property_id']);
        $note->setNote($data['note']);

        // Save to database
        return $this->noteRepository->create($note);
    }

    /**
     * Get all notes for a property
     * 
     * @param int $propertyId Property ID
     * @return Note[] Array of notes
     */
    public function getNotesByProperty(int $propertyId): array
    {
        return $this->noteRepository->findByPropertyId($propertyId);
    }

    /**
     * Get note by ID
     * 
     * @param int $id Note ID
     * @return Note|null Note or null if not found
     */
    public function getNote(int $id): ?Note
    {
        return $this->noteRepository->findById($id);
    }

    /**
     * Get all notes
     * 
     * @return Note[] Array of all notes
     */
    public function getAllNotes(): array
    {
        return $this->noteRepository->findAll();
    }
}

