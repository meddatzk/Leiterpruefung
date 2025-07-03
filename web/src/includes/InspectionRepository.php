<?php

require_once __DIR__ . '/Inspection.php';
require_once __DIR__ . '/InspectionItem.php';

/**
 * InspectionRepository - Datenbank-Operationen für Prüfungen
 * 
 * @author System
 * @version 1.0
 */
class InspectionRepository
{
    private PDO $pdo;
    private AuditLogger $auditLogger;

    public function __construct(PDO $pdo, AuditLogger $auditLogger)
    {
        $this->pdo = $pdo;
        $this->auditLogger = $auditLogger;
    }

    /**
     * Erstellt eine neue Prüfung mit Prüfpunkten (Transaktion)
     */
    public function create(Inspection $inspection, array $inspectionItems = []): int
    {
        // Validierung
        $errors = $inspection->validate();
        if (!empty($errors)) {
            throw new InvalidArgumentException('Validierungsfehler Prüfung: ' . implode(', ', $errors));
        }

        // Prüfpunkte validieren
        foreach ($inspectionItems as $item) {
            if (!$item instanceof InspectionItem) {
                throw new InvalidArgumentException('Alle Prüfpunkte müssen InspectionItem-Objekte sein');
            }
            $itemErrors = $item->validate();
            if (!empty($itemErrors)) {
                throw new InvalidArgumentException('Validierungsfehler Prüfpunkt: ' . implode(', ', $itemErrors));
            }
        }

        $this->pdo->beginTransaction();

        try {
            // Prüfung erstellen
            $inspectionId = $this->createInspection($inspection);
            $inspection->setId($inspectionId);

            // Prüfpunkte erstellen
            foreach ($inspectionItems as $item) {
                $item->setInspectionId($inspectionId);
                $this->createInspectionItem($item);
            }

            // Gesamtergebnis berechnen und aktualisieren
            $inspection->setInspectionItems($inspectionItems);
            $overallResult = $inspection->calculateOverallResult();
            $this->updateOverallResult($inspectionId, $overallResult);

            // Nächstes Prüfdatum der Leiter aktualisieren
            $this->updateLadderNextInspectionDate($inspection->getLadderId(), $inspection->getNextInspectionDate());

            $this->pdo->commit();

            // Audit-Log
            $this->auditLogger->logCreate('inspections', $inspectionId, $inspection->toArray());

            return $inspectionId;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new RuntimeException('Fehler beim Erstellen der Prüfung: ' . $e->getMessage());
        }
    }

    /**
     * Findet eine Prüfung anhand der ID
     */
    public function findById(int $id): ?Inspection
    {
        $sql = "SELECT i.*, 
                       l.ladder_number, l.manufacturer, l.model,
                       u1.username as inspector_username, u1.display_name as inspector_name,
                       u2.username as supervisor_username, u2.display_name as supervisor_name
                FROM inspections i
                LEFT JOIN ladders l ON i.ladder_id = l.id
                LEFT JOIN users u1 ON i.inspector_id = u1.id
                LEFT JOIN users u2 ON i.supervisor_approval_id = u2.id
                WHERE i.id = :id";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$data) {
                return null;
            }

            $inspection = new Inspection($data);
            
            // Prüfpunkte laden
            $inspectionItems = $this->getInspectionItems($id);
            $inspection->setInspectionItems($inspectionItems);

            // Audit-Log für Zugriff
            $this->auditLogger->logAccess('inspections', $id);

