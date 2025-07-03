<?php
/**
 * Login-Seite
 * Verwaltet die Benutzeranmeldung über LDAP
 */

require_once '../src/includes/bootstrap.php';

$sessionManager = new SessionManager();
$ldapAuth = new LdapAuth();

// Bereits angemeldet? Weiterleitung zur Startseite
if ($sessionManager->isAuthenticated()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';
$username = '';

// Login-Verarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    // CSRF-Token validieren
    if (!$sessionManager->validateCsrfToken($csrfToken)) {
        $error = 'Ungültiger Sicherheitstoken. Bitte versuchen Sie es erneut.';
    }
    // Eingaben validieren
    elseif (empty($username) || empty($password)) {
        $error = 'Bitte geben Sie Benutzername und Passwort ein.';
    }
    // Brute-Force-Schutz prüfen
    elseif ($sessionManager->isLocked($username)) {
        $lockoutTime = $sessionManager->getLockoutTime($username);
        $minutes = ceil($lockoutTime / 60);
        $error = "Zu viele fehlgeschlagene Anmeldeversuche. Konto ist für {$minutes} Minute(n) gesperrt.";
    }
    else {
        // LDAP-Verfügbarkeit prüfen
        if (!$ldapAuth->isAvailable()) {
            $error = 'Anmeldedienst ist derzeit nicht verfügbar: ' . $ldapAuth->getLastError();
        }
        else {
            // Authentifizierung versuchen
            $ldapUserData = $ldapAuth->authenticate($username, $password);
            
            if ($ldapUserData) {
                // Benutzer in lokaler Datenbank erstellen/aktualisieren
                $user = User::createOrUpdateFromLdap($ldapUserData);
                
                if ($user) {
                    // Login-Zeit aktualisieren
                    $user->updateLastLogin();
                    
                    // Session starten
                    if ($sessionManager->login($user)) {
                        // CSRF-Token erneuern
                        $sessionManager->regenerateCsrfToken();
                        
                        // Weiterleitung zur gewünschten Seite oder Startseite
                        $redirectUrl = $_GET['redirect'] ?? 'index.php';
                        
                        // Sicherheitscheck für Redirect-URL
                        if (!filter_var($redirectUrl, FILTER_VALIDATE_URL) && !preg_match('/^[a-zA-Z0-9_\-\/\.]+\.php(\?.*)?$/', $redirectUrl)) {
                            $redirectUrl = 'index.php';
                        }
                        
                        header('Location: ' . $redirectUrl);
                        exit;
                    } else {
                        $error = 'Fehler beim Starten der Sitzung.';
                    }
                } else {
                    $error = 'Fehler beim Laden der Benutzerdaten.';
                }
            } else {
                // Fehlgeschlagenen Login-Versuch registrieren
                $sessionManager->recordFailedLogin($username);
                $error = 'Ungültige Anmeldedaten: ' . $ldapAuth->getLastError();
            }
        }
    }
    
    // CSRF-Token nach Verarbeitung erneuern
    $sessionManager->regenerateCsrfToken();
}

// CSRF-Token generieren
$csrfToken = $sessionManager->generateCsrfToken();

// Template-Daten vorbereiten
$templateData = [
    'title' => 'Anmeldung',
    'page_class' => 'login-page',
    'show_navigation' => false,
    'show_sidebar' => false,
    'content' => [
        'error' => $error,
        'success' => $success,
        'username' => htmlspecialchars($username),
        'csrf_token' => $csrfToken,
        'redirect_url' => htmlspecialchars($_GET['redirect'] ?? ''),
        'ldap_available' => $ldapAuth->isAvailable()
    ]
];

// Template rendern
$template = new TemplateEngine();
echo $template->render('login', $templateData);
