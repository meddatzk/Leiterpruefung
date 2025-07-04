<?php
/**
 * Input-Validator-Klasse
 * Validiert verschiedene Eingabetypen mit umfassenden Sicherheitsprüfungen
 */

class InputValidator
{
    private $config;

    public function __construct()
    {
        $this->config = Config::get('security');
    }

    /**
     * Hauptvalidierungsmethode
     * 
     * @param mixed $input Eingabedaten
     * @param string $type Validierungstyp
     * @param array $options Zusätzliche Optionen
     * @return mixed Validierte Daten oder false bei Fehler
     */
    public function validate($input, $type, $options = [])
    {
        switch ($type) {
            case 'email':
                return $this->validateEmail($input, $options);
            case 'date':
                return $this->validateDate($input, $options);
            case 'numeric':
                return $this->validateNumeric($input, $options);
            case 'string':
                return $this->validateString($input, $options);
            case 'file':
                return $this->validateFile($input, $options);
            case 'url':
                return $this->validateUrl($input, $options);
            case 'phone':
                return $this->validatePhone($input, $options);
            case 'ip':
                return $this->validateIp($input, $options);
            case 'json':
                return $this->validateJson($input, $options);
            case 'base64':
                return $this->validateBase64($input, $options);
            default:
                return $this->validateString($input, $options);
        }
    }

    /**
     * Validiert E-Mail-Adressen
     * 
     * @param string $email E-Mail-Adresse
     * @param array $options Optionen (max_length, allow_international)
     * @return string|false Validierte E-Mail oder false
     */
    public function validateEmail($email, $options = [])
    {
        if (!is_string($email)) {
            return false;
        }

        // Grundlegende Sanitisierung
        $email = trim($email);
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);

        // Länge prüfen
        $maxLength = $options['max_length'] ?? 254;
        if (strlen($email) > $maxLength) {
            return false;
        }

        // E-Mail-Format validieren
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Internationale Domains prüfen
        if (!($options['allow_international'] ?? true)) {
            if (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
                return false;
            }
        }

        // Gefährliche Zeichen prüfen
        if (preg_match('/[<>"\']/', $email)) {
            return false;
        }

