<?php
/**
 * Leiter-Detailansicht
 * Zeigt alle Details einer Leiter an
 */

require_once __DIR__ . '/../../src/includes/bootstrap.php';

// Authentifizierung prüfen
requireAuth();

try {
    // Parameter prüfen
    $ladderId = (int)($_GET['id'] ?? 0);
    if ($ladderId <= 0) {
        throw new InvalidArgumentException('Ungültige Leiter-ID');
    }
    
    // Datenbankverbindung
    $pdo = getDatabaseConnection();
    $auditLogger = new AuditLogger($pdo);
    $ladderRepository = new LadderRepository($pdo, $auditLogger);
    
    // Leiter laden
    $ladder = $ladderRepository->findById($ladderId);
    if (!$ladder) {
        throw new Exception('Leiter nicht gefunden');
    }
    
    // Prüfstatus berechnen
    $daysUntilInspection = $ladder->getDaysUntilInspection();
    $needsInspection = $ladder->needsInspection();
    
    // Status-Informationen
    $statusInfo = [
        'class' => match($ladder->getStatus()) {
            'active' => 'success',
            'inactive' => 'secondary',
            'defective' => 'danger',
            'disposed' => 'dark',
            default => 'secondary'
        },
        'icon' => match($ladder->getStatus()) {
            'active' => 'check-circle',
            'inactive' => 'pause-circle',
            'defective' => 'x-circle',
            'disposed' => 'trash',
            default => 'circle'
        },
        'text' => ucfirst($ladder->getStatus())
    ];
    
    // Prüfstatus-Informationen
    $inspectionInfo = [
        'class' => $needsInspection ? 'danger' : ($daysUntilInspection <= 30 ? 'warning' : 'success'),
        'icon' => $needsInspection ? 'exclamation-triangle' : ($daysUntilInspection <= 30 ? 'clock' : 'check-circle'),
        'text' => $needsInspection ? 'Überfällig' : ($daysUntilInspection <= 30 ? 'Bald fällig' : 'Aktuell'),
        'days' => $daysUntilInspection
    ];
    
    // Template-Daten vorbereiten
    $templateData = [
        'title' => 'Leiter ' . $ladder->getLadderNumber(),
        'page_title' => $ladder->getLadderNumber(),
        'page_subtitle' => $ladder->getDescription(),
        'breadcrumb' => [
            ['title' => 'Dashboard', 'url' => '../index.php'],
            ['title' => 'Leitern', 'url' => 'index.php'],
            ['title' => $ladder->getLadderNumber(), 'url' => '']
        ],
        'page_actions' => '
            <div class="btn-group" role="group">
                <a href="edit.php?id=' . $ladder->getId() . '" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Bearbeiten
                </a>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="bi bi-three-dots"></i> Mehr
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="print.php?id=' . $ladder->getId() . '" target="_blank">
                            <i class="bi bi-printer"></i> Drucken
                        </a></li>
                        <li><a class="dropdown-item" href="qr.php?id=' . $ladder->getId() . '" target="_blank">
                            <i class="bi bi-qr-code"></i> QR-Code
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="duplicate.php?id=' . $ladder->getId() . '">
                            <i class="bi bi-files"></i> Duplizieren
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="#" onclick="confirmDelete(' . $ladder->getId() . ', \'' . addslashes($ladder->getLadderNumber()) . '\')">
                            <i class="bi bi-trash"></i> Löschen
                        </a></li>
                    </ul>
                </div>
            </div>
        ',
        'ladder' => $ladder,
        'status_info' => $statusInfo,
        'inspection_info' => $inspectionInfo,
        'needs_inspection' => $needsInspection,
        'days_until_inspection' => $daysUntilInspection
    ];
    
    // Template rendern
    $template = new TemplateEngine();
    $template->startSection('content');
    $template->partial('ladders/detail', $templateData);
    $template->endSection();
    
    $template->startSection('scripts');
    ?>
    <script>
        // Lösch-Bestätigung
        function confirmDelete(id, name) {
            if (confirm('Möchten Sie die Leiter "' + name + '" wirklich löschen?\n\nDiese Aktion kann nicht rückgängig gemacht werden.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'delete.php';
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = '<?= $_SESSION['csrf_token'] ?>';
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = id;
                
                form.appendChild(csrfInput);
                form.appendChild(idInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Tabs aktivieren
        document.addEventListener('DOMContentLoaded', function() {
            const triggerTabList = [].slice.call(document.querySelectorAll('#detailTabs button'));
            triggerTabList.forEach(function (triggerEl) {
                const tabTrigger = new bootstrap.Tab(triggerEl);
                
                triggerEl.addEventListener('click', function (event) {
                    event.preventDefault();
                    tabTrigger.show();
                });
            });
        });
    </script>
    <?php
    $template->endSection();
    
    echo $template->render('base', $templateData);
    
} catch (InvalidArgumentException $e) {
    // 404 für ungültige Parameter
    http_response_code(404);
    $template = new TemplateEngine();
    echo $template->render('error', [
        'title' => 'Leiter nicht gefunden',
        'error_title' => 'Leiter nicht gefunden',
        'error_message' => 'Die angeforderte Leiter konnte nicht gefunden werden.',
        'error_code' => 404
    ]);
    
} catch (Exception $e) {
    error_log('Fehler in ladders/view.php: ' . $e->getMessage());
    
    $template = new TemplateEngine();
    echo $template->render('error', [
        'title' => 'Fehler',
        'error_title' => 'Fehler beim Laden der Leiter',
        'error_message' => 'Die Leiter-Details konnten nicht geladen werden. Bitte versuchen Sie es später erneut.',
        'error_code' => 500
    ]);
}
?>
