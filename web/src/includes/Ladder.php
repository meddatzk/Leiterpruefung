<?php

/**
 * Ladder Model - Repräsentiert eine Leiter im System
 * 
 * @author System
 * @version 1.0
 */
class Ladder
{
    private ?int $id = null;
    private string $ladderNumber;
    private string $manufacturer;
    private ?string $model = null;
    private string $ladderType;
    private string $material = 'Aluminium';
    private int $maxLoadKg = 150;
    private int $heightCm;
    private ?string $purchaseDate = null;
    private string $location;
    private ?string $department = null;
    private ?string $responsiblePerson = null;
    private ?string $serialNumber = null;
    private ?string $notes = null;
    private string $status = 'active';
    private string $nextInspectionDate;
    private int $inspectionIntervalMonths = 12;
    private ?string $createdAt = null;
    private ?string $updatedAt = null;

    // Erlaubte Werte für Enums
    public const LADDER_TYPES = [
        'Anlegeleiter',
        'Stehleiter', 
        'Mehrzweckleiter',
        'Podestleiter',
        'Schiebeleiter'
    ];

    public const MATERIALS = [
        'Aluminium',
        'Holz',
        'Fiberglas',
        'Stahl'
    ];

    public const STATUSES = [
        'active',
        'inactive',
        'defective',
        'disposed'
    ];

