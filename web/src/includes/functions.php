<?php
/**
 * Hilfsfunktionen
 * Sammlung nützlicher Funktionen für die Anwendung
 */

/**
 * HTML-String sicher ausgeben
 * 
 * @param string $string Der zu escapeende String
 * @param int $flags HTML-Flags für htmlspecialchars
 * @param string $encoding Zeichenkodierung
 * @return string
 */
function escape($string, $flags = ENT_QUOTES | ENT_HTML5, $encoding = 'UTF-8')
{
    return htmlspecialchars($string, $flags, $encoding);
}

/**
 * CSRF-Token validieren
 * 
 * @param string $token Das zu validierende Token
 * @return bool
 */
function validateCSRFToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * CSRF-Token aus Session abrufen
 * 
 * @return string
 */
function getCSRFToken()
{
    return $_SESSION['csrf_token'] ?? '';
}

/**
 * CSRF-Token als Hidden-Input-Feld ausgeben
 * 
 * @return string
 */
function csrfTokenField()
{
    $token = getCSRFToken();
    return "<input type=\"hidden\" name=\"csrf_token\" value=\"{$token}\">";
}

/**
 * Sichere Weiterleitung
 * 
 * @param string $url Ziel-URL
 * @param int $statusCode HTTP-Status-Code
 */
function redirect($url, $statusCode = 302)
{
    // Relative URLs zu absoluten URLs konvertieren
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        $baseUrl = Config::get('app.base_url', 'http://localhost');
        $url = rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
    }
    
    // Header setzen und beenden
    header("Location: {$url}", true, $statusCode);
    exit;
}

/**
 * JSON-Response senden
 * 
 * @param mixed $data Die zu sendenden Daten
 * @param int $statusCode HTTP-Status-Code
 * @param array $headers Zusätzliche Header
 */
function jsonResponse($data, $statusCode = 200, $headers = [])
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    
    foreach ($headers as $key => $value) {
        header("{$key}: {$value}");
    }
    
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Fehler-Response senden
 * 
 * @param string $message Fehlermeldung
 * @param int $statusCode HTTP-Status-Code
 * @param array $details Zusätzliche Details
 */
function errorResponse($message, $statusCode = 400, $details = [])
{
    $response = [
        'error' => true,
        'message' => $message,
        'status_code' => $statusCode
    ];
    
    if (!empty($details)) {
        $response['details'] = $details;
    }
    
    jsonResponse($response, $statusCode);
}

/**
 * Erfolg-Response senden
 * 
 * @param mixed $data Die zu sendenden Daten
 * @param string $message Erfolgsmeldung
 * @param int $statusCode HTTP-Status-Code
 */
