<?php
/**
 * Rate-Limiter-Klasse
 * Implementiert verschiedene Rate-Limiting-Strategien zum Schutz vor Missbrauch
 */

class RateLimiter
{
    private $config;
    private $storage;
    private $storageType;
    private $defaultLimits;

    public function __construct()
    {
        $this->config = Config::get('security');
        $this->storageType = 'file'; // file, redis, database
        $this->initializeStorage();
        
        // Standard-Limits definieren
        $this->defaultLimits = [
            'login' => ['requests' => 5, 'window' => 900], // 5 Versuche in 15 Minuten
            'api' => ['requests' => 100, 'window' => 3600], // 100 Requests pro Stunde
            'password_reset' => ['requests' => 3, 'window' => 3600], // 3 Versuche pro Stunde
            'file_upload' => ['requests' => 10, 'window' => 600], // 10 Uploads in 10 Minuten
            'form_submission' => ['requests' => 20, 'window' => 3600], // 20 Formulare pro Stunde
            'search' => ['requests' => 50, 'window' => 3600], // 50 Suchen pro Stunde
            'export' => ['requests' => 5, 'window' => 3600] // 5 Exports pro Stunde
        ];
    }

    /**
     * Prüft ob ein Request erlaubt ist
     * 
     * @param string $key Eindeutiger Schlüssel (z.B. action_ip)
     * @param string $identifier Identifier (IP, User-ID, etc.)
     * @param array $limits Spezifische Limits (optional)
     * @return bool True wenn erlaubt
     */
    public function checkLimit($key, $identifier, $limits = null)
    {
        $limits = $limits ?: $this->getDefaultLimits($key);
        $cacheKey = $this->generateCacheKey($key, $identifier);
        
        $data = $this->getFromStorage($cacheKey);
        
        if (!$data) {
            // Erste Anfrage
            return true;
        }

        $currentTime = time();
        $windowStart = $currentTime - $limits['window'];
        
        // Alte Einträge entfernen
        $data['requests'] = array_filter($data['requests'], function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });

