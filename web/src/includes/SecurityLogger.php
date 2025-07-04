<?php
/**
 * Sicherheits-Logger-Klasse
 * Spezialisiertes Logging für sicherheitsrelevante Ereignisse
 */

class SecurityLogger
{
    private $systemLogger;
    private $config;
    private $logPath;
    private $alertThresholds;

    public function __construct()
    {
        $this->systemLogger = new SystemLogger();
        $this->config = Config::get('security');
        $this->logPath = dirname(__DIR__, 2) . '/logs/security';
        
        // Alert-Schwellenwerte
        $this->alertThresholds = [
            'failed_logins' => 5,
            'brute_force' => 10,
            'privilege_escalation' => 1,
            'suspicious_activity' => 3
        ];

        $this->ensureSecurityLogDirectory();
    }

    /**
     * Loggt Login-Versuche
     * 
     * @param string $username Benutzername
     * @param bool $success Erfolgreich oder fehlgeschlagen
     * @param string $ip IP-Adresse
     * @param array $context Zusätzliche Kontextdaten
     */
    public function logLoginAttempt($username, $success, $ip = null, $context = [])
    {
        $ip = $ip ?: ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        
        $baseContext = [
            'username' => $username,
            'ip' => $ip,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => time(),
            'success' => $success
        ];

        $fullContext = array_merge($baseContext, $context);

        if ($success) {
            $message = "Successful login for user: {$username}";
            $this->logSecurityEvent('LOGIN_SUCCESS', $message, $fullContext);
        } else {
            $message = "Failed login attempt for user: {$username}";
            $this->logSecurityEvent('LOGIN_FAILED', $message, $fullContext);
            
            // Fehlgeschlagene Versuche zählen
            $this->trackFailedAttempts('login', $username, $ip);
        }
    }

    /**
     * Loggt Privilegien-Änderungen
     * 
     * @param string $targetUser Ziel-Benutzer
     * @param string $action Aktion (grant, revoke, modify)
     * @param array $privileges Betroffene Privilegien
     * @param string $adminUser Administrator der die Änderung durchführt
     * @param array $context Zusätzliche Kontextdaten
     */
    public function logPrivilegeEscalation($targetUser, $action, $privileges, $adminUser = null, $context = [])
    {
        $adminUser = $adminUser ?: $this->getCurrentUser();
        
        $baseContext = [
            'target_user' => $targetUser,
            'admin_user' => $adminUser,
            'action' => $action,
            'privileges' => $privileges,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'timestamp' => time()
        ];

        $fullContext = array_merge($baseContext, $context);
        
        $message = "Privilege {$action} for user {$targetUser} by {$adminUser}";
        $this->logSecurityEvent('PRIVILEGE_CHANGE', $message, $fullContext, 'CRITICAL');

        // Sofortige Benachrichtigung bei Privilegien-Änderungen
        $this->sendSecurityAlert('PRIVILEGE_ESCALATION', $message, $fullContext);
    }

    /**
     * Loggt Datenzugriffe
     * 
     * @param string $resource Ressource/Datei
     * @param string $action Aktion (read, write, delete, etc.)
     * @param string $user Benutzer
     * @param bool $authorized Autorisiert oder nicht
     * @param array $context Zusätzliche Kontextdaten
     */
    public function logDataAccess($resource, $action, $user = null, $authorized = true, $context = [])
    {
        $user = $user ?: $this->getCurrentUser();
        
        $baseContext = [
            'resource' => $resource,
            'action' => $action,
            'user' => $user,
            'authorized' => $authorized,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'timestamp' => time()
        ];

        $fullContext = array_merge($baseContext, $context);

        if ($authorized) {
            $message = "Data access: {$user} performed {$action} on {$resource}";
            $this->logSecurityEvent('DATA_ACCESS', $message, $fullContext);
        } else {
            $message = "Unauthorized data access attempt: {$user} tried {$action} on {$resource}";
            $this->logSecurityEvent('UNAUTHORIZED_ACCESS', $message, $fullContext, 'WARNING');
            
            // Verdächtige Aktivität verfolgen
            $this->trackSuspiciousActivity('unauthorized_access', $user);
        }
    }

    /**
     * Loggt System-Änderungen
     * 
     * @param string $component System-Komponente
     * @param string $change Art der Änderung
     * @param array $details Details der Änderung
     * @param string $user Benutzer der die Änderung durchführt
     * @param array $context Zusätzliche Kontextdaten
     */
    public function logSystemChanges($component, $change, $details, $user = null, $context = [])
    {
        $user = $user ?: $this->getCurrentUser();
        
        $baseContext = [
            'component' => $component,
            'change' => $change,
            'details' => $details,
            'user' => $user,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'timestamp' => time()
        ];

        $fullContext = array_merge($baseContext, $context);
        
        $message = "System change: {$user} modified {$component} - {$change}";
        $this->logSecurityEvent('SYSTEM_CHANGE', $message, $fullContext, 'INFO');
    }

