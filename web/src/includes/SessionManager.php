<?php
/**
 * Session-Manager-Klasse
 * Verwaltet sichere Sessions mit Timeout und Brute-Force-Schutz
 */

class SessionManager
{
    private $config;
    private $sessionTimeout;
    private $maxLoginAttempts;
    private $lockoutDuration;

    public function __construct()
    {
        $this->config = Config::get('app');
        $this->sessionTimeout = $this->config['session_timeout'];
        $this->maxLoginAttempts = Config::get('security.max_login_attempts');
        $this->lockoutDuration = Config::get('security.lockout_duration');
        
        $this->configureSession();
    }

    /**
     * Konfiguriert sichere Session-Einstellungen
     */
    private function configureSession()
    {
        // Session-Name setzen
        session_name($this->config['session_name']);
        
        // Sichere Session-Parameter
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', 1);
        ini_set('session.gc_maxlifetime', $this->sessionTimeout);
        
        // Session-Cookie-Lebensdauer
        session_set_cookie_params([
            'lifetime' => $this->sessionTimeout,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }

    /**
     * Startet eine neue Session
     * 
     * @return bool
     */
    public function start()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return true;
        }

        if (!session_start()) {
            return false;
        }

        // Session-Sicherheit prüfen
        $this->validateSession();
        
        return true;
    }

