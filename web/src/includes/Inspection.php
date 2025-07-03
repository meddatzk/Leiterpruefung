<?php

/**
 * Inspection Model - Repräsentiert eine Prüfung im System
 * 
 * @author System
 * @version 1.0
 */
class Inspection
{
    private ?int $id = null;
    private int $ladderId;
    private int $inspectorId;
    private string $inspectionDate;
    private string $inspectionType = 'routine';
    private string $overallResult;
    private string $nextInspectionDate;
    private ?int $inspectionDurationMinutes = null;
    private ?string $weatherConditions = null;
    private ?int $temperatureCelsius = null;
    private ?string $generalNotes = null;
    private ?string $recommendations = null;
    private ?string $defectsFound = null;
    private ?string $actionsRequired = null;
    private ?string $inspectorSignature = null;
    private ?int $supervisorApprovalId = null;
    private ?string $approvalDate = null;
    private ?string $createdAt = null;
    private ?string $updatedAt = null;
    
    // Zugehörige Objekte
    private ?Ladder $ladder = null;
    private ?User $inspector = null;
    private ?User $supervisorApproval = null;
    private array $inspectionItems = [];
    
    // Unveränderlichkeit nach Speicherung
    private bool $isImmutable = false;

    // Erlaubte Werte für Enums
    public const INSPECTION_TYPES = [
        'routine',
        'initial',
        'after_incident',
        'special'
    ];

