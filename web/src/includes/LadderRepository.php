<?php

require_once __DIR__ . '/Ladder.php';

/**
 * LadderRepository - Datenbank-Operationen für Leitern
 * 
 * @author System
 * @version 1.0
 */
class LadderRepository
{
    private PDO $pdo;
    private AuditLogger $auditLogger;

    public function __construct(PDO $pdo, AuditLogger $auditLogger)
    {
        $this->pdo = $pdo;
        $this->auditLogger = $auditLogger;
    }

    /**
     * Erstellt eine neue Leiter
     */
    public function create(Ladder $ladder): int
    {
        // Validierung
        $errors = $ladder->validate();
        if (!empty($errors)) {
            throw new InvalidArgumentException('Validierungsfehler: ' . implode(', ', $errors));
        }

        // Eindeutigkeit der Leiternummer prüfen
        if ($this->existsByLadderNumber($ladder->getLadderNumber())) {
            throw new InvalidArgumentException('Leiternummer bereits vorhanden: ' . $ladder->getLadderNumber());
        }

        $sql = "INSERT INTO ladders (
            ladder_number, manufacturer, model, ladder_type, material, 
            max_load_kg, height_cm, purchase_date, location, department, 
            responsible_person, serial_number, notes, status, 
            next_inspection_date, inspection_interval_months
        ) VALUES (
            :ladder_number, :manufacturer, :model, :ladder_type, :material,
            :max_load_kg, :height_cm, :purchase_date, :location, :department,
            :responsible_person, :serial_number, :notes, :status,
            :next_inspection_date, :inspection_interval_months
        )";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':ladder_number' => $ladder->getLadderNumber(),
                ':manufacturer' => $ladder->getManufacturer(),
                ':model' => $ladder->getModel(),
                ':ladder_type' => $ladder->getLadderType(),
                ':material' => $ladder->getMaterial(),
                ':max_load_kg' => $ladder->getMaxLoadKg(),
                ':height_cm' => $ladder->getHeightCm(),
                ':purchase_date' => $ladder->getPurchaseDate(),
                ':location' => $ladder->getLocation(),
                ':department' => $ladder->getDepartment(),
                ':responsible_person' => $ladder->getResponsiblePerson(),
                ':serial_number' => $ladder->getSerialNumber(),
                ':notes' => $ladder->getNotes(),
                ':status' => $ladder->getStatus(),
                ':next_inspection_date' => $ladder->getNextInspectionDate(),
                ':inspection_interval_months' => $ladder->getInspectionIntervalMonths()
            ]);

            $ladderId = (int) $this->pdo->lastInsertId();
            $ladder->setId($ladderId);

            // Audit-Log
            $this->auditLogger->logCreate('ladders', $ladderId, $ladder->toArray());

            return $ladderId;

        } catch (PDOException $e) {
            throw new RuntimeException('Fehler beim Erstellen der Leiter: ' . $e->getMessage());
        }
    }

    /**
     * Aktualisiert eine bestehende Leiter
     */
    public function update(Ladder $ladder): bool
    {
        if (!$ladder->getId()) {
            throw new InvalidArgumentException('Leiter-ID ist erforderlich für Update');
        }

        // Validierung
        $errors = $ladder->validate();
        if (!empty($errors)) {
            throw new InvalidArgumentException('Validierungsfehler: ' . implode(', ', $errors));
        }

        // Alte Daten für Audit-Log laden
        $oldLadder = $this->findById($ladder->getId());
        if (!$oldLadder) {
            throw new InvalidArgumentException('Leiter nicht gefunden: ' . $ladder->getId());
        }

        // Eindeutigkeit der Leiternummer prüfen (außer für die aktuelle Leiter)
        if ($this->existsByLadderNumber($ladder->getLadderNumber(), $ladder->getId())) {
            throw new InvalidArgumentException('Leiternummer bereits vorhanden: ' . $ladder->getLadderNumber());
        }

        $sql = "UPDATE ladders SET 
            ladder_number = :ladder_number,
            manufacturer = :manufacturer,
            model = :model,
            ladder_type = :ladder_type,
            material = :material,
            max_load_kg = :max_load_kg,
            height_cm = :height_cm,
            purchase_date = :purchase_date,
            location = :location,
            department = :department,
            responsible_person = :responsible_person,
            serial_number = :serial_number,
            notes = :notes,
            status = :status,
            next_inspection_date = :next_inspection_date,
            inspection_interval_months = :inspection_interval_months,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :id";

        try {
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':id' => $ladder->getId(),
                ':ladder_number' => $ladder->getLadderNumber(),
                ':manufacturer' => $ladder->getManufacturer(),
                ':model' => $ladder->getModel(),
                ':ladder_type' => $ladder->getLadderType(),
                ':material' => $ladder->getMaterial(),
                ':max_load_kg' => $ladder->getMaxLoadKg(),
                ':height_cm' => $ladder->getHeightCm(),
                ':purchase_date' => $ladder->getPurchaseDate(),
                ':location' => $ladder->getLocation(),
                ':department' => $ladder->getDepartment(),
                ':responsible_person' => $ladder->getResponsiblePerson(),
                ':serial_number' => $ladder->getSerialNumber(),
                ':notes' => $ladder->getNotes(),
                ':status' => $ladder->getStatus(),
                ':next_inspection_date' => $ladder->getNextInspectionDate(),
                ':inspection_interval_months' => $ladder->getInspectionIntervalMonths()
            ]);

            if ($result && $stmt->rowCount() > 0) {
                // Audit-Log
                $this->auditLogger->logUpdate('ladders', $ladder->getId(), 
                    $oldLadder->toArray(), $ladder->toArray());
                return true;
            }

            return false;

        } catch (PDOException $e) {
            throw new RuntimeException('Fehler beim Aktualisieren der Leiter: ' . $e->getMessage());
        }
    }

    /**
     * Löscht eine Leiter (Soft Delete)
     */
    public function delete(int $id): bool
    {
        $ladder = $this->findById($id);
        if (!$ladder) {
            throw new InvalidArgumentException('Leiter nicht gefunden: ' . $id);
        }

        $sql = "UPDATE ladders SET status = 'disposed', updated_at = CURRENT_TIMESTAMP WHERE id = :id";

        try {
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([':id' => $id]);

            if ($result && $stmt->rowCount() > 0) {
                // Audit-Log
                $this->auditLogger->logDelete('ladders', $id, $ladder->toArray());
                return true;
            }

            return false;

        } catch (PDOException $e) {
            throw new RuntimeException('Fehler beim Löschen der Leiter: ' . $e->getMessage());
        }
    }

    /**
     * Findet eine Leiter anhand der ID
     */
    public function findById(int $id): ?Ladder
    {
        $sql = "SELECT * FROM ladders WHERE id = :id";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$data) {
                return null;
            }

            // Audit-Log für Zugriff
            $this->auditLogger->logAccess('ladders', $id);

            return new Ladder($data);

        } catch (PDOException $e) {
            throw new RuntimeException('Fehler beim Laden der Leiter: ' . $e->getMessage());
        }
    }

    /**
     * Findet eine Leiter anhand der Leiternummer
     */
    public function findByNumber(string $ladderNumber): ?Ladder
    {
        $sql = "SELECT * FROM ladders WHERE ladder_number = :ladder_number";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':ladder_number' => $ladderNumber]);
            
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$data) {
                return null;
            }

            // Audit-Log für Zugriff
            $this->auditLogger->logAccess('ladders', $data['id']);

            return new Ladder($data);

        } catch (PDOException $e) {
            throw new RuntimeException('Fehler beim Laden der Leiter: ' . $e->getMessage());
        }
    }

    /**
     * Sucht Leitern mit verschiedenen Filtern
     */
    public function search(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $where = [];
        $params = [];

        // Filter aufbauen
        if (!empty($filters['ladder_number'])) {
            $where[] = "ladder_number LIKE :ladder_number";
            $params[':ladder_number'] = '%' . $filters['ladder_number'] . '%';
        }

        if (!empty($filters['manufacturer'])) {
            $where[] = "manufacturer LIKE :manufacturer";
            $params[':manufacturer'] = '%' . $filters['manufacturer'] . '%';
        }

        if (!empty($filters['model'])) {
            $where[] = "model LIKE :model";
            $params[':model'] = '%' . $filters['model'] . '%';
        }

        if (!empty($filters['ladder_type'])) {
            $where[] = "ladder_type = :ladder_type";
            $params[':ladder_type'] = $filters['ladder_type'];
        }

        if (!empty($filters['material'])) {
            $where[] = "material = :material";
            $params[':material'] = $filters['material'];
        }

        if (!empty($filters['location'])) {
            $where[] = "location LIKE :location";
            $params[':location'] = '%' . $filters['location'] . '%';
        }

        if (!empty($filters['department'])) {
            $where[] = "department LIKE :department";
            $params[':department'] = '%' . $filters['department'] . '%';
        }

        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $placeholders = [];
                foreach ($filters['status'] as $i => $status) {
                    $placeholder = ":status_{$i}";
                    $placeholders[] = $placeholder;
                    $params[$placeholder] = $status;
                }
                $where[] = "status IN (" . implode(',', $placeholders) . ")";
            } else {
                $where[] = "status = :status";
                $params[':status'] = $filters['status'];
            }
        }

        if (!empty($filters['needs_inspection'])) {
            $where[] = "next_inspection_date <= CURDATE()";
        }

        if (!empty($filters['inspection_due_days'])) {
            $where[] = "next_inspection_date <= DATE_ADD(CURDATE(), INTERVAL :days DAY)";
            $params[':days'] = (int) $filters['inspection_due_days'];
        }

        // SQL zusammenbauen
        $sql = "SELECT * FROM ladders";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $sql .= " ORDER BY ladder_number ASC LIMIT :limit OFFSET :offset";

        try {
            $stmt = $this->pdo->prepare($sql);
            
            // Parameter binden
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            
            $ladders = [];
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $ladders[] = new Ladder($data);
            }

            return $ladders;

        } catch (PDOException $e) {
            throw new RuntimeException('Fehler bei der Suche: ' . $e->getMessage());
        }
    }

    /**
     * Zählt Leitern mit Filtern
     */
    public function count(array $filters = []): int
    {
        $where = [];
        $params = [];

        // Gleiche Filter wie bei search()
        if (!empty($filters['ladder_number'])) {
            $where[] = "ladder_number LIKE :ladder_number";
            $params[':ladder_number'] = '%' . $filters['ladder_number'] . '%';
        }

        if (!empty($filters['manufacturer'])) {
            $where[] = "manufacturer LIKE :manufacturer";
            $params[':manufacturer'] = '%' . $filters['manufacturer'] . '%';
        }

        if (!empty($filters['model'])) {
            $where[] = "model LIKE :model";
            $params[':model'] = '%' . $filters['model'] . '%';
        }

        if (!empty($filters['ladder_type'])) {
            $where[] = "ladder_type = :ladder_type";
            $params[':ladder_type'] = $filters['ladder_type'];
        }

        if (!empty($filters['material'])) {
            $where[] = "material = :material";
            $params[':material'] = $filters['material'];
        }

        if (!empty($filters['location'])) {
            $where[] = "location LIKE :location";
            $params[':location'] = '%' . $filters['location'] . '%';
        }

        if (!empty($filters['department'])) {
            $where[] = "department LIKE :department";
            $params[':department'] = '%' . $filters['department'] . '%';
        }

        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $placeholders = [];
                foreach ($filters['status'] as $i => $status) {
                    $placeholder = ":status_{$i}";
                    $placeholders[] = $placeholder;
                    $params[$placeholder] = $status;
                }
                $where[] = "status IN (" . implode(',', $placeholders) . ")";
            } else {
                $where[] = "status = :status";
                $params[':status'] = $filters['status'];
            }
        }

        if (!empty($filters['needs_inspection'])) {
            $where[] = "next_inspection_date <= CURDATE()";
        }

        if (!empty($filters['inspection_due_days'])) {
            $where[] = "next_inspection_date <= DATE_ADD(CURDATE(), INTERVAL :days DAY)";
            $params[':days'] = (int) $filters['inspection_due_days'];
        }

        $sql = "SELECT COUNT(*) FROM ladders";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return (int) $stmt->fetchColumn();

        } catch (PDOException $e) {
            throw new RuntimeException('Fehler beim Zählen: ' . $e->getMessage());
        }
    }

    /**
     * Gibt alle Leitern zurück (paginiert)
     */
    public function getAll(int $limit = 50, int $offset = 0, string $orderBy = 'ladder_number', string $orderDir = 'ASC'): array
    {
        $allowedOrderBy = ['ladder_number', 'manufacturer', 'model', 'ladder_type', 'location', 'status', 'next_inspection_date', 'created_at'];
        $allowedOrderDir = ['ASC', 'DESC'];

        if (!in_array($orderBy, $allowedOrderBy)) {
            $orderBy = 'ladder_number';
        }

        if (!in_array(strtoupper($orderDir), $allowedOrderDir)) {
            $orderDir = 'ASC';
        }

        $sql = "SELECT * FROM ladders ORDER BY {$orderBy} {$orderDir} LIMIT :limit OFFSET :offset";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $ladders = [];
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $ladders[] = new Ladder($data);
            }

            return $ladders;

        } catch (PDOException $e) {
            throw new RuntimeException('Fehler beim Laden der Leitern: ' . $e->getMessage());
        }
    }

    /**
     * Generiert eine eindeutige Leiternummer
     */
    public function generateUniqueNumber(string $prefix = 'L'): string
    {
        $year = date('Y');
        $attempts = 0;
        $maxAttempts = 1000;

        do {
            $attempts++;
            if ($attempts > $maxAttempts) {
                throw new RuntimeException('Konnte keine eindeutige Leiternummer generieren');
            }

            // Format: L-YYYY-NNNN (z.B. L-2024-0001)
            $number = sprintf('%04d', $this->getNextSequenceNumber($year));
            $ladderNumber = "{$prefix}-{$year}-{$number}";

        } while ($this->existsByLadderNumber($ladderNumber));

        return $ladderNumber;
    }

    /**
     * Prüft ob eine Leiternummer bereits existiert
     */
    public function existsByLadderNumber(string $ladderNumber, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM ladders WHERE ladder_number = :ladder_number";
        $params = [':ladder_number' => $ladderNumber];

        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchColumn() > 0;

        } catch (PDOException $e) {
            throw new RuntimeException('Fehler bei der Eindeutigkeitsprüfung: ' . $e->getMessage());
        }
    }

    /**
     * Gibt Leitern zurück, die eine Prüfung benötigen
     */
    public function getLaddersNeedingInspection(int $daysAhead = 0): array
    {
        $sql = "SELECT * FROM ladders 
                WHERE status = 'active' 
                AND next_inspection_date <= DATE_ADD(CURDATE(), INTERVAL :days DAY)
                ORDER BY next_inspection_date ASC";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':days', $daysAhead, PDO::PARAM_INT);
            $stmt->execute();
            
            $ladders = [];
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $ladders[] = new Ladder($data);
            }

            return $ladders;

        } catch (PDOException $e) {
            throw new RuntimeException('Fehler beim Laden der prüfpflichtigen Leitern: ' . $e->getMessage());
        }
    }

    /**
     * Gibt Statistiken zurück
     */
    public function getStatistics(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive,
                    SUM(CASE WHEN status = 'defective' THEN 1 ELSE 0 END) as defective,
                    SUM(CASE WHEN status = 'disposed' THEN 1 ELSE 0 END) as disposed,
                    SUM(CASE WHEN status = 'active' AND next_inspection_date <= CURDATE() THEN 1 ELSE 0 END) as needs_inspection,
                    SUM(CASE WHEN status = 'active' AND next_inspection_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as inspection_due_30_days
                FROM ladders";

        try {
            $stmt = $this->pdo->query($sql);
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            throw new RuntimeException('Fehler beim Laden der Statistiken: ' . $e->getMessage());
        }
    }

    /**
     * Ermittelt die nächste Sequenznummer für ein Jahr
     */
    private function getNextSequenceNumber(int $year): int
    {
        $sql = "SELECT MAX(CAST(SUBSTRING_INDEX(ladder_number, '-', -1) AS UNSIGNED)) as max_num 
                FROM ladders 
                WHERE ladder_number LIKE :pattern";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':pattern' => "%-{$year}-%"]);
            
            $maxNum = $stmt->fetchColumn();
            return $maxNum ? $maxNum + 1 : 1;

        } catch (PDOException $e) {
            throw new RuntimeException('Fehler beim Ermitteln der Sequenznummer: ' . $e->getMessage());
        }
    }
}
