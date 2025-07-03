<?php
/**
 * Authentifizierung-Middleware
 * Prüft die Benutzerauthentifizierung und Berechtigung
 */

class AuthMiddleware
{
    private $sessionManager;
    private $excludedPaths;
    private $publicPaths;

    public function __construct()
    {
        $this->sessionManager = new SessionManager();
        
        // Pfade die von der Authentifizierung ausgeschlossen sind
        $this->excludedPaths = [
            '/login.php',
            '/logout.php'
        ];
        
        // Öffentliche Pfade (keine Authentifizierung erforderlich)
        $this->publicPaths = [
            '/login.php'
        ];
    }

    /**
     * Prüft die Authentifizierung für die aktuelle Anfrage
     * 
     * @param array $options Optionen für die Middleware
     * @return bool|void True wenn authentifiziert, sonst Weiterleitung
     */
    public function authenticate($options = [])
    {
        $currentPath = $this->getCurrentPath();
        
        // Öffentliche Pfade überspringen
        if ($this->isPublicPath($currentPath)) {
            return true;
        }
        
        // Session starten
        if (!$this->sessionManager->start()) {
            $this->redirectToLogin('Session konnte nicht gestartet werden');
            return false;
        }
        
        // Authentifizierung prüfen
        if (!$this->sessionManager->isAuthenticated()) {
            $this->redirectToLogin('Anmeldung erforderlich');
            return false;
        }
        
        // Gruppenberechtigungen prüfen falls angegeben
        if (isset($options['required_groups']) && !empty($options['required_groups'])) {
            if (!$this->checkGroupPermissions($options['required_groups'], $options['require_all_groups'] ?? false)) {
                $this->accessDenied('Unzureichende Berechtigung');
                return false;
            }
        }
        
        // Benutzer-ID prüfen falls angegeben
        if (isset($options['required_user_id'])) {
            if ($this->sessionManager->getUserId() !== $options['required_user_id']) {
                $this->accessDenied('Zugriff nur für den Benutzer erlaubt');
                return false;
            }
        }
        
        return true;
    }

    /**
     * Prüft Gruppenberechtigungen
     * 
     * @param array $requiredGroups Erforderliche Gruppen
     * @param bool $requireAll Alle Gruppen erforderlich (true) oder eine beliebige (false)
     * @return bool
     */
    private function checkGroupPermissions($requiredGroups, $requireAll = false)
    {
        $userGroups = $this->sessionManager->getUserGroups();
        
        if (empty($userGroups)) {
            return false;
        }
        
        if ($requireAll) {
            // Alle Gruppen müssen vorhanden sein
            return empty(array_diff($requiredGroups, $userGroups));
        } else {
            // Mindestens eine Gruppe muss vorhanden sein
            return !empty(array_intersect($requiredGroups, $userGroups));
        }
    }