    /**
     * Validiert die aktuelle Session
     */
    private function validateSession()
    {
        // Timeout prüfen
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > $this->sessionTimeout) {
                $this->destroy();
                return;
            }
        }

        // Session-Fingerprint prüfen
        $currentFingerprint = $this->generateFingerprint();
        if (isset($_SESSION['fingerprint'])) {
            if ($_SESSION['fingerprint'] !== $currentFingerprint) {
                $this->destroy();
                return;
            }
        } else {
            $_SESSION['fingerprint'] = $currentFingerprint;
        }

        // Letzte Aktivität aktualisieren
        $_SESSION['last_activity'] = time();
    }

    /**
     * Generiert einen Session-Fingerprint
     * 
     * @return string
     */
    private function generateFingerprint()
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
        
        return hash('sha256', $userAgent . $acceptLanguage . $acceptEncoding);
    }

    /**
     * Meldet einen Benutzer an
     * 
     * @param User $user Benutzer-Objekt
     * @return bool
     */
    public function login(User $user)
    {
        if (!$this->start()) {
            return false;
        }

        // Session regenerieren für Sicherheit
        session_regenerate_id(true);

        // Benutzerinformationen in Session speichern
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['username'] = $user->getUsername();
        $_SESSION['display_name'] = $user->getDisplayName();
        $_SESSION['email'] = $user->getEmail();
        $_SESSION['groups'] = $user->getGroups();
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['fingerprint'] = $this->generateFingerprint();
        $_SESSION['is_authenticated'] = true;

        // Login-Versuche zurücksetzen
        $this->resetLoginAttempts();

        return true;
    }

    /**
     * Meldet den aktuellen Benutzer ab
     * 
     * @return bool
     */
    public function logout()
    {
        if (!$this->start()) {
            return false;
        }

        // Session-Daten löschen
        $_SESSION = [];

        // Session-Cookie löschen
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        // Session zerstören
        session_destroy();

        return true;
    }

    /**
     * Zerstört die aktuelle Session
     */
    public function destroy()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
    }

    /**
     * Prüft ob ein Benutzer angemeldet ist
     * 
     * @return bool
     */
    public function isAuthenticated()
    {
        if (!$this->start()) {
            return false;
        }

        return isset($_SESSION['is_authenticated']) && $_SESSION['is_authenticated'] === true;
    }

    /**
     * Holt die aktuelle Benutzer-ID
     * 
     * @return int|null
     */
    public function getUserId()
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Holt den aktuellen Benutzernamen
     * 
     * @return string|null
     */
    public function getUsername()
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        return $_SESSION['username'] ?? null;
    }

    /**
     * Holt den Display-Namen des aktuellen Benutzers
     * 
     * @return string|null
     */
    public function getDisplayName()
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        return $_SESSION['display_name'] ?? null;
    }

    /**
     * Holt die E-Mail des aktuellen Benutzers
     * 
     * @return string|null
     */
    public function getEmail()
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        return $_SESSION['email'] ?? null;
    }

    /**
     * Holt die Gruppen des aktuellen Benutzers
     * 
     * @return array
     */
    public function getUserGroups()
    {
        if (!$this->isAuthenticated()) {
            return [];
        }

        return $_SESSION['groups'] ?? [];
    }

    /**
     * Prüft ob der Benutzer in einer bestimmten Gruppe ist
     * 
     * @param string $group Gruppenname
     * @return bool
     */
    public function hasGroup($group)
    {
        return in_array($group, $this->getUserGroups());
    }

    /**
     * Registriert einen fehlgeschlagenen Login-Versuch
     * 
     * @param string $username Benutzername
     */
    public function recordFailedLogin($username)
    {
        $key = 'login_attempts_' . hash('sha256', $username . $_SERVER['REMOTE_ADDR']);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'count' => 0,
                'last_attempt' => 0,
                'locked_until' => 0
            ];
        }

        $_SESSION[$key]['count']++;
        $_SESSION[$key]['last_attempt'] = time();

        // Sperrung aktivieren wenn zu viele Versuche
        if ($_SESSION[$key]['count'] >= $this->maxLoginAttempts) {
            $_SESSION[$key]['locked_until'] = time() + $this->lockoutDuration;
        }
    }

    /**
     * Prüft ob ein Benutzer gesperrt ist
     * 
     * @param string $username Benutzername
     * @return bool
     */
    public function isLocked($username)
    {
        $key = 'login_attempts_' . hash('sha256', $username . $_SERVER['REMOTE_ADDR']);
        
        if (!isset($_SESSION[$key])) {
            return false;
        }

        $attempts = $_SESSION[$key];
        
        // Sperrung abgelaufen?
        if ($attempts['locked_until'] > 0 && time() > $attempts['locked_until']) {
            $this->resetLoginAttempts($username);
            return false;
        }

        return $attempts['locked_until'] > time();
    }

    /**
     * Gibt die verbleibende Sperrzeit zurück
     * 
     * @param string $username Benutzername
     * @return int Sekunden bis zur Entsperrung
     */
    public function getLockoutTime($username)
    {
        $key = 'login_attempts_' . hash('sha256', $username . $_SERVER['REMOTE_ADDR']);
        
        if (!isset($_SESSION[$key])) {
            return 0;
        }

        $attempts = $_SESSION[$key];
        
        if ($attempts['locked_until'] <= time()) {
            return 0;
        }

        return $attempts['locked_until'] - time();
    }

    /**
     * Setzt die Login-Versuche zurück
     * 
     * @param string|null $username Benutzername (optional)
     */
    public function resetLoginAttempts($username = null)
    {
        if ($username) {
            $key = 'login_attempts_' . hash('sha256', $username . $_SERVER['REMOTE_ADDR']);
            unset($_SESSION[$key]);
        } else {
            // Alle Login-Versuche zurücksetzen
            foreach ($_SESSION as $key => $value) {
                if (strpos($key, 'login_attempts_') === 0) {
                    unset($_SESSION[$key]);
                }
            }
        }
    }

    /**
     * Generiert ein CSRF-Token
     * 
     * @return string
     */
    public function generateCsrfToken()
    {
        if (!$this->start()) {
            return '';
        }

        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Validiert ein CSRF-Token
     * 
     * @param string $token Token zum Validieren
     * @return bool
     */
    public function validateCsrfToken($token)
    {
        if (!$this->start()) {
            return false;
        }

        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Erneuert das CSRF-Token
     */
    public function regenerateCsrfToken()
    {
        if ($this->start()) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    /**
     * Holt Session-Informationen für Debugging
     * 
     * @return array
     */
    public function getSessionInfo()
    {
        if (!$this->isAuthenticated()) {
            return [];
        }

        return [
            'user_id' => $this->getUserId(),
            'username' => $this->getUsername(),
            'login_time' => $_SESSION['login_time'] ?? null,
            'last_activity' => $_SESSION['last_activity'] ?? null,
            'session_timeout' => $this->sessionTimeout,
            'time_remaining' => $this->sessionTimeout - (time() - ($_SESSION['last_activity'] ?? time()))
        ];
    }
}
