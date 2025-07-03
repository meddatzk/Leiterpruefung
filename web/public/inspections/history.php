<?php
/**
 * Prüfungshistorie - Detaillierte Historie mit erweiterten Filtern
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
$limit = (int)($_GET['limit'] ?? 50);
$limit = min(max($limit, 10), 100); // Zwischen 10 und 100
$offset = ($page - 1) * $limit;

// Erweiterte Filter verarbeiten
if (!empty($_GET['ladder_id'])) {
    $filters['ladder_id'] = (int)$_GET['ladder_id'];
}

if (!empty($_GET['ladder_number'])) {
    $filters['ladder_number'] = trim($_GET['ladder_number']);
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

// Zeitraum-Shortcuts
if (!empty($_GET['period'])) {
    $today = new DateTime();
    switch ($_GET['period']) {
        case 'last_week':
            $filters['date_from'] = $today->modify('-1 week')->format('Y-m-d');
            $filters['date_to'] = date('Y-m-d');
            break;
        case 'last_month':
            $filters['date_from'] = $today->modify('-1 month')->format('Y-m-d');
            $filters['date_to'] = date('Y-m-d');
            break;
        case 'last_quarter':
            $filters['date_from'] = $today->modify('-3 months')->format('Y-m-d');
            $filters['date_to'] = date('Y-m-d');
            break;
        case 'last_year':
            $filters['date_from'] = $today->modify('-1 year')->format('Y-m-d');
            $filters['date_to'] = date('Y-m-d');
            break;
        case 'this_year':
            $filters['date_from'] = date('Y-01-01');
            $filters['date_to'] = date('Y-12-31');
            break;
    }
}

try {
    // Prüfungen laden
    $inspections = $inspectionRepo->getHistory($filters, $limit, $offset);
    $totalCount = $inspectionRepo->count($filters);
    $totalPages = ceil($totalCount / $limit);
    
    // Erweiterte Statistiken laden
    $statistics = $inspectionRepo->getStatistics($filters);
    $defectStatistics = $inspectionRepo->getDefectStatistics($filters);
    
    // Verfügbare Leitern für Filter-Dropdown
    $availableLadders = $ladderRepo->search(['status' => ['active', 'inactive']], 200);
    
    // Verfügbare Prüfer für Filter-Dropdown
    $availableInspectors = [];
    try {
        $sql = "SELECT DISTINCT u.id, u.display_name, u.username 
                FROM users u 
                INNER JOIN inspections i ON u.id = i.inspector_id 
                ORDER BY u.display_name";
        $stmt = $pdo->query($sql);
        $availableInspectors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Fehler beim Laden der Prüfer: " . $e->getMessage());
    }
    
    // Monatsweise Statistiken für Chart
    $monthlyStats = [];
    if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
        try {
            $sql = "SELECT 
                        DATE_FORMAT(inspection_date, '%Y-%m') as month,
                        COUNT(*) as total_inspections,
                        SUM(CASE WHEN overall_result = 'passed' THEN 1 ELSE 0 END) as passed,
                        SUM(CASE WHEN overall_result = 'failed' THEN 1 ELSE 0 END) as failed,
                        SUM(CASE WHEN overall_result = 'conditional' THEN 1 ELSE 0 END) as conditional
                    FROM inspections 
                    WHERE inspection_date BETWEEN :date_from AND :date_to";
            
            $params = [
                ':date_from' => $filters['date_from'],
                ':date_to' => $filters['date_to']
            ];
            
            if (!empty($filters['ladder_id'])) {
                $sql .= " AND ladder_id = :ladder_id";
                $params[':ladder_id'] = $filters['ladder_id'];
            }
            
            $sql .= " GROUP BY DATE_FORMAT(inspection_date, '%Y-%m') ORDER BY month";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $monthlyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Fehler beim Laden der monatlichen Statistiken: " . $e->getMessage());
        }
    }
    
    // Template-Daten
    $templateData = [
        'title' => 'Prüfungshistorie',
        'inspections' => $inspections,
        'statistics' => $statistics,
        'defectStatistics' => $defectStatistics,
        'monthlyStats' => $monthlyStats,
        'availableLadders' => $availableLadders,
        'availableInspectors' => $availableInspectors,
        'filters' => $filters,
        'pagination' => [
            'current' => $page,
            'total' => $totalPages,
            'count' => $totalCount,
            'limit' => $limit
        ],
        'user' => $_SESSION['user'] ?? null,
        'inspectionTypes' => Inspection::INSPECTION_TYPES,
        'overallResults' => Inspection::OVERALL_RESULTS,
        'periodOptions' => [
            'last_week' => 'Letzte Woche',
            'last_month' => 'Letzter Monat',
            'last_quarter' => 'Letztes Quartal',
            'last_year' => 'Letztes Jahr',
            'this_year' => 'Dieses Jahr'
        ]
    ];

    // Template rendern
    $templateEngine->render('inspections/history', $templateData);

} catch (Exception $e) {
    error_log("Fehler in inspections/history.php: " . $e->getMessage());
    
    $templateEngine->render('error', [
        'title' => 'Fehler',
        'message' => 'Fehler beim Laden der Prüfungshistorie: ' . $e->getMessage(),
        'user' => $_SESSION['user'] ?? null
    ]);
}