function successResponse($data = null, $message = 'Success', $statusCode = 200)
{
    $response = [
        'success' => true,
        'message' => $message,
        'status_code' => $statusCode
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    jsonResponse($response, $statusCode);
}

/**
 * Eingabe validieren und bereinigen
 * 
 * @param mixed $input Die zu bereinigende Eingabe
 * @param string $type Typ der Bereinigung (string, email, int, float, bool, url)
 * @return mixed
 */
function sanitizeInput($input, $type = 'string')
{
    if (is_array($input)) {
        return array_map(function($item) use ($type) {
            return sanitizeInput($item, $type);
        }, $input);
    }
    
    switch ($type) {
        case 'email':
            return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
        case 'int':
            return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
        case 'float':
            return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        case 'bool':
            return filter_var($input, FILTER_VALIDATE_BOOLEAN);
        case 'url':
            return filter_var(trim($input), FILTER_SANITIZE_URL);
        case 'string':
        default:
            return trim(strip_tags($input));
    }
}

/**
 * Passwort hashen
 * 
 * @param string $password Das zu hashende Passwort
 * @return string
 */
function hashPassword($password)
{
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Passwort verifizieren
 * 
 * @param string $password Das zu verifizierende Passwort
 * @param string $hash Der gespeicherte Hash
 * @return bool
 */
function verifyPassword($password, $hash)
{
    return password_verify($password, $hash);
}

/**
 * Zufälligen String generieren
 * 
 * @param int $length Länge des Strings
 * @param string $characters Erlaubte Zeichen
 * @return string
 */
function generateRandomString($length = 32, $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
{
    $charactersLength = strlen($characters);
    $randomString = '';
    
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }
    
    return $randomString;
}

/**
 * Dateigröße formatieren
 * 
 * @param int $bytes Anzahl Bytes
 * @param int $precision Dezimalstellen
 * @return string
 */
function formatFileSize($bytes, $precision = 2)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * Datum formatieren
 * 
 * @param string|int $date Datum als String oder Timestamp
 * @param string $format Gewünschtes Format
 * @param string $timezone Zeitzone
 * @return string
 */
function formatDate($date, $format = 'd.m.Y H:i', $timezone = null)
{
    if (is_numeric($date)) {
        $dateTime = new DateTime('@' . $date);
    } else {
        $dateTime = new DateTime($date);
    }
    
    if ($timezone) {
        $dateTime->setTimezone(new DateTimeZone($timezone));
    } else {
        $dateTime->setTimezone(new DateTimeZone(Config::get('app.timezone', 'Europe/Berlin')));
    }
    
    return $dateTime->format($format);
}

/**
 * Array zu CSV konvertieren
 * 
 * @param array $data Array mit Daten
 * @param string $delimiter Trennzeichen
 * @param string $enclosure Anführungszeichen
 * @return string
 */
function arrayToCsv($data, $delimiter = ';', $enclosure = '"')
{
    $output = '';
    $temp = fopen('php://temp', 'r+');
    
    foreach ($data as $row) {
        fputcsv($temp, $row, $delimiter, $enclosure);
    }
    
    rewind($temp);
    while (($line = fgets($temp)) !== false) {
        $output .= $line;
    }
    
    fclose($temp);
    return $output;
}

/**
 * Debug-Ausgabe (nur im Debug-Modus)
 * 
 * @param mixed $data Die auszugebenden Daten
 * @param string $label Optional: Label für die Ausgabe
 * @param bool $die Soll die Ausführung beendet werden?
 */
function debug($data, $label = null, $die = false)
{
    if (!Config::get('app.debug', false)) {
        return;
    }
    
    echo '<div style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; margin: 10px; border-radius: 4px; font-family: monospace;">';
    
    if ($label) {
        echo '<strong>' . escape($label) . ':</strong><br>';
    }
    
    echo '<pre>' . escape(print_r($data, true)) . '</pre>';
    echo '</div>';
    
    if ($die) {
        die();
    }
}

/**
 * Log-Nachricht schreiben
 * 
 * @param string $message Die Log-Nachricht
 * @param string $level Log-Level (info, warning, error, debug)
 * @param array $context Zusätzlicher Kontext
 */
function writeLog($message, $level = 'info', $context = [])
{
    $timestamp = date('Y-m-d H:i:s');
    $contextString = !empty($context) ? ' ' . json_encode($context) : '';
    
    $logMessage = "[{$timestamp}] {$level}: {$message}{$contextString}";
    
    error_log($logMessage);
}

/**
 * Prüft ob Request eine AJAX-Anfrage ist
 * 
 * @return bool
 */
function isAjaxRequest()
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Prüft ob Request eine POST-Anfrage ist
 * 
 * @return bool
 */
function isPostRequest()
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Prüft ob Request eine GET-Anfrage ist
 * 
 * @return bool
 */
function isGetRequest()
{
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

/**
 * Aktuelle URL abrufen
 * 
 * @param bool $withQuery Mit Query-String
 * @return string
 */
function getCurrentUrl($withQuery = true)
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];
    
    if (!$withQuery) {
        $uri = strtok($uri, '?');
    }
    
    return "{$protocol}://{$host}{$uri}";
}

/**
 * Flash-Nachricht setzen
 * 
 * @param string $type Typ der Nachricht (success, error, warning, info)
 * @param string $message Die Nachricht
 */
function setFlashMessage($type, $message)
{
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Flash-Nachrichten abrufen und löschen
 * 
 * @return array
 */
function getFlashMessages()
{
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Prüft ob Flash-Nachrichten vorhanden sind
 * 
 * @return bool
 */
function hasFlashMessages()
{
    return !empty($_SESSION['flash_messages']);
}
