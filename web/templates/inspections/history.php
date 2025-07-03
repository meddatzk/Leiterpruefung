<?php
/**
 * Template für Prüfungshistorie
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
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Prüfungshistorie</h1>
            <p class="text-muted">Detaillierte Übersicht und Analyse aller durchgeführten Prüfungen</p>
        </div>
        <div>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Zurück zur Übersicht
            </a>
            <a href="create.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Neue Prüfung
            </a>
        </div>
    </div>

    <!-- Statistiken -->
    <?php if (!empty($statistics)): ?>
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-primary"><?= number_format($statistics['total_inspections']) ?></h4>
                    <small class="text-muted">Prüfungen</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-success"><?= number_format($statistics['passed']) ?></h4>
                    <small class="text-muted">Bestanden</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-warning"><?= number_format($statistics['conditional']) ?></h4>
                    <small class="text-muted">Bedingt</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-danger"><?= number_format($statistics['failed']) ?></h4>
                    <small class="text-muted">Nicht bestanden</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-info"><?= number_format($statistics['unique_ladders']) ?></h4>
                    <small class="text-muted">Leitern</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-secondary"><?= $statistics['avg_duration'] ? round($statistics['avg_duration']) . ' Min' : 'N/A' ?></h4>
                    <small class="text-muted">Ø Dauer</small>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Monatliche Statistiken Chart -->
    <?php if (!empty($monthlyStats)): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-chart-line"></i>
                Prüfungen nach Monaten
            </h5>
        </div>
        <div class="card-body">
            <canvas id="monthlyChart" height="100"></canvas>
        </div>
    </div>
    <?php endif; ?>

    <!-- Mängel-Statistiken -->
    <?php if (!empty($defectStatistics)): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-exclamation-triangle"></i>
                Mängel-Statistiken nach Kategorien
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Kategorie</th>
                            <th>Prüfpunkte</th>
                            <th>Mängel</th>
                            <th>Kritisch</th>
                            <th>Hoch</th>
                            <th>Mittel</th>
                            <th>Niedrig</th>
                            <th>Verschleiß</th>
                            <th>Mängelquote</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($defectStatistics as $stat): ?>
                        <tr>
                            <td><strong><?= getCategoryLabel($stat['category']) ?></strong></td>
                            <td><?= number_format($stat['total_items']) ?></td>
                            <td>
                                <span class="badge badge-danger"><?= number_format($stat['defects']) ?></span>
                            </td>
                            <td><?= number_format($stat['critical_defects']) ?></td>
                            <td><?= number_format($stat['high_defects']) ?></td>
                            <td><?= number_format($stat['medium_defects']) ?></td>
                            <td><?= number_format($stat['low_defects']) ?></td>
                            <td>
                                <span class="badge badge-warning"><?= number_format($stat['wear_items']) ?></span>
                            </td>
                            <td>
                                <?php 
                                $rate = $stat['total_items'] > 0 ? round(($stat['defects'] / $stat['total_items']) * 100, 1) : 0;
                                $badgeClass = $rate > 20 ? 'badge-danger' : ($rate > 10 ? 'badge-warning' : 'badge-success');
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= $rate ?>%</span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Erweiterte Filter -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-filter"></i>
                Erweiterte Filter
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <!-- Zeitraum-Shortcuts -->
                <div class="col-md-12 mb-3">
                    <label class="form-label">Zeitraum-Shortcuts:</label>
                    <div class="btn-group btn-group-sm" role="group">
                        <?php foreach ($periodOptions as $period => $label): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['period' => $period])) ?>" 
                           class="btn btn-outline-secondary <?= ($_GET['period'] ?? '') === $period ? 'active' : '' ?>">
                            <?= $label ?>
                        </a>
                        <?php endforeach; ?>
                        <a href="?" class="btn btn-outline-secondary">Alle</a>
                    </div>
                </div>

                <div class="col-md-3">
                    <label for="ladder_number" class="form-label">Leiternummer</label>
                    <input type="text" class="form-control" id="ladder_number" name="ladder_number" 
                           value="<?= htmlspecialchars($filters['ladder_number'] ?? '') ?>"
                           placeholder="L-2024-0001">
                </div>

                <div class="col-md-3">
                    <label for="ladder_id" class="form-label">Leiter auswählen</label>
                    <select class="form-select" id="ladder_id" name="ladder_id">
                        <option value="">Alle Leitern</option>
                        <?php foreach ($availableLadders as $ladder): ?>
                            <option value="<?= $ladder->getId() ?>" 
                                    <?= ($filters['ladder_id'] ?? '') == $ladder->getId() ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ladder->getLadderNumber()) ?> - 
                                <?= htmlspecialchars($ladder->getLadderType()) ?>
                                (<?= htmlspecialchars($ladder->getLocation()) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="inspector_id" class="form-label">Prüfer</label>
                    <select class="form-select" id="inspector_id" name="inspector_id">
                        <option value="">Alle Prüfer</option>
                        <?php foreach ($availableInspectors as $inspector): ?>
                            <option value="<?= $inspector['id'] ?>" 
                                    <?= ($filters['inspector_id'] ?? '') == $inspector['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($inspector['display_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="inspection_type" class="form-label">Prüfungstyp</label>
                    <select class="form-select" id="inspection_type" name="inspection_type">
                        <option value="">Alle Typen</option>
                        <?php foreach ($inspectionTypes as $type): ?>
                            <option value="<?= $type ?>" 
                                    <?= ($filters['inspection_type'] ?? '') === $type ? 'selected' : '' ?>>
                                <?= getTypeLabel($type) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="overall_result" class="form-label">Ergebnis</label>
                    <select class="form-select" id="overall_result" name="overall_result">
                        <option value="">Alle Ergebnisse</option>
                        <?php foreach ($overallResults as $result): ?>
                            <option value="<?= $result ?>" 
                                    <?= ($filters['overall_result'] ?? '') === $result ? 'selected' : '' ?>>
                                <?= getResultLabel($result) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="date_from" class="form-label">Von Datum</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
                </div>

                <div class="col-md-3">
                    <label for="date_to" class="form-label">Bis Datum</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" 
                           value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
                </div>

                <div class="col-md-3">
                    <label for="limit" class="form-label">Einträge pro Seite</label>
                    <select class="form-select" id="limit" name="limit">
                        <option value="20" <?= ($pagination['limit'] ?? 50) == 20 ? 'selected' : '' ?>>20</option>
                        <option value="50" <?= ($pagination['limit'] ?? 50) == 50 ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= ($pagination['limit'] ?? 50) == 100 ? 'selected' : '' ?>>100</option>
                    </select>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filtern
                    </button>
                    <a href="history.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Zurücksetzen
                    </a>
                    <button type="button" class="btn btn-outline-info" onclick="exportData()">
                        <i class="fas fa-download"></i> Exportieren
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Prüfungsliste -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-history"></i>
                Prüfungshistorie
                <?php if (!empty($pagination['count'])): ?>
                    <span class="badge badge-secondary"><?= number_format($pagination['count']) ?></span>
                <?php endif; ?>
            </h5>
            <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-secondary" onclick="toggleView('table')">
                    <i class="fas fa-table"></i> Tabelle
                </button>
                <button class="btn btn-outline-secondary" onclick="toggleView('cards')">
                    <i class="fas fa-th-large"></i> Karten
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (empty($inspections)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Keine Prüfungen gefunden</h5>
                    <p class="text-muted">Es wurden keine Prüfungen gefunden, die den Filterkriterien entsprechen.</p>
                    <a href="?" class="btn btn-outline-secondary">Filter zurücksetzen</a>
                </div>
            <?php else: ?>
                <!-- Tabellen-Ansicht -->
                <div id="table-view" class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Datum</th>
                                <th>Leiter</th>
                                <th>Typ</th>
                                <th>Prüfer</th>
                                <th>Ergebnis</th>
                                <th>Mängel</th>
                                <th>Dauer</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inspections as $inspection): ?>
                            <tr>
                                <td>
                                    <a href="view.php?id=<?= $inspection->getId() ?>" class="text-decoration-none">
                                        <strong>#<?= $inspection->getId() ?></strong>
                                    </a>
                                </td>
                                <td>
                                    <?= date('d.m.Y', strtotime($inspection->getInspectionDate())) ?>
                                    <br><small class="text-muted">
                                        <?= getTypeLabel($inspection->getInspectionType()) ?>
                                    </small>
                                </td>
                                <td>
                                    <a href="../ladders/view.php?id=<?= $inspection->getLadderId() ?>" 
                                       class="text-decoration-none">
                                        <?= htmlspecialchars($inspection->getLadder()?->getLadderNumber() ?? 'N/A') ?>
                                    </a>
                                    <br><small class="text-muted">
                                        <?= htmlspecialchars($inspection->getLadder()?->getLadderType() ?? 'N/A') ?>
                                    </small>
                                </td>
                                <td>
                                    <?= htmlspecialchars($inspection->getLadder()?->getLocation() ?? 'N/A') ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($inspection->getInspector()?->getDisplayName() ?? 'N/A') ?>
                                </td>
                                <td>
                                    <span class="badge <?= getResultBadgeClass($inspection->getOverallResult()) ?>">
                                        <?= getResultLabel($inspection->getOverallResult()) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $defects = $inspection->getAllDefects();
                                    $criticalDefects = $inspection->getCriticalDefects();
                                    ?>
                                    <?php if (!empty($defects)): ?>
                                        <span class="badge badge-danger"><?= count($defects) ?></span>
                                        <?php if (!empty($criticalDefects)): ?>
                                            <br><small class="text-danger">
                                                <?= count($criticalDefects) ?> kritisch
                                            </small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= $inspection->getInspectionDurationMinutes() ? $inspection->getInspectionDurationMinutes() . ' Min' : '-' ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="view.php?id=<?= $inspection->getId() ?>" 
                                           class="btn btn-outline-primary" title="Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="create.php?ladder_id=<?= $inspection->getLadderId() ?>" 
                                           class="btn btn-outline-secondary" title="Neue Prüfung">
                                            <i class="fas fa-plus"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Karten-Ansicht -->
                <div id="cards-view" class="p-3" style="display: none;">
                    <div class="row">
                        <?php foreach ($inspections as $inspection): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <strong>#<?= $inspection->getId() ?></strong>
                                    <span class="badge <?= getResultBadgeClass($inspection->getOverallResult()) ?>">
                                        <?= getResultLabel($inspection->getOverallResult()) ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <?= htmlspecialchars($inspection->getLadder()?->getLadderNumber() ?? 'N/A') ?>
                                    </h6>
                                    <p class="card-text">
                                        <small class="text-muted">
                                            <?= date('d.m.Y', strtotime($inspection->getInspectionDate())) ?><br>
                                            <?= getTypeLabel($inspection->getInspectionType()) ?><br>
                                            <?= htmlspecialchars($inspection->getInspector()?->getDisplayName() ?? 'N/A') ?>
                                        </small>
                                    </p>
                                    <?php 
                                    $defects = $inspection->getAllDefects();
                                    $criticalDefects = $inspection->getCriticalDefects();
                                    ?>
                                    <?php if (!empty($defects)): ?>
                                    <div class="mb-2">
                                        <span class="badge badge-danger"><?= count($defects) ?> Mängel</span>
                                        <?php if (!empty($criticalDefects)): ?>
                                            <span class="badge badge-danger"><?= count($criticalDefects) ?> kritisch</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <div class="btn-group btn-group-sm w-100">
                                        <a href="view.php?id=<?= $inspection->getId() ?>" 
                                           class="btn btn-outline-primary">
                                            <i class="fas fa-eye"></i> Details
                                        </a>
                                        <a href="create.php?ladder_id=<?= $inspection->getLadderId() ?>" 
                                           class="btn btn-outline-secondary">
                                            <i class="fas fa-plus"></i> Neue Prüfung
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <?php if (!empty($pagination) && $pagination['total'] > 1): ?>
        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted">
                    Zeige <?= number_format(($pagination['current'] - 1) * $pagination['limit'] + 1) ?> 
                    bis <?= number_format(min($pagination['current'] * $pagination['limit'], $pagination['count'])) ?> 
                    von <?= number_format($pagination['count']) ?> Einträgen
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($pagination['current'] > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current'] - 1])) ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php
                        $start = max(1, $pagination['current'] - 2);
                        $end = min($pagination['total'], $pagination['current'] + 2);
                        ?>
                        
                        <?php for ($i = $start; $i <= $end; $i++): ?>
                            <li class="page-item <?= $i == $pagination['current'] ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['current'] < $pagination['total']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current'] + 1])) ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Chart.js für monatliche Statistiken -->
<?php if (!empty($monthlyStats)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('monthlyChart').getContext('2d');
    const monthlyData = <?= json_encode($monthlyStats) ?>;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: monthlyData.map(item => {
                const [year, month] = item.month.split('-');
                return new Date(year, month - 1).toLocaleDateString('de-DE', { 
                    year: 'numeric', 
                    month: 'short' 
                });
            }),
            datasets: [{
                label: 'Bestanden',
                data: monthlyData.map(item => item.passed),
                borderColor: 'rgb(40, 167, 69)',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.1
            }, {
                label: 'Bedingt bestanden',
                data: monthlyData.map(item => item.conditional),
                borderColor: 'rgb(255, 193, 7)',
                backgroundColor: 'rgba(255, 193, 7, 0.1)',
                tension: 0.1
            }, {
                label: 'Nicht bestanden',
                data: monthlyData.map(item => item.failed),
                borderColor: 'rgb(220, 53, 69)',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Prüfungsergebnisse nach Monaten'
                },
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
});
</script>
<?php endif; ?>

<script>
// Ansicht umschalten
function toggleView(view) {
    const tableView = document.getElementById('table-view');
    const cardsView = document.getElementById('cards-view');
    const buttons = document.querySelectorAll('.btn-group .btn');
    
    buttons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    if (view === 'table') {
        tableView.style.display = 'block';
        cardsView.style.display = 'none';
    } else {
        tableView.style.display = 'none';
        cardsView.style.display = 'block';
    }
}

// Export-Funktionalität
function exportData() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    window.location.href = '?' + params.toString();
}
</script>
