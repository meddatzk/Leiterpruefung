<?php
/**
 * Template für Prüfungs-Übersicht
 */

// Hilfsfunktionen für Status-Badges
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
?>

<div class="container-fluid">
    <!-- Header mit Aktionen -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Prüfungsübersicht</h1>
            <p class="text-muted">Verwaltung und Übersicht aller Leiterprüfungen</p>
        </div>
        <div>
            <a href="create.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Neue Prüfung
            </a>
            <a href="history.php" class="btn btn-outline-secondary">
                <i class="fas fa-history"></i> Historie
            </a>
        </div>
    </div>

    <!-- Statistiken -->
    <?php if (!empty($statistics)): ?>
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= number_format($statistics['total_inspections']) ?></h4>
                            <p class="mb-0">Prüfungen gesamt</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clipboard-check fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= number_format($statistics['passed']) ?></h4>
                            <p class="mb-0">Bestanden</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= number_format($statistics['conditional']) ?></h4>
                            <p class="mb-0">Bedingt bestanden</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= number_format($statistics['failed']) ?></h4>
                            <p class="mb-0">Nicht bestanden</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-times-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Anstehende Prüfungen -->
    <?php if (!empty($upcomingInspections)): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-clock text-warning"></i>
                Anstehende Prüfungen (nächste 30 Tage)
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Leiternummer</th>
                            <th>Typ</th>
                            <th>Standort</th>
                            <th>Fällig am</th>
                            <th>Letzte Prüfung</th>
                            <th>Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcomingInspections as $upcoming): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($upcoming['ladder_number']) ?></strong>
                            </td>
                            <td><?= htmlspecialchars($upcoming['ladder_type'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($upcoming['location'] ?? 'N/A') ?></td>
                            <td>
                                <?php 
                                $dueDate = new DateTime($upcoming['next_inspection_date']);
                                $today = new DateTime();
                                $diff = $today->diff($dueDate);
                                $isOverdue = $dueDate < $today;
                                ?>
                                <span class="badge <?= $isOverdue ? 'badge-danger' : 'badge-warning' ?>">
                                    <?= $dueDate->format('d.m.Y') ?>
                                    <?php if ($isOverdue): ?>
                                        (<?= $diff->days ?> Tage überfällig)
                                    <?php else: ?>
                                        (in <?= $diff->days ?> Tagen)
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($upcoming['last_inspection_date'])): ?>
                                    <?= date('d.m.Y', strtotime($upcoming['last_inspection_date'])) ?>
                                    <?php if (!empty($upcoming['last_result'])): ?>
                                        <span class="badge badge-sm <?= getResultBadgeClass($upcoming['last_result']) ?>">
                                            <?= getResultLabel($upcoming['last_result']) ?>
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">Keine</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="create.php?ladder_id=<?= $upcoming['id'] ?>" 
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Prüfen
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-filter"></i>
                Filter
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="ladder_number" class="form-label">Leiternummer</label>
                    <input type="text" class="form-control" id="ladder_number" name="ladder_number" 
                           value="<?= htmlspecialchars($filters['ladder_number'] ?? '') ?>"
                           placeholder="L-2024-0001">
                </div>
                <div class="col-md-3">
                    <label for="inspection_type" class="form-label">Prüfungstyp</label>
                    <select class="form-select" id="inspection_type" name="inspection_type">
                        <option value="">Alle Typen</option>
                        <option value="routine" <?= ($filters['inspection_type'] ?? '') === 'routine' ? 'selected' : '' ?>>Routine</option>
                        <option value="initial" <?= ($filters['inspection_type'] ?? '') === 'initial' ? 'selected' : '' ?>>Erstprüfung</option>
                        <option value="after_incident" <?= ($filters['inspection_type'] ?? '') === 'after_incident' ? 'selected' : '' ?>>Nach Vorfall</option>
                        <option value="special" <?= ($filters['inspection_type'] ?? '') === 'special' ? 'selected' : '' ?>>Sonderprüfung</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="overall_result" class="form-label">Ergebnis</label>
                    <select class="form-select" id="overall_result" name="overall_result">
                        <option value="">Alle Ergebnisse</option>
                        <option value="passed" <?= ($filters['overall_result'] ?? '') === 'passed' ? 'selected' : '' ?>>Bestanden</option>
                        <option value="conditional" <?= ($filters['overall_result'] ?? '') === 'conditional' ? 'selected' : '' ?>>Bedingt bestanden</option>
                        <option value="failed" <?= ($filters['overall_result'] ?? '') === 'failed' ? 'selected' : '' ?>>Nicht bestanden</option>
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
                <div class="col-md-9">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filtern
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Zurücksetzen
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Prüfungsliste -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-list"></i>
                Prüfungen
                <?php if (!empty($pagination['count'])): ?>
                    <span class="badge badge-secondary"><?= number_format($pagination['count']) ?></span>
                <?php endif; ?>
            </h5>
            <div class="btn-group btn-group-sm">
                <a href="?<?= http_build_query(array_merge($_GET, ['limit' => 20])) ?>" 
                   class="btn btn-outline-secondary <?= ($pagination['limit'] ?? 20) == 20 ? 'active' : '' ?>">20</a>
                <a href="?<?= http_build_query(array_merge($_GET, ['limit' => 50])) ?>" 
                   class="btn btn-outline-secondary <?= ($pagination['limit'] ?? 20) == 50 ? 'active' : '' ?>">50</a>
                <a href="?<?= http_build_query(array_merge($_GET, ['limit' => 100])) ?>" 
                   class="btn btn-outline-secondary <?= ($pagination['limit'] ?? 20) == 100 ? 'active' : '' ?>">100</a>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (empty($inspections)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Keine Prüfungen gefunden</h5>
                    <p class="text-muted">Es wurden keine Prüfungen gefunden, die den Filterkriterien entsprechen.</p>
                    <a href="create.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Erste Prüfung erstellen
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Leiternummer</th>
                                <th>Typ/Modell</th>
                                <th>Prüfdatum</th>
                                <th>Prüfungstyp</th>
                                <th>Prüfer</th>
                                <th>Ergebnis</th>
                                <th>Nächste Prüfung</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inspections as $inspection): ?>
                            <tr>
                                <td>
                                    <strong>#<?= $inspection->getId() ?></strong>
                                </td>
                                <td>
                                    <a href="../ladders/view.php?id=<?= $inspection->getLadderId() ?>" 
                                       class="text-decoration-none">
                                        <?= htmlspecialchars($inspection->getLadder()?->getLadderNumber() ?? 'N/A') ?>
                                    </a>
                                </td>
                                <td>
                                    <div>
                                        <strong><?= htmlspecialchars($inspection->getLadder()?->getLadderType() ?? 'N/A') ?></strong>
                                    </div>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($inspection->getLadder()?->getManufacturer() ?? '') ?>
                                        <?= htmlspecialchars($inspection->getLadder()?->getModel() ?? '') ?>
                                    </small>
                                </td>
                                <td>
                                    <?= date('d.m.Y', strtotime($inspection->getInspectionDate())) ?>
                                    <?php if ($inspection->getInspectionDurationMinutes()): ?>
                                        <br><small class="text-muted"><?= $inspection->getInspectionDurationMinutes() ?> Min.</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        <?= getTypeLabel($inspection->getInspectionType()) ?>
                                    </span>
                                </td>
                                <td>
                                    <?= htmlspecialchars($inspection->getInspector()?->getDisplayName() ?? 'N/A') ?>
                                </td>
                                <td>
                                    <span class="badge <?= getResultBadgeClass($inspection->getOverallResult()) ?>">
                                        <?= getResultLabel($inspection->getOverallResult()) ?>
                                    </span>
                                    <?php 
                                    $defects = $inspection->getAllDefects();
                                    $criticalDefects = $inspection->getCriticalDefects();
                                    ?>
                                    <?php if (!empty($defects)): ?>
                                        <br><small class="text-muted">
                                            <?= count($defects) ?> Mängel
                                            <?php if (!empty($criticalDefects)): ?>
                                                <span class="text-danger">(<?= count($criticalDefects) ?> kritisch)</span>
                                            <?php endif; ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= date('d.m.Y', strtotime($inspection->getNextInspectionDate())) ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="view.php?id=<?= $inspection->getId() ?>" 
                                           class="btn btn-outline-primary" title="Details anzeigen">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if (!$inspection->isImmutable()): ?>
                                        <a href="create.php?copy=<?= $inspection->getId() ?>" 
                                           class="btn btn-outline-secondary" title="Kopieren">
                                            <i class="fas fa-copy"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
