<?php
/**
 * System-Logger-Klasse
 * Zentrale Logging-Funktionalität für verschiedene Log-Level
 */

class SystemLogger
{
    private $config;
    private $logPath;
    private $maxFileSize;
    private $maxFiles;
    private $dateFormat;
    private $logFormat;

    // Log-Level Konstanten
    const LEVEL_DEBUG = 1;
    const LEVEL_INFO = 2;
    const LEVEL_WARNING = 3;
    const LEVEL_ERROR = 4;
    const LEVEL_CRITICAL = 5;

    private $levelNames = [
        self::LEVEL_DEBUG => 'DEBUG',
        self::LEVEL_INFO => 'INFO',
        self::LEVEL_WARNING => 'WARNING',
        self::LEVEL_ERROR => 'ERROR',
        self::LEVEL_CRITICAL => 'CRITICAL'
    ];

    public function __construct()
    {
        $this->config = Config::get('app');
        $this->logPath = dirname(__DIR__, 2) . '/logs';
        $this->maxFileSize = 10 * 1024 * 1024; // 10MB
        $this->maxFiles = 10;
        $this->dateFormat = 'Y-m-d H:i:s';
        $this->logFormat = '[%s] %s: %s %s';

        // Log-Verzeichnis erstellen falls nicht vorhanden
        $this->ensureLogDirectory();
    }

    /**
     * Loggt eine Debug-Nachricht
     * 
     * @param string $message Nachricht
     * @param array $context Zusätzliche Kontextdaten
     */
    public function logDebug($message, $context = [])
    {
        $this->log(self::LEVEL_DEBUG, $message, $context);
    }

    /**
     * Loggt eine Info-Nachricht
     * 
     * @param string $message Nachricht
     * @param array $context Zusätzliche Kontextdaten
     */
    public function logInfo($message, $context = [])
    {
        $this->log(self::LEVEL_INFO, $message, $context);
    }

    /**
     * Loggt eine Warnung
     * 
     * @param string $message Nachricht
     * @param array $context Zusätzliche Kontextdaten
     */
    public function logWarning($message, $context = [])
    {
        $this->log(self::LEVEL_WARNING, $message, $context);
    }

    /**
     * Loggt einen Fehler
     * 
     * @param string $message Nachricht
     * @param array $context Zusätzliche Kontextdaten
     */
    public function logError($message, $context = [])
    {
        $this->log(self::LEVEL_ERROR, $message, $context);
    }

    /**
     * Loggt einen kritischen Fehler
     * 
     * @param string $message Nachricht
     * @param array $context Zusätzliche Kontextdaten
     */
    public function logCritical($message, $context = [])
    {
        $this->log(self::LEVEL_CRITICAL, $message, $context);
    }

    /**
     * Hauptlogging-Methode
     * 
     * @param int $level Log-Level
     * @param string $message Nachricht
     * @param array $context Zusätzliche Kontextdaten
     */
    public function log($level, $message, $context = [])
    {
        // Debug-Modus prüfen
        if ($level === self::LEVEL_DEBUG && !($this->config['debug'] ?? false)) {
            return;
        }

        try {
            $timestamp = date($this->dateFormat);
            $levelName = $this->levelNames[$level] ?? 'UNKNOWN';
            
            // Kontext-Informationen sammeln
            $contextString = $this->formatContext($context);
            
            // Log-Nachricht formatieren
            $logMessage = sprintf(
                $this->logFormat,
                $timestamp,
                $levelName,
                $this->sanitizeMessage($message),
                $contextString
            );

            // In Datei schreiben
            $this->writeToFile($logMessage, $level);

            // Bei kritischen Fehlern zusätzliche Aktionen
            if ($level >= self::LEVEL_CRITICAL) {
                $this->handleCriticalError($message, $context);
            }

        } catch (Exception $e) {
            // Fallback: Error-Log verwenden
            error_log("SystemLogger Error: " . $e->getMessage());
            error_log("Original Message: " . $message);
        }
    }

    /**
     * Loggt eine Exception
     * 
     * @param Exception $exception Exception-Objekt
     * @param string $message Zusätzliche Nachricht
     * @param array $context Zusätzliche Kontextdaten
     */
    public function logException($exception, $message = '', $context = [])
    {
        $exceptionData = [
            'exception_class' => get_class($exception),
            'exception_message' => $exception->getMessage(),
            'exception_code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];

        $context = array_merge($context, $exceptionData);
        
        $fullMessage = $message ? $message . ': ' . $exception->getMessage() : $exception->getMessage();
        
        $this->logError($fullMessage, $context);
    }

    /**
     * Loggt Performance-Metriken
     * 
     * @param string $operation Name der Operation
     * @param float $duration Dauer in Sekunden
     * @param array $metrics Zusätzliche Metriken
     */
    public function logPerformance($operation, $duration, $metrics = [])
    {
        $context = array_merge([
            'operation' => $operation,
            'duration_seconds' => round($duration, 4),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true)
        ], $metrics);

        $message = sprintf('Performance: %s completed in %.4fs', $operation, $duration);
        
        $this->logInfo($message, $context);
    }

