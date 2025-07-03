<?php
/**
 * Template für Prüfungs-Details
 */

// Hilfsfunktionen
function getResultBadgeClass($result) {
    switch ($result) {
        case 'passed': return 'badge-success';
        case 'failed': return 'badge-danger';
        case 'conditional': return 'badge-warning';
        default: return 'badge-secondary';
    }
}

function getResultLabel($result) {
    switch ($result) {
        case 'passed': return 'Bestanden';
        case 'failed': return 'Nicht bestanden';
        case 'conditional': return 'Bedingt bestanden';
        default: return ucfirst($result);
    }
}

function getTypeLabel($type) {
    switch ($type) {
        case 'routine': return 'Routine';
        case 'initial': return 'Erstprüfung';
        case 'after_incident': return 'Nach Vorfall';
        case 'special': return 'Sonderprüfung';
        default: return ucfirst($type);
    }
}

function getCategoryLabel($category) {
    $labels = [
        'structure' => 'Struktur',
        'safety' => 'Sicherheit',
        'function' => 'Funktion',
        'marking' => 'Kennzeichnung',
        'accessories' => 'Zubehör'
    ];
    return $labels[$category] ?? ucfirst($category);
}

function getItemResultLabel($result) {
    $labels = [
        'ok' => 'In Ordnung',
        'defect' => 'Defekt',
        'wear' => 'Verschleiß',
        'not_applicable' => 'Nicht anwendbar'
    ];
    return $labels[$result] ?? ucfirst($result);
}

