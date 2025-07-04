<?php
/**
 * Sicherheits-Manager-Klasse
 * Zentrale Verwaltung aller Sicherheitsfunktionen
 */

class SecurityManager
{
    private $config;
    private $inputValidator;
    private $securityLogger;
    private $rateLimiter;

    public function __construct()
    {
        $this->config = Config::get('security');
        $this->inputValidator = new InputValidator();
        $this->securityLogger = new SecurityLogger();
        $this->rateLimiter = new RateLimiter();
    }

    /**
     * Validiert und sanitisiert Input-Daten
     * 
     * @param mixed $input Eingabedaten
     * @param string $type Datentyp (string, email, numeric, etc.)
     * @param array $options Zusätzliche Optionen
     * @return mixed Sanitisierte Daten oder false bei Fehler
     */
    public function validateInput($input, $type = 'string', $options = [])
    {
        try {
            // Rate Limiting prüfen
            if (!$this->rateLimiter->checkLimit('input_validation', $_SERVER['REMOTE_ADDR'])) {
                $this->securityLogger->logRateLimitExceeded('input_validation', $_SERVER['REMOTE_ADDR']);
                return false;
            }

            // Input-Validierung durchführen
            $result = $this->inputValidator->validate($input, $type, $options);
            
            if ($result === false) {
                $this->securityLogger->logInvalidInput($input, $type, $_SERVER['REMOTE_ADDR']);
            }

            return $result;
        } catch (Exception $e) {
            $this->securityLogger->logError('Input validation error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Prüft auf Brute-Force-Angriffe
     * 
     * @param string $identifier Eindeutige Kennung (z.B. Username, IP)
     * @param string $action Aktion (login, password_reset, etc.)
     * @return bool True wenn erlaubt, false wenn blockiert
     */
    public function checkBruteForce($identifier, $action = 'login')
    {
        $key = $action . '_' . hash('sha256', $identifier . $_SERVER['REMOTE_ADDR']);
        
        // Rate Limiting prüfen
        if (!$this->rateLimiter->checkLimit($key, $_SERVER['REMOTE_ADDR'])) {
            $this->securityLogger->logBruteForceAttempt($identifier, $action, $_SERVER['REMOTE_ADDR']);
            return false;
        }

        return true;
    }

    /**
     * Registriert einen fehlgeschlagenen Versuch
     * 
     * @param string $identifier Eindeutige Kennung
     * @param string $action Aktion
     */
    public function recordFailedAttempt($identifier, $action = 'login')
    {
        $key = $action . '_' . hash('sha256', $identifier . $_SERVER['REMOTE_ADDR']);
        $this->rateLimiter->recordAttempt($key);
        $this->securityLogger->logFailedAttempt($identifier, $action, $_SERVER['REMOTE_ADDR']);
    }

    /**
     * Validiert Datei-Uploads auf Sicherheit
     * 
     * @param array $file $_FILES Array-Element
     * @param array $allowedTypes Erlaubte MIME-Types
     * @param int $maxSize Maximale Dateigröße in Bytes
     * @return array Validierungsergebnis
     */
    public function validateFileUpload($file, $allowedTypes = [], $maxSize = 5242880)
    {
        $result = [
            'valid' => false,
            'errors' => [],
            'sanitized_name' => ''
        ];

        try {
            // Grundlegende Validierung
            if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                $result['errors'][] = 'Ungültige Datei-Upload';
                $this->securityLogger->logInvalidFileUpload('Invalid upload', $_SERVER['REMOTE_ADDR']);
                return $result;
            }

            // Dateigröße prüfen
            if ($file['size'] > $maxSize) {
                $result['errors'][] = 'Datei zu groß (max. ' . number_format($maxSize / 1024 / 1024, 2) . ' MB)';
                $this->securityLogger->logInvalidFileUpload('File too large: ' . $file['size'], $_SERVER['REMOTE_ADDR']);
                return $result;
            }

            // MIME-Type prüfen
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!empty($allowedTypes) && !in_array($mimeType, $allowedTypes)) {
                $result['errors'][] = 'Dateityp nicht erlaubt: ' . $mimeType;
                $this->securityLogger->logInvalidFileUpload('Invalid MIME type: ' . $mimeType, $_SERVER['REMOTE_ADDR']);
                return $result;
            }

            // Dateiname sanitisieren
            $result['sanitized_name'] = $this->sanitizeFilename($file['name']);

            // Auf schädliche Inhalte prüfen
            if ($this->containsMaliciousContent($file['tmp_name'])) {
                $result['errors'][] = 'Datei enthält potentiell schädliche Inhalte';
                $this->securityLogger->logMaliciousFileUpload($file['name'], $_SERVER['REMOTE_ADDR']);
                return $result;
            }

            $result['valid'] = true;
            $this->securityLogger->logValidFileUpload($file['name'], $mimeType, $_SERVER['REMOTE_ADDR']);

        } catch (Exception $e) {
            $result['errors'][] = 'Fehler bei Datei-Validierung: ' . $e->getMessage();
            $this->securityLogger->logError('File validation error: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Sanitisiert Output für sichere Ausgabe
     * 
     * @param mixed $data Daten zum Sanitisieren
     * @param string $context Kontext (html, attribute, javascript, css)
     * @return mixed Sanitisierte Daten
     */
    public function sanitizeOutput($data, $context = 'html')
    {
        if (is_array($data)) {
            return array_map(function($item) use ($context) {
                return $this->sanitizeOutput($item, $context);
            }, $data);
        }

        if (!is_string($data)) {
            return $data;
        }

        switch ($context) {
            case 'html':
                return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            
            case 'attribute':
                return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            
            case 'javascript':
                return json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
            
            case 'css':
                return preg_replace('/[^a-zA-Z0-9\-_#.]/', '', $data);
            
            case 'url':
                return urlencode($data);
            
            default:
                return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
    }

    /**
     * Sanitisiert Dateinamen
     * 
     * @param string $filename Original-Dateiname
     * @return string Sanitisierter Dateiname
     */
    private function sanitizeFilename($filename)
    {
        // Gefährliche Zeichen entfernen
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Mehrfache Punkte entfernen
        $filename = preg_replace('/\.+/', '.', $filename);
        
        // Führende/nachfolgende Punkte und Unterstriche entfernen
        $filename = trim($filename, '._');
        
        // Maximale Länge begrenzen
        if (strlen($filename) > 255) {
            $pathinfo = pathinfo($filename);
            $extension = isset($pathinfo['extension']) ? '.' . $pathinfo['extension'] : '';
            $basename = substr($pathinfo['filename'], 0, 255 - strlen($extension));
            $filename = $basename . $extension;
        }

        return $filename;
    }

    /**
     * Prüft Datei auf schädliche Inhalte
     * 
     * @param string $filepath Pfad zur Datei
     * @return bool True wenn schädlich
     */
    private function containsMaliciousContent($filepath)
    {
        $maliciousPatterns = [
            '/<\?php/i',
            '/<script/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload=/i',
            '/onerror=/i',
            '/eval\(/i',
            '/base64_decode/i',
            '/shell_exec/i',
            '/system\(/i',
            '/exec\(/i',
            '/passthru/i'
        ];

        $content = file_get_contents($filepath, false, null, 0, 8192); // Nur erste 8KB prüfen
        
        foreach ($maliciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generiert einen sicheren Zufallsstring
     * 
     * @param int $length Länge des Strings
     * @param string $charset Zeichensatz
     * @return string Zufallsstring
     */
    public function generateSecureRandom($length = 32, $charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789')
    {
        $random = '';
        $charsetLength = strlen($charset);
        
        for ($i = 0; $i < $length; $i++) {
            $random .= $charset[random_int(0, $charsetLength - 1)];
        }
        
        return $random;
    }

    /**
     * Prüft die Stärke eines Passworts
     * 
     * @param string $password Passwort
     * @return array Bewertung der Passwortstärke
     */
    public function checkPasswordStrength($password)
    {
        $result = [
            'score' => 0,
            'strength' => 'weak',
            'feedback' => []
        ];

        $length = strlen($password);
        
        // Länge prüfen
        if ($length < 8) {
            $result['feedback'][] = 'Passwort sollte mindestens 8 Zeichen haben';
        } else {
            $result['score'] += 1;
        }

        // Verschiedene Zeichentypen prüfen
        if (preg_match('/[a-z]/', $password)) {
            $result['score'] += 1;
        } else {
            $result['feedback'][] = 'Passwort sollte Kleinbuchstaben enthalten';
        }

        if (preg_match('/[A-Z]/', $password)) {
            $result['score'] += 1;
        } else {
            $result['feedback'][] = 'Passwort sollte Großbuchstaben enthalten';
        }

        if (preg_match('/[0-9]/', $password)) {
            $result['score'] += 1;
        } else {
            $result['feedback'][] = 'Passwort sollte Zahlen enthalten';
        }

        if (preg_match('/[^a-zA-Z0-9]/', $password)) {
            $result['score'] += 1;
        } else {
            $result['feedback'][] = 'Passwort sollte Sonderzeichen enthalten';
        }

        // Stärke bestimmen
        if ($result['score'] >= 4) {
            $result['strength'] = 'strong';
        } elseif ($result['score'] >= 3) {
            $result['strength'] = 'medium';
        }

        return $result;
    }

    /**
     * Erstellt einen Hash für sichere Passwort-Speicherung
     * 
     * @param string $password Klartext-Passwort
     * @return string Passwort-Hash
     */
    public function hashPassword($password)
    {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64 MB
            'time_cost' => 4,       // 4 Iterationen
            'threads' => 3          // 3 Threads
        ]);
    }

    /**
     * Verifiziert ein Passwort gegen einen Hash
     * 
     * @param string $password Klartext-Passwort
     * @param string $hash Gespeicherter Hash
     * @return bool True wenn korrekt
     */
    public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    /**
     * Prüft ob ein Hash aktualisiert werden sollte
     * 
     * @param string $hash Gespeicherter Hash
     * @return bool True wenn Update nötig
     */
    public function needsRehash($hash)
    {
        return password_needs_rehash($hash, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }
}