    /**
     * Holt den aktuellen Pfad
     * 
     * @return string
     */
    private function getCurrentPath()
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        return basename($path);
    }

    /**
     * Prüft ob der Pfad öffentlich ist
     * 
     * @param string $path Pfad
     * @return bool
     */
    private function isPublicPath($path)
    {
        return in_array('/' . $path, $this->publicPaths);
    }

    /**
     * Leitet zur Login-Seite weiter
     * 
     * @param string $message Fehlermeldung
     */
    private function redirectToLogin($message = '')
    {
        $currentUrl = $_SERVER['REQUEST_URI'];
        $loginUrl = 'login.php';
        
        // Aktuelle URL als Redirect-Parameter anhängen (außer bei Login/Logout)
        if (!in_array('/' . basename($currentUrl), $this->excludedPaths)) {
            $loginUrl .= '?redirect=' . urlencode($currentUrl);
        }
        
        // Fehlermeldung in Session speichern
        if (!empty($message)) {
            session_start();
            $_SESSION['auth_error'] = $message;
        }
        
        header('Location: ' . $loginUrl);
        exit;
    }

    /**
     * Zeigt eine Zugriff-verweigert-Seite
     * 
     * @param string $message Fehlermeldung
     */
    private function accessDenied($message = 'Zugriff verweigert')
    {
        http_response_code(403);
        
        // Template-Daten vorbereiten
        $templateData = [
            'title' => 'Zugriff verweigert',
            'page_class' => 'error-page access-denied',
            'show_navigation' => true,
            'show_sidebar' => false,
            'content' => [
                'error_code' => 403,
                'error_title' => 'Zugriff verweigert',
                'error_message' => $message,
                'user_info' => [
                    'username' => $this->sessionManager->getUsername(),
                    'display_name' => $this->sessionManager->getDisplayName(),
                    'groups' => $this->sessionManager->getUserGroups()
                ]
            ]
        ];
        
        // Template rendern
        $template = new TemplateEngine();
        echo $template->render('error', $templateData);
        exit;
    }

    /**
     * Prüft ob der aktuelle Benutzer eine bestimmte Gruppe hat
     * 
     * @param string $group Gruppenname
     * @return bool
     */
    public function hasGroup($group)
    {
        if (!$this->sessionManager->isAuthenticated()) {
            return false;
        }
        
        return $this->sessionManager->hasGroup($group);
    }

    /**
     * Prüft ob der aktuelle Benutzer eine der angegebenen Gruppen hat
     * 
     * @param array $groups Gruppennamen
     * @return bool
     */
    public function hasAnyGroup($groups)
    {
        if (!$this->sessionManager->isAuthenticated()) {
            return false;
        }
        
        $userGroups = $this->sessionManager->getUserGroups();
        return !empty(array_intersect($groups, $userGroups));
    }

    /**
     * Prüft ob der aktuelle Benutzer alle angegebenen Gruppen hat
     * 
     * @param array $groups Gruppennamen
     * @return bool
     */
    public function hasAllGroups($groups)
    {
        if (!$this->sessionManager->isAuthenticated()) {
            return false;
        }
        
        $userGroups = $this->sessionManager->getUserGroups();
        return empty(array_diff($groups, $userGroups));
    }

    /**
     * Holt Informationen über den aktuellen Benutzer
     * 
     * @return array|null
     */
    public function getCurrentUser()
    {
        if (!$this->sessionManager->isAuthenticated()) {
            return null;
        }
        
        return [
            'id' => $this->sessionManager->getUserId(),
            'username' => $this->sessionManager->getUsername(),
            'display_name' => $this->sessionManager->getDisplayName(),
            'email' => $this->sessionManager->getEmail(),
            'groups' => $this->sessionManager->getUserGroups(),
            'session_info' => $this->sessionManager->getSessionInfo()
        ];
    }

    /**
     * Prüft ob der aktuelle Benutzer authentifiziert ist
     * 
     * @return bool
     */
    public function isAuthenticated()
    {
        return $this->sessionManager->isAuthenticated();
    }

    /**
     * Holt die Session-Manager-Instanz
     * 
     * @return SessionManager
     */
    public function getSessionManager()
    {
        return $this->sessionManager;
    }
}

/**
 * Globale Hilfsfunktionen für die Authentifizierung
 */

/**
 * Prüft die Authentifizierung mit optionalen Anforderungen
 * 
 * @param array $options Optionen (required_groups, require_all_groups, required_user_id)
 * @return bool
 */
function requireAuth($options = [])
{
    static $authMiddleware = null;
    
    if ($authMiddleware === null) {
        $authMiddleware = new AuthMiddleware();
    }
    
    return $authMiddleware->authenticate($options);
}

/**
 * Prüft ob der aktuelle Benutzer eine bestimmte Gruppe hat
 * 
 * @param string $group Gruppenname
 * @return bool
 */
function hasGroup($group)
{
    static $authMiddleware = null;
    
    if ($authMiddleware === null) {
        $authMiddleware = new AuthMiddleware();
    }
    
    return $authMiddleware->hasGroup($group);
}

/**
 * Prüft ob der aktuelle Benutzer eine der angegebenen Gruppen hat
 * 
 * @param array $groups Gruppennamen
 * @return bool
 */
function hasAnyGroup($groups)
{
    static $authMiddleware = null;
    
    if ($authMiddleware === null) {
        $authMiddleware = new AuthMiddleware();
    }
    
    return $authMiddleware->hasAnyGroup($groups);
}

/**
 * Holt Informationen über den aktuellen Benutzer
 * 
 * @return array|null
 */
function getCurrentUser()
{
    static $authMiddleware = null;
    
    if ($authMiddleware === null) {
        $authMiddleware = new AuthMiddleware();
    }
    
    return $authMiddleware->getCurrentUser();
}

/**
 * Prüft ob der aktuelle Benutzer authentifiziert ist
 * 
 * @return bool
 */
function isAuthenticated()
{
    static $authMiddleware = null;
    
    if ($authMiddleware === null) {
        $authMiddleware = new AuthMiddleware();
    }
    
    return $authMiddleware->isAuthenticated();
}
