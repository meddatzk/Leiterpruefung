<?php
/**
 * Erweiterte Leiter-Suche
 * Bietet umfangreiche Suchfunktionen für Leitern
 */

require_once __DIR__ . '/../../src/includes/bootstrap.php';

// Authentifizierung prüfen
requireAuth();

try {
    // Datenbankverbindung
    $pdo = getDatabaseConnection();
    $auditLogger = new AuditLogger($pdo);
    $ladderRepository = new LadderRepository($pdo, $auditLogger);
    
    $searchResults = [];
    $searchPerformed = false;
    $totalResults = 0;
    
    // Suchparameter
    $searchParams = [
        'ladder_number' => trim($_GET['ladder_number'] ?? ''),
        'manufacturer' => trim($_GET['manufacturer'] ?? ''),
        'model' => trim($_GET['model'] ?? ''),
        'ladder_type' => $_GET['ladder_type'] ?? '',
        'material' => $_GET['material'] ?? '',
        'location' => trim($_GET['location'] ?? ''),
        'department' => trim($_GET['department'] ?? ''),
        'responsible_person' => trim($_GET['responsible_person'] ?? ''),
        'serial_number' => trim($_GET['serial_number'] ?? ''),
        'status' => $_GET['status'] ?? '',
        'min_height' => (int)($_GET['min_height'] ?? 0),
        'max_height' => (int)($_GET['max_height'] ?? 0),
        'min_load' => (int)($_GET['min_load'] ?? 0),
        'max_load' => (int)($_GET['max_load'] ?? 0),
        'purchase_date_from' => trim($_GET['purchase_date_from'] ?? ''),
        'purchase_date_to' => trim($_GET['purchase_date_to'] ?? ''),
        'inspection_date_from' => trim($_GET['inspection_date_from'] ?? ''),
        'inspection_date_to' => trim($_GET['inspection_date_to'] ?? ''),
        'needs_inspection' => !empty($_GET['needs_inspection']),
        'inspection_overdue' => !empty($_GET['inspection_overdue']),
        'inspection_due_days' => (int)($_GET['inspection_due_days'] ?? 0),
        'notes' => trim($_GET['notes'] ?? ''),
        'created_from' => trim($_GET['created_from'] ?? ''),
        'created_to' => trim($_GET['created_to'] ?? '')
    ];
    
    // Suche durchführen wenn Parameter vorhanden
    if (!empty(array_filter($searchParams, function($value) { 
        return !empty($value) || $value === true; 
    }))) {
        $searchPerformed = true;
        
        // Filter für Repository aufbauen
        $filters = [];
        
        if (!empty($searchParams['ladder_number'])) {
            $filters['ladder_number'] = $searchParams['ladder_number'];
        }
        
        if (!empty($searchParams['manufacturer'])) {
            $filters['manufacturer'] = $searchParams['manufacturer'];
        }
        
        if (!empty($searchParams['model'])) {
            $filters['model'] = $searchParams['model'];
        }
        
        if (!empty($searchParams['ladder_type'])) {
            $filters['ladder_type'] = $searchParams['ladder_type'];
        }
        
        if (!empty($searchParams['material'])) {
            $filters['material'] = $searchParams['material'];
        }
        
        if (!empty($searchParams['location'])) {
            $filters['location'] = $searchParams['location'];
        }
        
        if (!empty($searchParams['department'])) {
            $filters['department'] = $searchParams['department'];
        }
        
        if (!empty($searchParams['status'])) {
            $filters['status'] = $searchParams['status'];
        }
        
        if ($searchParams['needs_inspection']) {
            $filters['needs_inspection'] = true;
        }
        
        if ($searchParams['inspection_due_days'] > 0) {
            $filters['inspection_due_days'] = $searchParams['inspection_due_days'];
        }
        
        // Erweiterte Suche mit SQL
        $searchResults = $ladderRepository->search($filters, 100, 0);
        $totalResults = count($searchResults);
        
        // Weitere Filter anwenden (die nicht im Repository implementiert sind)
        if (!empty($searchParams['responsible_person']) || 
            !empty($searchParams['serial_number']) ||
            !empty($searchParams['notes']) ||
            $searchParams['min_height'] > 0 ||
            $searchParams['max_height'] > 0 ||
            $searchParams['min_load'] > 0 ||
            $searchParams['max_load'] > 0 ||
            !empty($searchParams['purchase_date_from']) ||
            !empty($searchParams['purchase_date_to']) ||
            !empty($searchParams['inspection_date_from']) ||
            !empty($searchParams['inspection_date_to']) ||
            $searchParams['inspection_overdue']) {
            
            $searchResults = array_filter($searchResults, function($ladder) use ($searchParams) {
                // Verantwortliche Person
                if (!empty($searchParams['responsible_person'])) {
                    if (stripos($ladder->getResponsiblePerson() ?? '', $searchParams['responsible_person']) === false) {
                        return false;
                    }
                }
                
                // Seriennummer
                if (!empty($searchParams['serial_number'])) {
                    if (stripos($ladder->getSerialNumber() ?? '', $searchParams['serial_number']) === false) {
                        return false;
                    }
                }
                
                // Notizen
                if (!empty($searchParams['notes'])) {
                    if (stripos($ladder->getNotes() ?? '', $searchParams['notes']) === false) {
                        return false;
                    }
                }
                
                // Höhe
                if ($searchParams['min_height'] > 0 && $ladder->getHeightCm() < $searchParams['min_height']) {
                    return false;
                }
                if ($searchParams['max_height'] > 0 && $ladder->getHeightCm() > $searchParams['max_height']) {
                    return false;
                }
                
                // Belastung
                if ($searchParams['min_load'] > 0 && $ladder->getMaxLoadKg() < $searchParams['min_load']) {
                    return false;
                }
                if ($searchParams['max_load'] > 0 && $ladder->getMaxLoadKg() > $searchParams['max_load']) {
                    return false;
                }
                
                // Kaufdatum
                if (!empty($searchParams['purchase_date_from']) && $ladder->getPurchaseDate()) {
                    if ($ladder->getPurchaseDate() < $searchParams['purchase_date_from']) {
                        return false;
                    }
                }
                if (!empty($searchParams['purchase_date_to']) && $ladder->getPurchaseDate()) {
                    if ($ladder->getPurchaseDate() > $searchParams['purchase_date_to']) {
                        return false;
                    }
                }
                
                // Prüfdatum
                if (!empty($searchParams['inspection_date_from'])) {
                    if ($ladder->getNextInspectionDate() < $searchParams['inspection_date_from']) {
                        return false;
                    }
                }
                if (!empty($searchParams['inspection_date_to'])) {
                    if ($ladder->getNextInspectionDate() > $searchParams['inspection_date_to']) {
                        return false;
                    }
                }
                
                // Überfällige Prüfung
                if ($searchParams['inspection_overdue'] && !$ladder->needsInspection()) {
                    return false;
                }
                
                return true;
            });
            
            $totalResults = count($searchResults);
        }
    }
    
    // Template-Daten vorbereiten
    $templateData = [
        'title' => 'Leiter-Suche',
        'page_title' => 'Erweiterte Suche',
        'page_subtitle' => 'Finden Sie Leitern anhand verschiedener Kriterien',
        'breadcrumb' => [
            ['title' => 'Dashboard', 'url' => '../index.php'],
            ['title' => 'Leitern', 'url' => 'index.php'],
            ['title' => 'Suche', 'url' => '']
        ],
        'search_params' => $searchParams,
        'search_results' => $searchResults,
        'search_performed' => $searchPerformed,
        'total_results' => $totalResults,
        'ladder_types' => Ladder::LADDER_TYPES,
        'materials' => Ladder::MATERIALS,
        'statuses' => Ladder::STATUSES
    ];
    
    // Template rendern
    $template = new TemplateEngine();
    $template->startSection('content');
    ?>
    
    <!-- Suchformular -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="bi bi-search"></i> Suchkriterien
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" id="searchForm">
                <div class="row">
                    <!-- Grunddaten -->
                    <div class="col-md-6">
                        <h6 class="fw-bold mb-3">Grunddaten</h6>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="ladder_number" class="form-label">Leiternummer</label>
                                    <input type="text" class="form-control" id="ladder_number" name="ladder_number" 
                                           value="<?= $this->e($search_params['ladder_number']) ?>" 
                                           placeholder="z.B. L-2024-0001">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="serial_number" class="form-label">Seriennummer</label>
                                    <input type="text" class="form-control" id="serial_number" name="serial_number" 
                                           value="<?= $this->e($search_params['serial_number']) ?>" 
                                           placeholder="Herstellerseriennummer">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="manufacturer" class="form-label">Hersteller</label>
                                    <input type="text" class="form-control" id="manufacturer" name="manufacturer" 
                                           value="<?= $this->e($search_params['manufacturer']) ?>" 
                                           placeholder="z.B. Hailo, Zarges">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="model" class="form-label">Modell</label>
                                    <input type="text" class="form-control" id="model" name="model" 
                                           value="<?= $this->e($search_params['model']) ?>" 
                                           placeholder="Modellbezeichnung">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="ladder_type" class="form-label">Leitertyp</label>
                                    <select class="form-select" id="ladder_type" name="ladder_type">
                                        <option value="">Alle Typen</option>
                                        <?php foreach ($ladder_types as $type): ?>
                                        <option value="<?= $this->e($type) ?>" <?= $search_params['ladder_type'] === $type ? 'selected' : '' ?>>
                                            <?= $this->e($type) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="material" class="form-label">Material</label>
                                    <select class="form-select" id="material" name="material">
                                        <option value="">Alle Materialien</option>
                                        <?php foreach ($materials as $material): ?>
                                        <option value="<?= $this->e($material) ?>" <?= $search_params['material'] === $material ? 'selected' : '' ?>>
                                            <?= $this->e($material) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">Alle Status</option>
                                <?php foreach ($statuses as $status): ?>
                                <option value="<?= $this->e($status) ?>" <?= $search_params['status'] === $status ? 'selected' : '' ?>>
                                    <?= $this->e(ucfirst($status)) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Standort und Technische Daten -->
                    <div class="col-md-6">
                        <h6 class="fw-bold mb-3">Standort und Technische Daten</h6>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="location" class="form-label">Standort</label>
                                    <input type="text" class="form-control" id="location" name="location" 
                                           value="<?= $this->e($search_params['location']) ?>" 
                                           placeholder="z.B. Werkstatt, Lager">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="department" class="form-label">Abteilung</label>
                                    <input type="text" class="form-control" id="department" name="department" 
                                           value="<?= $this->e($search_params['department']) ?>" 
                                           placeholder="z.B. Instandhaltung">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="responsible_person" class="form-label">Verantwortliche Person</label>
                            <input type="text" class="form-control" id="responsible_person" name="responsible_person" 
                                   value="<?= $this->e($search_params['responsible_person']) ?>" 
                                   placeholder="Name der verantwortlichen Person">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Höhe (cm)</label>
                                    <div class="row">
                                        <div class="col-6">
                                            <input type="number" class="form-control" name="min_height" 
                                                   value="<?= $search_params['min_height'] ?: '' ?>" 
                                                   placeholder="Von" min="0">
                                        </div>
                                        <div class="col-6">
                                            <input type="number" class="form-control" name="max_height" 
                                                   value="<?= $search_params['max_height'] ?: '' ?>" 
                                                   placeholder="Bis" min="0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Belastung (kg)</label>
                                    <div class="row">
                                        <div class="col-6">
                                            <input type="number" class="form-control" name="min_load" 
                                                   value="<?= $search_params['min_load'] ?: '' ?>" 
                                                   placeholder="Von" min="0">
                                        </div>
                                        <div class="col-6">
                                            <input type="number" class="form-control" name="max_load" 
                                                   value="<?= $search_params['max_load'] ?: '' ?>" 
                                                   placeholder="Bis" min="0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Erweiterte Optionen -->
                <div class="row">
                    <div class="col-12">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3">Erweiterte Optionen</h6>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Datumsfilter</h6>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Kaufdatum</label>
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <input type="date" class="form-control" name="purchase_date_from" 
                                                                   value="<?= $this->e($search_params['purchase_date_from']) ?>">
                                                        </div>
                                                        <div class="col-6">
                                                            <input type="date" class="form-control" name="purchase_date_to" 
                                                                   value="<?= $this->e($search_params['purchase_date_to']) ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Prüfdatum</label>
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <input type="date" class="form-control" name="inspection_date_from" 
                                                                   value="<?= $this->e($search_params['inspection_date_from']) ?>">
                                                        </div>
                                                        <div class="col-6">
                                                            <input type="date" class="form-control" name="inspection_date_to" 
                                                                   value="<?= $this->e($search_params['inspection_date_to']) ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <h6>Prüfstatus</h6>
                                        
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="needs_inspection" 
                                                       name="needs_inspection" value="1" 
                                                       <?= $search_params['needs_inspection'] ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="needs_inspection">
                                                    Prüfung erforderlich
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="inspection_overdue" 
                                                       name="inspection_overdue" value="1" 
                                                       <?= $search_params['inspection_overdue'] ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="inspection_overdue">
                                                    Prüfung überfällig
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="inspection_due_days" class="form-label">Prüfung fällig in (Tagen)</label>
                                            <input type="number" class="form-control" id="inspection_due_days" 
                                                   name="inspection_due_days" 
                                                   value="<?= $search_params['inspection_due_days'] ?: '' ?>" 
                                                   placeholder="z.B. 30" min="0">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notizen durchsuchen</label>
                                    <input type="text" class="form-control" id="notes" name="notes" 
                                           value="<?= $this->e($search_params['notes']) ?>" 
                                           placeholder="Suchbegriff in Notizen">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Aktionsbuttons -->
                <div class="row mt-3">
                    <div class="col-md-6">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Suchen
                        </button>
                        <a href="search.php" class="btn btn-outline-secondary ms-2">
                            <i class="bi bi-x-circle"></i> Zurücksetzen
                        </a>
                    </div>
                    <div class="col-md-6 text-end">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Zurück zur Übersicht
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Suchergebnisse -->
    <?php if ($search_performed): ?>
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="bi bi-list-ul"></i> 
                Suchergebnisse (<?= number_format($total_results) ?>)
            </h5>
        </div>
        
        <?php if (empty($search_results)): ?>
        <div class="card-body text-center py-5">
            <i class="bi bi-search display-1 text-muted"></i>
            <h4 class="mt-3">Keine Ergebnisse gefunden</h4>
            <p class="text-muted">
                Keine Leitern entsprechen den angegebenen Suchkriterien.
            </p>
            <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('searchForm').reset();">
                Suchkriterien anpassen
            </button>
        </div>
        <?php else: ?>
        
        <!-- Ergebnistabelle -->
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nummer</th>
                        <th>Hersteller</th>
                        <th>Typ</th>
                        <th>Standort</th>
                        <th>Status</th>
                        <th>Nächste Prüfung</th>
                        <th width="120">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($search_results as $ladder): ?>
                    <tr class="<?= $ladder->needsInspection() ? 'table-warning' : '' ?>">
                        <td>
                            <strong><?= $this->e($ladder->getLadderNumber()) ?></strong>
                            <?php if ($ladder->getSerialNumber()): ?>
                            <br><small class="text-muted">SN: <?= $this->e($ladder->getSerialNumber()) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= $this->e($ladder->getManufacturer()) ?>
                            <?php if ($ladder->getModel()): ?>
                            <br><small class="text-muted"><?= $this->e($ladder->getModel()) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-secondary"><?= $this->e($ladder->getLadderType()) ?></span>
                            <br><small class="text-muted"><?= $this->e($ladder->getMaterial()) ?> - <?= $ladder->getHeightCm() ?>cm</small>
                        </td>
                        <td>
                            <?= $this->e($ladder->getLocation()) ?>
                            <?php if ($ladder->getDepartment()): ?>
                            <br><small class="text-muted"><?= $this->e($ladder->getDepartment()) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $statusClass = match($ladder->getStatus()) {
                                'active' => 'bg-success',
                                'inactive' => 'bg-secondary',
                                'defective' => 'bg-danger',
                                'disposed' => 'bg-dark',
                                default => 'bg-secondary'
                            };
                            ?>
                            <span class="badge <?= $statusClass ?>"><?= $this->e(ucfirst($ladder->getStatus())) ?></span>
                        </td>
                        <td>
                            <?php
                            $daysUntil = $ladder->getDaysUntilInspection();
                            $inspectionDate = new DateTime($ladder->getNextInspectionDate());
                            ?>
                            <span class="<?= $daysUntil <= 0 ? 'text-danger fw-bold' : ($daysUntil <= 30 ? 'text-warning fw-bold' : '') ?>">
                                <?= $inspectionDate->format('d.m.Y') ?>
                            </span>
                            <br>
                            <small class="<?= $daysUntil <= 0 ? 'text-danger' : ($daysUntil <= 30 ? 'text-warning' : 'text-muted') ?>">
                                <?php if ($daysUntil <= 0): ?>
                                    <?= abs($daysUntil) ?> Tage überfällig
                                <?php else: ?>
                                    in <?= $daysUntil ?> Tagen
                                <?php endif; ?>
                            </small>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="view.php?id=<?= $ladder->getId() ?>" class="btn btn-outline-primary" title="Details anzeigen">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="edit.php?id=<?= $ladder->getId() ?>" class="btn btn-outline-secondary" title="Bearbeiten">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <?php
    $template->endSection();
    
    $template->startSection('scripts');
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Formular-Verbesserungen
            const form = document.getElementById('searchForm');
            
            // Enter-Taste im Formular
            form.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    form.submit();
                }
            });
            
            // Checkbox-Logik
            const needsInspectionCheckbox = document.getElementById('needs_inspection');
            const inspectionOverdueCheckbox = document.getElementById('inspection_overdue');
            
            inspectionOverdueCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    needsInspectionCheckbox.checked = true;
                }
            });
        });
    </script>
    <?php
    $template->endSection();
    
    echo $template->render('base', $templateData);
    
} catch (Exception $e) {
    error_log('Fehler in ladders/search.php: ' . $e->getMessage());
    
    $template = new TemplateEngine();
    echo $template->render('error', [
        'title' => 'Fehler',
        'error_title' => 'Fehler bei der Suche',
        'error_message' => 'Die Suche konnte nicht durchgeführt werden. Bitte versuchen Sie es später erneut.',
        'error_code' => 500
    ]);
}
?>