        // Domain-Teil validieren
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return false;
        }

        $domain = $parts[1];
        
        // Domain-Länge prüfen
        if (strlen($domain) > 253) {
            return false;
        }

        // MX-Record prüfen (optional)
        if ($options['check_mx'] ?? false) {
            if (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A')) {
                return false;
            }
        }

        return $email;
    }

    /**
     * Validiert Datumswerte
     * 
     * @param string $date Datum
     * @param array $options Optionen (format, min_date, max_date)
     * @return string|false Validiertes Datum oder false
     */
    public function validateDate($date, $options = [])
    {
        if (!is_string($date)) {
            return false;
        }

        $date = trim($date);
        $format = $options['format'] ?? 'Y-m-d';

        // Datum parsen
        $dateTime = DateTime::createFromFormat($format, $date);
        
        if (!$dateTime || $dateTime->format($format) !== $date) {
            return false;
        }

        // Mindestdatum prüfen
        if (isset($options['min_date'])) {
            $minDate = DateTime::createFromFormat($format, $options['min_date']);
            if ($minDate && $dateTime < $minDate) {
                return false;
            }
        }

        // Maximaldatum prüfen
        if (isset($options['max_date'])) {
            $maxDate = DateTime::createFromFormat($format, $options['max_date']);
            if ($maxDate && $dateTime > $maxDate) {
                return false;
            }
        }

        // Realistische Datumsgrenzen
        $year = (int)$dateTime->format('Y');
        if ($year < 1900 || $year > 2100) {
            return false;
        }

        return $dateTime->format($format);
    }

    /**
     * Validiert numerische Werte
     * 
     * @param mixed $number Numerischer Wert
     * @param array $options Optionen (min, max, decimals, type)
     * @return int|float|false Validierter Wert oder false
     */
    public function validateNumeric($number, $options = [])
    {
        if (is_string($number)) {
            $number = trim($number);
        }

        $type = $options['type'] ?? 'auto'; // auto, int, float

        // Typ-spezifische Validierung
        switch ($type) {
            case 'int':
                if (!filter_var($number, FILTER_VALIDATE_INT)) {
                    return false;
                }
                $number = (int)$number;
                break;
                
            case 'float':
                if (!filter_var($number, FILTER_VALIDATE_FLOAT)) {
                    return false;
                }
                $number = (float)$number;
                break;
                
            default:
                if (filter_var($number, FILTER_VALIDATE_INT) !== false) {
                    $number = (int)$number;
                } elseif (filter_var($number, FILTER_VALIDATE_FLOAT) !== false) {
                    $number = (float)$number;
                } else {
                    return false;
                }
        }

        // Bereichsprüfung
        if (isset($options['min']) && $number < $options['min']) {
            return false;
        }

        if (isset($options['max']) && $number > $options['max']) {
            return false;
        }

        // Dezimalstellen begrenzen (nur bei float)
        if (is_float($number) && isset($options['decimals'])) {
            $number = round($number, $options['decimals']);
        }

        return $number;
    }

    /**
     * Validiert String-Werte
     * 
     * @param string $string String-Wert
     * @param array $options Optionen (min_length, max_length, pattern, allowed_chars)
     * @return string|false Validierter String oder false
     */
    public function validateString($string, $options = [])
    {
        if (!is_string($string)) {
            return false;
        }

        // Grundlegende Sanitisierung
        $string = trim($string);

        // Null-Bytes entfernen
        $string = str_replace("\0", '', $string);

        // Längenprüfung
        $minLength = $options['min_length'] ?? 0;
        $maxLength = $options['max_length'] ?? 1000;

        if (strlen($string) < $minLength || strlen($string) > $maxLength) {
            return false;
        }

        // Pattern-Validierung
        if (isset($options['pattern'])) {
            if (!preg_match($options['pattern'], $string)) {
                return false;
            }
        }

        // Erlaubte Zeichen prüfen
        if (isset($options['allowed_chars'])) {
            $allowedPattern = '/^[' . preg_quote($options['allowed_chars'], '/') . ']+$/';
            if (!preg_match($allowedPattern, $string)) {
                return false;
            }
        }

        // Gefährliche Zeichen entfernen (optional)
        if ($options['strip_dangerous'] ?? true) {
            $string = $this->stripDangerousChars($string);
        }

        // HTML-Entities dekodieren und wieder enkodieren für Konsistenz
        if ($options['normalize_html'] ?? false) {
            $string = htmlspecialchars_decode($string);
            $string = htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return $string;
    }

    /**
     * Validiert Datei-Informationen
     * 
     * @param array $file $_FILES Array-Element
     * @param array $options Optionen (allowed_types, max_size, required)
     * @return array|false Validierte Datei-Info oder false
     */
    public function validateFile($file, $options = [])
    {
        if (!is_array($file)) {
            return false;
        }

        // Erforderliche Felder prüfen
        $requiredFields = ['name', 'type', 'size', 'tmp_name', 'error'];
        foreach ($requiredFields as $field) {
            if (!isset($file[$field])) {
                return false;
            }
        }

        // Upload-Fehler prüfen
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        // Datei erforderlich?
        if (($options['required'] ?? false) && empty($file['name'])) {
            return false;
        }

        // Leere Datei überspringen wenn nicht erforderlich
        if (empty($file['name']) && !($options['required'] ?? false)) {
            return null;
        }

        // Dateigröße prüfen
        $maxSize = $options['max_size'] ?? 5242880; // 5MB Standard
        if ($file['size'] > $maxSize) {
            return false;
        }

        // MIME-Type prüfen
        if (isset($options['allowed_types'])) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $options['allowed_types'])) {
                return false;
            }
        }

        // Dateiname validieren
        $sanitizedName = $this->sanitizeFilename($file['name']);
        if (empty($sanitizedName)) {
            return false;
        }

        return [
            'original_name' => $file['name'],
            'sanitized_name' => $sanitizedName,
            'type' => $file['type'],
            'size' => $file['size'],
            'tmp_name' => $file['tmp_name']
        ];
    }

    /**
     * Validiert URLs
     * 
     * @param string $url URL
     * @param array $options Optionen (schemes, check_dns)
     * @return string|false Validierte URL oder false
     */
    public function validateUrl($url, $options = [])
    {
        if (!is_string($url)) {
            return false;
        }

        $url = trim($url);

        // URL-Format validieren
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Erlaubte Schemas prüfen
        $allowedSchemes = $options['schemes'] ?? ['http', 'https'];
        $scheme = parse_url($url, PHP_URL_SCHEME);
        
        if (!in_array($scheme, $allowedSchemes)) {
            return false;
        }

        // DNS-Prüfung (optional)
        if ($options['check_dns'] ?? false) {
            $host = parse_url($url, PHP_URL_HOST);
            if ($host && !checkdnsrr($host, 'A') && !checkdnsrr($host, 'AAAA')) {
                return false;
            }
        }

        return $url;
    }

    /**
     * Validiert Telefonnummern
     * 
     * @param string $phone Telefonnummer
     * @param array $options Optionen (format, country)
     * @return string|false Validierte Telefonnummer oder false
     */
    public function validatePhone($phone, $options = [])
    {
        if (!is_string($phone)) {
            return false;
        }

        // Nur Zahlen, +, -, (, ), Leerzeichen erlauben
        $phone = preg_replace('/[^0-9+\-() ]/', '', trim($phone));

        // Mindestlänge prüfen
        $digitsOnly = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($digitsOnly) < 7 || strlen($digitsOnly) > 15) {
            return false;
        }

        // Format-spezifische Validierung
        $format = $options['format'] ?? 'international';
        
        switch ($format) {
            case 'german':
                if (!preg_match('/^(\+49|0)[1-9][0-9]{1,11}$/', $digitsOnly)) {
                    return false;
                }
                break;
                
            case 'international':
                if (!preg_match('/^\+?[1-9][0-9]{6,14}$/', $digitsOnly)) {
                    return false;
                }
                break;
        }

        return $phone;
    }

    /**
     * Validiert IP-Adressen
     * 
     * @param string $ip IP-Adresse
     * @param array $options Optionen (version, allow_private)
     * @return string|false Validierte IP oder false
     */
    public function validateIp($ip, $options = [])
    {
        if (!is_string($ip)) {
            return false;
        }

        $ip = trim($ip);
        $version = $options['version'] ?? 'both'; // ipv4, ipv6, both

        $flags = 0;
        
        // Version festlegen
        if ($version === 'ipv4') {
            $flags |= FILTER_FLAG_IPV4;
        } elseif ($version === 'ipv6') {
            $flags |= FILTER_FLAG_IPV6;
        }

        // Private IPs ausschließen
        if (!($options['allow_private'] ?? true)) {
            $flags |= FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
        }

        $validatedIp = filter_var($ip, FILTER_VALIDATE_IP, $flags);
        
        return $validatedIp !== false ? $validatedIp : false;
    }

    /**
     * Validiert JSON-Strings
     * 
     * @param string $json JSON-String
     * @param array $options Optionen (max_depth, required_keys)
     * @return array|false Dekodierte Daten oder false
     */
    public function validateJson($json, $options = [])
    {
        if (!is_string($json)) {
            return false;
        }

        $maxDepth = $options['max_depth'] ?? 512;
        
        $data = json_decode($json, true, $maxDepth);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        // Erforderliche Schlüssel prüfen
        if (isset($options['required_keys']) && is_array($data)) {
            foreach ($options['required_keys'] as $key) {
                if (!array_key_exists($key, $data)) {
                    return false;
                }
            }
        }

        return $data;
    }

    /**
     * Validiert Base64-kodierte Strings
     * 
     * @param string $base64 Base64-String
     * @param array $options Optionen (max_size)
     * @return string|false Dekodierte Daten oder false
     */
    public function validateBase64($base64, $options = [])
    {
        if (!is_string($base64)) {
            return false;
        }

        $base64 = trim($base64);

        // Base64-Format prüfen
        if (!preg_match('/^[a-zA-Z0-9+\/]*={0,2}$/', $base64)) {
            return false;
        }

        $decoded = base64_decode($base64, true);
        
        if ($decoded === false) {
            return false;
        }

        // Größe prüfen
        $maxSize = $options['max_size'] ?? 1048576; // 1MB Standard
        if (strlen($decoded) > $maxSize) {
            return false;
        }

        return $decoded;
    }

    /**
     * Entfernt gefährliche Zeichen aus Strings
     * 
     * @param string $string Input-String
     * @return string Bereinigter String
     */
    private function stripDangerousChars($string)
    {
        // Null-Bytes entfernen
        $string = str_replace("\0", '', $string);
        
        // Kontrollzeichen entfernen (außer Tabs, Zeilenumbrüche)
        $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $string);
        
        return $string;
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
}