            return $inspection;

        } catch (PDOException $e) {
            throw new RuntimeException('Fehler beim Laden der Prüfung: ' . $e->getMessage());
        }
    }

    /**
     * Findet Prüfungen einer Leiter
     */
    public function findByLadder(int $ladderId, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT i.*, 
                       u1.username as inspector_username, u1.display_name as inspector_name,
                       u2.username as supervisor_username, u2.display_name as supervisor_name
                FROM inspections i
                LEFT JOIN users u1 ON i.inspector_id = u1.id
                LEFT JOIN users u2 ON i.supervisor_approval_id = u2.id
                WHERE i.ladder_id = :ladder_id
                ORDER BY i.inspection_date DESC
                LIMIT :limit OFFSET :offset";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':ladder_id', $ladderId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $inspections = [];
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $inspection = new Inspection($data);
                
                // Prüfpunkte laden
                $inspectionItems = $this->getInspectionItems($inspection->getId());
                $inspection->setInspectionItems($inspectionItems);
                
                $inspections[] = $inspection;
            }

            return $inspections;

        } catch (PDOException $e) {
            throw new RuntimeException('Fehler beim Laden der Prüfungen: ' . $e->getMessage());
        }
    }

    /**
     * Gibt die Prüfungshistorie zurück
     */
    public function getHistory(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $where = [];
        $params = [];

        // Filter aufbauen
        if (!empty($filters['ladder_id'])) {
            $where[] = "i.ladder_id = :ladder_id";
            $params[':ladder_id'] = $filters['ladder_id'];
        }

        if (!empty($filters['inspector_id'])) {
            $where[] = "i.inspector_id = :inspector_id";
            $params[':inspector_id'] = $filters['inspector_id'];
        }

        if (!empty($filters['inspection_type'])) {
            $where[] = "i.inspection_type = :inspection_type";
            $params[':inspection_type'] = $filters['inspection_type'];
        }

        if (!empty($filters['overall_result'])) {
            $where[] = "i.overall_result = :overall_result";
            $params[':overall_result'] = $filters['overall_result'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "i.inspection_date >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "i.inspection_date <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }

        if (!empty($filters['ladder_number'])) {
            $where[] = "l.ladder_number LIKE :ladder_number";
            $params[':ladder_number'] = '%' . $filters['ladder_number'] . '%';
        }

        $sql = "SELECT i.*, 
                       l.ladder_number, l.manufacturer, l.model, l.location,
                       u1.username as inspector_username, u1.display_name as inspector_name,
                       u2.username as supervisor_username, u2.display_name as supervisor_name
                FROM inspections i
                LEFT JOIN ladders l ON i.ladder_id = l.id
                LEFT JOIN users u1 ON i.inspector_id = u1.id
                LEFT JOIN users u2 ON i.supervisor_approval_id = u2.id";

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " ORDER BY i.inspection_date DESC LIMIT :limit OFFSET :offset";

        try {
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            
            $inspections = [];
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $inspections[] = new Inspection($data);
            }

            return $inspections;

        } catch (PDOException $e) {
            throw new RuntimeException('Fehler beim Laden der Prüfungshistorie: ' . $e->getMessage());
        }
    }

    /**
     * Gibt anstehende Prüfungen zurück
     */
    public function getUpcoming(int $daysAhead = 30): array
    {
        $sql = "SELECT l.*, 
                       COALESCE(last_inspection.inspection_date, l.created_at) as last_inspection_date,
                       last_inspection.overall_result as last_result
                FROM ladders l
                LEFT JOIN (
                    SELECT ladder_id, 
                           MAX(inspection_date) as inspection_date,
                           overall_result
                    FROM inspections 
                    GROUP BY ladder_id
                ) last_inspection ON l.id = last_inspection.ladder_id
                WHERE l.status = 'active' 
                AND l.next_inspection_date <= DATE_ADD(CURDATE(), INTERVAL :days DAY)
                ORDER BY l.next_inspection_date ASC";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':days', $daysAhead, PDO::PARAM_INT);
            $stmt->execute();
            
            $upcoming = [];
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $upcoming[] = $data;
            }

            return $upcoming;

        } catch (PDOException $e) {
            throw new RuntimeException('Fehler beim Laden der anstehenden Prüfungen: ' . $e->getMessage());
        }
    }

    /**
     * Berechnet das nächste Prüfdatum
     */
    public function calculateNextDate(int $ladderId, ?DateTime $baseDate = null): string
    {
        // Leiter-Daten laden
        $sql = "SELECT inspection_interval_months FROM ladders WHERE id = :id";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $ladderId]);
            
            $ladder = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$ladder) {
                throw new InvalidArgumentException("Leiter nicht gefunden: {$ladderId}");
            }

            $base = $baseDate ?: new DateTime();
            $next = clone $base;
            $next->add(new DateInterval('P' . $ladder['inspection_interval_months'] . 'M'));
            
            return $next->format('Y-m-d');

        } catch (PDOException $e) {
            throw new RuntimeException('Fehler beim Berechnen des nächsten Prüfdatums: ' . $e->getMessage());
        }
    }

    /**
     * Gibt Statistiken zurück
     */
    public function getStatistics(array $filters = []): array
    {
        $where = [];
        $params = [];

        // Filter für Zeitraum
        if (!empty($filters['date_from'])) {
            $where[] = "inspection_date >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "inspection_date <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT 
                    COUNT(*) as total_inspections,
                    SUM(CASE WHEN overall_result = 'passed' THEN 1 ELSE 0 END) as passed,
                    SUM(CASE WHEN overall_result = 'failed' THEN 1 ELSE 0 END) as failed,
                    SUM(CASE WHEN overall_result = 'conditional' THEN 1 ELSE 0 END) as conditional,
                    SUM(CASE WHEN inspection_type = 'routine' THEN 1 ELSE 0 END) as routine,
                    SUM(CASE WHEN inspection_type = 'initial' THEN 1 ELSE 0 END) as initial,
                    SUM(CASE WHEN inspection_type = 'after_incident' THEN 1 ELSE 0 END) as after_incident,
                    SUM(CASE WHEN inspection_type = 'special' THEN 1 ELSE 0 END) as special,
                    AVG(inspection_duration_minutes) as avg_duration,
                    COUNT(DISTINCT ladder_id) as unique_ladders,
                    COUNT(DISTINCT inspector_id) as unique_inspectors
                FROM inspections {$whereClause}";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            throw new RuntimeException('Fehler beim Laden der Statistiken: ' . $e->getMessage());
        }
    }

    /**
     * Gibt Mängel-Statistiken zurück
     */
    public function getDefectStatistics(array $filters = []): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['date_from'])) {
            $where[] = "i.inspection_date >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "i.inspection_date <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT 
                    ii.category,
                    COUNT(*) as total_items,
                    SUM(CASE WHEN ii.result = 'defect' THEN 1 ELSE 0 END) as defects,
                    SUM(CASE WHEN ii.result = 'defect' AND ii.severity = 'critical' THEN 1 ELSE 0 END) as critical_defects,
                    SUM(CASE WHEN ii.result = 'defect' AND ii.severity = 'high' THEN 1 ELSE 0 END) as high_defects,
                    SUM(CASE WHEN ii.result = 'defect' AND ii.severity = 'medium' THEN 1 ELSE 0 END) as medium_defects,
                    SUM(CASE WHEN ii.result = 'defect' AND ii.severity = 'low' THEN 1 ELSE 0 END) as low_defects,
                    SUM(CASE WHEN ii.result = 'wear' THEN 1 ELSE 0 END) as wear_items
                FROM inspection_items ii
                JOIN inspections i ON ii.inspection_id = i.id
                {$whereClause}
                GROUP BY ii.category
                ORDER BY defects DESC";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            throw new RuntimeException('Fehler beim Laden der Mängel-Statistiken: ' . $e->getMessage());
        }
    }

    /**
     * Erstellt eine Prüfung (interne Methode)
     */
    private function createInspection(Inspection $inspection): int
    {
        $sql = "INSERT INTO inspections (
            ladder_id, inspector_id, inspection_date, inspection_type, 
            overall_result, next_inspection_date, inspection_duration_minutes,
            weather_conditions, temperature_celsius, general_notes,
            recommendations, defects_found, actions_required,
            inspector_signature, supervisor_approval_id, approval_date
        ) VALUES (
            :ladder_id, :inspector_id, :inspection_date, :inspection_type,
            :overall_result, :next_inspection_date, :inspection_duration_minutes,
            :weather_conditions, :temperature_celsius, :general_notes,
            :recommendations, :defects_found, :actions_required,
            :inspector_signature, :supervisor_approval_id, :approval_date
        )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':ladder_id' => $inspection->getLadderId(),
            ':inspector_id' => $inspection->getInspectorId(),
            ':inspection_date' => $inspection->getInspectionDate(),
            ':inspection_type' => $inspection->getInspectionType(),
            ':overall_result' => $inspection->getOverallResult(),
            ':next_inspection_date' => $inspection->getNextInspectionDate(),
            ':inspection_duration_minutes' => $inspection->getInspectionDurationMinutes(),
            ':weather_conditions' => $inspection->getWeatherConditions(),
            ':temperature_celsius' => $inspection->getTemperatureCelsius(),
            ':general_notes' => $inspection->getGeneralNotes(),
            ':recommendations' => $inspection->getRecommendations(),
            ':defects_found' => $inspection->getDefectsFound(),
            ':actions_required' => $inspection->getActionsRequired(),
            ':inspector_signature' => $inspection->getInspectorSignature(),
            ':supervisor_approval_id' => $inspection->getSupervisorApprovalId(),
            ':approval_date' => $inspection->getApprovalDate()
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Erstellt einen Prüfpunkt (interne Methode)
     */
    private function createInspectionItem(InspectionItem $item): int
    {
        $sql = "INSERT INTO inspection_items (
            inspection_id, category, item_name, description, result,
            severity, notes, photo_path, repair_required, repair_deadline, sort_order
        ) VALUES (
            :inspection_id, :category, :item_name, :description, :result,
            :severity, :notes, :photo_path, :repair_required, :repair_deadline, :sort_order
        )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':inspection_id' => $item->getInspectionId(),
            ':category' => $item->getCategory(),
            ':item_name' => $item->getItemName(),
            ':description' => $item->getDescription(),
            ':result' => $item->getResult(),
            ':severity' => $item->getSeverity(),
            ':notes' => $item->getNotes(),
            ':photo_path' => $item->getPhotoPath(),
            ':repair_required' => $item->isRepairRequired() ? 1 : 0,
            ':repair_deadline' => $item->getRepairDeadline(),
            ':sort_order' => $item->getSortOrder()
        ]);

        $itemId = (int) $this->pdo->lastInsertId();
        $item->setId($itemId);

        return $itemId;
    }

    /**
     * Lädt Prüfpunkte einer Prüfung
     */
    private function getInspectionItems(int $inspectionId): array
    {
        $sql = "SELECT * FROM inspection_items 
                WHERE inspection_id = :inspection_id 
                ORDER BY sort_order ASC, category ASC, item_name ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':inspection_id' => $inspectionId]);
        
        $items = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $items[] = new InspectionItem($data);
        }

        return $items;
    }

    /**
     * Aktualisiert das Gesamtergebnis einer Prüfung
     */
    private function updateOverallResult(int $inspectionId, string $overallResult): void
    {
        $sql = "UPDATE inspections SET overall_result = :overall_result WHERE id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':overall_result' => $overallResult,
            ':id' => $inspectionId
        ]);
    }

    /**
     * Aktualisiert das nächste Prüfdatum der Leiter
     */
    private function updateLadderNextInspectionDate(int $ladderId, string $nextInspectionDate): void
    {
        $sql = "UPDATE ladders SET next_inspection_date = :next_inspection_date WHERE id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':next_inspection_date' => $nextInspectionDate,
            ':id' => $ladderId
        ]);
    }

    /**
     * Zählt Prüfungen mit Filtern
     */
    public function count(array $filters = []): int
    {
        $where = [];
        $params = [];

        if (!empty($filters['ladder_id'])) {
            $where[] = "i.ladder_id = :ladder_id";
            $params[':ladder_id'] = $filters['ladder_id'];
        }

        if (!empty($filters['inspector_id'])) {
            $where[] = "i.inspector_id = :inspector_id";
            $params[':inspector_id'] = $filters['inspector_id'];
        }

        if (!empty($filters['inspection_type'])) {
            $where[] = "i.inspection_type = :inspection_type";
            $params[':inspection_type'] = $filters['inspection_type'];
        }

        if (!empty($filters['overall_result'])) {
            $where[] = "i.overall_result = :overall_result";
            $params[':overall_result'] = $filters['overall_result'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "i.inspection_date >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "i.inspection_date <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }

        $sql = "SELECT COUNT(*) FROM inspections i";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return (int) $stmt->fetchColumn();

        } catch (PDOException $e) {
            throw new RuntimeException('Fehler beim Zählen der Prüfungen: ' . $e->getMessage());
        }
    }
}
