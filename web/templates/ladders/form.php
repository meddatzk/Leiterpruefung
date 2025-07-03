<?php
/**
 * Leiter-Formular-Template
 * Formular zum Erstellen und Bearbeiten von Leitern
 */
?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <h5><i class="bi bi-exclamation-triangle"></i> Fehler beim Speichern</h5>
    <ul class="mb-0">
        <?php foreach ($errors as $error): ?>
        <li><?= $this->e($error) ?></li>
        <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST" id="ladderForm" class="needs-validation" novalidate>
    <?= $this->csrfField() ?>
    
    <div class="row">
        <!-- Linke Spalte -->
        <div class="col-md-6">
            <!-- Grunddaten -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-info-circle"></i> Grunddaten
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="ladder_number" class="form-label">
                                    Leiternummer <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="ladder_number" name="ladder_number" 
                                       value="<?= $this->e($form_data['ladder_number'] ?? '') ?>" 
                                       placeholder="z.B. L-2024-0001" required>
                                <div class="form-text">
                                    Eindeutige Identifikationsnummer der Leiter
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <?php if (!$is_edit): ?>
                            <div class="mb-3">
                                <label class="form-label">&nbsp;</label>
                                <button type="button" class="btn btn-outline-secondary d-block" id="generateNumber">
                                    <i class="bi bi-magic"></i> Generieren
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="manufacturer" class="form-label">
                                    Hersteller <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="manufacturer" name="manufacturer" 
                                       value="<?= $this->e($form_data['manufacturer'] ?? '') ?>" 
                                       placeholder="z.B. Hailo, Zarges" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="model" class="form-label">Modell</label>
                                <input type="text" class="form-control" id="model" name="model" 
                                       value="<?= $this->e($form_data['model'] ?? '') ?>" 
                                       placeholder="z.B. ProfiStep, MultiMatic">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="ladder_type" class="form-label">
                                    Leitertyp <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="ladder_type" name="ladder_type" required>
                                    <option value="">Bitte wählen...</option>
                                    <?php foreach ($ladder_types as $type): ?>
                                    <option value="<?= $this->e($type) ?>" 
                                            <?= ($form_data['ladder_type'] ?? '') === $type ? 'selected' : '' ?>>
                                        <?= $this->e($type) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="material" class="form-label">
                                    Material <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="material" name="material" required>
                                    <?php foreach ($materials as $material): ?>
                                    <option value="<?= $this->e($material) ?>" 
                                            <?= ($form_data['material'] ?? 'Aluminium') === $material ? 'selected' : '' ?>>
                                        <?= $this->e($material) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="height_cm" class="form-label">
                                    Höhe (cm) <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control" id="height_cm" name="height_cm" 
                                       value="<?= $this->e($form_data['height_cm'] ?? '') ?>" 
                                       min="1" max="2000" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="max_load_kg" class="form-label">
                                    Max. Belastung (kg) <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control" id="max_load_kg" name="max_load_kg" 
                                       value="<?= $this->e($form_data['max_load_kg'] ?? 150) ?>" 
                                       min="1" max="1000" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="serial_number" class="form-label">Seriennummer</label>
                        <input type="text" class="form-control" id="serial_number" name="serial_number" 
                               value="<?= $this->e($form_data['serial_number'] ?? '') ?>" 
                               placeholder="Herstellerseriennummer">
                    </div>
                </div>
            </div>
            
            <!-- Standort und Zuständigkeit -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-geo-alt"></i> Standort und Zuständigkeit
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="location" class="form-label">
                            Standort <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="location" name="location" 
                               value="<?= $this->e($form_data['location'] ?? '') ?>" 
                               placeholder="z.B. Werkstatt, Lager A, Gebäude 1" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="department" class="form-label">Abteilung</label>
                        <input type="text" class="form-control" id="department" name="department" 
                               value="<?= $this->e($form_data['department'] ?? '') ?>" 
                               placeholder="z.B. Instandhaltung, Produktion">
                    </div>
                    
                    <div class="mb-3">
                        <label for="responsible_person" class="form-label">Verantwortliche Person</label>
                        <input type="text" class="form-control" id="responsible_person" name="responsible_person" 
                               value="<?= $this->e($form_data['responsible_person'] ?? '') ?>" 
                               placeholder="Name der verantwortlichen Person">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Rechte Spalte -->
        <div class="col-md-6">
            <!-- Prüfung und Status -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-calendar-check"></i> Prüfung und Status
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label">
                                    Status <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="status" name="status" required>
                                    <?php foreach ($statuses as $status): ?>
                                    <option value="<?= $this->e($status) ?>" 
                                            <?= ($form_data['status'] ?? 'active') === $status ? 'selected' : '' ?>>
                                        <?= $this->e(ucfirst($status)) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="inspection_interval_months" class="form-label">
                                    Prüfintervall (Monate) <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control" id="inspection_interval_months" 
                                       name="inspection_interval_months" 
                                       value="<?= $this->e($form_data['inspection_interval_months'] ?? 12) ?>" 
                                       min="1" max="60" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="next_inspection_date" class="form-label">
                            Nächste Prüfung <span class="text-danger">*</span>
                        </label>
                        <input type="date" class="form-control" id="next_inspection_date" 
                               name="next_inspection_date" 
                               value="<?= $this->e($form_data['next_inspection_date'] ?? '') ?>" required>
                        <div class="form-text">
                            Datum der nächsten fälligen Prüfung
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="purchase_date" class="form-label">Kaufdatum</label>
                        <input type="date" class="form-control" id="purchase_date" name="purchase_date" 
                               value="<?= $this->e($form_data['purchase_date'] ?? '') ?>">
                        <div class="form-text">
                            Datum des Kaufs oder der Inbetriebnahme
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Notizen -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-chat-text"></i> Notizen
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="notes" class="form-label">Bemerkungen</label>
                        <textarea class="form-control" id="notes" name="notes" rows="4" 
                                  placeholder="Zusätzliche Informationen, Besonderheiten, etc."><?= $this->e($form_data['notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Vorschau -->
            <?php if (!empty($form_data['ladder_number']) || !empty($form_data['manufacturer'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-eye"></i> Vorschau
                    </h5>
                </div>
                <div class="card-body">
                    <div class="ladder-preview">
                        <h6 class="fw-bold">
                            <?= $this->e($form_data['ladder_number'] ?? 'Neue Leiter') ?>
                        </h6>
                        <p class="mb-1">
                            <strong><?= $this->e($form_data['manufacturer'] ?? '') ?></strong>
                            <?php if (!empty($form_data['model'])): ?>
                            - <?= $this->e($form_data['model']) ?>
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($form_data['ladder_type'])): ?>
                        <p class="mb-1">
                            <span class="badge bg-secondary"><?= $this->e($form_data['ladder_type']) ?></span>
                            <?php if (!empty($form_data['material'])): ?>
                            <span class="badge bg-info"><?= $this->e($form_data['material']) ?></span>
                            <?php endif; ?>
                        </p>
                        <?php endif; ?>
                        <?php if (!empty($form_data['height_cm'])): ?>
                        <p class="mb-1">
                            <small class="text-muted">
                                Höhe: <?= $this->e($form_data['height_cm']) ?>cm
                                <?php if (!empty($form_data['max_load_kg'])): ?>
                                | Max. Last: <?= $this->e($form_data['max_load_kg']) ?>kg
                                <?php endif; ?>
                            </small>
                        </p>
                        <?php endif; ?>
                        <?php if (!empty($form_data['location'])): ?>
                        <p class="mb-0">
                            <small class="text-muted">
                                <i class="bi bi-geo-alt"></i> <?= $this->e($form_data['location']) ?>
                            </small>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Aktionsbuttons -->
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="text-muted">
                        <small>
                            <i class="bi bi-info-circle"></i>
                            Felder mit <span class="text-danger">*</span> sind Pflichtfelder
                        </small>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <a href="index.php" class="btn btn-secondary me-2">
                        <i class="bi bi-arrow-left"></i> Zurück
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> 
                        <?= $is_edit ? 'Änderungen speichern' : 'Leiter erstellen' ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Live-Vorschau aktualisieren
    const previewFields = ['ladder_number', 'manufacturer', 'model', 'ladder_type', 'material', 'height_cm', 'max_load_kg', 'location'];
    
    function updatePreview() {
        // Hier könnte eine Live-Vorschau implementiert werden
        // Für jetzt reicht die statische Vorschau
    }
    
    previewFields.forEach(fieldName => {
        const field = document.getElementById(fieldName);
        if (field) {
            field.addEventListener('input', updatePreview);
            field.addEventListener('change', updatePreview);
        }
    });
    
    // Bootstrap-Validierung aktivieren
    const form = document.getElementById('ladderForm');
    if (form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    }
    
    // Tooltips aktivieren
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
