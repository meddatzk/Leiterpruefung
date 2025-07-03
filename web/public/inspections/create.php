<?php
/**
 * Neue Prüfung erstellen
 */

require_once __DIR__ . '/../../src/includes/bootstrap.php';
require_once __DIR__ . '/../../src/includes/auth_middleware.php';
require_once __DIR__ . '/../../src/includes/InspectionRepository.php';
require_once __DIR__ . '/../../src/includes/LadderRepository.php';
require_once __DIR__ . '/../../src/includes/InspectionTemplate.php';

// Authentifizierung prüfen
requireAuth();

// CSRF-Token generieren
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Repositories initialisieren
$inspectionRepo = new InspectionRepository($pdo, $auditLogger);
$ladderRepo = new LadderRepository($pdo, $auditLogger);

$errors = [];
$success = false;
$inspection = null;
$inspectionItems = [];
$ladder = null;

// Leiter-ID aus URL
$ladderId = !empty($_GET['ladder_id']) ? (int)$_GET['ladder_id'] : null;

// POST-Request verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // CSRF-Token prüfen
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
            throw new InvalidArgumentException('Ungültiger CSRF-Token');
        }

        // Prüfungsdaten validieren
        $inspectionData = [
            'ladder_id' => (int)$_POST['ladder_id'],
            'inspector_id' => $_SESSION['user']['id'],
            'inspection_date' => $_POST['inspection_date'],
            'inspection_type' => $_POST['inspection_type'],
            'next_inspection_date' => $_POST['next_inspection_date'],
            'inspection_duration_minutes' => !empty($_POST['inspection_duration_minutes']) ? (int)$_POST['inspection_duration_minutes'] : null,
            'weather_conditions' => $_POST['weather_conditions'] ?? null,
            'temperature_celsius' => !empty($_POST['temperature_celsius']) ? (int)$_POST['temperature_celsius'] : null,
            'general_notes' => $_POST['general_notes'] ?? null,
            'recommendations' => $_POST['recommendations'] ?? null,
            'defects_found' => $_POST['defects_found'] ?? null,
            'actions_required' => $_POST['actions_required'] ?? null,
            'inspector_signature' => $_POST['inspector_signature'] ?? null,
            'overall_result' => 'passed' // Wird automatisch berechnet
        ];

        // Prüfung erstellen
        $inspection = new Inspection($inspectionData);

        // Prüfpunkte verarbeiten
        $inspectionItems = [];
        if (!empty($_POST['inspection_items'])) {
            foreach ($_POST['inspection_items'] as $index => $itemData) {
                if (empty($itemData['item_name'])) continue;

                $item = new InspectionItem([
                    'category' => $itemData['category'],
                    'item_name' => $itemData['item_name'],
                    'description' => $itemData['description'] ?? null,
                    'result' => $itemData['result'],
                    'severity' => $itemData['severity'] ?? null,
                    'notes' => $itemData['notes'] ?? null,
                    'repair_required' => isset($itemData['repair_required']) ? true : false,
                    'repair_deadline' => $itemData['repair_deadline'] ?? null,
                    'sort_order' => $index
                ]);

                $inspectionItems[] = $item;
            }
        }

        if (empty($inspectionItems)) {
            throw new InvalidArgumentException('Mindestens ein Prüfpunkt ist erforderlich');
        }

        // Gesamtergebnis berechnen
        $inspection->setInspectionItems($inspectionItems);
        $overallResult = $inspection->calculateOverallResult();
        $inspection->setOverallResult($overallResult);

        // Prüfung speichern
        $inspectionId = $inspectionRepo->create($inspection, $inspectionItems);
        
        $success = true;
        
        // Weiterleitung zur Detailansicht
        header("Location: view.php?id={$inspectionId}&success=1");
        exit;

    } catch (Exception $e) {
        $errors[] = $e->getMessage();
        error_log("Fehler beim Erstellen der Prüfung: " . $e->getMessage());
    }
}

// Leiter laden (aus URL oder POST)
if ($ladderId || (!empty($_POST['ladder_id']))) {
    $ladderId = $ladderId ?: (int)$_POST['ladder_id'];
    try {
        $ladder = $ladderRepo->findById($ladderId);
        if (!$ladder) {
            $errors[] = 'Leiter nicht gefunden';
        }
    } catch (Exception $e) {
        $errors[] = 'Fehler beim Laden der Leiter: ' . $e->getMessage();
    }
}

// Standard-Prüfpunkte laden wenn keine POST-Daten vorhanden
if (empty($inspectionItems) && $ladder) {
    try {
        $inspectionItems = InspectionTemplate::getItemsByLadderType($ladder->getLadderType());
    } catch (Exception $e) {
        $inspectionItems = InspectionTemplate::getStandardItems();
    }
}

// Verfügbare Leitern für Dropdown
$availableLadders = [];
try {
    $availableLadders = $ladderRepo->search(['status' => 'active'], 100);
} catch (Exception $e) {
    error_log("Fehler beim Laden der Leitern: " . $e->getMessage());
}

// Nächstes Prüfdatum berechnen
$nextInspectionDate = '';
if ($ladder) {
    try {
        $nextInspectionDate = $inspectionRepo->calculateNextDate($ladder->getId());
    } catch (Exception $e) {
        // Fallback: 12 Monate
        $nextInspectionDate = date('Y-m-d', strtotime('+12 months'));
    }
}

// Template-Daten
$templateData = [
    'title' => 'Neue Prüfung',
    'errors' => $errors,
    'success' => $success,
    'inspection' => $inspection,
    'inspectionItems' => $inspectionItems,
    'ladder' => $ladder,
    'availableLadders' => $availableLadders,
    'nextInspectionDate' => $nextInspectionDate,
    'csrf_token' => $_SESSION['csrf_token'],
    'user' => $_SESSION['user'] ?? null,
    'inspectionTypes' => Inspection::INSPECTION_TYPES,
    'overallResults' => Inspection::OVERALL_RESULTS,
    'itemCategories' => InspectionItem::CATEGORIES,
    'itemResults' => InspectionItem::RESULTS,
    'itemSeverities' => InspectionItem::SEVERITIES
];

// Template rendern
$templateEngine->render('inspections/form', $templateData);
