<?php
/**
 * Prüfungs-Details anzeigen
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

$inspection = null;
$ladder = null;
$errors = [];
$success = !empty($_GET['success']);

// Prüfungs-ID aus URL
$inspectionId = !empty($_GET['id']) ? (int)$_GET['id'] : null;

if (!$inspectionId) {
    $errors[] = 'Prüfungs-ID ist erforderlich';
} else {
    try {
        // Prüfung laden
        $inspection = $inspectionRepo->findById($inspectionId);
        
        if (!$inspection) {
            $errors[] = 'Prüfung nicht gefunden';
        } else {
            // Zugehörige Leiter laden
            $ladder = $ladderRepo->findById($inspection->getLadderId());
            
            if (!$ladder) {
                $errors[] = 'Zugehörige Leiter nicht gefunden';
            }
        }
        
    } catch (Exception $e) {
        $errors[] = 'Fehler beim Laden der Prüfung: ' . $e->getMessage();
        error_log("Fehler in inspections/view.php: " . $e->getMessage());
    }
}

// Weitere Prüfungen dieser Leiter laden (für Historie)
$ladderInspections = [];
if ($ladder) {
    try {
        $ladderInspections = $inspectionRepo->findByLadder($ladder->getId(), 10);
        // Aktuelle Prüfung aus der Liste entfernen
        $ladderInspections = array_filter($ladderInspections, function($insp) use ($inspectionId) {
            return $insp->getId() !== $inspectionId;
        });
    } catch (Exception $e) {
        error_log("Fehler beim Laden der Leiter-Prüfungen: " . $e->getMessage());
    }
}

// Mängel-Statistiken für diese Prüfung
$defectStats = [];
if ($inspection) {
    $allDefects = $inspection->getAllDefects();
    $criticalDefects = $inspection->getCriticalDefects();
    
    $defectStats = [
        'total_items' => count($inspection->getInspectionItems()),
        'total_defects' => count($allDefects),
        'critical_defects' => count($criticalDefects),
        'defect_rate' => count($inspection->getInspectionItems()) > 0 
            ? round((count($allDefects) / count($inspection->getInspectionItems())) * 100, 1) 
            : 0
    ];
    
    // Defekte nach Kategorien gruppieren
    $defectsByCategory = [];
    foreach ($allDefects as $defect) {
        $category = $defect->getCategory();
        if (!isset($defectsByCategory[$category])) {
            $defectsByCategory[$category] = [];
        }
        $defectsByCategory[$category][] = $defect;
    }
    $defectStats['by_category'] = $defectsByCategory;
}

// Template-Daten
$templateData = [
    'title' => $inspection ? 'Prüfung #' . $inspection->getId() : 'Prüfung nicht gefunden',
    'inspection' => $inspection,
    'ladder' => $ladder,
    'ladderInspections' => $ladderInspections,
    'defectStats' => $defectStats,
    'errors' => $errors,
    'success' => $success,
    'user' => $_SESSION['user'] ?? null
];

// Template rendern
if (!empty($errors)) {
    $templateEngine->render('error', [
        'title' => 'Fehler',
        'message' => implode('<br>', $errors),
        'user' => $_SESSION['user'] ?? null
    ]);
} else {
    $templateEngine->render('inspections/detail', $templateData);
}
