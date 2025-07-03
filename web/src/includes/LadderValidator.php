<?php

require_once __DIR__ . '/Ladder.php';

/**
 * LadderValidator - Validierung für Leiter-Daten
 * 
 * @author System
 * @version 1.0
 */
class LadderValidator
{
    private PDO $pdo;
    private array $errors = [];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Validiert eine Leiter vollständig
     */
    public function validate(Ladder $ladder, bool $isUpdate = false): array
    {
        $this->errors = [];

        // Grundvalidierung
        $this->validateRequired($ladder);
        $this->validateFormat($ladder);
        $this->validateDates($ladder);
        $this->validateRanges($ladder);
        $this->validateEnums($ladder);

        // Eindeutigkeitsprüfung
        $excludeId = $isUpdate ? $ladder->getId() : null;
        $this->validateLadderNumber($ladder->getLadderNumber(), $excludeId);

        return $this->errors;
    }

    /**
     * Validiert nur die Leiternummer auf Eindeutigkeit
     */
    public function validateLadderNumber(string $ladderNumber, ?int $excludeId = null): bool
    {
        // Format prüfen
        if (empty(trim($ladderNumber))) {
            $this->addError('ladder_number', 'Leiternummer ist erforderlich');
            return false;
        }

        // Länge prüfen
        if (strlen($ladderNumber) > 50) {
            $this->addError('ladder_number', 'Leiternummer darf maximal 50 Zeichen lang sein');
            return false;
        }

        // Format-Pattern prüfen (optional - kann angepasst werden)
        if (!preg_match('/^[A-Z0-9\-_]+$/i', $ladderNumber)) {
            $this->addError('ladder_number', 'Leiternummer darf nur Buchstaben, Zahlen, Bindestriche und Unterstriche enthalten');
            return false;
        }

        // Eindeutigkeit in Datenbank prüfen
        try {
            $sql = "SELECT COUNT(*) FROM ladders WHERE ladder_number = :ladder_number";
            $params = [':ladder_number' => $ladderNumber];

            if ($excludeId !== null) {
                $sql .= " AND id != :exclude_id";
                $params[':exclude_id'] = $excludeId;
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->fetchColumn() > 0) {
                $this->addError('ladder_number', 'Leiternummer bereits vorhanden: ' . $ladderNumber);
                return false;
            }

        } catch (PDOException $e) {
            $this->addError('ladder_number', 'Fehler bei der Eindeutigkeitsprüfung: ' . $e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Validiert Pflichtfelder
     */
    public function validateRequired(Ladder $ladder): bool
    {
        $hasErrors = false;

        // Leiternummer
        if (empty(trim($ladder->getLadderNumber()))) {
            $this->addError('ladder_number', 'Leiternummer ist erforderlich');
            $hasErrors = true;
        }

        // Hersteller
        if (empty(trim($ladder->getManufacturer()))) {
            $this->addError('manufacturer', 'Hersteller ist erforderlich');
            $hasErrors = true;
        }

        // Leitertyp
        if (empty(trim($ladder->getLadderType()))) {
            $this->addError('ladder_type', 'Leitertyp ist erforderlich');
            $hasErrors = true;
        }

        // Standort
        if (empty(trim($ladder->getLocation()))) {
            $this->addError('location', 'Standort ist erforderlich');
            $hasErrors = true;
        }

        // Nächstes Prüfdatum
        if (empty(trim($ladder->getNextInspectionDate()))) {
            $this->addError('next_inspection_date', 'Nächstes Prüfdatum ist erforderlich');
            $hasErrors = true;
        }

        // Höhe
        if ($ladder->getHeightCm() <= 0) {
            $this->addError('height_cm', 'Höhe ist erforderlich und muss größer als 0 sein');
            $hasErrors = true;
        }

        return !$hasErrors;
    }

    /**
     * Validiert Formate und Längen
     */
    public function validateFormat(Ladder $ladder): bool
    {
        $hasErrors = false;

        // Leiternummer
        $ladderNumber = $ladder->getLadderNumber();
        if (!empty($ladderNumber)) {
            if (strlen($ladderNumber) > 50) {
                $this->addError('ladder_number', 'Leiternummer darf maximal 50 Zeichen lang sein');
                $hasErrors = true;
            }

            if (!preg_match('/^[A-Z0-9\-_]+$/i', $ladderNumber)) {
                $this->addError('ladder_number', 'Leiternummer darf nur Buchstaben, Zahlen, Bindestriche und Unterstriche enthalten');
                $hasErrors = true;
            }
        }

        // Hersteller
        $manufacturer = $ladder->getManufacturer();
        if (!empty($manufacturer)) {
            if (strlen($manufacturer) > 100) {
                $this->addError('manufacturer', 'Hersteller darf maximal 100 Zeichen lang sein');
                $hasErrors = true;
            }

            if (!preg_match('/^[\p{L}\p{N}\s\-_&.()]+$/u', $manufacturer)) {
                $this->addError('manufacturer', 'Hersteller enthält ungültige Zeichen');
                $hasErrors = true;
            }
        }

        // Modell
        $model = $ladder->getModel();
        if (!empty($model)) {
            if (strlen($model) > 100) {
                $this->addError('model', 'Modell darf maximal 100 Zeichen lang sein');
                $hasErrors = true;
            }

            if (!preg_match('/^[\p{L}\p{N}\s\-_&.()]+$/u', $model)) {
                $this->addError('model', 'Modell enthält ungültige Zeichen');
                $hasErrors = true;
            }
        }

        // Standort
        $location = $ladder->getLocation();
        if (!empty($location)) {
            if (strlen($location) > 255) {
                $this->addError('location', 'Standort darf maximal 255 Zeichen lang sein');
                $hasErrors = true;
            }
        }

        // Abteilung
        $department = $ladder->getDepartment();
        if (!empty($department)) {
            if (strlen($department) > 100) {
                $this->addError('department', 'Abteilung darf maximal 100 Zeichen lang sein');
                $hasErrors = true;
            }
        }

        // Verantwortliche Person
        $responsiblePerson = $ladder->getResponsiblePerson();
        if (!empty($responsiblePerson)) {
            if (strlen($responsiblePerson) > 255) {
                $this->addError('responsible_person', 'Verantwortliche Person darf maximal 255 Zeichen lang sein');
                $hasErrors = true;
            }

            if (!preg_match('/^[\p{L}\s\-_.]+$/u', $responsiblePerson)) {
                $this->addError('responsible_person', 'Name der verantwortlichen Person enthält ungültige Zeichen');
                $hasErrors = true;
            }
        }

        // Seriennummer
        $serialNumber = $ladder->getSerialNumber();
        if (!empty($serialNumber)) {
            if (strlen($serialNumber) > 100) {
                $this->addError('serial_number', 'Seriennummer darf maximal 100 Zeichen lang sein');
                $hasErrors = true;
            }

            if (!preg_match('/^[A-Z0-9\-_]+$/i', $serialNumber)) {
                $this->addError('serial_number', 'Seriennummer darf nur Buchstaben, Zahlen, Bindestriche und Unterstriche enthalten');
                $hasErrors = true;
            }
        }

        return !$hasErrors;
    }

    /**
     * Validiert Datumswerte
     */
    public function validateDates(Ladder $ladder): bool
    {
        $hasErrors = false;

        // Kaufdatum
        $purchaseDate = $ladder->getPurchaseDate();
        if (!empty($purchaseDate)) {
            if (!$this->isValidDate($purchaseDate)) {
                $this->addError('purchase_date', 'Ungültiges Kaufdatum: ' . $purchaseDate);
                $hasErrors = true;
            } else {
                // Kaufdatum darf nicht in der Zukunft liegen
                $purchase = new DateTime($purchaseDate);
                $today = new DateTime();
                if ($purchase > $today) {
                    $this->addError('purchase_date', 'Kaufdatum darf nicht in der Zukunft liegen');
                    $hasErrors = true;
                }

                // Kaufdatum darf nicht zu weit in der Vergangenheit liegen (z.B. max. 50 Jahre)
                $minDate = new DateTime();
                $minDate->sub(new DateInterval('P50Y'));
                if ($purchase < $minDate) {
                    $this->addError('purchase_date', 'Kaufdatum liegt zu weit in der Vergangenheit');
                    $hasErrors = true;
                }
            }
        }

        // Nächstes Prüfdatum
        $nextInspectionDate = $ladder->getNextInspectionDate();
        if (!empty($nextInspectionDate)) {
            if (!$this->isValidDate($nextInspectionDate)) {
                $this->addError('next_inspection_date', 'Ungültiges Prüfdatum: ' . $nextInspectionDate);
                $hasErrors = true;
            } else {
                // Prüfdatum darf nicht zu weit in der Vergangenheit liegen (max. 2 Jahre)
                $inspection = new DateTime($nextInspectionDate);
                $minDate = new DateTime();
                $minDate->sub(new DateInterval('P2Y'));
                if ($inspection < $minDate) {
                    $this->addError('next_inspection_date', 'Prüfdatum liegt zu weit in der Vergangenheit');
                    $hasErrors = true;
                }

                // Prüfdatum darf nicht zu weit in der Zukunft liegen (max. 5 Jahre)
                $maxDate = new DateTime();
                $maxDate->add(new DateInterval('P5Y'));
                if ($inspection > $maxDate) {
                    $this->addError('next_inspection_date', 'Prüfdatum liegt zu weit in der Zukunft');
                    $hasErrors = true;
                }
            }
        }

        // Logische Prüfung: Prüfdatum sollte nach Kaufdatum liegen
        if (!empty($purchaseDate) && !empty($nextInspectionDate) && 
            $this->isValidDate($purchaseDate) && $this->isValidDate($nextInspectionDate)) {
            
            $purchase = new DateTime($purchaseDate);
            $inspection = new DateTime($nextInspectionDate);
            
            if ($inspection < $purchase) {
                $this->addError('next_inspection_date', 'Prüfdatum muss nach dem Kaufdatum liegen');
                $hasErrors = true;
            }
        }

        return !$hasErrors;
    }

    /**
     * Validiert Wertebereiche
     */
    public function validateRanges(Ladder $ladder): bool
    {
        $hasErrors = false;

        // Maximale Belastung
        $maxLoad = $ladder->getMaxLoadKg();
        if ($maxLoad <= 0) {
            $this->addError('max_load_kg', 'Maximale Belastung muss größer als 0 sein');
            $hasErrors = true;
        } elseif ($maxLoad > 1000) {
            $this->addError('max_load_kg', 'Maximale Belastung scheint unrealistisch hoch (>1000kg)');
            $hasErrors = true;
        } elseif ($maxLoad < 50) {
            $this->addError('max_load_kg', 'Maximale Belastung scheint unrealistisch niedrig (<50kg)');
            $hasErrors = true;
        }

        // Höhe
        $height = $ladder->getHeightCm();
        if ($height <= 0) {
            $this->addError('height_cm', 'Höhe muss größer als 0 sein');
            $hasErrors = true;
        } elseif ($height > 2000) {
            $this->addError('height_cm', 'Höhe scheint unrealistisch hoch (>20m)');
            $hasErrors = true;
        } elseif ($height < 50) {
            $this->addError('height_cm', 'Höhe scheint unrealistisch niedrig (<50cm)');
            $hasErrors = true;
        }

        // Prüfintervall
        $interval = $ladder->getInspectionIntervalMonths();
        if ($interval <= 0) {
            $this->addError('inspection_interval_months', 'Prüfintervall muss größer als 0 sein');
            $hasErrors = true;
        } elseif ($interval > 60) {
            $this->addError('inspection_interval_months', 'Prüfintervall scheint zu lang (>5 Jahre)');
            $hasErrors = true;
        } elseif ($interval < 1) {
            $this->addError('inspection_interval_months', 'Prüfintervall muss mindestens 1 Monat betragen');
            $hasErrors = true;
        }

        return !$hasErrors;
    }

    /**
     * Validiert Enum-Werte
     */
    public function validateEnums(Ladder $ladder): bool
    {
        $hasErrors = false;

        // Leitertyp
        if (!in_array($ladder->getLadderType(), Ladder::LADDER_TYPES)) {
            $this->addError('ladder_type', 'Ungültiger Leitertyp: ' . $ladder->getLadderType());
            $hasErrors = true;
        }

        // Material
        if (!in_array($ladder->getMaterial(), Ladder::MATERIALS)) {
            $this->addError('material', 'Ungültiges Material: ' . $ladder->getMaterial());
            $hasErrors = true;
        }

        // Status
        if (!in_array($ladder->getStatus(), Ladder::STATUSES)) {
            $this->addError('status', 'Ungültiger Status: ' . $ladder->getStatus());
            $hasErrors = true;
        }

        return !$hasErrors;
    }

    /**
     * Validiert Geschäftslogik-Regeln
     */
    public function validateBusinessRules(Ladder $ladder): bool
    {
        $hasErrors = false;

        // Regel: Defekte Leitern dürfen kein zukünftiges Prüfdatum haben
        if ($ladder->getStatus() === 'defective') {
            $nextInspection = new DateTime($ladder->getNextInspectionDate());
            $today = new DateTime();
            
            if ($nextInspection > $today) {
                $this->addError('next_inspection_date', 'Defekte Leitern dürfen kein zukünftiges Prüfdatum haben');
                $hasErrors = true;
            }
        }

        // Regel: Entsorgte Leitern dürfen nicht aktiv sein
        if ($ladder->getStatus() === 'disposed') {
            // Zusätzliche Validierung könnte hier erfolgen
        }

        // Regel: Materialspezifische Validierung
        if ($ladder->getMaterial() === 'Holz') {
            // Holzleitern haben oft kürzere Prüfintervalle
            if ($ladder->getInspectionIntervalMonths() > 12) {
                $this->addError('inspection_interval_months', 'Holzleitern sollten mindestens jährlich geprüft werden');
                $hasErrors = true;
            }
        }

        // Regel: Höhenabhängige Belastung
        $height = $ladder->getHeightCm();
        $maxLoad = $ladder->getMaxLoadKg();
        
        if ($height > 500 && $maxLoad > 150) {
            // Hohe Leitern mit hoher Belastung sind ungewöhnlich
            $this->addError('max_load_kg', 'Hohe Leitern (>5m) haben normalerweise eine geringere maximale Belastung');
            $hasErrors = true;
        }

        return !$hasErrors;
    }

    /**
     * Prüft ob ein Datum gültig ist
     */
    private function isValidDate(string $date): bool
    {
        if (empty($date)) {
            return false;
        }

        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Fügt einen Validierungsfehler hinzu
     */
    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    /**
     * Gibt alle Validierungsfehler zurück
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Prüft ob Validierungsfehler vorhanden sind
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Gibt Fehler für ein bestimmtes Feld zurück
     */
    public function getFieldErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * Gibt alle Fehlermeldungen als flaches Array zurück
     */
    public function getAllErrorMessages(): array
    {
        $messages = [];
        foreach ($this->errors as $field => $fieldErrors) {
            foreach ($fieldErrors as $error) {
                $messages[] = $error;
            }
        }
        return $messages;
    }

    /**
     * Setzt die Fehler zurück
     */
    public function clearErrors(): void
    {
        $this->errors = [];
    }

    /**
     * Validiert ein Array von Leiter-Daten (für Batch-Import)
     */
    public function validateArray(array $data, bool $isUpdate = false): array
    {
        try {
            $ladder = new Ladder($data);
            return $this->validate($ladder, $isUpdate);
        } catch (Exception $e) {
            return ['general' => ['Fehler beim Erstellen des Leiter-Objekts: ' . $e->getMessage()]];
        }
    }

    /**
     * Schnelle Validierung nur der wichtigsten Felder
     */
    public function quickValidate(array $data): array
    {
        $errors = [];

        // Nur die wichtigsten Pflichtfelder prüfen
        if (empty($data['ladder_number'])) {
            $errors['ladder_number'] = ['Leiternummer ist erforderlich'];
        }

        if (empty($data['manufacturer'])) {
            $errors['manufacturer'] = ['Hersteller ist erforderlich'];
        }

        if (empty($data['ladder_type'])) {
            $errors['ladder_type'] = ['Leitertyp ist erforderlich'];
        }

        if (empty($data['location'])) {
            $errors['location'] = ['Standort ist erforderlich'];
        }

        if (empty($data['next_inspection_date'])) {
            $errors['next_inspection_date'] = ['Nächstes Prüfdatum ist erforderlich'];
        }

        return $errors;
    }
}