    /**
     * Konstruktor
     */
    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->fillFromArray($data);
        }
    }

    /**
     * Füllt das Objekt mit Daten aus einem Array
     */
    public function fillFromArray(array $data): void
    {
        foreach ($data as $key => $value) {
            $method = 'set' . str_replace('_', '', ucwords($key, '_'));
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
    }

    // Getter-Methoden
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLadderNumber(): string
    {
        return $this->ladderNumber;
    }

    public function getManufacturer(): string
    {
        return $this->manufacturer;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function getLadderType(): string
    {
        return $this->ladderType;
    }

    public function getMaterial(): string
    {
        return $this->material;
    }

    public function getMaxLoadKg(): int
    {
        return $this->maxLoadKg;
    }

    public function getHeightCm(): int
    {
        return $this->heightCm;
    }

    public function getPurchaseDate(): ?string
    {
        return $this->purchaseDate;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function getResponsiblePerson(): ?string
    {
        return $this->responsiblePerson;
    }

    public function getSerialNumber(): ?string
    {
        return $this->serialNumber;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getNextInspectionDate(): string
    {
        return $this->nextInspectionDate;
    }

    public function getInspectionIntervalMonths(): int
    {
        return $this->inspectionIntervalMonths;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    // Setter-Methoden
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function setLadderNumber(string $ladderNumber): void
    {
        $this->ladderNumber = trim($ladderNumber);
    }

    public function setManufacturer(string $manufacturer): void
    {
        $this->manufacturer = trim($manufacturer);
    }

    public function setModel(?string $model): void
    {
        $this->model = $model ? trim($model) : null;
    }

    public function setLadderType(string $ladderType): void
    {
        if (!in_array($ladderType, self::LADDER_TYPES)) {
            throw new InvalidArgumentException("Ungültiger Leitertyp: {$ladderType}");
        }
        $this->ladderType = $ladderType;
    }

    public function setMaterial(string $material): void
    {
        if (!in_array($material, self::MATERIALS)) {
            throw new InvalidArgumentException("Ungültiges Material: {$material}");
        }
        $this->material = $material;
    }

    public function setMaxLoadKg(int $maxLoadKg): void
    {
        if ($maxLoadKg <= 0) {
            throw new InvalidArgumentException("Maximale Belastung muss größer als 0 sein");
        }
        $this->maxLoadKg = $maxLoadKg;
    }

    public function setHeightCm(int $heightCm): void
    {
        if ($heightCm <= 0) {
            throw new InvalidArgumentException("Höhe muss größer als 0 sein");
        }
        $this->heightCm = $heightCm;
    }

    public function setPurchaseDate(?string $purchaseDate): void
    {
        if ($purchaseDate && !$this->isValidDate($purchaseDate)) {
            throw new InvalidArgumentException("Ungültiges Kaufdatum: {$purchaseDate}");
        }
        $this->purchaseDate = $purchaseDate;
    }

    public function setLocation(string $location): void
    {
        $this->location = trim($location);
    }

    public function setDepartment(?string $department): void
    {
        $this->department = $department ? trim($department) : null;
    }

    public function setResponsiblePerson(?string $responsiblePerson): void
    {
        $this->responsiblePerson = $responsiblePerson ? trim($responsiblePerson) : null;
    }

    public function setSerialNumber(?string $serialNumber): void
    {
        $this->serialNumber = $serialNumber ? trim($serialNumber) : null;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes ? trim($notes) : null;
    }

    public function setStatus(string $status): void
    {
        if (!in_array($status, self::STATUSES)) {
            throw new InvalidArgumentException("Ungültiger Status: {$status}");
        }
        $this->status = $status;
    }

    public function setNextInspectionDate(string $nextInspectionDate): void
    {
        if (!$this->isValidDate($nextInspectionDate)) {
            throw new InvalidArgumentException("Ungültiges Prüfdatum: {$nextInspectionDate}");
        }
        $this->nextInspectionDate = $nextInspectionDate;
    }

    public function setInspectionIntervalMonths(int $inspectionIntervalMonths): void
    {
        if ($inspectionIntervalMonths <= 0) {
            throw new InvalidArgumentException("Prüfintervall muss größer als 0 sein");
        }
        $this->inspectionIntervalMonths = $inspectionIntervalMonths;
    }

    public function setCreatedAt(?string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function setUpdatedAt(?string $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Validiert das gesamte Objekt
     */
    public function validate(): array
    {
        $errors = [];

        // Pflichtfelder prüfen
        if (empty($this->ladderNumber)) {
            $errors[] = 'Leiternummer ist erforderlich';
        }

        if (empty($this->manufacturer)) {
            $errors[] = 'Hersteller ist erforderlich';
        }

        if (empty($this->ladderType)) {
            $errors[] = 'Leitertyp ist erforderlich';
        }

        if (empty($this->location)) {
            $errors[] = 'Standort ist erforderlich';
        }

        if (empty($this->nextInspectionDate)) {
            $errors[] = 'Nächstes Prüfdatum ist erforderlich';
        }

        // Wertebereiche prüfen
        if ($this->maxLoadKg <= 0) {
            $errors[] = 'Maximale Belastung muss größer als 0 sein';
        }

        if ($this->heightCm <= 0) {
            $errors[] = 'Höhe muss größer als 0 sein';
        }

        if ($this->inspectionIntervalMonths <= 0) {
            $errors[] = 'Prüfintervall muss größer als 0 sein';
        }

        // Enum-Werte prüfen
        if (!in_array($this->ladderType, self::LADDER_TYPES)) {
            $errors[] = 'Ungültiger Leitertyp';
        }

        if (!in_array($this->material, self::MATERIALS)) {
            $errors[] = 'Ungültiges Material';
        }

        if (!in_array($this->status, self::STATUSES)) {
            $errors[] = 'Ungültiger Status';
        }

        // Datumsvalidierung
        if ($this->purchaseDate && !$this->isValidDate($this->purchaseDate)) {
            $errors[] = 'Ungültiges Kaufdatum';
        }

        if (!$this->isValidDate($this->nextInspectionDate)) {
            $errors[] = 'Ungültiges Prüfdatum';
        }

        return $errors;
    }

    /**
     * Prüft ob ein Datum gültig ist
     */
    private function isValidDate(string $date): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Konvertiert das Objekt zu einem Array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'ladder_number' => $this->ladderNumber,
            'manufacturer' => $this->manufacturer,
            'model' => $this->model,
            'ladder_type' => $this->ladderType,
            'material' => $this->material,
            'max_load_kg' => $this->maxLoadKg,
            'height_cm' => $this->heightCm,
            'purchase_date' => $this->purchaseDate,
            'location' => $this->location,
            'department' => $this->department,
            'responsible_person' => $this->responsiblePerson,
            'serial_number' => $this->serialNumber,
            'notes' => $this->notes,
            'status' => $this->status,
            'next_inspection_date' => $this->nextInspectionDate,
            'inspection_interval_months' => $this->inspectionIntervalMonths,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }

    /**
     * Konvertiert das Objekt zu JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Prüft ob die Leiter eine Prüfung benötigt
     */
    public function needsInspection(): bool
    {
        $nextInspection = new DateTime($this->nextInspectionDate);
        $today = new DateTime();
        
        return $nextInspection <= $today;
    }

    /**
     * Berechnet Tage bis zur nächsten Prüfung
     */
    public function getDaysUntilInspection(): int
    {
        $nextInspection = new DateTime($this->nextInspectionDate);
        $today = new DateTime();
        
        $diff = $today->diff($nextInspection);
        return $diff->invert ? -$diff->days : $diff->days;
    }

    /**
     * Berechnet das nächste Prüfdatum basierend auf dem aktuellen Datum
     */
    public function calculateNextInspectionDate(?DateTime $baseDate = null): string
    {
        $base = $baseDate ?: new DateTime();
        $next = clone $base;
        $next->add(new DateInterval('P' . $this->inspectionIntervalMonths . 'M'));
        
        return $next->format('Y-m-d');
    }

    /**
     * Gibt eine lesbare Beschreibung der Leiter zurück
     */
    public function getDescription(): string
    {
        $parts = [$this->manufacturer];
        
        if ($this->model) {
            $parts[] = $this->model;
        }
        
        $parts[] = $this->ladderType;
        $parts[] = $this->heightCm . 'cm';
        $parts[] = $this->material;
        
        return implode(' - ', $parts);
    }
}
