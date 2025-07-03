<?php
/**
 * Template für Berichts-Formular (erweiterte Optionen)
 */
?>

<?php $this->extend('base'); ?>

<?php $this->section('title'); ?>
<?= htmlspecialchars($pageTitle) ?>
<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<div class="container-fluid">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/reports/">Berichte</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($reportConfig['title']) ?></li>
        </ol>
    </nav>

    <!-- Seitenkopf -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <i class="<?= $reportConfig['icon'] ?> fa-2x text-primary"></i>
                </div>
                <div>
                    <h1 class="h3 mb-0"><?= htmlspecialchars($reportConfig['title']) ?></h1>
                    <p class="text-muted mb-0"><?= htmlspecialchars($reportConfig['description']) ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Hauptformular -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-cog me-2"></i>
                        Berichts-Konfiguration
                    </h5>
                </div>
                <div class="card-body">
                    <form id="reportForm" action="/reports/generate.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="report_type" value="<?= htmlspecialchars($reportType) ?>">

                        <!-- Export-Format -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Export-Format</label>
                            <div class="row">
                                <?php foreach ($reportConfig['formats'] as $format): ?>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="format" 
                                               id="format_<?= $format ?>" value="<?= $format ?>"
                                               <?= $format === 'pdf' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="format_<?= $format ?>">
                                            <i class="fas fa-file-<?= $format === 'excel' ? 'excel' : 'pdf' ?> me-2"></i>
                                            <?= strtoupper($format) ?>
                                            <small class="text-muted d-block">
                                                <?= $format === 'pdf' ? 'Für Ausdrucke und Archivierung' : 'Für Datenanalyse und Weiterverarbeitung' ?>
                                            </small>
                                        </label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Spezielle Auswahl für Prüfungsprotokoll -->
                        <?php if ($reportType === 'inspection_report'): ?>
                        <div class="mb-4">
                            <label for="ladderSelect" class="form-label fw-bold">Leiter auswählen *</label>
                            <select class="form-select" name="ladder_id" id="ladderSelect" required>
                                <option value="">Bitte wählen Sie eine Leiter aus...</option>
                                <?php foreach ($availableLadders as $ladder): ?>
                                <option value="<?= $ladder->getId() ?>" 
                                        data-location="<?= htmlspecialchars($ladder->getLocation()) ?>"
                                        data-type="<?= htmlspecialchars($ladder->getLadderType()) ?>">
                                    <?= htmlspecialchars($ladder->getLadderNumber()) ?> - 
                                    <?= htmlspecialchars($ladder->getManufacturer()) ?> 
                                    (<?= htmlspecialchars($ladder->getLocation()) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">
                                Wählen Sie die Leiter aus, für die das Prüfungsprotokoll erstellt werden soll.
                            </div>
                        </div>

                        <div class="mb-4" id="inspectionSelection" style="display: none;">
                            <label for="inspectionSelect" class="form-label fw-bold">Prüfung auswählen</label>
                            <select class="form-select" name="inspection_id" id="inspectionSelect">
                                <option value="">Neueste Prüfung verwenden</option>
                            </select>
                            <div class="form-text">
                                Lassen Sie das Feld leer, um die neueste Prüfung zu verwenden.
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Zeitraum-Filter -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Zeitraum</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="dateFrom" class="form-label">Von Datum</label>
                                    <input type="date" class="form-control" name="filters[date_from]" 
                                           id="dateFrom" value="<?= date('Y-m-01') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="dateTo" class="form-label">Bis Datum</label>
                                    <input type="date" class="form-control" name="filters[date_to]" 
                                           id="dateTo" value="<?= date('Y-m-d') ?>">
                                </div>
                            </div>
                            <div class="form-text">
                                Zeitraum für die Datenauswahl. Lassen Sie die Felder leer für alle verfügbaren Daten.
                            </div>
                        </div>

                        <!-- Filter-Optionen -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Filter-Optionen</label>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="locationFilter" class="form-label">Standort</label>
                                        <input type="text" class="form-control" name="filters[location]" 
                                               id="locationFilter" placeholder="z.B. Lager A, Werkstatt...">
                                        <datalist id="locationSuggestions">
                                            <?php foreach ($availableLocations as $location): ?>
                                            <option value="<?= htmlspecialchars($location) ?>">
                                            <?php endforeach; ?>
                                        </datalist>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="departmentFilter" class="form-label">Abteilung</label>
                                        <input type="text" class="form-control" name="filters[department]" 
                                               id="departmentFilter" placeholder="z.B. Produktion, Wartung...">
                                        <datalist id="departmentSuggestions">
                                            <?php foreach ($availableDepartments as $department): ?>
                                            <option value="<?= htmlspecialchars($department) ?>">
                                            <?php endforeach; ?>
                                        </datalist>
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

                            <?php if (in_array($reportType, ['statistics_report', 'failure_report'])): ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="inspectionTypeFilter" class="form-label">Prüfungstyp</label>
                                        <select class="form-select" name="filters[inspection_type]" id="inspectionTypeFilter">
                                            <option value="">Alle Typen</option>
                                            <option value="routine">Routine</option>
                                            <option value="initial">Erstprüfung</option>
                                            <option value="after_incident">Nach Vorfall</option>
                                            <option value="special">Sonderprüfung</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="resultFilter" class="form-label">Prüfungsergebnis</label>
                                        <select class="form-select" name="filters[overall_result]" id="resultFilter">
                                            <option value="">Alle Ergebnisse</option>
                                            <option value="passed">Bestanden</option>
                                            <option value="failed">Nicht bestanden</option>
                                            <option value="conditional">Bedingt bestanden</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Erweiterte Optionen -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="options[include_images]" 
                                       id="includeImages" value="1">
                                <label class="form-check-label" for="includeImages">
                                    Bilder in Bericht einschließen
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="options[detailed_statistics]" 
                                       id="detailedStats" value="1" checked>
                                <label class="form-check-label" for="detailedStats">
                                    Detaillierte Statistiken einschließen
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="options[include_history]" 
                                       id="includeHistory" value="1">
                                <label class="form-check-label" for="includeHistory">
                                    Historische Daten einschließen
                                </label>
                            </div>
                        </div>

                        <!-- Aktionen -->
                        <div class="d-flex justify-content-between">
                            <a href="/reports/" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>
                                Zurück
                            </a>
                            <div>
                                <button type="button" class="btn btn-outline-primary me-2" id="previewBtn">
                                    <i class="fas fa-eye me-1"></i>
                                    Vorschau
                                </button>
                                <button type="submit" class="btn btn-primary" id="generateBtn">
                                    <i class="fas fa-download me-1"></i>
                                    Bericht generieren
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar mit Informationen -->
        <div class="col-lg-4">
            <!-- Berichts-Informationen -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Berichts-Informationen
                    </h6>
                </div>
                <div class="card-body">
                    <dl class="row small">
                        <dt class="col-sm-5">Typ:</dt>
                        <dd class="col-sm-7"><?= htmlspecialchars($reportConfig['title']) ?></dd>
                        
                        <dt class="col-sm-5">Formate:</dt>
                        <dd class="col-sm-7">
                            <?php foreach ($reportConfig['formats'] as $format): ?>
                            <span class="badge bg-secondary me-1"><?= strtoupper($format) ?></span>
                            <?php endforeach; ?>
                        </dd>
                        
                        <dt class="col-sm-5">Geschätzte Größe:</dt>
                        <dd class="col-sm-7">
                            <span id="estimatedSize">Wird berechnet...</span>
                        </dd>
                        
                        <dt class="col-sm-5">Generierungszeit:</dt>
                        <dd class="col-sm-7">
                            <span id="estimatedTime">~30 Sekunden</span>
                        </dd>
                    </dl>
                </div>
            </div>

            <!-- Datenvorschau -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-chart-pie me-2"></i>
                        Datenvorschau
                    </h6>
                </div>
                <div class="card-body">
                    <div id="dataPreview">
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                            <p class="mb-0">Lade Datenvorschau...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hilfe -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-question-circle me-2"></i>
                        Hilfe
                    </h6>
                </div>
                <div class="card-body">
                    <div class="accordion accordion-flush" id="helpAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#helpFilters">
                                    Filter verwenden
                                </button>
                            </h2>
                            <div id="helpFilters" class="accordion-collapse collapse" 
                                 data-bs-parent="#helpAccordion">
                                <div class="accordion-body small">
                                    Verwenden Sie Filter, um die Daten einzugrenzen. 
                                    Lassen Sie Felder leer, um alle verfügbaren Daten einzuschließen.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#helpFormats">
                                    Export-Formate
                                </button>
                            </h2>
                            <div id="helpFormats" class="accordion-collapse collapse" 
                                 data-bs-parent="#helpAccordion">
                                <div class="accordion-body small">
                                    <strong>PDF:</strong> Ideal für Ausdrucke und Archivierung.<br>
                                    <strong>Excel:</strong> Für weitere Datenanalyse und Bearbeitung.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Vorschau Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Berichts-Vorschau</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="previewContent">
                    <!-- Wird dynamisch geladen -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                <button type="button" class="btn btn-primary" id="generateFromPreview">
                    <i class="fas fa-download me-1"></i>
                    Bericht generieren
                </button>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
// Berichts-Formular JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('reportForm');
    const ladderSelect = document.getElementById('ladderSelect');
    const inspectionSelect = document.getElementById('inspectionSelect');
    const previewBtn = document.getElementById('previewBtn');
    const generateBtn = document.getElementById('generateBtn');
    
    // Leiter-Auswahl Handler
    if (ladderSelect) {
        ladderSelect.addEventListener('change', function() {
            const ladderId = this.value;
            const inspectionSelection = document.getElementById('inspectionSelection');
            
            if (ladderId) {
                inspectionSelection.style.display = 'block';
                loadInspections(ladderId);
            } else {
                inspectionSelection.style.display = 'none';
            }
        });
    }
    
    // Datenvorschau laden
    function loadDataPreview() {
        const formData = new FormData(form);
        
        fetch('/reports/preview.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            updateDataPreview(data);
        })
        .catch(error => {
            console.error('Fehler beim Laden der Datenvorschau:', error);
        });
    }
    
    // Prüfungen für Leiter laden
    function loadInspections(ladderId) {
        fetch(`/api/ladders/${ladderId}/inspections`)
        .then(response => response.json())
        .then(data => {
            inspectionSelect.innerHTML = '<option value="">Neueste Prüfung verwenden</option>';
            data.forEach(inspection => {
                const option = document.createElement('option');
                option.value = inspection.id;
                option.textContent = `${inspection.inspection_date} - ${inspection.overall_result}`;
                inspectionSelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Fehler beim Laden der Prüfungen:', error);
        });
    }
    
    // Datenvorschau aktualisieren
    function updateDataPreview(data) {
        const preview = document.getElementById('dataPreview');
        preview.innerHTML = `
            <div class="row text-center">
                <div class="col-4">
                    <div class="h4 text-primary">${data.total_records || 0}</div>
                    <small class="text-muted">Datensätze</small>
                </div>
                <div class="col-4">
                    <div class="h4 text-info">${data.estimated_pages || 1}</div>
                    <small class="text-muted">Seiten</small>
                </div>
                <div class="col-4">
                    <div class="h4 text-success">${data.estimated_size || '< 1 MB'}</div>
                    <small class="text-muted">Größe</small>
                </div>
            </div>
        `;
    }
    
    // Vorschau anzeigen
    previewBtn.addEventListener('click', function() {
        const modal = new bootstrap.Modal(document.getElementById('previewModal'));
        modal.show();
        
        // Vorschau-Inhalt laden
        const formData = new FormData(form);
        formData.append('preview', '1');
        
        fetch('/reports/generate.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(html => {
            document.getElementById('previewContent').innerHTML = html;
        })
        .catch(error => {
            console.error('Fehler beim Laden der Vorschau:', error);
        });
    });
    
    // Formular-Validierung
    form.addEventListener('submit', function(e) {
        const reportType = document.querySelector('input[name="report_type"]').value;
        
        if (reportType === 'inspection_report') {
            const ladderId = ladderSelect.value;
            if (!ladderId) {
                e.preventDefault();
                alert('Bitte wählen Sie eine Leiter aus.');
                ladderSelect.focus();
                return;
            }
        }
        
        // Loading-Zustand
        generateBtn.disabled = true;
        generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Generiere...';
    });
    
    // Filter-Änderungen überwachen
    const filterInputs = form.querySelectorAll('input, select');
    filterInputs.forEach(input => {
        input.addEventListener('change', debounce(loadDataPreview, 500));
    });
    
    // Debounce-Funktion
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // Initiale Datenvorschau laden
    loadDataPreview();
});
</script>
<?php $this->endSection(); ?>