    /**
     * Loggt Brute-Force-Angriffe
     * 
     * @param string $identifier Ziel-Identifier (Username, IP, etc.)
     * @param string $type Art des Angriffs
     * @param string $ip IP-Adresse
     * @param array $context Zusätzliche Kontextdaten
     */
    public function logBruteForceAttempt($identifier, $type, $ip = null, $context = [])
    {
        $ip = $ip ?: ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        
        $baseContext = [
            'identifier' => $identifier,
            'type' => $type,
            'ip' => $ip,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => time()
        ];

        $fullContext = array_merge($baseContext, $context);
        
        $message = "Brute force attempt detected: {$type} against {$identifier} from {$ip}";
        $this->logSecurityEvent('BRUTE_FORCE', $message, $fullContext, 'WARNING');

        // Brute-Force-Versuche verfolgen
        $this->trackFailedAttempts('brute_force', $identifier, $ip);
    }

    /**
     * Loggt Rate-Limit-Überschreitungen
     * 
     * @param string $action Aktion die limitiert wurde
     * @param string $ip IP-Adresse
     * @param array $context Zusätzliche Kontextdaten
     */
    public function logRateLimitExceeded($action, $ip = null, $context = [])
    {
        $ip = $ip ?: ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        
        $baseContext = [
            'action' => $action,
            'ip' => $ip,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => time()
        ];

        $fullContext = array_merge($baseContext, $context);
        
        $message = "Rate limit exceeded for {$action} from {$ip}";
        $this->logSecurityEvent('RATE_LIMIT_EXCEEDED', $message, $fullContext, 'WARNING');
    }

    /**
     * Loggt ungültige Input-Daten
     * 
     * @param mixed $input Ungültige Eingabe
     * @param string $type Erwarteter Typ
     * @param string $ip IP-Adresse
     * @param array $context Zusätzliche Kontextdaten
     */
    public function logInvalidInput($input, $type, $ip = null, $context = [])
    {
        $ip = $ip ?: ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        
        // Input für Logging sanitisieren
        $sanitizedInput = $this->sanitizeForLogging($input);
        
        $baseContext = [
            'input' => $sanitizedInput,
            'expected_type' => $type,
            'ip' => $ip,
            'timestamp' => time()
        ];

        $fullContext = array_merge($baseContext, $context);
        
        $message = "Invalid input detected: expected {$type} from {$ip}";
        $this->logSecurityEvent('INVALID_INPUT', $message, $fullContext, 'WARNING');
    }

    /**
     * Loggt ungültige Datei-Uploads
     * 
     * @param string $reason Grund der Ablehnung
     * @param string $ip IP-Adresse
     * @param array $context Zusätzliche Kontextdaten
     */
    public function logInvalidFileUpload($reason, $ip = null, $context = [])
    {
        $ip = $ip ?: ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        
        $baseContext = [
            'reason' => $reason,
            'ip' => $ip,
            'timestamp' => time()
        ];

        $fullContext = array_merge($baseContext, $context);
        
        $message = "Invalid file upload rejected: {$reason} from {$ip}";
        $this->logSecurityEvent('INVALID_FILE_UPLOAD', $message, $fullContext, 'WARNING');
    }

    /**
     * Loggt schädliche Datei-Uploads
     * 
     * @param string $filename Dateiname
     * @param string $ip IP-Adresse
     * @param array $context Zusätzliche Kontextdaten
     */
    public function logMaliciousFileUpload($filename, $ip = null, $context = [])
    {
        $ip = $ip ?: ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        
        $baseContext = [
            'filename' => $filename,
            'ip' => $ip,
            'timestamp' => time()
        ];

        $fullContext = array_merge($baseContext, $context);
        
        $message = "Malicious file upload blocked: {$filename} from {$ip}";
        $this->logSecurityEvent('MALICIOUS_FILE_UPLOAD', $message, $fullContext, 'CRITICAL');

        // Sofortige Benachrichtigung
        $this->sendSecurityAlert('MALICIOUS_FILE_UPLOAD', $message, $fullContext);
    }

    /**
     * Loggt gültige Datei-Uploads
     * 
     * @param string $filename Dateiname
     * @param string $mimeType MIME-Type
     * @param string $ip IP-Adresse
     * @param array $context Zusätzliche Kontextdaten
     */
    public function logValidFileUpload($filename, $mimeType, $ip = null, $context = [])
    {
        $ip = $ip ?: ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        
        $baseContext = [
            'filename' => $filename,
            'mime_type' => $mimeType,
            'ip' => $ip,
            'timestamp' => time()
        ];

        $fullContext = array_merge($baseContext, $context);
        
        $message = "File uploaded successfully: {$filename} ({$mimeType}) from {$ip}";
        $this->logSecurityEvent('FILE_UPLOAD_SUCCESS', $message, $fullContext);
    }