function getSeverityLabel($severity) {
    $labels = [
        'low' => 'Niedrig',
        'medium' => 'Mittel',
        'high' => 'Hoch',
        'critical' => 'Kritisch'
    ];
    return $labels[$severity] ?? ucfirst($severity);
}
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                Prüfung #<?= $inspection->getId() ?>
                <span class="badge <?= getResultBadgeClass($inspection->getOverallResult()) ?> ms-2">
                    <?= getResultLabel($inspection->getOverallResult()) ?>
                </span>
            </h1>
            <p class="text-muted">
                <?= getTypeLabel($inspection->getInspectionType()) ?> vom 
                <?= date('d.m.Y', strtotime($inspection->getInspectionDate())) ?>
                <?php if ($inspection->getInspectionDurationMinutes()): ?>
                    (<?= $inspection->getInspectionDurationMinutes() ?> Minuten)
                <?php endif; ?>
            </p>
        </div>
        <div>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Zurück zur Übersicht
            </a>
            <?php if ($ladder): ?>
            <a href="../ladders/view.php?id=<?= $ladder->getId() ?>" class="btn btn-outline-info">
                <i class="fas fa-ladder"></i> Leiter anzeigen
            </a>
            <?php endif; ?>
            <a href="create.php?ladder_id=<?= $inspection->getLadderId() ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> Neue Prüfung
            </a>
        </div>
    </div>

    <!-- Erfolg anzeigen -->
    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i>
        Prüfung wurde erfolgreich gespeichert!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Linke Spalte: Grunddaten und Leiter-Info -->
        <div class="col-lg-4">
            <!-- Leiter-Informationen -->
            <?php if ($ladder): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-ladder"></i>
                        Leiter-Informationen
                    </h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-5">Leiternummer:</dt>
                        <dd class="col-sm-7">
                            <strong><?= htmlspecialchars($ladder->getLadderNumber()) ?></strong>
                        </dd>
                        
                        <dt class="col-sm-5">Typ:</dt>
                        <dd class="col-sm-7"><?= htmlspecialchars($ladder->getLadderType()) ?></dd>
                        
                        <dt class="col-sm-5">Hersteller:</dt>
                        <dd class="col-sm-7"><?= htmlspecialchars($ladder->getManufacturer()) ?></dd>
                        
                        <dt class="col-sm-5">Modell:</dt>
                        <dd class="col-sm-7"><?= htmlspecialchars($ladder->getModel()) ?></dd>
                        
                        <dt class="col-sm-5">Material:</dt>
                        <dd class="col-sm-7"><?= htmlspecialchars($ladder->getMaterial()) ?></dd>
                        
                        <dt class="col-sm-5">Standort:</dt>
                        <dd class="col-sm-7"><?= htmlspecialchars($ladder->getLocation()) ?></dd>
                        
                        <dt class="col-sm-5">Max. Belastung:</dt>
                        <dd class="col-sm-7"><?= $ladder->getMaxLoadKg() ?> kg</dd>
                        
                        <dt class="col-sm-5">Höhe:</dt>
                        <dd class="col-sm-7"><?= $ladder->getHeightCm() ?> cm</dd>
                    </dl>
                </div>
            </div>
            <?php endif; ?>

            <!-- Prüfungs-Grunddaten -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle"></i>
                        Prüfungsdaten
                    </h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-5">Prüfungs-ID:</dt>
                        <dd class="col-sm-7">#<?= $inspection->getId() ?></dd>
                        
                        <dt class="col-sm-5">Prüfdatum:</dt>
                        <dd class="col-sm-7"><?= date('d.m.Y', strtotime($inspection->getInspectionDate())) ?></dd>
                        
                        <dt class="col-sm-5">Prüfungstyp:</dt>
                        <dd class="col-sm-7">
                            <span class="badge badge-info"><?= getTypeLabel($inspection->getInspectionType()) ?></span>
                        </dd>
                        
                        <dt class="col-sm-5">Prüfer:</dt>
                        <dd class="col-sm-7"><?= htmlspecialchars($inspection->getInspector()?->getDisplayName() ?? 'N/A') ?></dd>
                        
                        <dt class="col-sm-5">Prüfdauer:</dt>
                        <dd class="col-sm-7">
                            <?= $inspection->getInspectionDurationMinutes() ? $inspection->getInspectionDurationMinutes() . ' Minuten' : 'Nicht angegeben' ?>
                        </dd>
                        
                        <dt class="col-sm-5">Nächste Prüfung:</dt>
                        <dd class="col-sm-7">
                            <?php 
                            $nextDate = new DateTime($inspection->getNextInspectionDate());
                            $today = new DateTime();
                            $isOverdue = $nextDate < $today;
                            ?>
                            <span class="badge <?= $isOverdue ? 'badge-danger' : 'badge-success' ?>">
                                <?= $nextDate->format('d.m.Y') ?>
                            </span>
                        </dd>
                        
                        <?php if ($inspection->getWeatherConditions()): ?>
                        <dt class="col-sm-5">Wetter:</dt>
                        <dd class="col-sm-7"><?= htmlspecialchars($inspection->getWeatherConditions()) ?></dd>
                        <?php endif; ?>
                        
                        <?php if ($inspection->getTemperatureCelsius() !== null): ?>
                        <dt class="col-sm-5">Temperatur:</dt>
                        <dd class="col-sm-7"><?= $inspection->getTemperatureCelsius() ?>°C</dd>
                        <?php endif; ?>
                        
                        <dt class="col-sm-5">Erstellt:</dt>
                        <dd class="col-sm-7">
                            <?= $inspection->getCreatedAt() ? date('d.m.Y H:i', strtotime($inspection->getCreatedAt())) : 'N/A' ?>
                        </dd>
                    </dl>
                </div>
            </div>

            <!-- Mängel-Statistiken -->
            <?php if (!empty($defectStats)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie"></i>
                        Mängel-Übersicht
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-end">
                                <h4 class="text-primary"><?= $defectStats['total_items'] ?></h4>
                                <small class="text-muted">Prüfpunkte</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h4 class="text-<?= $defectStats['total_defects'] > 0 ? 'danger' : 'success' ?>">
                                <?= $defectStats['total_defects'] ?>
                            </h4>
                            <small class="text-muted">Mängel</small>
                        </div>
                    </div>
                    
                    <?php if ($defectStats['total_defects'] > 0): ?>
                    <hr>
                    <div class="row text-center">
                        <div class="col-6">
                            <h5 class="text-danger"><?= $defectStats['critical_defects'] ?></h5>
                            <small class="text-muted">Kritische Mängel</small>
                        </div>
                        <div class="col-6">
                            <h5 class="text-warning"><?= $defectStats['defect_rate'] ?>%</h5>
                            <small class="text-muted">Mängelquote</small>
                        </div>
                    </div>
                    
                    <!-- Mängel nach Kategorien -->
                    <?php if (!empty($defectStats['by_category'])): ?>
                    <hr>
                    <h6>Mängel nach Kategorien:</h6>
                    <?php foreach ($defectStats['by_category'] as $category => $categoryDefects): ?>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small><?= getCategoryLabel($category) ?>:</small>
                        <span class="badge badge-danger"><?= count($categoryDefects) ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Prüfungshistorie dieser Leiter -->
            <?php if (!empty($ladderInspections)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-history"></i>
                        Prüfungshistorie
                    </h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($ladderInspections, 0, 5) as $historyInspection): ?>
                        <div class="list-group-item px-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted">
                                        <?= date('d.m.Y', strtotime($historyInspection->getInspectionDate())) ?>
                                    </small>
                                    <br>
                                    <span class="badge badge-sm <?= getResultBadgeClass($historyInspection->getOverallResult()) ?>">
                                        <?= getResultLabel($historyInspection->getOverallResult()) ?>
                                    </span>
                                </div>
                                <a href="view.php?id=<?= $historyInspection->getId() ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($ladderInspections) > 5): ?>
                    <div class="text-center mt-2">
                        <a href="history.php?ladder_id=<?= $ladder->getId() ?>" class="btn btn-sm btn-outline-secondary">
                            Alle <?= count($ladderInspections) ?> Prüfungen anzeigen
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Rechte Spalte: Prüfpunkte und Details -->
        <div class="col-lg-8">
            <!-- Prüfpunkte -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-tasks"></i>
                        Prüfpunkte
                        <span class="badge badge-secondary"><?= count($inspection->getInspectionItems()) ?></span>
                    </h5>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-secondary" onclick="filterItems('all')">Alle</button>
                        <button class="btn btn-outline-success" onclick="filterItems('ok')">OK</button>
                        <button class="btn btn-outline-danger" onclick="filterItems('defect')">Mängel</button>
                        <button class="btn btn-outline-warning" onclick="filterItems('wear')">Verschleiß</button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($inspection->getInspectionItems())): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Keine Prüfpunkte vorhanden</h5>
                        </div>
                    <?php else: ?>
                        <!-- Prüfpunkte nach Kategorien gruppiert -->
                        <?php 
                        $itemsByCategory = [];
                        foreach ($inspection->getInspectionItems() as $item) {
                            $itemsByCategory[$item->getCategory()][] = $item;
                        }
                        ?>
                        
                        <div class="accordion" id="inspection-items-accordion">
                            <?php foreach ($itemsByCategory as $category => $items): ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading-<?= $category ?>">
                                    <button class="accordion-button" type="button" 
                                            data-bs-toggle="collapse" data-bs-target="#collapse-<?= $category ?>"
                                            aria-expanded="true" aria-controls="collapse-<?= $category ?>">
                                        <strong><?= getCategoryLabel($category) ?></strong>
                                        <span class="badge badge-secondary ms-2"><?= count($items) ?></span>
                                        <?php 
                                        $categoryDefects = array_filter($items, function($item) { 
                                            return $item->getResult() === 'defect'; 
                                        });
                                        ?>
                                        <?php if (!empty($categoryDefects)): ?>
                                            <span class="badge badge-danger ms-1"><?= count($categoryDefects) ?> Mängel</span>
                                        <?php endif; ?>
                                    </button>
                                </h2>
                                <div id="collapse-<?= $category ?>" class="accordion-collapse collapse show"
                                     aria-labelledby="heading-<?= $category ?>" data-bs-parent="#inspection-items-accordion">
                                    <div class="accordion-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-sm mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Prüfpunkt</th>
                                                        <th>Ergebnis</th>
                                                        <th>Schweregrad</th>
                                                        <th>Notizen</th>
                                                        <th>Reparatur</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($items as $item): ?>
                                                    <tr class="inspection-item-row" data-result="<?= $item->getResult() ?>">
                                                        <td>
                                                            <strong><?= htmlspecialchars($item->getItemName()) ?></strong>
                                                            <?php if ($item->getDescription()): ?>
                                                                <br><small class="text-muted"><?= htmlspecialchars($item->getDescription()) ?></small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge <?= $item->getResultCssClass() ?>">
                                                                <?= getItemResultLabel($item->getResult()) ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php if ($item->getSeverity()): ?>
                                                                <span class="badge <?= $item->getSeverityCssClass() ?>">
                                                                    <?= getSeverityLabel($item->getSeverity()) ?>
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($item->getNotes()): ?>
                                                                <small><?= htmlspecialchars($item->getNotes()) ?></small>
                                                            <?php else: ?>
                                                                <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($item->isRepairRequired()): ?>
                                                                <span class="badge badge-warning">Erforderlich</span>
                                                                <?php if ($item->getRepairDeadline()): ?>
                                                                    <br><small class="text-muted">
                                                                        bis <?= date('d.m.Y', strtotime($item->getRepairDeadline())) ?>
                                                                        <?php if ($item->isRepairOverdue()): ?>
                                                                            <span class="text-danger">(überfällig)</span>
                                                                        <?php endif; ?>
                                                                    </small>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Zusätzliche Informationen -->
            <?php if ($inspection->getGeneralNotes() || $inspection->getRecommendations() || 
                      $inspection->getDefectsFound() || $inspection->getActionsRequired()): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-sticky-note"></i>
                        Zusätzliche Informationen
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($inspection->getGeneralNotes()): ?>
                    <div class="mb-3">
                        <h6>Allgemeine Notizen:</h6>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($inspection->getGeneralNotes())) ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if ($inspection->getDefectsFound()): ?>
                    <div class="mb-3">
                        <h6>Gefundene Mängel:</h6>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($inspection->getDefectsFound())) ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if ($inspection->getActionsRequired()): ?>
                    <div class="mb-3">
                        <h6>Erforderliche Maßnahmen:</h6>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($inspection->getActionsRequired())) ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if ($inspection->getRecommendations()): ?>
                    <div class="mb-3">
                        <h6>Empfehlungen:</h6>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($inspection->getRecommendations())) ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if ($inspection->getInspectorSignature()): ?>
                    <div class="mb-0">
                        <h6>Prüfer-Unterschrift:</h6>
                        <p class="text-muted"><?= htmlspecialchars($inspection->getInspectorSignature()) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Filter-Funktionalität für Prüfpunkte
function filterItems(filter) {
    const rows = document.querySelectorAll('.inspection-item-row');
    const buttons = document.querySelectorAll('.btn-group .btn');
    
    // Button-Status aktualisieren
    buttons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // Zeilen filtern
    rows.forEach(row => {
        const result = row.dataset.result;
        if (filter === 'all' || result === filter) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Alle Accordion-Panels standardmäßig öffnen
document.addEventListener('DOMContentLoaded', function() {
    const collapseElements = document.querySelectorAll('.accordion-collapse');
    collapseElements.forEach(element => {
        element.classList.add('show');
    });
});
</script>
