<?php

namespace App\Models;

/**
 * Property Model
 * Represents a property entity with validation
 */
class Property
{
    private ?int $id = null;
    private string $name;
    private string $address;
    private float $latitude;
    private float $longitude;
    private ?string $extraField = null;
    private ?string $createdAt = null;
    private ?string $updatedAt = null;
    private array $notes = [];

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
        if (isset($data['name'])) {
            $this->name = $data['name'];
        }
        if (isset($data['address'])) {
            $this->address = $data['address'];
        }
        if (isset($data['latitude'])) {
            $this->latitude = (float) $data['latitude'];
        }
        if (isset($data['longitude'])) {
            $this->longitude = (float) $data['longitude'];
        }
        if (isset($data['extra_field'])) {
            $this->extraField = $data['extra_field'];
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
            'name' => $this->name,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'extra_field' => $this->extraField,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'notes' => array_map(fn($note) => $note->toArray(), $this->notes)
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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = trim($name);
        return $this;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = trim($address);
        return $this;
    }

    public function getLatitude(): float
    {
        return $this->latitude;
    }

    public function setLatitude(float $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): float
    {
        return $this->longitude;
    }

    public function setLongitude(float $longitude): self
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function getExtraField(): ?string
    {
        return $this->extraField;
    }

    public function setExtraField(?string $extraField): self
    {
        $this->extraField = $extraField;
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

    public function getNotes(): array
    {
        return $this->notes;
    }

    public function setNotes(array $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    public function addNote(Note $note): self
    {
        $this->notes[] = $note;
        return $this;
    }
}