    /**
     * Loggt SQL-Queries (für Debugging)
     * 
     * @param string $query SQL-Query
     * @param array $params Parameter
     * @param float $duration Ausführungsdauer
     */
    public function logQuery($query, $params = [], $duration = null)
    {
        if (!($this->config['debug'] ?? false)) {
            return;
        }

        $context = [
            'query' => $query,
            'params' => $params
        ];

        if ($duration !== null) {
            $context['duration_seconds'] = round($duration, 4);
        }

        $this->logDebug('SQL Query executed', $context);
    }

    /**
     * Loggt HTTP-Requests
     * 
     * @param string $method HTTP-Methode
     * @param string $uri Request-URI
     * @param int $statusCode Response-Status-Code
     * @param float $duration Request-Dauer
     */
    public function logRequest($method, $uri, $statusCode, $duration = null)
    {
        $context = [
            'method' => $method,
            'uri' => $uri,
            'status_code' => $statusCode,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];

        if ($duration !== null) {
            $context['duration_seconds'] = round($duration, 4);
        }

        $message = sprintf('%s %s - %d', $method, $uri, $statusCode);
        
        $this->logInfo($message, $context);
    }

    /**
     * Schreibt Log-Nachricht in Datei
     * 
     * @param string $message Formatierte Log-Nachricht
     * @param int $level Log-Level
     */
    private function writeToFile($message, $level)
    {
        $filename = $this->getLogFilename($level);
        $filepath = $this->logPath . '/' . $filename;

        // Datei-Rotation prüfen
        if (file_exists($filepath) && filesize($filepath) > $this->maxFileSize) {
            $this->rotateLogFile($filepath);
        }

        // Log-Nachricht schreiben
        $result = file_put_contents($filepath, $message . PHP_EOL, FILE_APPEND | LOCK_EX);
        
        if ($result === false) {
            throw new Exception('Could not write to log file: ' . $filepath);
        }

        // Dateiberechtigungen setzen
        chmod($filepath, 0644);
    }

    /**
     * Bestimmt Log-Dateiname basierend auf Level
     * 
     * @param int $level Log-Level
     * @return string Dateiname
     */
    private function getLogFilename($level)
    {
        $date = date('Y-m-d');
        
        switch ($level) {
            case self::LEVEL_DEBUG:
                return "debug-{$date}.log";
            case self::LEVEL_ERROR:
            case self::LEVEL_CRITICAL:
                return "error-{$date}.log";
            default:
                return "app-{$date}.log";
        }
    }

    /**
     * Rotiert Log-Dateien
     * 
     * @param string $filepath Pfad zur aktuellen Log-Datei
     */
    private function rotateLogFile($filepath)
    {
        $pathinfo = pathinfo($filepath);
        $basename = $pathinfo['filename'];
        $extension = $pathinfo['extension'];
        $directory = $pathinfo['dirname'];

        // Bestehende rotierte Dateien verschieben
        for ($i = $this->maxFiles - 1; $i >= 1; $i--) {
            $oldFile = "{$directory}/{$basename}.{$i}.{$extension}";
            $newFile = "{$directory}/{$basename}." . ($i + 1) . ".{$extension}";
            
            if (file_exists($oldFile)) {
                if ($i === $this->maxFiles - 1) {
                    unlink($oldFile); // Älteste Datei löschen
                } else {
                    rename($oldFile, $newFile);
                }
            }
        }

        // Aktuelle Datei zu .1 verschieben
        $rotatedFile = "{$directory}/{$basename}.1.{$extension}";
        rename($filepath, $rotatedFile);
    }

