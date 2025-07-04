<?php
/**
 * CSRF-Schutz-Klasse
 * Generiert und validiert CSRF-Tokens für Formulare und AJAX-Requests
 */

class CsrfProtection
{
    private $config;
    private $sessionManager;
    private $tokenName;
    private $tokenLifetime;

    public function __construct()
    {
        $this->config = Config::get('security');
        $this->sessionManager = new SessionManager();
        $this->tokenName = 'csrf_token';
        $this->tokenLifetime = 3600; // 1 Stunde
    }

    /**
     * Generiert ein neues CSRF-Token
     * 
     * @param string $action Spezifische Aktion (optional)
     * @return string CSRF-Token
     */
    public function generateToken($action = 'default')
    {
        if (!$this->sessionManager->start()) {
            throw new Exception('Session konnte nicht gestartet werden');
        }

        // Token generieren
        $token = bin2hex(random_bytes(32));
        $timestamp = time();
        
        // Token-Daten erstellen
        $tokenData = [
            'token' => $token,
            'timestamp' => $timestamp,
            'action' => $action,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];

        // Token in Session speichern
        if (!isset($_SESSION['csrf_tokens'])) {
            $_SESSION['csrf_tokens'] = [];
        }

        $_SESSION['csrf_tokens'][$action] = $tokenData;

        // Alte Tokens aufräumen
        $this->cleanupExpiredTokens();

        return $token;
    }

    /**
     * Validiert ein CSRF-Token
     * 
     * @param string $token Token zum Validieren
     * @param string $action Spezifische Aktion (optional)
     * @return bool True wenn gültig
     */
    public function validateToken($token, $action = 'default')
    {
        if (!$this->sessionManager->start()) {
            return false;
        }

        // Token-Daten aus Session holen
        if (!isset($_SESSION['csrf_tokens'][$action])) {
            return false;
        }

        $tokenData = $_SESSION['csrf_tokens'][$action];

        // Token-Gültigkeit prüfen
        if (!$this->isTokenValid($token, $tokenData)) {
            // Ungültiges Token aus Session entfernen
            unset($_SESSION['csrf_tokens'][$action]);
            return false;
        }

        // Token nach erfolgreicher Validierung entfernen (One-Time-Use)
        unset($_SESSION['csrf_tokens'][$action]);

        return true;
    }