    /**
     * Loggt fehlgeschlagene Versuche
     * 
     * @param string $identifier Identifier
     * @param string $action Aktion
     * @param string $ip IP-Adresse
     * @param array $context Zusätzliche Kontextdaten
     */
    public function logFailedAttempt($identifier, $action, $ip = null, $context = [])
    {
        $ip = $ip ?: ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        
        $baseContext = [
            'identifier' => $identifier,
            'action' => $action,
            'ip' => $ip,
            'timestamp' => time()
        ];

        $fullContext = array_merge($baseContext, $context);
        
        $message = "Failed attempt: {$action} for {$identifier} from {$ip}";
        $this->logSecurityEvent('FAILED_ATTEMPT', $message, $fullContext, 'WARNING');
    }

    /**
     * Loggt allgemeine Sicherheitsereignisse
     * 
     * @param string $eventType Ereignistyp
     * @param string $message Nachricht
     * @param array $context Kontext-Daten
     * @param string $severity Schweregrad
     */
    private function logSecurityEvent($eventType, $message, $context = [], $severity = 'INFO')
    {
        // Erweiterte Kontext-Informationen hinzufügen
        $enhancedContext = array_merge($context, [
            'event_type' => $eventType,
            'severity' => $severity,
            'session_id' => session_id(),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'http_method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'server_name' => $_SERVER['SERVER_NAME'] ?? ''
        ]);

        // In System-Logger schreiben
        switch ($severity) {
            case 'CRITICAL':
                $this->systemLogger->logCritical($message, $enhancedContext);
                break;
            case 'ERROR':
                $this->systemLogger->logError($message, $enhancedContext);
                break;
            case 'WARNING':
                $this->systemLogger->logWarning($message, $enhancedContext);
                break;
            default:
                $this->systemLogger->logInfo($message, $enhancedContext);
        }

        // In separate Sicherheits-Log-Datei schreiben
        $this->writeToSecurityLog($eventType, $message, $enhancedContext);
    }

    /**
     * Schreibt in separate Sicherheits-Log-Datei
     * 
     * @param string $eventType Ereignistyp
     * @param string $message Nachricht
     * @param array $context Kontext-Daten
     */
    private function writeToSecurityLog($eventType, $message, $context)
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = [
            'timestamp' => $timestamp,
            'event_type' => $eventType,
            'message' => $message,
            'context' => $context
        ];

        $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        
        $filename = 'security-' . date('Y-m-d') . '.log';
        $filepath = $this->logPath . '/' . $filename;

