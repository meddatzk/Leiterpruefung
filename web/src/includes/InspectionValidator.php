<?php

require_once __DIR__ . '/Inspection.php';
require_once __DIR__ . '/InspectionItem.php';

/**
 * InspectionValidator - Validierung für Prüfungen und Prüfpunkte
 * 
 * @author System
 * @version 1.0
 */
class InspectionValidator
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Validiert ein Prüfdatum
     */
    public function validateInspectionDate(string $inspectionDate, ?int $ladderId = null): array
    {
        $errors = [];

        // Grundlegende Datumsvalidierung
        if (!$this->isValidDate($inspectionDate)) {
            $errors[] = 'Ungültiges Datumsformat (YYYY-MM-DD erwartet)';
            return $errors;
        }

        $date = new DateTime($inspectionDate);
        $today = new DateTime();
        $maxPastDays = 365; // Maximal 1 Jahr in der Vergangenheit
        $maxFutureDays = 30; // Maximal 30 Tage in der Zukunft

        // Datum nicht zu weit in der Vergangenheit
        $minDate = clone $today;
        $minDate->sub(new DateInterval('P' . $maxPastDays . 'D'));
        
        if ($date < $minDate) {
            $errors[] = "Prüfdatum darf nicht mehr als {$maxPastDays} Tage in der Vergangenheit liegen";
        }

        // Datum nicht zu weit in der Zukunft
        $maxDate = clone $today;
        $maxDate->add(new DateInterval('P' . $maxFutureDays . 'D'));
        
        if ($date > $maxDate) {
            $errors[] = "Prüfdatum darf nicht mehr als {$maxFutureDays} Tage in der Zukunft liegen";
        }

        // Leiter-spezifische Validierung
        if ($ladderId) {
            $ladderErrors = $this->validateInspectionDateForLadder($inspectionDate, $ladderId);
            $errors = array_merge($errors, $ladderErrors);
        }

        return $errors;
    }

    /**
     * Validiert Prüfpunkte
     */
    public function validateItems(array $inspectionItems): array
    {
        $errors = [];

        if (empty($inspectionItems)) {
            $errors[] = 'Mindestens ein Prüfpunkt ist erforderlich';
            return $errors;
        }

        $categories = [];
        $sortOrders = [];

        foreach ($inspectionItems as $index => $item) {
            if (!$item instanceof InspectionItem) {
                $errors[] = "Prüfpunkt {$index} ist kein gültiges InspectionItem-Objekt";
                continue;
            }

            // Einzelvalidierung des Prüfpunkts
            $itemErrors = $item->validate();
            foreach ($itemErrors as $error) {
                $errors[] = "Prüfpunkt {$index}: {$error}";
            }

            // Kategorien sammeln
            $categories[] = $item->getCategory();

            // Sortierreihenfolge prüfen
            $sortOrder = $item->getSortOrder();
            if (in_array($sortOrder, $sortOrders)) {
                $errors[] = "Sortierreihenfolge {$sortOrder} ist mehrfach vergeben";
            }
            $sortOrders[] = $sortOrder;
        }

        // Mindestens eine Kategorie aus jeder Hauptgruppe
        $requiredCategories = ['structure', 'safety', 'function'];
        $missingCategories = array_diff($requiredCategories, $categories);
        
        if (!empty($missingCategories)) {
            $errors[] = 'Folgende Kategorien sind erforderlich: ' . implode(', ', $missingCategories);
        }

        // Kritische Defekte prüfen
        $criticalDefects = [];
        foreach ($inspectionItems as $item) {
            if ($item->isCritical()) {
                $criticalDefects[] = $item->getItemName();
            }
        }

        if (!empty($criticalDefects)) {
            $errors[] = 'Kritische Defekte gefunden: ' . implode(', ', $criticalDefects) . 
                       ' - Prüfung kann nicht als "bestanden" markiert werden';
        }

        return $errors;
    }

    /**
     * Validiert einen Status
     */
    public function validateStatus(string $status, array $inspectionItems = []): array
    {
        $errors = [];

        // Gültiger Status
        if (!in_array($status, Inspection::OVERALL_RESULTS)) {
            $errors[] = 'Ungültiger Status: ' . $status;
            return $errors;
        }

        // Status-spezifische Validierung
        if (!empty($inspectionItems)) {
            $calculatedStatus = $this->calculateStatusFromItems($inspectionItems);
            
            if ($status === 'passed' && $calculatedStatus !== 'passed') {
                $errors[] = 'Status "bestanden" nicht möglich bei vorhandenen Defekten';
            }
            
            if ($status === 'failed' && $calculatedStatus !== 'failed') {
                $errors[] = 'Status "nicht bestanden" nur bei kritischen Defekten erlaubt';
            }
        }

        return $errors;
    }

    /**
     * Validiert die Vollständigkeit einer Prüfung
     */
    public function validateCompleteness(Inspection $inspection): array
    {
        $errors = [];

        // Pflichtfelder
        if (!$inspection->getLadderId()) {
            $errors[] = 'Leiter-ID ist erforderlich';
        }

        if (!$inspection->getInspectorId()) {
            $errors[] = 'Prüfer-ID ist erforderlich';
        }

        if (!$inspection->getInspectionDate()) {
            $errors[] = 'Prüfdatum ist erforderlich';
        }

        if (!$inspection->getOverallResult()) {
            $errors[] = 'Gesamtergebnis ist erforderlich';
        }

        if (!$inspection->getNextInspectionDate()) {
            $errors[] = 'Nächstes Prüfdatum ist erforderlich';
        }

        // Prüfpunkte
        $inspectionItems = $inspection->getInspectionItems();
        if (empty($inspectionItems)) {
            $errors[] = 'Prüfpunkte sind erforderlich';
        }

        // Unterschrift bei abgeschlossener Prüfung
        if ($inspection->getOverallResult() && !$inspection->getInspectorSignature()) {
            $errors[] = 'Prüfer-Unterschrift ist bei abgeschlossener Prüfung erforderlich';
        }

        // Genehmigung bei kritischen Defekten
        $criticalDefects = $inspection->getCriticalDefects();
        if (!empty($criticalDefects) && !$inspection->isApproved()) {
            $errors[] = 'Vorgesetztengenehmigung bei kritischen Defekten erforderlich';
        }

        // Konsistenz zwischen Prüfpunkten und Gesamtergebnis
        if (!empty($inspectionItems)) {
            $calculatedResult = $inspection->calculateOverallResult();
            if ($inspection->getOverallResult() !== $calculatedResult) {
                $errors[] = "Gesamtergebnis inkonsistent: erwartet '{$calculatedResult}', erhalten '{$inspection->getOverallResult()}'";
            }
        }

        return $errors;
    }

    /**
     * Validiert eine komplette Prüfung mit allen Abhängigkeiten
     */
    public function validateComplete(Inspection $inspection): array
    {
        $errors = [];

        // Grundvalidierung
        $basicErrors = $inspection->validate();
        $errors = array_merge($errors, $basicErrors);

        // Vollständigkeitsvalidierung
        $completenessErrors = $this->validateCompleteness($inspection);
        $errors = array_merge($errors, $completenessErrors);

        // Prüfdatum validieren
        if ($inspection->getInspectionDate()) {
            $dateErrors = $this->validateInspectionDate(
                $inspection->getInspectionDate(), 
                $inspection->getLadderId()
            );
            $errors = array_merge($errors, $dateErrors);
        }

        // Prüfpunkte validieren
        $inspectionItems = $inspection->getInspectionItems();
        if (!empty($inspectionItems)) {
            $itemErrors = $this->validateItems($inspectionItems);
            $errors = array_merge($errors, $itemErrors);
        }

        // Status validieren
        if ($inspection->getOverallResult()) {
            $statusErrors = $this->validateStatus($inspection->getOverallResult(), $inspectionItems);
            $errors = array_merge($errors, $statusErrors);
        }

        // Geschäftslogik-Validierung
        $businessErrors = $this->validateBusinessRules($inspection);
        $errors = array_merge($errors, $businessErrors);

        return array_unique($errors);
    }

    /**
     * Validiert Geschäftsregeln
     */
    public function validateBusinessRules(Inspection $inspection): array
    {
        $errors = [];

        // Leiter muss existieren und aktiv sein
        if ($inspection->getLadderId()) {
            $ladderErrors = $this->validateLadderStatus($inspection->getLadderId());
            $errors = array_merge($errors, $ladderErrors);
        }

        // Prüfer muss existieren und berechtigt sein
        if ($inspection->getInspectorId()) {
            $inspectorErrors = $this->validateInspectorPermissions($inspection->getInspectorId());
            $errors = array_merge($errors, $inspectorErrors);
        }

        // Prüfintervall einhalten
        if ($inspection->getLadderId() && $inspection->getInspectionDate()) {
            $intervalErrors = $this->validateInspectionInterval($inspection->getLadderId(), $inspection->getInspectionDate());
            $errors = array_merge($errors, $intervalErrors);
        }

        // Reparatur-Deadlines prüfen
        foreach ($inspection->getInspectionItems() as $item) {
            if ($item->isRepairRequired() && $item->getRepairDeadline()) {
                $deadlineErrors = $this->validateRepairDeadline($item);
                $errors = array_merge($errors, $deadlineErrors);
            }
        }

        return $errors;
    }

    /**
     * Validiert das Prüfdatum für eine spezifische Leiter
     */
    private function validateInspectionDateForLadder(string $inspectionDate, int $ladderId): array
    {
        $errors = [];

        try {
            // Letzte Prüfung dieser Leiter finden
            $sql = "SELECT MAX(inspection_date) as last_inspection 
                    FROM inspections 
                    WHERE ladder_id = :ladder_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':ladder_id' => $ladderId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['last_inspection']) {
                $lastInspection = new DateTime($result['last_inspection']);
                $currentInspection = new DateTime($inspectionDate);

                // Mindestabstand zwischen Prüfungen (z.B. 1 Tag)
                $minInterval = clone $lastInspection;
                $minInterval->add(new DateInterval('P1D'));

                if ($currentInspection < $minInterval) {
                    $errors[] = 'Prüfdatum muss mindestens 1 Tag nach der letzten Prüfung liegen';
                }
            }

        } catch (PDOException $e) {
            $errors[] = 'Fehler bei der Validierung des Prüfdatums: ' . $e->getMessage();
        }

        return $errors;
    }

    /**
     * Berechnet den Status basierend auf Prüfpunkten
     */
    private function calculateStatusFromItems(array $inspectionItems): string
    {
        $hasDefects = false;
        $hasCriticalDefects = false;

        foreach ($inspectionItems as $item) {
            if ($item instanceof InspectionItem && $item->isDefect()) {
                $hasDefects = true;
                if ($item->isCritical()) {
                    $hasCriticalDefects = true;
                    break;
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
     * Validiert den Status der Leiter
     */
    private function validateLadderStatus(int $ladderId): array
    {
        $errors = [];

        try {
            $sql = "SELECT status FROM ladders WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $ladderId]);
            
            $ladder = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$ladder) {
                $errors[] = "Leiter mit ID {$ladderId} nicht gefunden";
            } elseif ($ladder['status'] !== 'active') {
                $errors[] = "Leiter ist nicht aktiv (Status: {$ladder['status']})";
            }

        } catch (PDOException $e) {
            $errors[] = 'Fehler bei der Leiter-Validierung: ' . $e->getMessage();
        }

        return $errors;
    }

    /**
     * Validiert die Berechtigung des Prüfers
     */
    private function validateInspectorPermissions(int $inspectorId): array
    {
        $errors = [];

        try {
            $sql = "SELECT role, is_active FROM users WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $inspectorId]);
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                $errors[] = "Prüfer mit ID {$inspectorId} nicht gefunden";
            } else {
                if (!$user['is_active']) {
                    $errors[] = "Prüfer ist nicht aktiv";
                }
                
                $allowedRoles = ['admin', 'inspector'];
                if (!in_array($user['role'], $allowedRoles)) {
                    $errors[] = "Prüfer hat keine Berechtigung für Prüfungen (Rolle: {$user['role']})";
                }
            }

        } catch (PDOException $e) {
            $errors[] = 'Fehler bei der Prüfer-Validierung: ' . $e->getMessage();
        }

        return $errors;
    }

    /**
     * Validiert das Prüfintervall
     */
    private function validateInspectionInterval(int $ladderId, string $inspectionDate): array
    {
        $errors = [];

        try {
            $sql = "SELECT next_inspection_date, inspection_interval_months 
                    FROM ladders 
                    WHERE id = :id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $ladderId]);
            
            $ladder = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($ladder) {
                $nextDue = new DateTime($ladder['next_inspection_date']);
                $inspectionDateTime = new DateTime($inspectionDate);
                
                // Warnung wenn Prüfung zu früh
                $earlyThreshold = clone $nextDue;
                $earlyThreshold->sub(new DateInterval('P30D')); // 30 Tage vor Fälligkeit
                
                if ($inspectionDateTime < $earlyThreshold) {
                    $errors[] = 'Prüfung erfolgt sehr früh (mehr als 30 Tage vor Fälligkeit)';
                }
                
                // Fehler wenn Prüfung zu spät
                $lateThreshold = clone $nextDue;
                $lateThreshold->add(new DateInterval('P90D')); // 90 Tage nach Fälligkeit
                
                if ($inspectionDateTime > $lateThreshold) {
                    $errors[] = 'Prüfung erfolgt zu spät (mehr als 90 Tage nach Fälligkeit)';
                }
            }

        } catch (PDOException $e) {
            $errors[] = 'Fehler bei der Intervall-Validierung: ' . $e->getMessage();
        }

        return $errors;
    }

    /**
     * Validiert Reparatur-Deadlines
     */
    private function validateRepairDeadline(InspectionItem $item): array
    {
        $errors = [];

        if (!$item->getRepairDeadline()) {
            return $errors;
        }

        $deadline = new DateTime($item->getRepairDeadline());
        $today = new DateTime();

        // Deadline nicht in der Vergangenheit
        if ($deadline < $today) {
            $errors[] = "Reparatur-Deadline für '{$item->getItemName()}' liegt in der Vergangenheit";
        }

        // Deadline nicht zu weit in der Zukunft
        $maxFuture = clone $today;
        $maxFuture->add(new DateInterval('P2Y')); // Maximal 2 Jahre

        if ($deadline > $maxFuture) {
            $errors[] = "Reparatur-Deadline für '{$item->getItemName()}' liegt zu weit in der Zukunft";
        }

        // Deadline abhängig vom Schweregrad
        $maxDays = $this->getMaxRepairDays($item->getSeverity());
        $maxDeadline = clone $today;
        $maxDeadline->add(new DateInterval('P' . $maxDays . 'D'));

        if ($deadline > $maxDeadline) {
            $errors[] = "Reparatur-Deadline für '{$item->getItemName()}' überschreitet maximale Frist für Schweregrad '{$item->getSeverity()}' ({$maxDays} Tage)";
        }

        return $errors;
    }

    /**
     * Gibt maximale Reparaturtage basierend auf Schweregrad zurück
     */
    private function getMaxRepairDays(?string $severity): int
    {
        switch ($severity) {
            case 'critical':
                return 1; // Sofortige Reparatur
            case 'high':
                return 7; // 1 Woche
            case 'medium':
                return 30; // 1 Monat
            case 'low':
            default:
                return 90; // 3 Monate
        }
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
     * Validiert eine Prüfung vor dem Speichern
     */
    public function validateForSave(Inspection $inspection): array
    {
        $errors = $this->validateComplete($inspection);

        // Zusätzliche Validierung für das Speichern
        if ($inspection->isImmutable()) {
            $errors[] = 'Prüfung ist unveränderlich und kann nicht gespeichert werden';
        }

        return $errors;
    }

    /**
     * Validiert eine Prüfung vor der Genehmigung
     */
    public function validateForApproval(Inspection $inspection, int $supervisorId): array
    {
        $errors = [];

        // Prüfung muss vollständig sein
        $completenessErrors = $this->validateCompleteness($inspection);
        $errors = array_merge($errors, $completenessErrors);

        // Vorgesetzter muss berechtigt sein
        $supervisorErrors = $this->validateSupervisorPermissions($supervisorId);
        $errors = array_merge($errors, $supervisorErrors);

        // Prüfung darf nicht bereits genehmigt sein
        if ($inspection->isApproved()) {
            $errors[] = 'Prüfung ist bereits genehmigt';
        }

        return $errors;
    }

    /**
     * Validiert die Berechtigung des Vorgesetzten
     */
    private function validateSupervisorPermissions(int $supervisorId): array
    {
        $errors = [];

        try {
            $sql = "SELECT role, is_active FROM users WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $supervisorId]);
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                $errors[] = "Vorgesetzter mit ID {$supervisorId} nicht gefunden";
            } else {
                if (!$user['is_active']) {
                    $errors[] = "Vorgesetzter ist nicht aktiv";
                }
                
                if ($user['role'] !== 'admin') {
                    $errors[] = "Vorgesetzter hat keine Berechtigung für Genehmigungen (Rolle: {$user['role']})";
                }
            }

        } catch (PDOException $e) {
            $errors[] = 'Fehler bei der Vorgesetzten-Validierung: ' . $e->getMessage();
        }

        return $errors;
    }
}
