<?php
/**
 * Template für Berichts-Übersicht
 */

// CSRF-Token generieren falls nicht vorhanden
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<?php $this->extend('base'); ?>

<?php $this->section('title'); ?>
<?= htmlspecialchars($pageTitle) ?>
<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<div class="container-fluid">
    <!-- Seitenkopf -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-chart-line me-2"></i>
                        Berichte
                    </h1>
                    <p class="text-muted mb-0">Generieren Sie detaillierte Berichte und Statistiken</p>
                </div>
                <div>
                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#helpModal">
                        <i class="fas fa-question-circle me-1"></i>
                        Hilfe
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik-Dashboard -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Aktive Leitern</h6>
                            <h3 class="mb-0"><?= $ladderStats['active'] ?? 0 ?></h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-ladder fa-2x opacity-75"></i>
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
                            <h6 class="card-title">Prüfung fällig</h6>
                            <h3 class="mb-0"><?= $ladderStats['needs_inspection'] ?? 0 ?></h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-exclamation-triangle fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Prüfungen (Monat)</h6>
                            <h3 class="mb-0"><?= $inspectionStats['total_inspections'] ?? 0 ?></h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clipboard-check fa-2x opacity-75"></i>
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
                            <h6 class="card-title">Erfolgsquote</h6>
                            <h3 class="mb-0">
                                <?php
                                $total = $inspectionStats['total_inspections'] ?? 0;
                                $passed = $inspectionStats['passed'] ?? 0;
                                $rate = $total > 0 ? round(($passed / $total) * 100, 1) : 0;
                                echo $rate . '%';
                                ?>
                            </h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Verfügbare Berichte -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-file-alt me-2"></i>
                        Verfügbare Berichte
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($availableReports as $reportType => $report): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100 report-card" data-report-type="<?= $reportType ?>">
                                <div class="card-body">
                                    <div class="d-flex align-items-start">
                                        <div class="me-3">
                                            <i class="<?= $report['icon'] ?> fa-2x text-primary"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="card-title"><?= htmlspecialchars($report['title']) ?></h6>
                                            <p class="card-text text-muted small">
                                                <?= htmlspecialchars($report['description']) ?>
                                            </p>
                                            <div class="d-flex flex-wrap gap-1 mb-2">
                                                <?php foreach ($report['formats'] as $format): ?>
                                                <span class="badge bg-secondary">
                                                    <?= strtoupper($format) ?>
                                                </span>
                                                <?php endforeach; ?>
                                            </div>
                                            <button type="button" 
                                                    class="btn btn-primary btn-sm generate-report-btn"
                                                    data-report-type="<?= $reportType ?>"
                                                    data-requires-selection="<?= $report['requires_selection'] ? 'true' : 'false' ?>"
                                                    data-selection-type="<?= $report['selection_type'] ?? '' ?>"
                                                    data-formats="<?= implode(',', $report['formats']) ?>">
                                                <i class="fas fa-download me-1"></i>
                                                Generieren
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kürzlich generierte Berichte -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-history me-2"></i>
                        Kürzlich generiert
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($recentReports)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach (array_slice($recentReports, 0, 5) as $report): ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 small"><?= htmlspecialchars($report['title']) ?></h6>
                                        <p class="mb-1 text-muted small">
                                            <?= strtoupper($report['format']) ?> • 
                                            <?= htmlspecialchars($report['user']) ?>
                                        </p>
                                        <small class="text-muted">
                                            <?= date('d.m.Y H:i', strtotime($report['generated_at'])) ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-3">
                            <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                            Noch keine Berichte generiert
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Anstehende Prüfungen -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-clock me-2"></i>
                        Anstehende Prüfungen
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($upcomingInspections)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach (array_slice($upcomingInspections, 0, 5) as $upcoming): ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1 small"><?= htmlspecialchars($upcoming['ladder_number']) ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars($upcoming['location']) ?></small>
                                    </div>
                                    <div class="text-end">
                                        <small class="<?= strtotime($upcoming['next_inspection_date']) < time() ? 'text-danger' : 'text-warning' ?>">
                                            <?= date('d.m.Y', strtotime($upcoming['next_inspection_date'])) ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-3">
                            <i class="fas fa-check-circle fa-2x d-block mb-2 text-success"></i>
                            Keine anstehenden Prüfungen
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Berichts-Generierung Modal -->
<div class="modal fade" id="generateReportModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bericht generieren</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="generateReportForm" action="/reports/generate.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="report_type" id="modalReportType">
                
                <div class="modal-body">
                    <div id="reportDescription" class="alert alert-info mb-3"></div>
                    
                    <!-- Format-Auswahl -->
                    <div class="mb-3">
                        <label class="form-label">Export-Format</label>
                        <div id="formatOptions" class="btn-group w-100" role="group">
                            <!-- Wird dynamisch gefüllt -->
                        </div>
                        <input type="hidden" name="format" id="selectedFormat" value="pdf">
                    </div>
                    
                    <!-- Leiter-Auswahl (nur für Prüfungsprotokoll) -->
                    <div id="ladderSelection" class="mb-3" style="display: none;">
                        <label for="ladderSelect" class="form-label">Leiter auswählen</label>
                        <select class="form-select" name="ladder_id" id="ladderSelect">
                            <option value="">Leiter auswählen...</option>
                        </select>
                    </div>
                    
                    <!-- Filter-Optionen -->
                    <div id="filterOptions">
                        <h6>Filter-Optionen</h6>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="dateFrom" class="form-label">Von Datum</label>
                                    <input type="date" class="form-control" name="filters[date_from]" id="dateFrom">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="dateTo" class="form-label">Bis Datum</label>
                                    <input type="date" class="form-control" name="filters[date_to]" id="dateTo">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="locationFilter" class="form-label">Standort</label>
                                    <input type="text" class="form-control" name="filters[location]" id="locationFilter" placeholder="Standort filtern...">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="departmentFilter" class="form-label">Abteilung</label>
                                    <input type="text" class="form-control" name="filters[department]" id="departmentFilter" placeholder="Abteilung filtern...">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="statusFilter" class="form-label">Status</label>
                                    <select class="form-select" name="filters[status]" id="statusFilter">
                                        <option value="">Alle Status</option>
                                        <option value="active">Aktiv</option>
                                        <option value="inactive">Inaktiv</option>
                                        <option value="defective">Defekt</option>
                                        <option value="disposed">Entsorgt</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="ladderTypeFilter" class="form-label">Leitertyp</label>
                                    <select class="form-select" name="filters[ladder_type]" id="ladderTypeFilter">
                                        <option value="">Alle Typen</option>
                                        <option value="Anlegeleiter">Anlegeleiter</option>
                                        <option value="Stehleiter">Stehleiter</option>
                                        <option value="Mehrzweckleiter">Mehrzweckleiter</option>
                                        <option value="Podestleiter">Podestleiter</option>
                                        <option value="Schiebeleiter">Schiebeleiter</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary" id="generateBtn">
                        <i class="fas fa-download me-1"></i>
                        Bericht generieren
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hilfe Modal -->
<div class="modal fade" id="helpModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Hilfe - Berichte</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6>Verfügbare Berichtstypen:</h6>
                <ul class="list-unstyled">
                    <li><strong>Prüfungsprotokoll:</strong> Detailliertes Protokoll einer einzelnen Prüfung</li>
                    <li><strong>Leitern-Übersicht:</strong> Komplette Liste aller Leitern mit Status</li>
                    <li><strong>Prüfungsstatistiken:</strong> Statistische Auswertungen und Trends</li>
                    <li><strong>Prüfkalender:</strong> Übersicht anstehender Prüfungen</li>
                    <li><strong>Ausfallbericht:</strong> Analyse von Mängeln und Ausfällen</li>
                </ul>
                
                <h6 class="mt-3">Export-Formate:</h6>
                <ul class="list-unstyled">
                    <li><strong>PDF:</strong> Für Ausdrucke und Archivierung</li>
                    <li><strong>Excel:</strong> Für weitere Datenanalyse</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script src="/src/assets/js/reports.js"></script>
<?php $this->endSection(); ?>