        file_put_contents($filepath, $logLine, FILE_APPEND | LOCK_EX);
        chmod($filepath, 0600); // Nur Owner kann lesen/schreiben
    }

    /**
     * Verfolgt fehlgeschlagene Versuche
     * 
     * @param string $type Typ der Versuche
     * @param string $identifier Identifier
     * @param string $ip IP-Adresse
     */
    private function trackFailedAttempts($type, $identifier, $ip)
    {
        $key = "{$type}_{$identifier}_{$ip}";
        $cacheFile = $this->logPath . '/attempts_' . date('Y-m-d') . '.json';
        
        $attempts = [];
        if (file_exists($cacheFile)) {
            $attempts = json_decode(file_get_contents($cacheFile), true) ?: [];
        }

        if (!isset($attempts[$key])) {
            $attempts[$key] = ['count' => 0, 'first_attempt' => time()];
        }

        $attempts[$key]['count']++;
        $attempts[$key]['last_attempt'] = time();

        file_put_contents($cacheFile, json_encode($attempts));

        // Alert-Schwellenwerte prüfen
        $threshold = $this->alertThresholds[$type] ?? 5;
        if ($attempts[$key]['count'] >= $threshold) {
            $this->sendSecurityAlert($type, "Threshold exceeded for {$type}: {$identifier}", [
                'identifier' => $identifier,
                'ip' => $ip,
                'attempt_count' => $attempts[$key]['count'],
                'threshold' => $threshold
            ]);
        }
    }

    /**
     * Verfolgt verdächtige Aktivitäten
     * 
     * @param string $type Typ der Aktivität
     * @param string $user Benutzer
     */
    private function trackSuspiciousActivity($type, $user)
    {
        $key = "{$type}_{$user}";
        $cacheFile = $this->logPath . '/suspicious_' . date('Y-m-d') . '.json';
        
        $activities = [];
        if (file_exists($cacheFile)) {
            $activities = json_decode(file_get_contents($cacheFile), true) ?: [];
        }

        if (!isset($activities[$key])) {
            $activities[$key] = ['count' => 0, 'first_occurrence' => time()];
        }

        $activities[$key]['count']++;
        $activities[$key]['last_occurrence'] = time();

        file_put_contents($cacheFile, json_encode($activities));

        // Alert bei verdächtigen Aktivitäten
        $threshold = $this->alertThresholds['suspicious_activity'] ?? 3;
        if ($activities[$key]['count'] >= $threshold) {
            $this->sendSecurityAlert('SUSPICIOUS_ACTIVITY', "Suspicious activity detected: {$type} by {$user}", [
                'user' => $user,
                'activity_type' => $type,
                'occurrence_count' => $activities[$key]['count']
            ]);
        }
    }

    /**
     * Sendet Sicherheits-Alerts
     * 
     * @param string $alertType Alert-Typ
     * @param string $message Nachricht
     * @param array $context Kontext-Daten
     */
    private function sendSecurityAlert($alertType, $message, $context)
    {
        // E-Mail-Benachrichtigung
        $adminEmail = Config::get('app.admin_email');
        if ($adminEmail && function_exists('mail')) {
            $subject = "Security Alert: {$alertType}";
            $body = "Security alert triggered:\n\n";
            $body .= "Type: {$alertType}\n";
            $body .= "Message: {$message}\n";
            $body .= "Time: " . date('Y-m-d H:i:s') . "\n";
            $body .= "Context: " . json_encode($context, JSON_PRETTY_PRINT) . "\n";
            
            mail($adminEmail, $subject, $body);
        }

        // In kritische Alerts-Datei schreiben
        $alertEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'alert_type' => $alertType,
            'message' => $message,
            'context' => $context
        ];

        $alertLine = json_encode($alertEntry) . PHP_EOL;
        $alertFile = $this->logPath . '/alerts-' . date('Y-m-d') . '.log';
        
        file_put_contents($alertFile, $alertLine, FILE_APPEND | LOCK_EX);
    }

    /**
     * Sanitisiert Daten für Logging
     * 
     * @param mixed $data Zu sanitisierende Daten
     * @return mixed Sanitisierte Daten
     */
    private function sanitizeForLogging($data)
    {
        if (is_string($data)) {
            // Maximale Länge begrenzen
            if (strlen($data) > 200) {
                $data = substr($data, 0, 197) . '...';
            }
            
            // Gefährliche Zeichen entfernen
            $data = str_replace(["\0", "\r", "\n"], ['', '', ' '], $data);
        }

        return $data;
    }

    /**
     * Holt den aktuellen Benutzer
     * 
     * @return string Benutzername oder 'anonymous'
     */
    private function getCurrentUser()
    {
        $sessionManager = new SessionManager();
        return $sessionManager->getUsername() ?: 'anonymous';
    }

    /**
     * Stellt sicher, dass Sicherheits-Log-Verzeichnis existiert
     */
    private function ensureSecurityLogDirectory()
    {
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0700, true); // Nur Owner-Zugriff
        }

        // .htaccess für zusätzliche Sicherheit
        $htaccessPath = $this->logPath . '/.htaccess';
        if (!file_exists($htaccessPath)) {
            file_put_contents($htaccessPath, "Deny from all\n");
        }
    }

    /**
     * Loggt allgemeine Fehler
     * 
     * @param string $message Fehlernachricht
     * @param array $context Kontext-Daten
     */
    public function logError($message, $context = [])
    {
        $this->logSecurityEvent('GENERAL_ERROR', $message, $context, 'ERROR');
    }

    /**
     * Holt Sicherheits-Statistiken
     * 
     * @param string $date Datum (Y-m-d Format)
     * @return array Statistiken
     */
    public function getSecurityStats($date = null)
    {
        $date = $date ?: date('Y-m-d');
        $logFile = $this->logPath . "/security-{$date}.log";
        
        if (!file_exists($logFile)) {
            return [];
        }

        $stats = [
            'total_events' => 0,
            'by_type' => [],
            'by_severity' => [],
            'by_hour' => array_fill(0, 24, 0)
        ];

        $handle = fopen($logFile, 'r');
        while (($line = fgets($handle)) !== false) {
            $entry = json_decode($line, true);
            if ($entry) {
                $stats['total_events']++;
                
                // Nach Typ zählen
                $eventType = $entry['event_type'] ?? 'UNKNOWN';
                $stats['by_type'][$eventType] = ($stats['by_type'][$eventType] ?? 0) + 1;
                
                // Nach Schweregrad zählen
                $severity = $entry['context']['severity'] ?? 'INFO';
                $stats['by_severity'][$severity] = ($stats['by_severity'][$severity] ?? 0) + 1;
                
                // Nach Stunde zählen
                $hour = (int)date('H', strtotime($entry['timestamp']));
                $stats['by_hour'][$hour]++;
            }
        }
        fclose($handle);

        return $stats;
    }
}