    /**
     * Formatiert Kontext-Daten für Log-Ausgabe
     * 
     * @param array $context Kontext-Daten
     * @return string Formatierter Kontext-String
     */
    private function formatContext($context)
    {
        if (empty($context)) {
            return '';
        }

        // Sensible Daten entfernen/maskieren
        $context = $this->sanitizeContext($context);
        
        return json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Sanitisiert Log-Nachrichten
     * 
     * @param string $message Original-Nachricht
     * @return string Sanitisierte Nachricht
     */
    private function sanitizeMessage($message)
    {
        // Zeilenumbrüche durch Leerzeichen ersetzen
        $message = str_replace(["\r\n", "\r", "\n"], ' ', $message);
        
        // Null-Bytes entfernen
        $message = str_replace("\0", '', $message);
        
        // Maximale Länge begrenzen
        if (strlen($message) > 1000) {
            $message = substr($message, 0, 997) . '...';
        }

        return $message;
    }

    /**
     * Sanitisiert Kontext-Daten
     * 
     * @param array $context Original-Kontext
     * @return array Sanitisierter Kontext
     */
    private function sanitizeContext($context)
    {
        $sensitiveKeys = [
            'password', 'passwd', 'pwd', 'secret', 'token', 'key', 'auth',
            'authorization', 'cookie', 'session', 'csrf', 'api_key'
        ];

        array_walk_recursive($context, function(&$value, $key) use ($sensitiveKeys) {
            if (is_string($key) && in_array(strtolower($key), $sensitiveKeys)) {
                $value = '[REDACTED]';
            } elseif (is_string($value)) {
                // Null-Bytes entfernen
                $value = str_replace("\0", '', $value);
                
                // Sehr lange Strings kürzen
                if (strlen($value) > 500) {
                    $value = substr($value, 0, 497) . '...';
                }
            }
        });

        return $context;
    }

    /**
     * Behandelt kritische Fehler
     * 
     * @param string $message Fehlernachricht
     * @param array $context Kontext-Daten
     */
    private function handleCriticalError($message, $context)
    {
        // E-Mail-Benachrichtigung (falls konfiguriert)
        $adminEmail = Config::get('app.admin_email');
        if ($adminEmail && function_exists('mail')) {
            $subject = 'Critical Error in Leiterpruefung System';
            $body = "Critical error occurred:\n\n";
            $body .= "Message: {$message}\n";
            $body .= "Time: " . date($this->dateFormat) . "\n";
            $body .= "Context: " . $this->formatContext($context) . "\n";
            
            mail($adminEmail, $subject, $body);
        }

        // System-Error-Log als Fallback
        error_log("CRITICAL ERROR: {$message}");
    }

    /**
     * Stellt sicher, dass Log-Verzeichnis existiert
     */
    private function ensureLogDirectory()
    {
        if (!is_dir($this->logPath)) {
            if (!mkdir($this->logPath, 0755, true)) {
                throw new Exception('Could not create log directory: ' . $this->logPath);
            }
        }

        // .htaccess für Sicherheit erstellen
        $htaccessPath = $this->logPath . '/.htaccess';
        if (!file_exists($htaccessPath)) {
            file_put_contents($htaccessPath, "Deny from all\n");
        }
    }

    /**
     * Liest Log-Einträge aus Datei
     * 
     * @param string $filename Log-Dateiname
     * @param int $lines Anzahl der letzten Zeilen
     * @return array Log-Einträge
     */
    public function readLogFile($filename, $lines = 100)
    {
        $filepath = $this->logPath . '/' . $filename;
        
        if (!file_exists($filepath)) {
            return [];
        }

        $file = new SplFileObject($filepath);
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();
        
        $startLine = max(0, $totalLines - $lines);
        $logEntries = [];
        
        $file->seek($startLine);
        while (!$file->eof()) {
            $line = trim($file->current());
            if (!empty($line)) {
                $logEntries[] = $this->parseLogLine($line);
            }
            $file->next();
        }

        return array_reverse($logEntries);
    }

    /**
     * Parst eine Log-Zeile
     * 
     * @param string $line Log-Zeile
     * @return array Geparste Log-Daten
     */
    private function parseLogLine($line)
    {
        // Regex für Log-Format: [timestamp] LEVEL: message context
        $pattern = '/^\[([^\]]+)\]\s+(\w+):\s+(.+?)(\s+\{.+\})?$/';
        
        if (preg_match($pattern, $line, $matches)) {
            return [
                'timestamp' => $matches[1],
                'level' => $matches[2],
                'message' => $matches[3],
                'context' => isset($matches[4]) ? json_decode(trim($matches[4]), true) : [],
                'raw' => $line
            ];
        }

        return [
            'timestamp' => '',
            'level' => 'UNKNOWN',
            'message' => $line,
            'context' => [],
            'raw' => $line
        ];
    }

    /**
     * Holt verfügbare Log-Dateien
     * 
     * @return array Liste der Log-Dateien
     */
    public function getLogFiles()
    {
        $files = [];
        
        if (is_dir($this->logPath)) {
            $iterator = new DirectoryIterator($this->logPath);
            
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'log') {
                    $files[] = [
                        'name' => $file->getFilename(),
                        'size' => $file->getSize(),
                        'modified' => $file->getMTime()
                    ];
                }
            }
        }

        // Nach Änderungsdatum sortieren (neueste zuerst)
        usort($files, function($a, $b) {
            return $b['modified'] - $a['modified'];
        });

        return $files;
    }

    /**
     * Löscht alte Log-Dateien
     * 
     * @param int $days Alter in Tagen
     * @return int Anzahl gelöschter Dateien
     */
    public function cleanupOldLogs($days = 30)
    {
        $cutoffTime = time() - ($days * 24 * 60 * 60);
        $deletedCount = 0;

        if (is_dir($this->logPath)) {
            $iterator = new DirectoryIterator($this->logPath);
            
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'log') {
                    if ($file->getMTime() < $cutoffTime) {
                        unlink($file->getPathname());
                        $deletedCount++;
                    }
                }
            }
        }

        $this->logInfo("Cleaned up old log files", [
            'deleted_count' => $deletedCount,
            'older_than_days' => $days
        ]);

        return $deletedCount;
    }
}
