<?php
/**
 * Dashboard - Hauptübersicht des Leiterprüfungssystems
 */

require_once __DIR__ . '/../src/includes/bootstrap.php';
require_once __DIR__ . '/../src/includes/auth_middleware.php';
require_once __DIR__ . '/../src/includes/DashboardController.php';

// Authentifizierung prüfen
requireAuth();

// Datenbankverbindung
$pdo = getDBConnection();
$auditLogger = new AuditLogger($pdo);

// Dashboard-Controller initialisieren
$dashboardController = new DashboardController($pdo, $auditLogger);

// Template-Engine initialisieren
$templateEngine = new TemplateEngine();

// Aktuelle Benutzer-ID
$currentUserId = $_SESSION['user']['id'] ?? null;

try {
    // Dashboard-Daten laden
    $dashboardData = $dashboardController->getOverviewData();
    
    // Benutzer-spezifische Daten laden
    $userOverview = null;
    if ($currentUserId) {
        $userOverview = $dashboardController->getUserOverview($currentUserId);
    }
    
    // Zusätzliche Statistiken
    $inspectionTypeStats = $dashboardController->getInspectionTypeStatistics();
    $ladderTypeStats = $dashboardController->getLadderTypeStatistics();
    $locationStats = $dashboardController->getLocationStatistics();
    
    // Template-Variablen setzen
    $templateEngine->assign('pageTitle', 'Dashboard');
    $templateEngine->assign('currentPage', 'dashboard');
    $templateEngine->assign('dashboardData', $dashboardData);
    $templateEngine->assign('userOverview', $userOverview);
    $templateEngine->assign('inspectionTypeStats', $inspectionTypeStats);
    $templateEngine->assign('ladderTypeStats', $ladderTypeStats);
    $templateEngine->assign('locationStats', $locationStats);
    $templateEngine->assign('currentUser', $_SESSION['user'] ?? null);
    
    // AJAX-Request für Live-Updates
    if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
        header('Content-Type: application/json');
        
        $response = [
            'success' => true,
            'data' => [
                'statistics' => $dashboardData['statistics'],
                'upcoming_inspections' => $dashboardData['upcoming_inspections'],
                'overdue_inspections' => $dashboardData['overdue_inspections'],
                'today_inspections' => $dashboardData['today_inspections'],
                'recent_activity' => $dashboardData['recent_activity']
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        echo json_encode($response);
        exit;
    }
    
    // Template rendern
    $templateEngine->render('dashboard/overview.php');
    
} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    
    if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Fehler beim Laden der Dashboard-Daten'
        ]);
        exit;
    }
    
    $templateEngine->assign('pageTitle', 'Dashboard - Fehler');
    $templateEngine->assign('currentPage', 'dashboard');
    $templateEngine->assign('error', 'Fehler beim Laden der Dashboard-Daten: ' . $e->getMessage());
    $templateEngine->render('error.php');
}
