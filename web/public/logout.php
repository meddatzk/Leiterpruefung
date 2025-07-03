<?php
/**
 * Logout-Funktionalität
 * Meldet den Benutzer ab und zerstört die Session
 */

require_once '../src/includes/bootstrap.php';

$sessionManager = new SessionManager();

// Prüfen ob Benutzer angemeldet ist
if (!$sessionManager->isAuthenticated()) {
    header('Location: login.php');
    exit;
}

$message = '';
$messageType = 'info';

// Logout-Verarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    // CSRF-Token validieren
    if (!$sessionManager->validateCsrfToken($csrfToken)) {
        $message = 'Ungültiger Sicherheitstoken.';
        $messageType = 'error';
    } else {
        // Benutzerinformationen für Abschiedsmeldung speichern
        $displayName = $sessionManager->getDisplayName();
        
        // Session beenden
        if ($sessionManager->logout()) {
            // Erfolgreiche Abmeldung - Weiterleitung zur Login-Seite mit Nachricht
            session_start();
            $_SESSION['logout_message'] = "Auf Wiedersehen, {$displayName}! Sie wurden erfolgreich abgemeldet.";
            $_SESSION['logout_message_type'] = 'success';
            
            header('Location: login.php');
            exit;
        } else {
            $message = 'Fehler beim Abmelden. Bitte versuchen Sie es erneut.';
            $messageType = 'error';
        }
    }
}

// GET-Request oder Fehler beim Logout - Bestätigungsseite anzeigen
$csrfToken = $sessionManager->generateCsrfToken();
$displayName = $sessionManager->getDisplayName();
$username = $sessionManager->getUsername();

// Template-Daten vorbereiten
$templateData = [
    'title' => 'Abmelden',
    'page_class' => 'logout-page',
    'show_navigation' => true,
    'show_sidebar' => false,
    'content' => [
        'message' => $message,
        'message_type' => $messageType,
        'display_name' => htmlspecialchars($displayName),
        'username' => htmlspecialchars($username),
        'csrf_token' => $csrfToken,
        'session_info' => $sessionManager->getSessionInfo()
    ]
];

// Template rendern
$template = new TemplateEngine();
echo $template->render('logout', $templateData);
