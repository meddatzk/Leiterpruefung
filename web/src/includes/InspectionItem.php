<?php

/**
 * InspectionItem Model - Repräsentiert einen Prüfpunkt im System
 * 
 * @author System
 * @version 1.0
 */
class InspectionItem
{
    private ?int $id = null;
    private int $inspectionId;
    private string $category;
    private string $itemName;
    private ?string $description = null;
    private string $result;
    private ?string $severity = null;
    private ?string $notes = null;
    private ?string $photoPath = null;
    private bool $repairRequired = false;
    private ?string $repairDeadline = null;
    private int $sortOrder = 0;
    private ?string $createdAt = null;
    private ?string $updatedAt = null;

    // Erlaubte Werte für Enums
    public const CATEGORIES = [
        'structure',
        'safety',
        'function',
        'marking',
        'accessories'
    ];

    public const RESULTS = [
        'ok',
        'defect',
        'wear',
        'not_applicable'
    ];

    public const SEVERITIES = [
        'low',
        'medium',
        'high',
        'critical'
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

    public function getInspectionId(): int
    {
        return $this->inspectionId;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getItemName(): string
    {
        return $this->itemName;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getResult(): string
    {
        return $this->result;
    }

    public function getSeverity(): ?string
    {
        return $this->severity;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getPhotoPath(): ?string
    {
        return $this->photoPath;
    }

    public function isRepairRequired(): bool
    {
        return $this->repairRequired;
    }

    public function getRepairDeadline(): ?string
    {
        return $this->repairDeadline;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
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

    public function setInspectionId(int $inspectionId): void
    {
        $this->inspectionId = $inspectionId;
    }

    public function setCategory(string $category): void
    {
        if (!in_array($category, self::CATEGORIES)) {
            throw new InvalidArgumentException("Ungültige Kategorie: {$category}");
        }
        $this->category = $category;
    }

    public function setItemName(string $itemName): void
    {
        $this->itemName = trim($itemName);
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description ? trim($description) : null;
    }

    public function setResult(string $result): void
    {
        if (!in_array($result, self::RESULTS)) {
            throw new InvalidArgumentException("Ungültiges Ergebnis: {$result}");
        }
        $this->result = $result;
        
        // Automatische Logik: Bei Defekt ist Reparatur erforderlich
        if ($result === 'defect') {
            $this->repairRequired = true;
        } elseif ($result === 'ok' || $result === 'not_applicable') {
            $this->repairRequired = false;
            $this->severity = null;
            $this->repairDeadline = null;
        }
    }

    public function setSeverity(?string $severity): void
    {
        if ($severity !== null && !in_array($severity, self::SEVERITIES)) {
            throw new InvalidArgumentException("Ungültiger Schweregrad: {$severity}");
        }
        
        // Schweregrad nur bei Defekten erlaubt
        if ($severity !== null && $this->result !== 'defect') {
            throw new InvalidArgumentException("Schweregrad nur bei Defekten erlaubt");
        }
        
        $this->severity = $severity;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes ? trim($notes) : null;
    }

    public function setPhotoPath(?string $photoPath): void
    {
        $this->photoPath = $photoPath ? trim($photoPath) : null;
    }

    public function setRepairRequired(bool $repairRequired): void
    {
        $this->repairRequired = $repairRequired;
        
        // Wenn keine Reparatur erforderlich, Deadline entfernen
        if (!$repairRequired) {
            $this->repairDeadline = null;
        }
    }

    public function setRepairDeadline(?string $repairDeadline): void
    {
        if ($repairDeadline && !$this->isValidDate($repairDeadline)) {
            throw new InvalidArgumentException("Ungültiges Reparatur-Deadline: {$repairDeadline}");
        }
        
        // Deadline nur wenn Reparatur erforderlich
        if ($repairDeadline && !$this->repairRequired) {
            throw new InvalidArgumentException("Reparatur-Deadline nur wenn Reparatur erforderlich");
        }
        
        $this->repairDeadline = $repairDeadline;
    }

    public function setSortOrder(int $sortOrder): void
    {
        if ($sortOrder < 0) {
            throw new InvalidArgumentException("Sortierreihenfolge muss >= 0 sein");
        }
        $this->sortOrder = $sortOrder;
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
     * Prüft ob der Prüfpunkt ein Defekt ist
     */
    public function isDefect(): bool
    {
        return $this->result === 'defect';
    }

    /**
     * Prüft ob der Prüfpunkt kritisch ist
     */
    public function isCritical(): bool
    {
        return $this->isDefect() && $this->severity === 'critical';
    }

    /**
     * Prüft ob der Prüfpunkt in Ordnung ist
     */
    public function isOk(): bool
    {
        return $this->result === 'ok';
    }

    /**
     * Prüft ob der Prüfpunkt Verschleiß zeigt
     */
    public function hasWear(): bool
    {
        return $this->result === 'wear';
    }

    /**
     * Prüft ob der Prüfpunkt nicht anwendbar ist
     */
    public function isNotApplicable(): bool
    {
        return $this->result === 'not_applicable';
    }

    /**
     * Prüft ob die Reparatur-Deadline überschritten ist
     */
    public function isRepairOverdue(): bool
    {
        if (!$this->repairRequired || !$this->repairDeadline) {
            return false;
        }
        
        $deadline = new DateTime($this->repairDeadline);
        $today = new DateTime();
        
        return $today > $deadline;
    }

    /**
     * Berechnet Tage bis zur Reparatur-Deadline
     */
    public function getDaysUntilRepairDeadline(): ?int
    {
        if (!$this->repairRequired || !$this->repairDeadline) {
            return null;
        }
        
        $deadline = new DateTime($this->repairDeadline);
        $today = new DateTime();
        
        $diff = $today->diff($deadline);
        return $diff->invert ? -$diff->days : $diff->days;
    }

    /**
     * Gibt die Kategorie in lesbarer Form zurück
     */
    public function getCategoryLabel(): string
    {
        $labels = [
            'structure' => 'Struktur',
            'safety' => 'Sicherheit',
            'function' => 'Funktion',
            'marking' => 'Kennzeichnung',
            'accessories' => 'Zubehör'
        ];
        
        return $labels[$this->category] ?? $this->category;
    }

    /**
     * Gibt das Ergebnis in lesbarer Form zurück
     */
    public function getResultLabel(): string
    {
        $labels = [
            'ok' => 'In Ordnung',
            'defect' => 'Defekt',
            'wear' => 'Verschleiß',
            'not_applicable' => 'Nicht anwendbar'
        ];
        
        return $labels[$this->result] ?? $this->result;
    }

    /**
     * Gibt den Schweregrad in lesbarer Form zurück
     */
    public function getSeverityLabel(): ?string
    {
        if (!$this->severity) {
            return null;
        }
        
        $labels = [
            'low' => 'Niedrig',
            'medium' => 'Mittel',
            'high' => 'Hoch',
            'critical' => 'Kritisch'
        ];
        
        return $labels[$this->severity] ?? $this->severity;
    }

    /**
     * Gibt die CSS-Klasse für das Ergebnis zurück
     */
    public function getResultCssClass(): string
    {
        $classes = [
            'ok' => 'success',
            'defect' => 'danger',
            'wear' => 'warning',
            'not_applicable' => 'secondary'
        ];
        
        return $classes[$this->result] ?? 'secondary';
    }

    /**
     * Gibt die CSS-Klasse für den Schweregrad zurück
     */
    public function getSeverityCssClass(): string
    {
        if (!$this->severity) {
            return '';
        }
        
        $classes = [
            'low' => 'info',
            'medium' => 'warning',
            'high' => 'danger',
            'critical' => 'danger'
        ];
        
        return $classes[$this->severity] ?? '';
    }

    /**
     * Validiert das gesamte Objekt
     */
    public function validate(): array
    {
        $errors = [];

        // Pflichtfelder prüfen
        if (empty($this->inspectionId)) {
            $errors[] = 'Prüfungs-ID ist erforderlich';
        }

        if (empty($this->category)) {
            $errors[] = 'Kategorie ist erforderlich';
        }

        if (empty($this->itemName)) {
            $errors[] = 'Prüfpunkt-Name ist erforderlich';
        }

        if (empty($this->result)) {
            $errors[] = 'Ergebnis ist erforderlich';
        }

        // Enum-Werte prüfen
        if (!in_array($this->category, self::CATEGORIES)) {
            $errors[] = 'Ungültige Kategorie';
        }

        if (!in_array($this->result, self::RESULTS)) {
            $errors[] = 'Ungültiges Ergebnis';
        }

        if ($this->severity && !in_array($this->severity, self::SEVERITIES)) {
            $errors[] = 'Ungültiger Schweregrad';
        }

        // Logische Validierung
        if ($this->severity && $this->result !== 'defect') {
            $errors[] = 'Schweregrad nur bei Defekten erlaubt';
        }

        if ($this->result === 'defect' && !$this->severity) {
            $errors[] = 'Schweregrad bei Defekten erforderlich';
        }

        if ($this->repairDeadline && !$this->repairRequired) {
            $errors[] = 'Reparatur-Deadline nur wenn Reparatur erforderlich';
        }

        if ($this->repairRequired && $this->result !== 'defect') {
            $errors[] = 'Reparatur nur bei Defekten erforderlich';
        }

        // Datumsvalidierung
        if ($this->repairDeadline && !$this->isValidDate($this->repairDeadline)) {
            $errors[] = 'Ungültiges Reparatur-Deadline';
        }

        // Sortierreihenfolge
        if ($this->sortOrder < 0) {
            $errors[] = 'Sortierreihenfolge muss >= 0 sein';
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
            'inspection_id' => $this->inspectionId,
            'category' => $this->category,
            'item_name' => $this->itemName,
            'description' => $this->description,
            'result' => $this->result,
            'severity' => $this->severity,
            'notes' => $this->notes,
            'photo_path' => $this->photoPath,
            'repair_required' => $this->repairRequired,
            'repair_deadline' => $this->repairDeadline,
            'sort_order' => $this->sortOrder,
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
     * Gibt eine lesbare Beschreibung des Prüfpunkts zurück
     */
    public function getDescription(): string
    {
        $parts = [];
        
        $parts[] = $this->getCategoryLabel();
        $parts[] = $this->itemName;
        $parts[] = $this->getResultLabel();
        
        if ($this->severity) {
            $parts[] = $this->getSeverityLabel();
        }
        
        return implode(' - ', $parts);
    }

    /**
     * Erstellt eine Kopie des Prüfpunkts für eine neue Prüfung
     */
    public function createTemplate(): InspectionItem
    {
        $template = new InspectionItem();
        $template->setCategory($this->category);
        $template->setItemName($this->itemName);
        $template->setDescription($this->description);
        $template->setSortOrder($this->sortOrder);
        
        // Ergebnis zurücksetzen
        $template->setResult('ok');
        
        return $template;
    }
}
