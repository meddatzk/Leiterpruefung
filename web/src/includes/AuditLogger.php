<?php

/**
 * AuditLogger - Audit-Trail für Datenbank-Operationen
 * 
 * @author System
 * @version 1.0
 */
class AuditLogger
{
    private PDO $pdo;
    private ?int $userId = null;
    private ?string $userIp = null;
    private ?string $userAgent = null;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->initializeContext();
    }

    /**
     * Initialisiert den Kontext (User, IP, User-Agent)
     */
    private function initializeContext(): void
    {
        // User-ID aus Session ermitteln
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_id'])) {
            $this->userId = (int) $_SESSION['user_id'];
        }

        // IP-Adresse ermitteln
        $this->userIp = $this->getRealIpAddress();

        // User-Agent ermitteln
        $this->userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    }

    /**
     * Setzt die User-ID manuell (für CLI-Skripte oder spezielle Fälle)
     */
    public function setUserId(?int $userId): void
    {
        $this->userId = $userId;
    }

    /**
     * Setzt die IP-Adresse manuell
     */
    public function setUserIp(?string $userIp): void
    {
        $this->userIp = $userIp;
    }

    /**
     * Setzt den User-Agent manuell
     */
    public function setUserAgent(?string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }

    /**
     * Protokolliert eine CREATE-Operation
     */
    public function logCreate(string $tableName, int $recordId, array $newValues): bool
    {
        return $this->log($tableName, $recordId, 'INSERT', null, $newValues);
    }

    /**
     * Protokolliert eine UPDATE-Operation
     */
    public function logUpdate(string $tableName, int $recordId, array $oldValues, array $newValues): bool
    {
        // Nur geänderte Werte protokollieren
        $changes = $this->getChangedValues($oldValues, $newValues);
        
        if (empty($changes['old']) && empty($changes['new'])) {
            // Keine Änderungen - nichts zu protokollieren
            return true;
        }

        return $this->log($tableName, $recordId, 'UPDATE', $changes['old'], $changes['new']);
    }

    /**
     * Protokolliert eine DELETE-Operation
     */
    public function logDelete(string $tableName, int $recordId, array $oldValues): bool
    {
        return $this->log($tableName, $recordId, 'DELETE', $oldValues, null);
    }

    /**
     * Protokolliert einen Zugriff (READ-Operation)
     */
    public function logAccess(string $tableName, int $recordId, array $additionalData = []): bool
    {
        // Zugriffe werden in einer separaten Tabelle oder gar nicht protokolliert
        // um die audit_log Tabelle nicht zu überlasten
        
        // Für kritische Tabellen können wir Zugriffe protokollieren
        $criticalTables = ['ladders', 'inspections'];
        
        if (!in_array($tableName, $criticalTables)) {
            return true; // Nicht protokollieren
        }

        // Optional: Zugriffe in separater Tabelle protokollieren
        // Hier implementieren wir es nicht, um die Haupttabelle nicht zu überlasten
        return true;
    }

    /**
     * Protokolliert eine Batch-Operation
     */
    public function logBatch(string $tableName, string $action, array $recordIds, string $description = ''): bool
    {
        $batchData = [
            'action' => $action,
            'record_ids' => $recordIds,
            'count' => count($recordIds),
            'description' => $description
        ];

        return $this->log($tableName, 0, 'BATCH_' . strtoupper($action), null, $batchData);
    }

    /**
     * Protokolliert eine System-Operation
     */
    public function logSystem(string $action, array $data = []): bool
    {
        return $this->log('system', 0, $action, null, $data);
    }

    /**
     * Hauptmethode für das Protokollieren
     */
    private function log(string $tableName, int $recordId, string $action, ?array $oldValues, ?array $newValues): bool
    {
        $sql = "INSERT INTO audit_log (
            table_name, record_id, action, old_values, new_values, 
            user_id, user_ip, user_agent, timestamp
        ) VALUES (
            :table_name, :record_id, :action, :old_values, :new_values,
            :user_id, :user_ip, :user_agent, CURRENT_TIMESTAMP
        )";

        try {
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':table_name' => $tableName,
                ':record_id' => $recordId,
                ':action' => $action,
                ':old_values' => $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
                ':new_values' => $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
                ':user_id' => $this->userId,
                ':user_ip' => $this->userIp,
                ':user_agent' => $this->userAgent
            ]);

            return $result;

        } catch (PDOException $e) {
            // Audit-Logging sollte die Hauptoperation nicht beeinträchtigen
            // Daher loggen wir den Fehler nur und geben true zurück
            error_log("AuditLogger Error: " . $e->getMessage());
            return true;
        }
    }

    /**
     * Ermittelt geänderte Werte zwischen zwei Arrays
     */
    private function getChangedValues(array $oldValues, array $newValues): array
    {
        $oldChanged = [];
        $newChanged = [];

        // Felder die ignoriert werden sollen (z.B. Timestamps)
        $ignoreFields = ['updated_at', 'created_at'];

        foreach ($newValues as $key => $newValue) {
            if (in_array($key, $ignoreFields)) {
                continue;
            }

            $oldValue = $oldValues[$key] ?? null;

            // Werte vergleichen (auch null-Werte berücksichtigen)
            if ($this->valuesAreDifferent($oldValue, $newValue)) {
                $oldChanged[$key] = $oldValue;
                $newChanged[$key] = $newValue;
            }
        }

        // Prüfen ob Felder entfernt wurden
        foreach ($oldValues as $key => $oldValue) {
            if (in_array($key, $ignoreFields)) {
                continue;
            }

            if (!array_key_exists($key, $newValues)) {
                $oldChanged[$key] = $oldValue;
                $newChanged[$key] = null;
            }
        }

        return [
            'old' => $oldChanged,
            'new' => $newChanged
        ];
    }

    /**
     * Prüft ob zwei Werte unterschiedlich sind
     */
    private function valuesAreDifferent($oldValue, $newValue): bool
    {
        // Null-Werte behandeln
        if ($oldValue === null && $newValue === null) {
            return false;
        }

        if ($oldValue === null || $newValue === null) {
            return true;
        }

        // String-Vergleich für konsistente Ergebnisse
        return (string) $oldValue !== (string) $newValue;
    }

    /**
     * Ermittelt die echte IP-Adresse des Clients
     */
    private function getRealIpAddress(): ?string
    {
        // Verschiedene Header prüfen (für Proxy/Load Balancer)
        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                
                // Bei X-Forwarded-For kann eine Liste von IPs stehen
                if ($header === 'HTTP_X_FORWARDED_FOR') {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }

                // IP validieren
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        // Fallback auf REMOTE_ADDR
        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    /**
     * Gibt Audit-Logs für eine bestimmte Tabelle und Record-ID zurück
     */
    public function getAuditTrail(string $tableName, int $recordId, int $limit = 100): array
    {
        $sql = "SELECT 
                    al.*,
                    u.username,
                    u.display_name
                FROM audit_log al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE al.table_name = :table_name AND al.record_id = :record_id
                ORDER BY al.timestamp DESC
                LIMIT :limit";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':table_name', $tableName);
            $stmt->bindValue(':record_id', $recordId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            $logs = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // JSON-Daten dekodieren
                if ($row['old_values']) {
                    $row['old_values'] = json_decode($row['old_values'], true);
                }
                if ($row['new_values']) {
                    $row['new_values'] = json_decode($row['new_values'], true);
                }
                
                $logs[] = $row;
            }

            return $logs;

        } catch (PDOException $e) {
            throw new RuntimeException('Fehler beim Laden des Audit-Trails: ' . $e->getMessage());
        }
    }

    /**
     * Gibt Audit-Logs für einen bestimmten Zeitraum zurück
     */
    public function getAuditLogsByDateRange(string $startDate, string $endDate, ?string $tableName = null, int $limit = 1000): array
    {
        $sql = "SELECT 
                    al.*,
                    u.username,
                    u.display_name
                FROM audit_log al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE al.timestamp BETWEEN :start_date AND :end_date";

        $params = [
            ':start_date' => $startDate . ' 00:00:00',
            ':end_date' => $endDate . ' 23:59:59'
        ];

        if ($tableName) {
            $sql .= " AND al.table_name = :table_name";
            $params[':table_name'] = $tableName;
        }

        $sql .= " ORDER BY al.timestamp DESC LIMIT :limit";

        try {
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            $logs = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // JSON-Daten dekodieren
                if ($row['old_values']) {
                    $row['old_values'] = json_decode($row['old_values'], true);
                }
                if ($row['new_values']) {
                    $row['new_values'] = json_decode($row['new_values'], true);
                }
                
                $logs[] = $row;
            }

            return $logs;

        } catch (PDOException $e) {
            throw new RuntimeException('Fehler beim Laden der Audit-Logs: ' . $e->getMessage());
        }
    }

    /**
     * Gibt Audit-Statistiken zurück
     */
    public function getAuditStatistics(?string $tableName = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $sql = "SELECT 
                    action,
                    COUNT(*) as count,
                    COUNT(DISTINCT user_id) as unique_users,
                    MIN(timestamp) as first_action,
                    MAX(timestamp) as last_action
                FROM audit_log
                WHERE 1=1";

        $params = [];

        if ($tableName) {
            $sql .= " AND table_name = :table_name";
            $params[':table_name'] = $tableName;
        }

        if ($startDate) {
            $sql .= " AND timestamp >= :start_date";
            $params[':start_date'] = $startDate . ' 00:00:00';
        }

        if ($endDate) {
            $sql .= " AND timestamp <= :end_date";
            $params[':end_date'] = $endDate . ' 23:59:59';
        }

        $sql .= " GROUP BY action ORDER BY count DESC";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            throw new RuntimeException('Fehler beim Laden der Audit-Statistiken: ' . $e->getMessage());
        }
    }

    /**
     * Bereinigt alte Audit-Logs basierend auf Aufbewahrungszeit
     */
    public function cleanupOldLogs(int $retentionDays = 2555): int
    {
        $sql = "DELETE FROM audit_log WHERE timestamp < DATE_SUB(NOW(), INTERVAL :days DAY)";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':days', $retentionDays, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount();

        } catch (PDOException $e) {
            throw new RuntimeException('Fehler beim Bereinigen der Audit-Logs: ' . $e->getMessage());
        }
    }

    /**
     * Exportiert Audit-Logs als CSV
     */
    public function exportToCsv(string $filename, ?string $tableName = null, ?string $startDate = null, ?string $endDate = null): bool
    {
        $logs = $this->getAuditLogsByDateRange(
            $startDate ?: date('Y-m-d', strtotime('-30 days')),
            $endDate ?: date('Y-m-d'),
            $tableName,
            10000
        );

        $file = fopen($filename, 'w');
        if (!$file) {
            throw new RuntimeException('Konnte Datei nicht zum Schreiben öffnen: ' . $filename);
        }

        // CSV-Header
        fputcsv($file, [
            'ID', 'Tabelle', 'Datensatz-ID', 'Aktion', 'Alte Werte', 'Neue Werte',
            'Benutzer-ID', 'Benutzername', 'IP-Adresse', 'User-Agent', 'Zeitstempel'
        ], ';');

        // Daten schreiben
        foreach ($logs as $log) {
            fputcsv($file, [
                $log['id'],
                $log['table_name'],
                $log['record_id'],
                $log['action'],
                $log['old_values'] ? json_encode($log['old_values'], JSON_UNESCAPED_UNICODE) : '',
                $log['new_values'] ? json_encode($log['new_values'], JSON_UNESCAPED_UNICODE) : '',
                $log['user_id'],
                $log['username'] ?? '',
                $log['user_ip'],
                $log['user_agent'],
                $log['timestamp']
            ], ';');
        }

        fclose($file);
        return true;
    }

    /**
     * Erstellt einen lesbaren Audit-Trail-Bericht
     */
    public function generateReadableReport(string $tableName, int $recordId): string
    {
        $logs = $this->getAuditTrail($tableName, $recordId);
        
        if (empty($logs)) {
            return "Keine Audit-Logs für {$tableName} ID {$recordId} gefunden.";
        }

        $report = "Audit-Trail für {$tableName} ID {$recordId}\n";
        $report .= str_repeat('=', 50) . "\n\n";

        foreach ($logs as $log) {
            $report .= sprintf(
                "[%s] %s von %s (%s)\n",
                $log['timestamp'],
                $this->getActionDescription($log['action']),
                $log['display_name'] ?: $log['username'] ?: 'System',
                $log['user_ip'] ?: 'unbekannt'
            );

            if ($log['action'] === 'UPDATE' && $log['old_values'] && $log['new_values']) {
                $report .= "Änderungen:\n";
                foreach ($log['new_values'] as $field => $newValue) {
                    $oldValue = $log['old_values'][$field] ?? 'nicht gesetzt';
                    $report .= sprintf("  - %s: '%s' → '%s'\n", $field, $oldValue, $newValue);
                }
            }

            $report .= "\n";
        }

        return $report;
    }

    /**
     * Gibt eine lesbare Beschreibung für eine Aktion zurück
     */
    private function getActionDescription(string $action): string
    {
        $descriptions = [
            'INSERT' => 'Erstellt',
            'UPDATE' => 'Aktualisiert',
            'DELETE' => 'Gelöscht',
            'BATCH_INSERT' => 'Batch-Erstellung',
            'BATCH_UPDATE' => 'Batch-Aktualisierung',
            'BATCH_DELETE' => 'Batch-Löschung'
        ];

        return $descriptions[$action] ?? $action;
    }
}