        // Limit prüfen
        return count($data['requests']) < $limits['requests'];
    }

    /**
     * Registriert einen Request
     * 
     * @param string $key Eindeutiger Schlüssel
     * @param string $identifier Identifier
     * @param array $limits Spezifische Limits (optional)
     * @return bool True wenn erfolgreich registriert
     */
    public function recordAttempt($key, $identifier = null, $limits = null)
    {
        $identifier = $identifier ?: ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $limits = $limits ?: $this->getDefaultLimits($key);
        $cacheKey = $this->generateCacheKey($key, $identifier);
        
        $data = $this->getFromStorage($cacheKey) ?: ['requests' => []];
        
        $currentTime = time();
        $windowStart = $currentTime - $limits['window'];
        
        // Alte Einträge entfernen
        $data['requests'] = array_filter($data['requests'], function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });

        // Neuen Request hinzufügen
        $data['requests'][] = $currentTime;
        $data['last_request'] = $currentTime;
        
        // In Storage speichern
        $this->saveToStorage($cacheKey, $data, $limits['window']);
        
        return true;
    }

    /**
     * Holt verbleibende Requests
     * 
     * @param string $key Eindeutiger Schlüssel
     * @param string $identifier Identifier
     * @param array $limits Spezifische Limits (optional)
     * @return array Informationen über verbleibende Requests
     */
    public function getRemainingRequests($key, $identifier, $limits = null)
    {
        $limits = $limits ?: $this->getDefaultLimits($key);
        $cacheKey = $this->generateCacheKey($key, $identifier);
        
        $data = $this->getFromStorage($cacheKey);
        
        if (!$data) {
            return [
                'remaining' => $limits['requests'],
                'reset_time' => time() + $limits['window'],
                'total' => $limits['requests']
            ];
        }

        $currentTime = time();
        $windowStart = $currentTime - $limits['window'];
        
        // Aktuelle Requests zählen
        $currentRequests = array_filter($data['requests'], function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });

        $remaining = max(0, $limits['requests'] - count($currentRequests));
        $oldestRequest = !empty($currentRequests) ? min($currentRequests) : $currentTime;
        $resetTime = $oldestRequest + $limits['window'];

        return [
            'remaining' => $remaining,
            'reset_time' => $resetTime,
            'total' => $limits['requests'],
            'window' => $limits['window']
        ];
    }

    /**
     * Implementiert Token-Bucket-Algorithmus
     * 
     * @param string $key Eindeutiger Schlüssel
     * @param string $identifier Identifier
     * @param int $capacity Bucket-Kapazität
     * @param float $refillRate Nachfüllrate (Tokens pro Sekunde)
     * @param int $tokens Anzahl benötigter Tokens
     * @return bool True wenn erlaubt
     */
    public function checkTokenBucket($key, $identifier, $capacity = 10, $refillRate = 1.0, $tokens = 1)
    {
        $cacheKey = $this->generateCacheKey($key . '_bucket', $identifier);
        $data = $this->getFromStorage($cacheKey);
        
        $currentTime = microtime(true);
        
        if (!$data) {
            // Neuer Bucket
            $data = [
                'tokens' => $capacity,
                'last_refill' => $currentTime
            ];
        } else {
            // Tokens nachfüllen
            $timePassed = $currentTime - $data['last_refill'];
            $tokensToAdd = $timePassed * $refillRate;
            $data['tokens'] = min($capacity, $data['tokens'] + $tokensToAdd);
            $data['last_refill'] = $currentTime;
        }

        // Prüfen ob genügend Tokens vorhanden
        if ($data['tokens'] >= $tokens) {
            $data['tokens'] -= $tokens;
            $this->saveToStorage($cacheKey, $data, 3600); // 1 Stunde TTL
            return true;
        }

        // Bucket-Status speichern auch wenn Request abgelehnt
        $this->saveToStorage($cacheKey, $data, 3600);
        return false;
    }

    /**
     * Implementiert Sliding-Window-Algorithmus
     * 
     * @param string $key Eindeutiger Schlüssel
     * @param string $identifier Identifier
     * @param int $limit Anzahl erlaubter Requests
     * @param int $window Zeitfenster in Sekunden
     * @return bool True wenn erlaubt
     */
    public function checkSlidingWindow($key, $identifier, $limit, $window)
    {
        $cacheKey = $this->generateCacheKey($key . '_sliding', $identifier);
        $currentTime = time();
        
        // Aktuelle und vorherige Fenster
        $currentWindow = floor($currentTime / $window);
        $previousWindow = $currentWindow - 1;
        
        $currentKey = $cacheKey . '_' . $currentWindow;
        $previousKey = $cacheKey . '_' . $previousWindow;
        
        $currentCount = $this->getFromStorage($currentKey) ?: 0;
        $previousCount = $this->getFromStorage($previousKey) ?: 0;
        
        // Gewichtung basierend auf Position im aktuellen Fenster
        $windowProgress = ($currentTime % $window) / $window;
        $weightedPreviousCount = $previousCount * (1 - $windowProgress);
        
        $totalCount = $currentCount + $weightedPreviousCount;
        
        if ($totalCount < $limit) {
            // Request erlaubt, Counter erhöhen
            $this->saveToStorage($currentKey, $currentCount + 1, $window * 2);
            return true;
        }

        return false;
    }

    /**
     * Blockiert einen Identifier für eine bestimmte Zeit
     * 
     * @param string $key Eindeutiger Schlüssel
     * @param string $identifier Identifier
     * @param int $duration Blockierungsdauer in Sekunden
     * @param string $reason Grund der Blockierung
     */
    public function blockIdentifier($key, $identifier, $duration, $reason = '')
    {
        $cacheKey = $this->generateCacheKey($key . '_blocked', $identifier);
        
        $blockData = [
            'blocked_until' => time() + $duration,
            'reason' => $reason,
            'blocked_at' => time()
        ];

        $this->saveToStorage($cacheKey, $blockData, $duration);
    }

    /**
     * Prüft ob ein Identifier blockiert ist
     * 
     * @param string $key Eindeutiger Schlüssel
     * @param string $identifier Identifier
     * @return array|false Blockierungsinformationen oder false
     */
    public function isBlocked($key, $identifier)
    {
        $cacheKey = $this->generateCacheKey($key . '_blocked', $identifier);
        $blockData = $this->getFromStorage($cacheKey);
        
        if (!$blockData) {
            return false;
        }

        if (time() >= $blockData['blocked_until']) {
            // Blockierung abgelaufen
            $this->removeFromStorage($cacheKey);
            return false;
        }

        return $blockData;
    }

    /**
     * Entfernt eine Blockierung
     * 
     * @param string $key Eindeutiger Schlüssel
     * @param string $identifier Identifier
     */
    public function unblockIdentifier($key, $identifier)
    {
        $cacheKey = $this->generateCacheKey($key . '_blocked', $identifier);
        $this->removeFromStorage($cacheKey);
    }

    /**
     * Implementiert progressive Verzögerung
     * 
     * @param string $key Eindeutiger Schlüssel
     * @param string $identifier Identifier
     * @param int $baseDelay Basis-Verzögerung in Sekunden
     * @param int $maxDelay Maximale Verzögerung
     * @return int Verzögerung in Sekunden
     */
    public function getProgressiveDelay($key, $identifier, $baseDelay = 1, $maxDelay = 300)
    {
        $cacheKey = $this->generateCacheKey($key . '_attempts', $identifier);
        $attempts = $this->getFromStorage($cacheKey) ?: 0;
        
        // Exponentieller Backoff mit Jitter
        $delay = min($maxDelay, $baseDelay * pow(2, $attempts));
        $jitter = rand(0, $delay * 0.1); // 10% Jitter
        
        // Attempt-Counter erhöhen
        $this->saveToStorage($cacheKey, $attempts + 1, 3600);
        
        return $delay + $jitter;
    }

    /**
     * Setzt den Attempt-Counter zurück
     * 
     * @param string $key Eindeutiger Schlüssel
     * @param string $identifier Identifier
     */
    public function resetAttempts($key, $identifier)
    {
        $cacheKey = $this->generateCacheKey($key . '_attempts', $identifier);
        $this->removeFromStorage($cacheKey);
    }

    /**
     * Holt Rate-Limiting-Statistiken
     * 
     * @param string $key Eindeutiger Schlüssel
     * @param string $identifier Identifier
     * @return array Statistiken
     */
    public function getStats($key, $identifier)
    {
        $limits = $this->getDefaultLimits($key);
        $remaining = $this->getRemainingRequests($key, $identifier, $limits);
        $blocked = $this->isBlocked($key, $identifier);
        
        return [
            'key' => $key,
            'identifier' => $identifier,
            'limits' => $limits,
            'remaining' => $remaining,
            'blocked' => $blocked,
            'current_time' => time()
        ];
    }

    /**
     * Bereinigt abgelaufene Einträge
     * 
     * @param int $maxAge Maximales Alter in Sekunden
     * @return int Anzahl bereinigter Einträge
     */
    public function cleanup($maxAge = 86400)
    {
        if ($this->storageType === 'file') {
            return $this->cleanupFileStorage($maxAge);
        }
        
        return 0;
    }

    /**
     * Generiert Cache-Schlüssel
     * 
     * @param string $key Basis-Schlüssel
     * @param string $identifier Identifier
     * @return string Cache-Schlüssel
     */
    private function generateCacheKey($key, $identifier)
    {
        return 'rate_limit_' . hash('sha256', $key . '_' . $identifier);
    }

    /**
     * Holt Standard-Limits für einen Schlüssel
     * 
     * @param string $key Schlüssel
     * @return array Limits
     */
    private function getDefaultLimits($key)
    {
        // Basis-Schlüssel extrahieren (ohne Suffix wie _bucket, _sliding)
        $baseKey = preg_replace('/_(?:bucket|sliding|blocked|attempts)$/', '', $key);
        
        return $this->defaultLimits[$baseKey] ?? ['requests' => 10, 'window' => 3600];
    }

    /**
     * Initialisiert Storage-Backend
     */
    private function initializeStorage()
    {
        switch ($this->storageType) {
            case 'file':
                $this->storage = dirname(__DIR__, 2) . '/cache/rate_limits';
                if (!is_dir($this->storage)) {
                    mkdir($this->storage, 0755, true);
                }
                break;
                
            case 'redis':
                // Redis-Implementierung für zukünftige Erweiterung
                break;
                
            case 'database':
                // Datenbank-Implementierung für zukünftige Erweiterung
                break;
        }
    }

    /**
     * Holt Daten aus Storage
     * 
     * @param string $key Schlüssel
     * @return mixed Daten oder null
     */
    private function getFromStorage($key)
    {
        switch ($this->storageType) {
            case 'file':
                return $this->getFromFileStorage($key);
            default:
                return null;
        }
    }

    /**
     * Speichert Daten in Storage
     * 
     * @param string $key Schlüssel
     * @param mixed $data Daten
     * @param int $ttl Time-to-Live in Sekunden
     */
    private function saveToStorage($key, $data, $ttl)
    {
        switch ($this->storageType) {
            case 'file':
                $this->saveToFileStorage($key, $data, $ttl);
                break;
        }
    }

    /**
     * Entfernt Daten aus Storage
     * 
     * @param string $key Schlüssel
     */
    private function removeFromStorage($key)
    {
        switch ($this->storageType) {
            case 'file':
                $this->removeFromFileStorage($key);
                break;
        }
    }

    /**
     * Holt Daten aus Datei-Storage
     * 
     * @param string $key Schlüssel
     * @return mixed Daten oder null
     */
    private function getFromFileStorage($key)
    {
        $filepath = $this->storage . '/' . $key . '.json';
        
        if (!file_exists($filepath)) {
            return null;
        }

        $content = file_get_contents($filepath);
        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);
        if (!$data) {
            return null;
        }

        // TTL prüfen
        if (isset($data['expires']) && time() > $data['expires']) {
            unlink($filepath);
            return null;
        }

        return $data['data'] ?? null;
    }

    /**
     * Speichert Daten in Datei-Storage
     * 
     * @param string $key Schlüssel
     * @param mixed $data Daten
     * @param int $ttl Time-to-Live
     */
    private function saveToFileStorage($key, $data, $ttl)
    {
        $filepath = $this->storage . '/' . $key . '.json';
        
        $cacheData = [
            'data' => $data,
            'created' => time(),
            'expires' => time() + $ttl
        ];

        file_put_contents($filepath, json_encode($cacheData), LOCK_EX);
        chmod($filepath, 0644);
    }

    /**
     * Entfernt Daten aus Datei-Storage
     * 
     * @param string $key Schlüssel
     */
    private function removeFromFileStorage($key)
    {
        $filepath = $this->storage . '/' . $key . '.json';
        
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }

    /**
     * Bereinigt Datei-Storage
     * 
     * @param int $maxAge Maximales Alter
     * @return int Anzahl bereinigter Dateien
     */
    private function cleanupFileStorage($maxAge)
    {
        $cleaned = 0;
        $cutoffTime = time() - $maxAge;
        
        if (!is_dir($this->storage)) {
            return 0;
        }

        $iterator = new DirectoryIterator($this->storage);
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'json') {
                if ($file->getMTime() < $cutoffTime) {
                    unlink($file->getPathname());
                    $cleaned++;
                } else {
                    // Auch TTL-abgelaufene Dateien prüfen
                    $content = file_get_contents($file->getPathname());
                    if ($content) {
                        $data = json_decode($content, true);
                        if ($data && isset($data['expires']) && time() > $data['expires']) {
                            unlink($file->getPathname());
                            $cleaned++;
                        }
                    }
                }
            }
        }

        return $cleaned;
    }

    /**
     * Setzt benutzerdefinierte Limits
     * 
     * @param string $key Schlüssel
     * @param array $limits Limits
     */
    public function setLimits($key, $limits)
    {
        $this->defaultLimits[$key] = $limits;
    }

    /**
     * Holt alle konfigurierten Limits
     * 
     * @return array Alle Limits
     */
    public function getAllLimits()
    {
        return $this->defaultLimits;
    }

    /**
     * Prüft Rate-Limit mit automatischer Registrierung
     * 
     * @param string $key Eindeutiger Schlüssel
     * @param string $identifier Identifier
     * @param array $limits Spezifische Limits (optional)
     * @return bool True wenn erlaubt
     */
    public function checkAndRecord($key, $identifier = null, $limits = null)
    {
        $identifier = $identifier ?: ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        
        if ($this->checkLimit($key, $identifier, $limits)) {
            $this->recordAttempt($key, $identifier, $limits);
            return true;
        }
        
        return false;
    }

    /**
     * Erstellt HTTP-Header für Rate-Limiting
     * 
     * @param string $key Eindeutiger Schlüssel
     * @param string $identifier Identifier
     * @return array HTTP-Header
     */
    public function getHttpHeaders($key, $identifier)
    {
        $remaining = $this->getRemainingRequests($key, $identifier);
        
        return [
            'X-RateLimit-Limit' => $remaining['total'],
            'X-RateLimit-Remaining' => $remaining['remaining'],
            'X-RateLimit-Reset' => $remaining['reset_time'],
            'X-RateLimit-Window' => $remaining['window']
        ];
    }

    /**
     * Sendet Rate-Limit-Header
     * 
     * @param string $key Eindeutiger Schlüssel
     * @param string $identifier Identifier
     */
    public function sendHeaders($key, $identifier)
    {
        $headers = $this->getHttpHeaders($key, $identifier);
        
        foreach ($headers as $name => $value) {
            header("{$name}: {$value}");
        }
    }
}