    /**
     * Bettet ein CSRF-Token in ein Formular ein
     * 
     * @param string $action Spezifische Aktion (optional)
     * @return string HTML-Input-Feld
     */
    public function embedToken($action = 'default')
    {
        $token = $this->generateToken($action);
        
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            htmlspecialchars($this->tokenName, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($token, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Bettet ein CSRF-Token als Meta-Tag ein (für AJAX)
     * 
     * @param string $action Spezifische Aktion (optional)
     * @return string HTML-Meta-Tag
     */
    public function embedMetaToken($action = 'default')
    {
        $token = $this->generateToken($action);
        
        return sprintf(
            '<meta name="csrf-token" content="%s">',
            htmlspecialchars($token, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Holt ein Token für JavaScript/AJAX-Verwendung
     * 
     * @param string $action Spezifische Aktion (optional)
     * @return array Token-Informationen
     */
    public function getTokenForAjax($action = 'default')
    {
        $token = $this->generateToken($action);
        
        return [
            'token' => $token,
            'name' => $this->tokenName,
            'action' => $action
        ];
    }

    /**
     * Validiert CSRF-Token aus Request
     * 
     * @param string $action Spezifische Aktion (optional)
     * @param string $method Request-Methode (POST, GET, etc.)
     * @return bool True wenn gültig
     */
    public function validateRequest($action = 'default', $method = 'POST')
    {
        $token = null;

        // Token aus verschiedenen Quellen holen
        switch (strtoupper($method)) {
            case 'POST':
                $token = $_POST[$this->tokenName] ?? null;
                break;
            case 'GET':
                $token = $_GET[$this->tokenName] ?? null;
                break;
            default:
                // Für AJAX/API-Requests aus Header holen
                $token = $this->getTokenFromHeaders();
        }

        if (!$token) {
            return false;
        }

        return $this->validateToken($token, $action);
    }

    /**
     * Prüft ob ein Token gültig ist
     * 
     * @param string $token Token zum Prüfen
     * @param array $tokenData Gespeicherte Token-Daten
     * @return bool True wenn gültig
     */
    private function isTokenValid($token, $tokenData)
    {
        // Token-Format prüfen
        if (!is_string($token) || strlen($token) !== 64) {
            return false;
        }

        // Token vergleichen (timing-safe)
        if (!hash_equals($tokenData['token'], $token)) {
            return false;
        }

        // Zeitstempel prüfen
        if (time() - $tokenData['timestamp'] > $this->tokenLifetime) {
            return false;
        }

        // IP-Adresse prüfen (optional, kann bei Proxies problematisch sein)
        $checkIp = Config::get('security.csrf_check_ip', false);
        if ($checkIp && $tokenData['ip'] !== ($_SERVER['REMOTE_ADDR'] ?? '')) {
            return false;
        }

        // User-Agent prüfen (optional)
        $checkUserAgent = Config::get('security.csrf_check_user_agent', true);
        if ($checkUserAgent && $tokenData['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
            return false;
        }

        return true;
    }

    /**
     * Holt Token aus HTTP-Headern
     * 
     * @return string|null Token oder null
     */
    private function getTokenFromHeaders()
    {
        // X-CSRF-Token Header
        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            return $_SERVER['HTTP_X_CSRF_TOKEN'];
        }

        // X-Requested-With für AJAX
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            // Token aus Authorization Header
            if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $auth = $_SERVER['HTTP_AUTHORIZATION'];
                if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
                    return $matches[1];
                }
            }
        }

        return null;
    }

    /**
     * Räumt abgelaufene Tokens auf
     */
    private function cleanupExpiredTokens()
    {
        if (!isset($_SESSION['csrf_tokens'])) {
            return;
        }

        $currentTime = time();
        
        foreach ($_SESSION['csrf_tokens'] as $action => $tokenData) {
            if ($currentTime - $tokenData['timestamp'] > $this->tokenLifetime) {
                unset($_SESSION['csrf_tokens'][$action]);
            }
        }
    }

    /**
     * Erstellt ein Double-Submit-Cookie-Token
     * 
     * @param string $action Spezifische Aktion
     * @return string Cookie-Token
     */
    public function generateCookieToken($action = 'default')
    {
        $token = bin2hex(random_bytes(32));
        $cookieName = 'csrf_' . hash('sha256', $action);
        
        // Sicheres Cookie setzen
        setcookie(
            $cookieName,
            $token,
            [
                'expires' => time() + $this->tokenLifetime,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => false, // Muss für JavaScript zugänglich sein
                'samesite' => 'Strict'
            ]
        );

        return $token;
    }

    /**
     * Validiert Double-Submit-Cookie-Token
     * 
     * @param string $token Token aus Formular/AJAX
     * @param string $action Spezifische Aktion
     * @return bool True wenn gültig
     */
    public function validateCookieToken($token, $action = 'default')
    {
        $cookieName = 'csrf_' . hash('sha256', $action);
        
        if (!isset($_COOKIE[$cookieName])) {
            return false;
        }

        $cookieToken = $_COOKIE[$cookieName];
        
        // Tokens vergleichen (timing-safe)
        return hash_equals($cookieToken, $token);
    }

    /**
     * Generiert JavaScript-Code für AJAX-CSRF-Schutz
     * 
     * @param string $action Spezifische Aktion
     * @return string JavaScript-Code
     */
    public function generateAjaxScript($action = 'default')
    {
        $tokenData = $this->getTokenForAjax($action);
        
        return sprintf(
            '<script>
                window.csrfToken = "%s";
                window.csrfTokenName = "%s";
                
                // jQuery AJAX Setup
                if (typeof $ !== "undefined") {
                    $.ajaxSetup({
                        beforeSend: function(xhr, settings) {
                            if (!/^(GET|HEAD|OPTIONS|TRACE)$/i.test(settings.type) && !this.crossDomain) {
                                xhr.setRequestHeader("X-CSRF-Token", window.csrfToken);
                            }
                        }
                    });
                }
                
                // Fetch API Helper
                window.fetchWithCSRF = function(url, options = {}) {
                    options.headers = options.headers || {};
                    if (!/^(GET|HEAD|OPTIONS|TRACE)$/i.test(options.method || "GET")) {
                        options.headers["X-CSRF-Token"] = window.csrfToken;
                    }
                    return fetch(url, options);
                };
            </script>',
            htmlspecialchars($tokenData['token'], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($tokenData['name'], ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Middleware-Funktion für CSRF-Schutz
     * 
     * @param string $action Spezifische Aktion
     * @param array $excludeMethods Ausgeschlossene HTTP-Methoden
     * @return bool True wenn Request erlaubt
     */
    public function middleware($action = 'default', $excludeMethods = ['GET', 'HEAD', 'OPTIONS'])
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // Bestimmte Methoden ausschließen
        if (in_array(strtoupper($method), $excludeMethods)) {
            return true;
        }

        // CSRF-Token validieren
        if (!$this->validateRequest($action, $method)) {
            // 403 Forbidden senden
            http_response_code(403);
            
            // JSON-Response für AJAX-Requests
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'error' => 'CSRF token validation failed',
                    'code' => 'CSRF_TOKEN_INVALID'
                ]);
            } else {
                // HTML-Fehlerseite für normale Requests
                echo '<!DOCTYPE html>
                <html>
                <head>
                    <title>Forbidden</title>
                </head>
                <body>
                    <h1>403 Forbidden</h1>
                    <p>CSRF token validation failed. Please refresh the page and try again.</p>
                </body>
                </html>';
            }
            
            exit;
        }

        return true;
    }

    /**
     * Prüft ob Request ein AJAX-Request ist
     * 
     * @return bool True wenn AJAX
     */
    private function isAjaxRequest()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Setzt die Token-Lebensdauer
     * 
     * @param int $lifetime Lebensdauer in Sekunden
     */
    public function setTokenLifetime($lifetime)
    {
        $this->tokenLifetime = max(300, (int)$lifetime); // Minimum 5 Minuten
    }

    /**
     * Holt die aktuelle Token-Lebensdauer
     * 
     * @return int Lebensdauer in Sekunden
     */
    public function getTokenLifetime()
    {
        return $this->tokenLifetime;
    }

    /**
     * Setzt den Token-Namen
     * 
     * @param string $name Token-Name
     */
    public function setTokenName($name)
    {
        $this->tokenName = preg_replace('/[^a-zA-Z0-9_]/', '_', $name);
    }

    /**
     * Holt den aktuellen Token-Namen
     * 
     * @return string Token-Name
     */
    public function getTokenName()
    {
        return $this->tokenName;
    }

    /**
     * Löscht alle CSRF-Tokens aus der Session
     */
    public function clearAllTokens()
    {
        if ($this->sessionManager->start()) {
            unset($_SESSION['csrf_tokens']);
        }
    }

    /**
     * Holt Statistiken über aktive Tokens
     * 
     * @return array Token-Statistiken
     */
    public function getTokenStats()
    {
        if (!$this->sessionManager->start() || !isset($_SESSION['csrf_tokens'])) {
            return [
                'total' => 0,
                'expired' => 0,
                'active' => 0
            ];
        }

        $total = count($_SESSION['csrf_tokens']);
        $expired = 0;
        $currentTime = time();

        foreach ($_SESSION['csrf_tokens'] as $tokenData) {
            if ($currentTime - $tokenData['timestamp'] > $this->tokenLifetime) {
                $expired++;
            }
        }

        return [
            'total' => $total,
            'expired' => $expired,
            'active' => $total - $expired
        ];
    }
}
