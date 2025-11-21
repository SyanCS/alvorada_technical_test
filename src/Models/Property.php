<?php

namespace App\Models;

/**
 * Property Model
 * Represents a property entity with PostGIS geolocation support
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
     * Hydrate model from array (handles PostGIS data)
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
        
        // Handle PostGIS location field
        if (isset($data['location'])) {
            $this->parsePostGISLocation($data['location']);
        }
        
        // Fallback to separate lat/lon if provided
        if (isset($data['latitude'])) {
            $this->latitude = (float) $data['latitude'];
        }
        if (isset($data['longitude'])) {
            $this->longitude = (float) $data['longitude'];
        }
        
        // Handle JSONB extra_field
        if (isset($data['extra_field'])) {
            $this->extraField = is_string($data['extra_field']) 
                ? $data['extra_field'] 
                : json_encode($data['extra_field']);
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
     * Parse PostGIS location to lat/lon
     * Handles various PostGIS formats
     */
    private function parsePostGISLocation(string $location): void
    {
        // PostGIS returns in format: POINT(longitude latitude)
        // or in binary format that needs conversion
        
        if (preg_match('/POINT\(([0-9.\-]+)\s+([0-9.\-]+)\)/', $location, $matches)) {
            $this->longitude = (float) $matches[1];
            $this->latitude = (float) $matches[2];
        } elseif (preg_match('/([0-9.\-]+),([0-9.\-]+)/', $location, $matches)) {
            $this->latitude = (float) $matches[1];
            $this->longitude = (float) $matches[2];
        }
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
            'extra_field' => $this->getExtraFieldAsArray(),
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'notes' => array_map(fn($note) => $note->toArray(), $this->notes)
        ];
    }

    /**
     * Get extra field as array
     */
    public function getExtraFieldAsArray(): ?array
    {
        if ($this->extraField === null) {
            return null;
        }
        
        $decoded = json_decode($this->extraField, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
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

    /**
     * Get coordinates as PostGIS format
     */
    public function getLocationAsPostGIS(): string
    {
        return "POINT({$this->longitude} {$this->latitude})";
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
