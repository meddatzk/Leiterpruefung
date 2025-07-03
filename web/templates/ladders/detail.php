<?php
/**
 * Leiter-Detail-Template
 * Zeigt alle Details einer Leiter in Tabs an
 */
?>

<!-- Status-Alerts -->
<?php if ($needs_inspection): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <h5><i class="bi bi-exclamation-triangle"></i> Prüfung überfällig!</h5>
    <p class="mb-0">
        Diese Leiter ist seit <?= abs($days_until_inspection) ?> Tagen prüfpflichtig. 
        Bitte veranlassen Sie umgehend eine Prüfung.
    </p>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php elseif ($days_until_inspection <= 30): ?>
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <h5><i class="bi bi-clock"></i> Prüfung bald fällig</h5>
    <p class="mb-0">
        Die nächste Prüfung dieser Leiter ist in <?= $days_until_inspection ?> Tagen fällig.
    </p>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Hauptinformationen -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h3 class="card-title mb-3">
                            <?= $this->e($ladder->getLadderNumber()) ?>
                            <span class="badge bg-<?= $status_info['class'] ?> ms-2">
                                <i class="bi bi-<?= $status_info['icon'] ?>"></i>
                                <?= $status_info['text'] ?>
                            </span>
                        </h3>
                        
                        <dl class="row">
                            <dt class="col-sm-4">Hersteller:</dt>
                            <dd class="col-sm-8"><?= $this->e($ladder->getManufacturer()) ?></dd>
                            
                            <?php if ($ladder->getModel()): ?>
                            <dt class="col-sm-4">Modell:</dt>
                            <dd class="col-sm-8"><?= $this->e($ladder->getModel()) ?></dd>
                            <?php endif; ?>
                            
                            <dt class="col-sm-4">Typ:</dt>
                            <dd class="col-sm-8">
                                <span class="badge bg-secondary"><?= $this->e($ladder->getLadderType()) ?></span>
                            </dd>
                            
                            <dt class="col-sm-4">Material:</dt>
                            <dd class="col-sm-8">
                                <span class="badge bg-info"><?= $this->e($ladder->getMaterial()) ?></span>
                            </dd>
                        </dl>
                    </div>
                    
                    <div class="col-md-6">
                        <h5>Technische Daten</h5>
                        <dl class="row">
                            <dt class="col-sm-5">Höhe:</dt>
                            <dd class="col-sm-7"><?= number_format($ladder->getHeightCm()) ?> cm</dd>
                            
                            <dt class="col-sm-5">Max. Belastung:</dt>
                            <dd class="col-sm-7"><?= number_format($ladder->getMaxLoadKg()) ?> kg</dd>
                            
                            <?php if ($ladder->getSerialNumber()): ?>
                            <dt class="col-sm-5">Seriennummer:</dt>
                            <dd class="col-sm-7">
                                <code><?= $this->e($ladder->getSerialNumber()) ?></code>
                            </dd>
                            <?php endif; ?>
                            
                            <?php if ($ladder->getPurchaseDate()): ?>
                            <dt class="col-sm-5">Kaufdatum:</dt>
                            <dd class="col-sm-7">
                                <?php
                                $purchaseDate = new DateTime($ladder->getPurchaseDate());
                                echo $purchaseDate->format('d.m.Y');
                                ?>
                            </dd>
                            <?php endif; ?>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Prüfstatus -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-calendar-check"></i> Prüfstatus
                </h5>
            </div>
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="bi bi-<?= $inspection_info['icon'] ?> display-4 text-<?= $inspection_info['class'] ?>"></i>
                </div>
                <h5 class="text-<?= $inspection_info['class'] ?>">
                    <?= $inspection_info['text'] ?>
                </h5>
                <p class="mb-2">
                    <strong>Nächste Prüfung:</strong><br>
                    <?php
                    $nextInspection = new DateTime($ladder->getNextInspectionDate());
                    echo $nextInspection->format('d.m.Y');
                    ?>
                </p>
                <p class="text-muted">
                    <?php if ($needs_inspection): ?>
                        <span class="text-danger">
                            <?= abs($days_until_inspection) ?> Tage überfällig
                        </span>
                    <?php else: ?>
                        in <?= $days_until_inspection ?> Tagen
                    <?php endif; ?>
                </p>
                <small class="text-muted">
                    Prüfintervall: <?= $ladder->getInspectionIntervalMonths() ?> Monate
                </small>
            </div>
        </div>
        
        <!-- Schnellaktionen -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-lightning"></i> Schnellaktionen
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="edit.php?id=<?= $ladder->getId() ?>" class="btn btn-primary">
                        <i class="bi bi-pencil"></i> Bearbeiten
                    </a>
                    <a href="print.php?id=<?= $ladder->getId() ?>" class="btn btn-outline-secondary" target="_blank">
                        <i class="bi bi-printer"></i> Drucken
                    </a>
                    <a href="qr.php?id=<?= $ladder->getId() ?>" class="btn btn-outline-info" target="_blank">
                        <i class="bi bi-qr-code"></i> QR-Code
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detail-Tabs -->
<div class="card">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" id="detailTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="location-tab" data-bs-toggle="tab" data-bs-target="#location" type="button" role="tab">
                    <i class="bi bi-geo-alt"></i> Standort & Zuständigkeit
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="inspection-tab" data-bs-toggle="tab" data-bs-target="#inspection" type="button" role="tab">
                    <i class="bi bi-calendar-check"></i> Prüfungen
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab">
                    <i class="bi bi-clock-history"></i> Verlauf
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="notes-tab" data-bs-toggle="tab" data-bs-target="#notes" type="button" role="tab">
                    <i class="bi bi-chat-text"></i> Notizen
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content" id="detailTabsContent">
            <!-- Standort & Zuständigkeit -->
            <div class="tab-pane fade show active" id="location" role="tabpanel">
                <div class="row">
                    <div class="col-md-6">
                        <h5><i class="bi bi-geo-alt"></i> Standortinformationen</h5>
                        <dl class="row">
                            <dt class="col-sm-4">Standort:</dt>
                            <dd class="col-sm-8">
                                <strong><?= $this->e($ladder->getLocation()) ?></strong>
                            </dd>
                            
                            <?php if ($ladder->getDepartment()): ?>
                            <dt class="col-sm-4">Abteilung:</dt>
                            <dd class="col-sm-8"><?= $this->e($ladder->getDepartment()) ?></dd>
                            <?php endif; ?>
                            
                            <?php if ($ladder->getResponsiblePerson()): ?>
                            <dt class="col-sm-4">Verantwortlich:</dt>
                            <dd class="col-sm-8"><?= $this->e($ladder->getResponsiblePerson()) ?></dd>
                            <?php endif; ?>
                        </dl>
                    </div>
                    
                    <div class="col-md-6">
                        <h5><i class="bi bi-info-circle"></i> Systeminformationen</h5>
                        <dl class="row">
                            <dt class="col-sm-4">Erstellt:</dt>
                            <dd class="col-sm-8">
                                <?php
                                if ($ladder->getCreatedAt()) {
                                    $created = new DateTime($ladder->getCreatedAt());
                                    echo $created->format('d.m.Y H:i');
                                } else {
                                    echo 'Unbekannt';
                                }
                                ?>
                            </dd>
                            
                            <?php if ($ladder->getUpdatedAt()): ?>
                            <dt class="col-sm-4">Geändert:</dt>
                            <dd class="col-sm-8">
                                <?php
                                $updated = new DateTime($ladder->getUpdatedAt());
                                echo $updated->format('d.m.Y H:i');
                                ?>
                            </dd>
                            <?php endif; ?>
                            
                            <dt class="col-sm-4">ID:</dt>
                            <dd class="col-sm-8">
                                <code><?= $ladder->getId() ?></code>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            
            <!-- Prüfungen -->
            <div class="tab-pane fade" id="inspection" role="tabpanel">
                <div class="row">
                    <div class="col-md-6">
                        <h5><i class="bi bi-calendar-check"></i> Aktuelle Prüfung</h5>
                        <div class="card bg-light">
                            <div class="card-body">
                                <dl class="row mb-0">
                                    <dt class="col-sm-5">Nächste Prüfung:</dt>
                                    <dd class="col-sm-7">
                                        <span class="text-<?= $inspection_info['class'] ?> fw-bold">
                                            <?php
                                            $nextInspection = new DateTime($ladder->getNextInspectionDate());
                                            echo $nextInspection->format('d.m.Y');
                                            ?>
                                        </span>
                                    </dd>
                                    
                                    <dt class="col-sm-5">Status:</dt>
                                    <dd class="col-sm-7">
                                        <span class="badge bg-<?= $inspection_info['class'] ?>">
                                            <i class="bi bi-<?= $inspection_info['icon'] ?>"></i>
                                            <?= $inspection_info['text'] ?>
                                        </span>
                                    </dd>
                                    
                                    <dt class="col-sm-5">Intervall:</dt>
                                    <dd class="col-sm-7">
                                        <?= $ladder->getInspectionIntervalMonths() ?> Monate
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h5><i class="bi bi-tools"></i> Prüfaktionen</h5>
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-success" disabled>
                                <i class="bi bi-check-circle"></i> Prüfung durchführen
                            </button>
                            <button type="button" class="btn btn-outline-primary" disabled>
                                <i class="bi bi-calendar-plus"></i> Prüftermin planen
                            </button>
                            <button type="button" class="btn btn-outline-secondary" disabled>
                                <i class="bi bi-file-text"></i> Prüfprotokoll erstellen
                            </button>
                        </div>
                        <small class="text-muted mt-2 d-block">
                            <i class="bi bi-info-circle"></i>
                            Prüffunktionen sind in dieser Version noch nicht verfügbar.
                        </small>
                    </div>
                </div>
                
                <!-- Prüfhistorie Platzhalter -->
                <hr class="my-4">
                <h5><i class="bi bi-clock-history"></i> Prüfhistorie</h5>
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-calendar-x display-4"></i>
                    <p class="mt-2">Noch keine Prüfungen durchgeführt</p>
                    <small>Prüfungen werden hier angezeigt, sobald sie durchgeführt wurden.</small>
                </div>
            </div>
            
            <!-- Verlauf -->
            <div class="tab-pane fade" id="history" role="tabpanel">
                <h5><i class="bi bi-clock-history"></i> Änderungsverlauf</h5>
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-journal-text display-4"></i>
                    <p class="mt-2">Verlauf wird geladen...</p>
                    <small>Hier werden alle Änderungen an dieser Leiter angezeigt.</small>
                </div>
            </div>
            
            <!-- Notizen -->
            <div class="tab-pane fade" id="notes" role="tabpanel">
                <div class="row">
                    <div class="col-md-8">
                        <h5><i class="bi bi-chat-text"></i> Bemerkungen</h5>
                        <?php if ($ladder->getNotes()): ?>
                        <div class="card bg-light">
                            <div class="card-body">
                                <p class="mb-0"><?= nl2br($this->e($ladder->getNotes())) ?></p>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-chat display-4"></i>
                            <p class="mt-2">Keine Bemerkungen vorhanden</p>
                            <a href="edit.php?id=<?= $ladder->getId() ?>" class="btn btn-outline-primary">
                                <i class="bi bi-plus-circle"></i> Bemerkung hinzufügen
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-4">
                        <h5><i class="bi bi-tags"></i> Zusatzinformationen</h5>
                        <div class="card bg-light">
                            <div class="card-body">
                                <small class="text-muted">
                                    <strong>Beschreibung:</strong><br>
                                    <?= $this->e($ladder->getDescription()) ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Zurück-Button -->
<div class="mt-4">
    <a href="index.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Zurück zur Übersicht
    </a>
</div>
