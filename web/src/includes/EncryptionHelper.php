<?php
/**
 * Verschlüsselungs-Helper-Klasse
 * Bietet sichere Verschlüsselungs- und Entschlüsselungsfunktionen
 */

class EncryptionHelper
{
    private $config;
    private $encryptionKey;
    private $cipher;
    private $keyDerivationIterations;

    public function __construct()
    {
        $this->config = Config::get('security');
        $this->encryptionKey = $this->config['encryption_key'] ?? null;
        $this->cipher = 'aes-256-gcm';
        $this->keyDerivationIterations = 100000;

        if (!$this->encryptionKey) {
            throw new Exception('Encryption key not configured');
        }

        if (!in_array($this->cipher, openssl_get_cipher_methods())) {
            throw new Exception('Cipher method not available: ' . $this->cipher);
        }
    }

    /**
     * Verschlüsselt Daten
     * 
     * @param string $data Zu verschlüsselnde Daten
     * @param string $password Optionales zusätzliches Passwort
     * @return string Base64-kodierte verschlüsselte Daten
     */
    public function encrypt($data, $password = null)
    {
        if (!is_string($data)) {
            throw new InvalidArgumentException('Data must be a string');
        }

        try {
            // Zufälligen Salt generieren
            $salt = random_bytes(32);
            
            // Schlüssel ableiten
            $key = $this->deriveKey($this->encryptionKey, $salt, $password);
            
            // Zufälligen IV generieren
            $iv = random_bytes(openssl_cipher_iv_length($this->cipher));
            
            // Daten verschlüsseln
            $encrypted = openssl_encrypt($data, $this->cipher, $key, OPENSSL_RAW_DATA, $iv, $tag);
            
            if ($encrypted === false) {
                throw new Exception('Encryption failed');
            }

            // Alle Komponenten zusammenfügen
            $result = [
                'version' => 1,
                'cipher' => $this->cipher,
                'salt' => base64_encode($salt),
                'iv' => base64_encode($iv),
                'tag' => base64_encode($tag),
                'data' => base64_encode($encrypted)
            ];

            return base64_encode(json_encode($result));

        } catch (Exception $e) {
            throw new Exception('Encryption error: ' . $e->getMessage());
        }
    }