    public const OVERALL_RESULTS = [
        'passed',
        'failed',
        'conditional'
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
        if ($this->isImmutable) {
            throw new RuntimeException('Prüfung ist unveränderlich nach dem Speichern');
        }

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

    public function getLadderId(): int
    {
        return $this->ladderId;
    }

    public function getInspectorId(): int
    {
        return $this->inspectorId;
    }

    public function getInspectionDate(): string
    {
        return $this->inspectionDate;
    }

    public function getInspectionType(): string
    {
        return $this->inspectionType;
    }

    public function getOverallResult(): string
    {
        return $this->overallResult;
    }

    public function getNextInspectionDate(): string
    {
        return $this->nextInspectionDate;
    }

    public function getInspectionDurationMinutes(): ?int
    {
        return $this->inspectionDurationMinutes;
    }

    public function getWeatherConditions(): ?string
    {
        return $this->weatherConditions;
    }

    public function getTemperatureCelsius(): ?int
    {
        return $this->temperatureCelsius;
    }

    public function getGeneralNotes(): ?string
    {
        return $this->generalNotes;
    }

    public function getRecommendations(): ?string
    {
        return $this->recommendations;
    }

    public function getDefectsFound(): ?string
    {
        return $this->defectsFound;
    }

    public function getActionsRequired(): ?string
    {
        return $this->actionsRequired;
    }

    public function getInspectorSignature(): ?string
    {
        return $this->inspectorSignature;
    }

    public function getSupervisorApprovalId(): ?int
    {
        return $this->supervisorApprovalId;
    }

    public function getApprovalDate(): ?string
    {
        return $this->approvalDate;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function getLadder(): ?Ladder
    {
        return $this->ladder;
    }

    public function getInspector(): ?User
    {
        return $this->inspector;
    }

    public function getSupervisorApproval(): ?User
    {
        return $this->supervisorApproval;
    }

    public function getInspectionItems(): array
    {
        return $this->inspectionItems;
    }

    public function isImmutable(): bool
    {
        return $this->isImmutable;
    }

    // Setter-Methoden
    public function setId(?int $id): void
    {
        if ($this->isImmutable) {
            throw new RuntimeException('Prüfung ist unveränderlich nach dem Speichern');
        }
        $this->id = $id;
        
        // Nach dem ersten Speichern wird die Prüfung unveränderlich
        if ($id !== null) {
            $this->isImmutable = true;
        }
    }

    public function setLadderId(int $ladderId): void
    {
        if ($this->isImmutable) {
            throw new RuntimeException('Prüfung ist unveränderlich nach dem Speichern');
        }
        $this->ladderId = $ladderId;
    }

    public function setInspectorId(int $inspectorId): void
    {
        if ($this->isImmutable) {
            throw new RuntimeException('Prüfung ist unveränderlich nach dem Speichern');
        }
        $this->inspectorId = $inspectorId;
    }

    public function setInspectionDate(string $inspectionDate): void
    {
        if ($this->isImmutable) {
            throw new RuntimeException('Prüfung ist unveränderlich nach dem Speichern');
        }
        if (!$this->isValidDate($inspectionDate)) {
            throw new InvalidArgumentException("Ungültiges Prüfdatum: {$inspectionDate}");
        }
        $this->inspectionDate = $inspectionDate;
    }

    public function setInspectionType(string $inspectionType): void
    {
        if ($this->isImmutable) {
            throw new RuntimeException('Prüfung ist unveränderlich nach dem Speichern');
        }
        if (!in_array($inspectionType, self::INSPECTION_TYPES)) {
            throw new InvalidArgumentException("Ungültiger Prüfungstyp: {$inspectionType}");
        }
        $this->inspectionType = $inspectionType;
    }

    public function setOverallResult(string $overallResult): void
    {
        if ($this->isImmutable) {
            throw new RuntimeException('Prüfung ist unveränderlich nach dem Speichern');
        }
        if (!in_array($overallResult, self::OVERALL_RESULTS)) {
            throw new InvalidArgumentException("Ungültiges Prüfungsergebnis: {$overallResult}");
        }
        $this->overallResult = $overallResult;
    }

    public function setNextInspectionDate(string $nextInspectionDate): void
    {
        if ($this->isImmutable) {
            throw new RuntimeException('Prüfung ist unveränderlich nach dem Speichern');
        }
        if (!$this->isValidDate($nextInspectionDate)) {
            throw new InvalidArgumentException("Ungültiges nächstes Prüfdatum: {$nextInspectionDate}");
        }
        $this->nextInspectionDate = $nextInspectionDate;
    }

    public function setInspectionDurationMinutes(?int $inspectionDurationMinutes): void
    {
        if ($this->isImmutable) {
            throw new RuntimeException('Prüfung ist unveränderlich nach dem Speichern');
        }
        if ($inspectionDurationMinutes !== null && $inspectionDurationMinutes <= 0) {
            throw new InvalidArgumentException("Prüfdauer muss größer als 0 sein");
        }
        $this->inspectionDurationMinutes = $inspectionDurationMinutes;
    }

    public function setWeatherConditions(?string $weatherConditions): void
    {
        if ($this->isImmutable) {
            throw new RuntimeException('Prüfung ist unveränderlich nach dem Speichern');
        }
        $this->weatherConditions = $weatherConditions ? trim($weatherConditions) : null;
    }

    public function setTemperatureCelsius(?int $temperatureCelsius): void
    {
        if ($this->isImmutable) {
            throw new RuntimeException('Prüfung ist unveränderlich nach dem Speichern');
        }
        if ($temperatureCelsius !== null && ($temperatureCelsius < -50 || $temperatureCelsius > 60)) {
            throw new InvalidArgumentException("Temperatur muss zwischen -50°C und 60°C liegen");
        }
        $this->temperatureCelsius = $temperatureCelsius;
    }

    public function setGeneralNotes(?string $generalNotes): void
    {
        if ($this->isImmutable) {
            throw new RuntimeException('Prüfung ist unveränderlich nach dem Speichern');
        }
        $this->generalNotes = $generalNotes ? trim($generalNotes) : null;
    }

    public function setRecommendations(?string $recommendations): void
    {
        if ($this->isImmutable) {
            throw new RuntimeException('Prüfung ist unveränderlich nach dem Speichern');
        }
        $this->recommendations = $recommendations ? trim($recommendations) : null;
    }

    public function setDefectsFound(?string $defectsFound): void
    {
        if ($this->isImmutable) {
            throw new RuntimeException('Prüfung ist unveränderlich nach dem Speichern');
        }
        $this->defectsFound = $defectsFound ? trim($defectsFound) : null;
    }

    public function setActionsRequired(?string $actionsRequired): void
    {
        if ($this->isImmutable) {
            throw new RuntimeException('Prüfung ist unveränderlich nach dem Speichern');
        }
        $this->actionsRequired = $actionsRequired ? trim($actionsRequired) : null;
    }

    public function setInspectorSignature(?string $inspectorSignature): void
    {
        if ($this->isImmutable) {
            throw new RuntimeException('Prüfung ist unveränderlich nach dem Speichern');
        }
        $this->inspectorSignature = $inspectorSignature ? trim($inspectorSignature) : null;
    }

    public function setSupervisorApprovalId(?int $supervisorApprovalId): void
    {
        if ($this->isImmutable) {
            throw new RuntimeException('Prüfung ist unveränderlich nach dem Speichern');
        }
        $this->supervisorApprovalId = $supervisorApprovalId;
    }

    public function setApprovalDate(?string $approvalDate): void
    {
        if ($this->isImmutable) {
            throw new RuntimeException('Prüfung ist unveränderlich nach dem Speichern');
        }
        if ($approvalDate && !$this->isValidDateTime($approvalDate)) {
            throw new InvalidArgumentException("Ungültiges Genehmigungsdatum: {$approvalDate}");
        }
        $this->approvalDate = $approvalDate;
    }

    public function setCreatedAt(?string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function setUpdatedAt(?string $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function setLadder(?Ladder $ladder): void
    {
        $this->ladder = $ladder;
    }

    public function setInspector(?User $inspector): void
    {
        $this->inspector = $inspector;
    }

    public function setSupervisorApproval(?User $supervisorApproval): void
    {
        $this->supervisorApproval = $supervisorApproval;
    }

    public function setInspectionItems(array $inspectionItems): void
    {
        $this->inspectionItems = $inspectionItems;
    }

    /**
     * Berechnet das Gesamtergebnis basierend auf den Prüfpunkten
     */
    public function calculateOverallResult(): string
    {
        if (empty($this->inspectionItems)) {
            return 'conditional';
        }

        $hasDefects = false;
        $hasCriticalDefects = false;

        foreach ($this->inspectionItems as $item) {
            if ($item instanceof InspectionItem) {
                if ($item->getResult() === 'defect') {
                    $hasDefects = true;
                    if ($item->getSeverity() === 'critical') {
                        $hasCriticalDefects = true;
                        break;
                    }
                }
            }
        }

        if ($hasCriticalDefects) {
            return 'failed';
        } elseif ($hasDefects) {
            return 'conditional';
        } else {
            return 'passed';
        }
    }

    /**
     * Prüft ob die Prüfung genehmigt wurde
     */
    public function isApproved(): bool
    {
        return $this->supervisorApprovalId !== null && $this->approvalDate !== null;
    }

    /**
     * Prüft ob die Prüfung vollständig ist
     */
    public function isComplete(): bool
    {
        return !empty($this->inspectionItems) && 
               !empty($this->overallResult) && 
               !empty($this->inspectorSignature);
    }

    /**
     * Gibt alle kritischen Mängel zurück
     */
    public function getCriticalDefects(): array
    {
        $criticalDefects = [];
        
        foreach ($this->inspectionItems as $item) {
            if ($item instanceof InspectionItem && 
                $item->getResult() === 'defect' && 
                $item->getSeverity() === 'critical') {
                $criticalDefects[] = $item;
            }
        }
        
        return $criticalDefects;
    }

    /**
     * Gibt alle Mängel zurück
     */
    public function getAllDefects(): array
    {
        $defects = [];
        
        foreach ($this->inspectionItems as $item) {
            if ($item instanceof InspectionItem && $item->getResult() === 'defect') {
                $defects[] = $item;
            }
        }
        
        return $defects;
    }

    /**
     * Validiert das gesamte Objekt
     */
    public function validate(): array
    {
        $errors = [];

        // Pflichtfelder prüfen
        if (empty($this->ladderId)) {
            $errors[] = 'Leiter-ID ist erforderlich';
        }

        if (empty($this->inspectorId)) {
            $errors[] = 'Prüfer-ID ist erforderlich';
        }

        if (empty($this->inspectionDate)) {
            $errors[] = 'Prüfdatum ist erforderlich';
        }

        if (empty($this->overallResult)) {
            $errors[] = 'Gesamtergebnis ist erforderlich';
        }

        if (empty($this->nextInspectionDate)) {
            $errors[] = 'Nächstes Prüfdatum ist erforderlich';
        }

        // Enum-Werte prüfen
        if (!in_array($this->inspectionType, self::INSPECTION_TYPES)) {
            $errors[] = 'Ungültiger Prüfungstyp';
        }

        if (!in_array($this->overallResult, self::OVERALL_RESULTS)) {
            $errors[] = 'Ungültiges Prüfungsergebnis';
        }

        // Datumsvalidierung
        if (!$this->isValidDate($this->inspectionDate)) {
            $errors[] = 'Ungültiges Prüfdatum';
        }

        if (!$this->isValidDate($this->nextInspectionDate)) {
            $errors[] = 'Ungültiges nächstes Prüfdatum';
        }

        // Logische Validierung
        if ($this->inspectionDate && $this->nextInspectionDate) {
            $inspectionDate = new DateTime($this->inspectionDate);
            $nextInspectionDate = new DateTime($this->nextInspectionDate);
            
            if ($nextInspectionDate <= $inspectionDate) {
                $errors[] = 'Nächstes Prüfdatum muss nach dem Prüfdatum liegen';
            }
        }

        // Temperatur-Validierung
        if ($this->temperatureCelsius !== null && 
            ($this->temperatureCelsius < -50 || $this->temperatureCelsius > 60)) {
            $errors[] = 'Temperatur muss zwischen -50°C und 60°C liegen';
        }

        // Prüfdauer-Validierung
        if ($this->inspectionDurationMinutes !== null && $this->inspectionDurationMinutes <= 0) {
            $errors[] = 'Prüfdauer muss größer als 0 sein';
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
     * Prüft ob ein DateTime gültig ist
     */
    private function isValidDateTime(string $datetime): bool
    {
        $d = DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
        return $d && $d->format('Y-m-d H:i:s') === $datetime;
    }

    /**
     * Konvertiert das Objekt zu einem Array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'ladder_id' => $this->ladderId,
            'inspector_id' => $this->inspectorId,
            'inspection_date' => $this->inspectionDate,
            'inspection_type' => $this->inspectionType,
            'overall_result' => $this->overallResult,
            'next_inspection_date' => $this->nextInspectionDate,
            'inspection_duration_minutes' => $this->inspectionDurationMinutes,
            'weather_conditions' => $this->weatherConditions,
            'temperature_celsius' => $this->temperatureCelsius,
            'general_notes' => $this->generalNotes,
            'recommendations' => $this->recommendations,
            'defects_found' => $this->defectsFound,
            'actions_required' => $this->actionsRequired,
            'inspector_signature' => $this->inspectorSignature,
            'supervisor_approval_id' => $this->supervisorApprovalId,
            'approval_date' => $this->approvalDate,
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
     * Gibt eine lesbare Beschreibung der Prüfung zurück
     */
    public function getDescription(): string
    {
        $parts = [];
        
        if ($this->ladder) {
            $parts[] = $this->ladder->getLadderNumber();
        } else {
            $parts[] = "Leiter-ID: {$this->ladderId}";
        }
        
        $parts[] = date('d.m.Y', strtotime($this->inspectionDate));
        $parts[] = ucfirst($this->inspectionType);
        $parts[] = ucfirst($this->overallResult);
        
        return implode(' - ', $parts);
    }
}
