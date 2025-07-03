<?php
/**
 * Berichts-Übersicht - Hauptseite für das Berichtswesen
 */

require_once __DIR__ . '/../../src/includes/bootstrap.php';
require_once __DIR__ . '/../../src/includes/auth_middleware.php';
require_once __DIR__ . '/../../src/includes/ReportGenerator.php';

// Authentifizierung prüfen
requireAuth();

// Seitentitel und Navigation
$pageTitle = 'Berichte';
$currentPage = 'reports';

// Template Engine initialisieren
$templateEngine = new TemplateEngine(__DIR__ . '/../../templates');

// Verfügbare Berichte definieren
$availableReports = [
    'inspection_report' => [
        'title' => 'Prüfungsprotokoll',
        'description' => 'Detailliertes Protokoll einer Leiterprüfung mit allen Prüfpunkten und Ergebnissen',
        'icon' => 'fas fa-clipboard-check',
        'formats' => ['pdf'],
        'requires_selection' => true,
        'selection_type' => 'ladder'
    ],
    'overview_report' => [
        'title' => 'Leitern-Übersicht',
        'description' => 'Komplette Übersicht aller Leitern mit Status und Prüfterminen',
        'icon' => 'fas fa-list-alt',
        'formats' => ['excel', 'pdf'],
        'requires_selection' => false
    ],
    'statistics_report' => [
        'title' => 'Prüfungsstatistiken',
        'description' => 'Statistische Auswertung der Prüfungen mit Trends und Kennzahlen',
        'icon' => 'fas fa-chart-bar',
        'formats' => ['excel', 'pdf'],
        'requires_selection' => false
    ],
    'calendar_report' => [
        'title' => 'Prüfkalender',
        'description' => 'Übersicht anstehender Prüfungen nach Zeitraum und Standort',
        'icon' => 'fas fa-calendar-alt',
        'formats' => ['pdf'],
        'requires_selection' => false
    ],
    'failure_report' => [
        'title' => 'Ausfallbericht',
        'description' => 'Analyse von Ausfällen, Mängeln und deren Ursachen',
        'icon' => 'fas fa-exclamation-triangle',
        'formats' => ['pdf'],
        'requires_selection' => false
    ]
];

// Kürzlich generierte Berichte laden (aus Session oder Datenbank)
$recentReports = $_SESSION['recent_reports'] ?? [];

// Statistiken für Dashboard
try {
    $pdo = getDbConnection();
    $ladderRepo = new LadderRepository($pdo, new AuditLogger($pdo));
    $inspectionRepo = new InspectionRepository($pdo, new AuditLogger($pdo));
    
    $ladderStats = $ladderRepo->getStatistics();
    $inspectionStats = $inspectionRepo->getStatistics();
    $upcomingInspections = $inspectionRepo->getUpcoming(30);
    
} catch (Exception $e) {
    error_log("Fehler beim Laden der Berichts-Statistiken: " . $e->getMessage());
    $ladderStats = [];
    $inspectionStats = [];
    $upcomingInspections = [];
}

// Template-Variablen
$templateVars = [
    'pageTitle' => $pageTitle,
    'currentPage' => $currentPage,
    'availableReports' => $availableReports,
    'recentReports' => $recentReports,
    'ladderStats' => $ladderStats,
    'inspectionStats' => $inspectionStats,
    'upcomingInspections' => $upcomingInspections,
    'user' => $_SESSION['user'] ?? null
];

// Template rendern
echo $templateEngine->render('reports/list', $templateVars);
