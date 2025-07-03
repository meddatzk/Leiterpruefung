<?php
/**
 * Template für Prüfungs-Formular (Erstellen/Bearbeiten)
 */

// Hilfsfunktionen
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

function getResultLabel($result) {
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

function getTypeLabel($type) {
    $labels = [
        'routine' => 'Routine',
        'initial' => 'Erstprüfung',
        'after_incident' => 'Nach Vorfall',
        'special' => 'Sonderprüfung'
    ];
    return $labels[$type] ?? ucfirst($type);
}
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><?= $inspection ? 'Prüfung bearbeiten' : 'Neue Prüfung' ?></h1>
            <p class="text-muted">
                <?php if ($ladder): ?>
                    Prüfung für Leiter: <strong><?= htmlspecialchars($ladder->getLadderNumber()) ?></strong>
                    (<?= htmlspecialchars($ladder->getLadderType()) ?>)
                <?php else: ?>
                    Erstellen Sie eine neue Leiterprüfung
                <?php endif; ?>
            </p>
        </div>
        <div>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Zurück zur Übersicht
            </a>
        </div>
    </div>

    <!-- Fehler anzeigen -->
    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <h5><i class="fas fa-exclamation-triangle"></i> Fehler</h5>
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Erfolg anzeigen -->
    <?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        Prüfung wurde erfolgreich gespeichert!
    </div>
    <?php endif; ?>

    <form method="POST" id="inspection-form" class="needs-validation" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        
        <div class="row">
            <!-- Linke Spalte: Grunddaten -->
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle"></i>
                            Grunddaten
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Leiter auswählen -->
                        <div class="mb-3">
                            <label for="ladder_id" class="form-label">Leiter <span class="text-danger">*</span></label>
                            <select class="form-select" id="ladder_id" name="ladder_id" required 
                                    onchange="loadLadderInfo(this.value)">
                                <option value="">Leiter auswählen...</option>
                                <?php foreach ($availableLadders as $availableLadder): ?>
                                    <option value="<?= $availableLadder->getId() ?>" 
                                            <?= ($ladder && $ladder->getId() == $availableLadder->getId()) ? 'selected' : '' ?>
                                            data-type="<?= htmlspecialchars($availableLadder->getLadderType()) ?>"
                                            data-material="<?= htmlspecialchars($availableLadder->getMaterial()) ?>">
                                        <?= htmlspecialchars($availableLadder->getLadderNumber()) ?> - 
                                        <?= htmlspecialchars($availableLadder->getLadderType()) ?>
                                        (<?= htmlspecialchars($availableLadder->getLocation()) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">
                                Bitte wählen Sie eine Leiter aus.
                            </div>
                        </div>

                        <!-- Leiter-Info -->
                        <?php if ($ladder): ?>
                        <div class="alert alert-info" id="ladder-info">
                            <strong><?= htmlspecialchars($ladder->getLadderNumber()) ?></strong><br>
                            <small>
                                <?= htmlspecialchars($ladder->getManufacturer()) ?> 
                                <?= htmlspecialchars($ladder->getModel()) ?><br>
                                Typ: <?= htmlspecialchars($ladder->getLadderType()) ?><br>
                                Material: <?= htmlspecialchars($ladder->getMaterial()) ?><br>
                                Standort: <?= htmlspecialchars($ladder->getLocation()) ?>
                            </small>
                        </div>
                        <?php endif; ?>

                        <!-- Prüfdatum -->
                        <div class="mb-3">
                            <label for="inspection_date" class="form-label">Prüfdatum <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="inspection_date" name="inspection_date" 
                                   value="<?= $inspection ? $inspection->getInspectionDate() : date('Y-m-d') ?>" required>
                            <div class="invalid-feedback">
                                Bitte geben Sie das Prüfdatum ein.
                            </div>
                        </div>

                        <!-- Prüfungstyp -->
                        <div class="mb-3">
                            <label for="inspection_type" class="form-label">Prüfungstyp <span class="text-danger">*</span></label>
                            <select class="form-select" id="inspection_type" name="inspection_type" required>
                                <?php foreach ($inspectionTypes as $type): ?>
                                    <option value="<?= $type ?>" 
                                            <?= ($inspection && $inspection->getInspectionType() == $type) ? 'selected' : '' ?>>
                                        <?= getTypeLabel($type) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">
                                Bitte wählen Sie einen Prüfungstyp aus.
                            </div>
                        </div>

                        <!-- Nächstes Prüfdatum -->
                        <div class="mb-3">
                            <label for="next_inspection_date" class="form-label">Nächste Prüfung <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="next_inspection_date" name="next_inspection_date" 
                                   value="<?= $inspection ? $inspection->getNextInspectionDate() : $nextInspectionDate ?>" required>
                            <div class="invalid-feedback">
                                Bitte geben Sie das nächste Prüfdatum ein.
                            </div>
                        </div>

                        <!-- Prüfdauer -->
                        <div class="mb-3">
                            <label for="inspection_duration_minutes" class="form-label">Prüfdauer (Minuten)</label>
                            <input type="number" class="form-control" id="inspection_duration_minutes" 
                                   name="inspection_duration_minutes" min="1" max="480"
                                   value="<?= $inspection ? $inspection->getInspectionDurationMinutes() : '' ?>">
                        </div>

                        <!-- Wetterbedingungen -->
                        <div class="mb-3">
                            <label for="weather_conditions" class="form-label">Wetterbedingungen</label>
                            <input type="text" class="form-control" id="weather_conditions" name="weather_conditions" 
                                   value="<?= $inspection ? htmlspecialchars($inspection->getWeatherConditions()) : '' ?>"
                                   placeholder="z.B. Sonnig, trocken">
                        </div>

                        <!-- Temperatur -->
                        <div class="mb-3">
                            <label for="temperature_celsius" class="form-label">Temperatur (°C)</label>
                            <input type="number" class="form-control" id="temperature_celsius" name="temperature_celsius" 
                                   min="-50" max="60"
                                   value="<?= $inspection ? $inspection->getTemperatureCelsius() : '' ?>">
                        </div>
                    </div>
                </div>

                <!-- Zusätzliche Informationen -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-sticky-note"></i>
                            Zusätzliche Informationen
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Allgemeine Notizen -->
                        <div class="mb-3">
                            <label for="general_notes" class="form-label">Allgemeine Notizen</label>
                            <textarea class="form-control" id="general_notes" name="general_notes" rows="3"
                                      placeholder="Allgemeine Bemerkungen zur Prüfung..."><?= $inspection ? htmlspecialchars($inspection->getGeneralNotes()) : '' ?></textarea>
                        </div>

                        <!-- Empfehlungen -->
                        <div class="mb-3">
                            <label for="recommendations" class="form-label">Empfehlungen</label>
                            <textarea class="form-control" id="recommendations" name="recommendations" rows="3"
                                      placeholder="Empfehlungen für zukünftige Prüfungen..."><?= $inspection ? htmlspecialchars($inspection->getRecommendations()) : '' ?></textarea>
                        </div>

                        <!-- Gefundene Mängel -->
                        <div class="mb-3">
                            <label for="defects_found" class="form-label">Gefundene Mängel</label>
                            <textarea class="form-control" id="defects_found" name="defects_found" rows="3"
                                      placeholder="Zusammenfassung der gefundenen Mängel..."><?= $inspection ? htmlspecialchars($inspection->getDefectsFound()) : '' ?></textarea>
                        </div>

                        <!-- Erforderliche Maßnahmen -->
                        <div class="mb-3">
                            <label for="actions_required" class="form-label">Erforderliche Maßnahmen</label>
                            <textarea class="form-control" id="actions_required" name="actions_required" rows="3"
                                      placeholder="Welche Maßnahmen sind erforderlich..."><?= $inspection ? htmlspecialchars($inspection->getActionsRequired()) : '' ?></textarea>
                        </div>

                        <!-- Prüfer-Unterschrift -->
                        <div class="mb-3">
                            <label for="inspector_signature" class="form-label">Prüfer-Unterschrift</label>
                            <input type="text" class="form-control" id="inspector_signature" name="inspector_signature" 
                                   value="<?= $inspection ? htmlspecialchars($inspection->getInspectorSignature()) : htmlspecialchars($user['display_name'] ?? '') ?>"
                                   placeholder="Name des Prüfers">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rechte Spalte: Prüfpunkte -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-tasks"></i>
                            Prüfpunkte
                            <span class="badge badge-secondary" id="items-count"><?= count($inspectionItems) ?></span>
                        </h5>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addInspectionItem()">
                                <i class="fas fa-plus"></i> Prüfpunkt hinzufügen
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="loadTemplate()">
                                <i class="fas fa-clipboard-list"></i> Vorlage laden
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Gesamtstatus-Anzeige -->
                        <div class="alert alert-info" id="overall-status">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>Gesamtstatus:</strong>
                                    <span id="overall-result-text">Wird automatisch berechnet</span>
                                </div>
                                <div>
                                    <span class="badge badge-lg" id="overall-result-badge">-</span>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small id="defect-summary">Keine Mängel gefunden</small>
                            </div>
                        </div>

                        <!-- Prüfpunkte Container -->
                        <div id="inspection-items-container">
                            <?php if (!empty($inspectionItems)): ?>
                                <?php foreach ($inspectionItems as $index => $item): ?>
                                    <?php include __DIR__ . '/inspection_item_row.php'; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Leerer Zustand -->
                        <div id="empty-state" class="text-center py-5" style="<?= !empty($inspectionItems) ? 'display: none;' : '' ?>">
                            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Keine Prüfpunkte vorhanden</h5>
                            <p class="text-muted">Fügen Sie Prüfpunkte hinzu oder laden Sie eine Vorlage.</p>
                            <button type="button" class="btn btn-primary" onclick="addInspectionItem()">
                                <i class="fas fa-plus"></i> Ersten Prüfpunkt hinzufügen
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Aktions-Buttons -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i>
                                    Nach dem Speichern kann die Prüfung nicht mehr bearbeitet werden.
                                </small>
                            </div>
                            <div>
                                <a href="index.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> Abbrechen
                                </a>
                                <button type="submit" class="btn btn-success" id="save-button">
                                    <i class="fas fa-save"></i> Prüfung speichern
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Template für neue Prüfpunkte -->
<template id="inspection-item-template">
    <div class="inspection-item border rounded p-3 mb-3" data-index="__INDEX__">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <h6 class="mb-0">
                <span class="item-category-label">Neue Kategorie</span> - 
                <span class="item-name-display">Neuer Prüfpunkt</span>
            </h6>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeInspectionItem(__INDEX__)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        
        <div class="row">
            <div class="col-md-3">
                <label class="form-label">Kategorie</label>
                <select class="form-select form-select-sm" name="inspection_items[__INDEX__][category]" 
                        onchange="updateItemDisplay(__INDEX__)" required>
                    <?php foreach ($itemCategories as $category): ?>
                        <option value="<?= $category ?>"><?= getCategoryLabel($category) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Prüfpunkt</label>
                <input type="text" class="form-control form-control-sm" 
                       name="inspection_items[__INDEX__][item_name]" 
                       onchange="updateItemDisplay(__INDEX__)" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Ergebnis</label>
                <select class="form-select form-select-sm" name="inspection_items[__INDEX__][result]" 
                        onchange="handleResultChange(__INDEX__)" required>
                    <?php foreach ($itemResults as $result): ?>
                        <option value="<?= $result ?>"><?= getResultLabel($result) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Schweregrad</label>
                <select class="form-select form-select-sm" name="inspection_items[__INDEX__][severity]" 
                        style="display: none;">
                    <option value="">-</option>
                    <?php foreach ($itemSeverities as $severity): ?>
                        <option value="<?= $severity ?>"><?= getSeverityLabel($severity) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="row mt-2">
            <div class="col-md-6">
                <label class="form-label">Beschreibung</label>
                <input type="text" class="form-control form-control-sm" 
                       name="inspection_items[__INDEX__][description]">
            </div>
            <div class="col-md-6">
                <label class="form-label">Notizen</label>
                <input type="text" class="form-control form-control-sm" 
                       name="inspection_items[__INDEX__][notes]">
            </div>
        </div>
        
        <div class="row mt-2 repair-section" style="display: none;">
            <div class="col-md-6">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" 
                           name="inspection_items[__INDEX__][repair_required]" value="1">
                    <label class="form-check-label">Reparatur erforderlich</label>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Reparatur-Deadline</label>
                <input type="date" class="form-control form-control-sm" 
                       name="inspection_items[__INDEX__][repair_deadline]">
            </div>
        </div>
    </div>
</template>

<!-- JavaScript für dynamische Funktionalität -->
<script src="../../src/assets/js/inspection_form.js"></script>
