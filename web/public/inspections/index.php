<?php
/**
 * Prüfungs-Übersicht
 */

require_once __DIR__ . '/../../src/includes/bootstrap.php';
require_once __DIR__ . '/../../src/includes/auth_middleware.php';
require_once __DIR__ . '/../../src/includes/InspectionRepository.php';
require_once __DIR__ . '/../../src/includes/LadderRepository.php';

// Authentifizierung prüfen
requireAuth();

// Repositories initialisieren
$inspectionRepo = new InspectionRepository($pdo, $auditLogger);
$ladderRepo = new LadderRepository($pdo, $auditLogger);

// Filter aus GET-Parametern
$filters = [];
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Filter verarbeiten
if (!empty($_GET['ladder_id'])) {
    $filters['ladder_id'] = (int)$_GET['ladder_id'];
}

if (!empty($_GET['inspector_id'])) {
    $filters['inspector_id'] = (int)$_GET['inspector_id'];
}

if (!empty($_GET['inspection_type'])) {
    $filters['inspection_type'] = $_GET['inspection_type'];
}

if (!empty($_GET['overall_result'])) {
    $filters['overall_result'] = $_GET['overall_result'];
}

if (!empty($_GET['date_from'])) {
    $filters['date_from'] = $_GET['date_from'];
}

if (!empty($_GET['date_to'])) {
    $filters['date_to'] = $_GET['date_to'];
}

if (!empty($_GET['ladder_number'])) {
    $filters['ladder_number'] = $_GET['ladder_number'];
}

try {
    // Prüfungen laden
    $inspections = $inspectionRepo->getHistory($filters, $limit, $offset);
    $totalCount = $inspectionRepo->count($filters);
    $totalPages = ceil($totalCount / $limit);
    
    // Statistiken laden
    $statistics = $inspectionRepo->getStatistics($filters);
    
    // Anstehende Prüfungen
    $upcomingInspections = $inspectionRepo->getUpcoming(30);
    
    // Template-Daten
    $templateData = [
        'title' => 'Prüfungsübersicht',
        'inspections' => $inspections,
        'statistics' => $statistics,
        'upcomingInspections' => $upcomingInspections,
        'filters' => $filters,
        'pagination' => [
            'current' => $page,
            'total' => $totalPages,
            'count' => $totalCount,
            'limit' => $limit
        ],
        'user' => $_SESSION['user'] ?? null
    ];

    // Template rendern
    $templateEngine->render('inspections/list', $templateData);

} catch (Exception $e) {
    error_log("Fehler in inspections/index.php: " . $e->getMessage());
    
    $templateEngine->render('error', [
        'title' => 'Fehler',
        'message' => 'Fehler beim Laden der Prüfungen: ' . $e->getMessage(),
        'user' => $_SESSION['user'] ?? null
    ]);
}
