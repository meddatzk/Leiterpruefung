<?php
/**
 * Neue Leiter erstellen
 * Formular zum Hinzufügen einer neuen Leiter
 */

require_once __DIR__ . '/../../src/includes/bootstrap.php';

// Authentifizierung prüfen
requireAuth();

$errors = [];
$formData = [];
$success = false;

try {
    // Datenbankverbindung
    $pdo = getDatabaseConnection();
    $auditLogger = new AuditLogger($pdo);
    $ladderRepository = new LadderRepository($pdo, $auditLogger);
    
    // POST-Request verarbeiten
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // CSRF-Token prüfen
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Ungültiges CSRF-Token');
        }
        
        // Formulardaten sammeln
        $formData = [
            'ladder_number' => trim($_POST['ladder_number'] ?? ''),
            'manufacturer' => trim($_POST['manufacturer'] ?? ''),
            'model' => trim($_POST['model'] ?? '') ?: null,
            'ladder_type' => $_POST['ladder_type'] ?? '',
            'material' => $_POST['material'] ?? 'Aluminium',
            'max_load_kg' => (int)($_POST['max_load_kg'] ?? 150),
            'height_cm' => (int)($_POST['height_cm'] ?? 0),
            'purchase_date' => trim($_POST['purchase_date'] ?? '') ?: null,
            'location' => trim($_POST['location'] ?? ''),
            'department' => trim($_POST['department'] ?? '') ?: null,
            'responsible_person' => trim($_POST['responsible_person'] ?? '') ?: null,
            'serial_number' => trim($_POST['serial_number'] ?? '') ?: null,
            'notes' => trim($_POST['notes'] ?? '') ?: null,
            'status' => $_POST['status'] ?? 'active',
            'next_inspection_date' => trim($_POST['next_inspection_date'] ?? ''),
            'inspection_interval_months' => (int)($_POST['inspection_interval_months'] ?? 12)
        ];
        
        // Automatische Leiternummer generieren falls leer
        if (empty($formData['ladder_number'])) {
            $formData['ladder_number'] = $ladderRepository->generateUniqueNumber();
        }
        
        // Nächstes Prüfdatum automatisch berechnen falls leer
        if (empty($formData['next_inspection_date'])) {
            $baseDate = $formData['purchase_date'] ? new DateTime($formData['purchase_date']) : new DateTime();
            $nextDate = clone $baseDate;
            $nextDate->add(new DateInterval('P' . $formData['inspection_interval_months'] . 'M'));
            $formData['next_inspection_date'] = $nextDate->format('Y-m-d');
        }
        
        try {
            // Leiter-Objekt erstellen
            $ladder = new Ladder($formData);
            
            // Validierung
            $validationErrors = $ladder->validate();
            if (!empty($validationErrors)) {
                $errors = $validationErrors;
            } else {
                // Leiter speichern
                $ladderId = $ladderRepository->create($ladder);
                
                // Flash-Message setzen
                $_SESSION['flash_messages']['success'][] = 'Leiter "' . $ladder->getLadderNumber() . '" wurde erfolgreich erstellt.';
                
                // Weiterleitung zur Detailansicht
                header('Location: view.php?id=' . $ladderId);
                exit;
            }
            
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    } else {
        // Standardwerte für neues Formular
        $formData = [
            'ladder_number' => '', // Wird automatisch generiert
            'manufacturer' => '',
            'model' => '',
            'ladder_type' => '',
            'material' => 'Aluminium',
            'max_load_kg' => 150,
            'height_cm' => '',
            'purchase_date' => '',
            'location' => '',
            'department' => '',
            'responsible_person' => '',
            'serial_number' => '',
            'notes' => '',
            'status' => 'active',
            'next_inspection_date' => '',
            'inspection_interval_months' => 12
        ];
    }
    
    // Template-Daten vorbereiten
    $templateData = [
        'title' => 'Neue Leiter erstellen',
        'page_title' => 'Neue Leiter erstellen',
        'page_subtitle' => 'Fügen Sie eine neue Leiter zum System hinzu',
        'breadcrumb' => [
            ['title' => 'Dashboard', 'url' => '../index.php'],
            ['title' => 'Leitern', 'url' => 'index.php'],
            ['title' => 'Neue Leiter', 'url' => '']
        ],
        'form_data' => $formData,
        'errors' => $errors,
        'ladder_types' => Ladder::LADDER_TYPES,
        'materials' => Ladder::MATERIALS,
        'statuses' => Ladder::STATUSES,
        'is_edit' => false
    ];
    
    // Template rendern
    $template = new TemplateEngine();
    $template->startSection('content');
    $template->partial('ladders/form', $templateData);
    $template->endSection();
    
    $template->startSection('scripts');
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Automatische Leiternummer generieren
            const ladderNumberInput = document.getElementById('ladder_number');
            const generateNumberBtn = document.getElementById('generateNumber');
            
            if (generateNumberBtn) {
                generateNumberBtn.addEventListener('click', function() {
                    fetch('generate_number.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            csrf_token: '<?= $_SESSION['csrf_token'] ?>'
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            ladderNumberInput.value = data.number;
                        } else {
                            alert('Fehler beim Generieren der Leiternummer: ' + data.error);
                        }
                    })
                    .catch(error => {
                        console.error('Fehler:', error);
                        alert('Fehler beim Generieren der Leiternummer');
                    });
                });
            }
            
            // Automatisches Prüfdatum berechnen
            const purchaseDateInput = document.getElementById('purchase_date');
            const intervalInput = document.getElementById('inspection_interval_months');
            const nextInspectionInput = document.getElementById('next_inspection_date');
            
            function calculateNextInspection() {
                const purchaseDate = purchaseDateInput.value;
                const interval = parseInt(intervalInput.value) || 12;
                
                if (purchaseDate) {
                    const date = new Date(purchaseDate);
                    date.setMonth(date.getMonth() + interval);
                    nextInspectionInput.value = date.toISOString().split('T')[0];
                }
            }
            
            purchaseDateInput.addEventListener('change', calculateNextInspection);
            intervalInput.addEventListener('change', calculateNextInspection);
            
            // Formular-Validierung
            const form = document.getElementById('ladderForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const requiredFields = ['manufacturer', 'ladder_type', 'height_cm', 'location', 'next_inspection_date'];
                    let hasErrors = false;
                    
                    requiredFields.forEach(fieldName => {
                        const field = document.getElementById(fieldName);
                        if (field && !field.value.trim()) {
                            field.classList.add('is-invalid');
                            hasErrors = true;
                        } else if (field) {
                            field.classList.remove('is-invalid');
                        }
                    });
                    
                    // Numerische Validierung
                    const heightField = document.getElementById('height_cm');
                    const maxLoadField = document.getElementById('max_load_kg');
                    
                    if (heightField && (parseInt(heightField.value) <= 0 || isNaN(parseInt(heightField.value)))) {
                        heightField.classList.add('is-invalid');
                        hasErrors = true;
                    }
                    
                    if (maxLoadField && (parseInt(maxLoadField.value) <= 0 || isNaN(parseInt(maxLoadField.value)))) {
                        maxLoadField.classList.add('is-invalid');
                        hasErrors = true;
                    }
                    
                    if (hasErrors) {
                        e.preventDefault();
                        alert('Bitte füllen Sie alle Pflichtfelder korrekt aus.');
                    }
                });
            }
        });
    </script>
    <?php
    $template->endSection();
    
    echo $template->render('base', $templateData);
    
} catch (Exception $e) {
    error_log('Fehler in ladders/create.php: ' . $e->getMessage());
    
    $template = new TemplateEngine();
    echo $template->render('error', [
        'title' => 'Fehler',
        'error_title' => 'Fehler beim Erstellen der Leiter',
        'error_message' => 'Die Leiter konnte nicht erstellt werden. Bitte versuchen Sie es später erneut.',
        'error_code' => 500
    ]);
}
?>
