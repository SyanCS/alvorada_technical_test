<?php

namespace App\Models;

/**
 * Note Model
 * Represents a note entity associated with a property
 */
class Note
{
    private ?int $id = null;
    private int $propertyId;
    private string $note;
    private ?string $createdAt = null;
    private ?string $updatedAt = null;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->hydrate($data);
        }
    }

    /**
     * Hydrate model from array
     */
    public function hydrate(array $data): self
    {
        if (isset($data['id'])) {
            $this->id = (int) $data['id'];
        }
        if (isset($data['property_id'])) {
            $this->propertyId = (int) $data['property_id'];
        }
        if (isset($data['note'])) {
            $this->note = $data['note'];
        }
        if (isset($data['created_at'])) {
            $this->createdAt = $data['created_at'];
        }
        if (isset($data['updated_at'])) {
            $this->updatedAt = $data['updated_at'];
        }

        return $this;
    }

    /**
     * Convert model to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'property_id' => $this->propertyId,
            'note' => $this->note,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }

    /**
     * Convert to JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    // Getters and Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getPropertyId(): int
    {
        return $this->propertyId;
    }

    public function setPropertyId(int $propertyId): self
    {
        $this->propertyId = $propertyId;
        return $this;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function setNote(string $note): self
    {
        $this->note = trim($note);
        return $this;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }
}