    /**
     * Entschlüsselt Daten
     * 
     * @param string $encryptedData Base64-kodierte verschlüsselte Daten
     * @param string $password Optionales zusätzliches Passwort
     * @return string Entschlüsselte Daten
     */
    public function decrypt($encryptedData, $password = null)
    {
        if (!is_string($encryptedData)) {
            throw new InvalidArgumentException('Encrypted data must be a string');
        }

        try {
            // Base64-Dekodierung
            $decoded = base64_decode($encryptedData, true);
            if ($decoded === false) {
                throw new Exception('Invalid base64 encoding');
            }

            // JSON-Dekodierung
            $data = json_decode($decoded, true);
            if (!$data || !is_array($data)) {
                throw new Exception('Invalid encrypted data format');
            }

            // Erforderliche Felder prüfen
            $requiredFields = ['version', 'cipher', 'salt', 'iv', 'tag', 'data'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    throw new Exception("Missing field: {$field}");
                }
            }

            // Version prüfen
            if ($data['version'] !== 1) {
                throw new Exception('Unsupported encryption version');
            }

            // Cipher prüfen
            if ($data['cipher'] !== $this->cipher) {
                throw new Exception('Unsupported cipher method');
            }

            // Komponenten dekodieren
            $salt = base64_decode($data['salt'], true);
            $iv = base64_decode($data['iv'], true);
            $tag = base64_decode($data['tag'], true);
            $encrypted = base64_decode($data['data'], true);

            if ($salt === false || $iv === false || $tag === false || $encrypted === false) {
                throw new Exception('Invalid component encoding');
            }

            // Schlüssel ableiten
            $key = $this->deriveKey($this->encryptionKey, $salt, $password);

            // Daten entschlüsseln
            $decrypted = openssl_decrypt($encrypted, $this->cipher, $key, OPENSSL_RAW_DATA, $iv, $tag);

            if ($decrypted === false) {
                throw new Exception('Decryption failed - invalid key or corrupted data');
            }

            return $decrypted;

        } catch (Exception $e) {
            throw new Exception('Decryption error: ' . $e->getMessage());
        }
    }

    /**
     * Verschlüsselt eine Datei
     * 
     * @param string $inputFile Pfad zur Eingabedatei
     * @param string $outputFile Pfad zur Ausgabedatei
     * @param string $password Optionales zusätzliches Passwort
     * @return bool True bei Erfolg
     */
    public function encryptFile($inputFile, $outputFile, $password = null)
    {
        if (!file_exists($inputFile)) {
            throw new Exception('Input file does not exist: ' . $inputFile);
        }

        if (!is_readable($inputFile)) {
            throw new Exception('Input file is not readable: ' . $inputFile);
        }

        try {
            $data = file_get_contents($inputFile);
            if ($data === false) {
                throw new Exception('Could not read input file');
            }

            $encrypted = $this->encrypt($data, $password);
            
            $result = file_put_contents($outputFile, $encrypted, LOCK_EX);
            if ($result === false) {
                throw new Exception('Could not write output file');
            }

            // Sichere Dateiberechtigungen setzen
            chmod($outputFile, 0600);

            return true;

        } catch (Exception $e) {
            throw new Exception('File encryption error: ' . $e->getMessage());
        }
    }

    /**
     * Entschlüsselt eine Datei
     * 
     * @param string $inputFile Pfad zur verschlüsselten Datei
     * @param string $outputFile Pfad zur Ausgabedatei
     * @param string $password Optionales zusätzliches Passwort
     * @return bool True bei Erfolg
     */
    public function decryptFile($inputFile, $outputFile, $password = null)
    {
        if (!file_exists($inputFile)) {
            throw new Exception('Input file does not exist: ' . $inputFile);
        }

        if (!is_readable($inputFile)) {
            throw new Exception('Input file is not readable: ' . $inputFile);
        }

        try {
            $encryptedData = file_get_contents($inputFile);
            if ($encryptedData === false) {
                throw new Exception('Could not read input file');
            }

            $decrypted = $this->decrypt($encryptedData, $password);
            
            $result = file_put_contents($outputFile, $decrypted, LOCK_EX);
            if ($result === false) {
                throw new Exception('Could not write output file');
            }

            return true;

        } catch (Exception $e) {
            throw new Exception('File decryption error: ' . $e->getMessage());
        }
    }

    /**
     * Erstellt einen Hash für Passwörter
     * 
     * @param string $password Passwort
     * @param array $options Hash-Optionen
     * @return string Passwort-Hash
     */
    public function hashPassword($password, $options = [])
    {
        $defaultOptions = [
            'memory_cost' => 65536, // 64 MB
            'time_cost' => 4,       // 4 Iterationen
            'threads' => 3          // 3 Threads
        ];

        $options = array_merge($defaultOptions, $options);

        return password_hash($password, PASSWORD_ARGON2ID, $options);
    }

    /**
     * Verifiziert ein Passwort gegen einen Hash
     * 
     * @param string $password Passwort
     * @param string $hash Hash
     * @return bool True wenn korrekt
     */
    public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    /**
     * Generiert einen sicheren Zufallsstring
     * 
     * @param int $length Länge des Strings
     * @param bool $base64 Als Base64 kodieren
     * @return string Zufallsstring
     */
    public function generateRandomString($length = 32, $base64 = false)
    {
        $bytes = random_bytes($length);
        
        if ($base64) {
            return base64_encode($bytes);
        }

        return bin2hex($bytes);
    }

    /**
     * Generiert einen sicheren Token
     * 
     * @param int $length Länge in Bytes
     * @return string Base64-kodierter Token
     */
    public function generateToken($length = 32)
    {
        return base64_encode(random_bytes($length));
    }

    /**
     * Erstellt einen HMAC
     * 
     * @param string $data Daten
     * @param string $key Schlüssel (optional, verwendet Encryption-Key)
     * @param string $algorithm Hash-Algorithmus
     * @return string HMAC
     */
    public function createHmac($data, $key = null, $algorithm = 'sha256')
    {
        $key = $key ?: $this->encryptionKey;
        
        if (!in_array($algorithm, hash_algos())) {
            throw new Exception('Unsupported hash algorithm: ' . $algorithm);
        }

        return hash_hmac($algorithm, $data, $key);
    }

    /**
     * Verifiziert einen HMAC
     * 
     * @param string $data Originaldaten
     * @param string $hmac HMAC zum Verifizieren
     * @param string $key Schlüssel (optional)
     * @param string $algorithm Hash-Algorithmus
     * @return bool True wenn gültig
     */
    public function verifyHmac($data, $hmac, $key = null, $algorithm = 'sha256')
    {
        $expectedHmac = $this->createHmac($data, $key, $algorithm);
        return hash_equals($expectedHmac, $hmac);
    }

    /**
     * Verschlüsselt sensible Konfigurationsdaten
     * 
     * @param array $config Konfigurationsdaten
     * @param array $sensitiveKeys Schlüssel für sensible Daten
     * @return array Verschlüsselte Konfiguration
     */
    public function encryptConfig($config, $sensitiveKeys = [])
    {
        $defaultSensitiveKeys = [
            'password', 'secret', 'key', 'token', 'api_key', 'private_key'
        ];

        $sensitiveKeys = array_merge($defaultSensitiveKeys, $sensitiveKeys);
        
        return $this->encryptArrayValues($config, $sensitiveKeys);
    }

    /**
     * Entschlüsselt sensible Konfigurationsdaten
     * 
     * @param array $config Verschlüsselte Konfiguration
     * @param array $sensitiveKeys Schlüssel für sensible Daten
     * @return array Entschlüsselte Konfiguration
     */
    public function decryptConfig($config, $sensitiveKeys = [])
    {
        $defaultSensitiveKeys = [
            'password', 'secret', 'key', 'token', 'api_key', 'private_key'
        ];

        $sensitiveKeys = array_merge($defaultSensitiveKeys, $sensitiveKeys);
        
        return $this->decryptArrayValues($config, $sensitiveKeys);
    }

    /**
     * Leitet einen Schlüssel ab
     * 
     * @param string $password Basis-Passwort
     * @param string $salt Salt
     * @param string $additionalPassword Zusätzliches Passwort
     * @return string Abgeleiteter Schlüssel
     */
    private function deriveKey($password, $salt, $additionalPassword = null)
    {
        $input = $password;
        
        if ($additionalPassword) {
            $input .= $additionalPassword;
        }

        return hash_pbkdf2('sha256', $input, $salt, $this->keyDerivationIterations, 32, true);
    }

    /**
     * Verschlüsselt Werte in einem Array rekursiv
     * 
     * @param array $array Array
     * @param array $sensitiveKeys Sensible Schlüssel
     * @return array Verschlüsseltes Array
     */
    private function encryptArrayValues($array, $sensitiveKeys)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->encryptArrayValues($value, $sensitiveKeys);
            } elseif (is_string($value) && in_array($key, $sensitiveKeys)) {
                $array[$key] = $this->encrypt($value);
            }
        }

        return $array;
    }

    /**
     * Entschlüsselt Werte in einem Array rekursiv
     * 
     * @param array $array Verschlüsseltes Array
     * @param array $sensitiveKeys Sensible Schlüssel
     * @return array Entschlüsseltes Array
     */
    private function decryptArrayValues($array, $sensitiveKeys)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->decryptArrayValues($value, $sensitiveKeys);
            } elseif (is_string($value) && in_array($key, $sensitiveKeys)) {
                try {
                    $array[$key] = $this->decrypt($value);
                } catch (Exception $e) {
                    // Wenn Entschlüsselung fehlschlägt, Wert unverändert lassen
                    // (könnte bereits entschlüsselt oder nicht verschlüsselt sein)
                }
            }
        }

        return $array;
    }

    /**
     * Erstellt einen sicheren Fingerprint von Daten
     * 
     * @param mixed $data Daten
     * @param string $algorithm Hash-Algorithmus
     * @return string Fingerprint
     */
    public function createFingerprint($data, $algorithm = 'sha256')
    {
        if (!is_string($data)) {
            $data = serialize($data);
        }

        return hash($algorithm, $data);
    }

    /**
     * Verschlüsselt Daten für URL-sichere Übertragung
     * 
     * @param string $data Daten
     * @param string $password Optionales Passwort
     * @return string URL-sichere verschlüsselte Daten
     */
    public function encryptForUrl($data, $password = null)
    {
        $encrypted = $this->encrypt($data, $password);
        return rtrim(strtr($encrypted, '+/', '-_'), '=');
    }

    /**
     * Entschlüsselt URL-sichere Daten
     * 
     * @param string $encryptedData URL-sichere verschlüsselte Daten
     * @param string $password Optionales Passwort
     * @return string Entschlüsselte Daten
     */
    public function decryptFromUrl($encryptedData, $password = null)
    {
        // URL-sichere Zeichen zurück konvertieren
        $encrypted = str_pad(strtr($encryptedData, '-_', '+/'), strlen($encryptedData) % 4, '=', STR_PAD_RIGHT);
        
        return $this->decrypt($encrypted, $password);
    }

    /**
     * Sicheres Löschen von Variablen aus dem Speicher
     * 
     * @param mixed &$variable Variable zum Löschen
     */
    public function secureUnset(&$variable)
    {
        if (is_string($variable)) {
            // String mit Zufallsdaten überschreiben
            $length = strlen($variable);
            $variable = str_repeat("\0", $length);
            $variable = random_bytes($length);
        }
        
        unset($variable);
    }

    /**
     * Prüft die Stärke des Verschlüsselungsschlüssels
     * 
     * @return array Bewertung der Schlüsselstärke
     */
    public function checkKeyStrength()
    {
        $key = $this->encryptionKey;
        $result = [
            'length' => strlen($key),
            'entropy' => $this->calculateEntropy($key),
            'strength' => 'weak',
            'recommendations' => []
        ];

        // Mindestlänge prüfen
        if ($result['length'] < 32) {
            $result['recommendations'][] = 'Schlüssel sollte mindestens 32 Zeichen haben';
        }

        // Entropie bewerten
        if ($result['entropy'] < 4.0) {
            $result['recommendations'][] = 'Schlüssel sollte mehr Zufälligkeit enthalten';
        } elseif ($result['entropy'] >= 4.0 && $result['length'] >= 32) {
            $result['strength'] = 'strong';
        } elseif ($result['entropy'] >= 3.0 && $result['length'] >= 24) {
            $result['strength'] = 'medium';
        }

        return $result;
    }

    /**
     * Berechnet die Entropie eines Strings
     * 
     * @param string $string String
     * @return float Entropie
     */
    private function calculateEntropy($string)
    {
        $length = strlen($string);
        if ($length === 0) {
            return 0;
        }

        $frequencies = array_count_values(str_split($string));
        $entropy = 0;

        foreach ($frequencies as $frequency) {
            $probability = $frequency / $length;
            $entropy -= $probability * log($probability, 2);
        }

        return $entropy;
    }

    /**
     * Rotiert den Verschlüsselungsschlüssel
     * 
     * @param string $newKey Neuer Schlüssel
     * @return string Alter Schlüssel (für Migration)
     */
    public function rotateKey($newKey)
    {
        $oldKey = $this->encryptionKey;
        $this->encryptionKey = $newKey;
        
        return $oldKey;
    }

    /**
     * Migriert verschlüsselte Daten zu neuem Schlüssel
     * 
     * @param string $encryptedData Mit altem Schlüssel verschlüsselte Daten
     * @param string $oldKey Alter Schlüssel
     * @param string $password Optionales Passwort
     * @return string Mit neuem Schlüssel verschlüsselte Daten
     */
    public function migrateEncryptedData($encryptedData, $oldKey, $password = null)
    {
        // Temporär alten Schlüssel verwenden
        $currentKey = $this->encryptionKey;
        $this->encryptionKey = $oldKey;
        
        try {
            // Mit altem Schlüssel entschlüsseln
            $decrypted = $this->decrypt($encryptedData, $password);
            
            // Neuen Schlüssel wiederherstellen
            $this->encryptionKey = $currentKey;
            
            // Mit neuem Schlüssel verschlüsseln
            return $this->encrypt($decrypted, $password);
            
        } catch (Exception $e) {
            // Neuen Schlüssel wiederherstellen
            $this->encryptionKey = $currentKey;
            throw $e;
        }
    }
}
