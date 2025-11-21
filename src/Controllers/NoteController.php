<?php

namespace App\Controllers;

use App\Core\View;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Services\NoteService;
use Exception;

/**
 * Note Controller
 * Thin controller that delegates to NoteService
 */
class NoteController
{
    private NoteService $noteService;

    public function __construct(NoteService $noteService)
    {
        $this->noteService = $noteService;
    }

    /**
     * Add a note to a property
     * POST /api/add_note.php
     */
    public function addNote(): void
    {
        // Get JSON input
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        // Fallback to POST data if JSON not provided
        if ($data === null) {
            $data = $_POST;
        }

        try {
            // Delegate to service
            $savedNote = $this->noteService->addNote($data);

            // Return JSON response
            View::json([
                'success' => true,
                'message' => 'Note added successfully',
                'note' => $savedNote->toArray()
            ], 201);

        } catch (ValidationException $e) {
            View::json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->getErrors()
            ], 422);
        } catch (NotFoundException $e) {
            View::json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        } catch (Exception $e) {
            error_log("NoteController::addNote Error: " . $e->getMessage());
            View::json([
                'success' => false,
                'message' => 'Failed to add note',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all notes for a property
     * GET /api/notes.php?property_id={id}
     */
    public function getNotesByProperty(): void
    {
        $propertyId = $_GET['property_id'] ?? null;

        if (!$propertyId || !is_numeric($propertyId)) {
            View::json([
                'success' => false,
                'message' => 'Invalid property_id parameter'
            ], 400);
            return;
        }

        try {
            // Delegate to service
            $notes = $this->noteService->getNotesByProperty((int) $propertyId);

            View::json([
                'success' => true,
                'notes' => array_map(fn($note) => $note->toArray(), $notes),
                'count' => count($notes)
            ]);

        } catch (Exception $e) {
            error_log("NoteController::getNotesByProperty Error: " . $e->getMessage());
            View::json([
                'success' => false,
                'message' => 'Failed to retrieve notes',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

