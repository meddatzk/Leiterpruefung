<div class="sidebar-content">
    <!-- Quick Actions -->
    <div class="sidebar-section">
        <h6 class="sidebar-heading">Schnellzugriff</h6>
        <div class="quick-actions">
            <a href="<?= $this->url('leitern/neu') ?>" class="btn btn-primary btn-sm w-100 mb-2">
                <i class="bi bi-plus-circle"></i> Neue Leiter
            </a>
            <a href="<?= $this->url('pruefungen/neu') ?>" class="btn btn-outline-primary btn-sm w-100 mb-2">
                <i class="bi bi-clipboard-plus"></i> Neue Prüfung
            </a>
        </div>
    </div>

    <!-- Status Overview -->
    <div class="sidebar-section">
        <h6 class="sidebar-heading">Status Übersicht</h6>
        <div class="status-cards">
            <!-- Fällige Prüfungen -->
            <div class="status-card status-warning">
                <div class="status-icon">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div class="status-content">
                    <div class="status-number"><?= $faellige_pruefungen ?? '0' ?></div>
                    <div class="status-label">Fällige Prüfungen</div>
                </div>
            </div>

            <!-- Überfällige Prüfungen -->
            <div class="status-card status-danger">
                <div class="status-icon">
                    <i class="bi bi-x-circle"></i>
                </div>
                <div class="status-content">
                    <div class="status-number"><?= $ueberfaellige_pruefungen ?? '0' ?></div>
                    <div class="status-label">Überfällig</div>
                </div>
            </div>

            <!-- Aktive Leitern -->
            <div class="status-card status-success">
                <div class="status-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="status-content">
                    <div class="status-number"><?= $aktive_leitern ?? '0' ?></div>
                    <div class="status-label">Aktive Leitern</div>
                </div>
            </div>

            <!-- Gesamt Leitern -->
            <div class="status-card status-info">
                <div class="status-icon">
                    <i class="bi bi-ladder"></i>
                </div>
                <div class="status-content">
                    <div class="status-number"><?= $gesamt_leitern ?? '0' ?></div>
                    <div class="status-label">Gesamt Leitern</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="sidebar-section">
        <h6 class="sidebar-heading">Letzte Aktivitäten</h6>
        <div class="activity-list">
            <?php if (isset($recent_activities) && !empty($recent_activities)): ?>
                <?php foreach ($recent_activities as $activity): ?>
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="bi <?= $this->e($activity['icon'] ?? 'bi-circle') ?>"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-text"><?= $this->e($activity['text']) ?></div>
                        <div class="activity-time"><?= $this->e($activity['time']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-activities">
                    <i class="bi bi-info-circle"></i>
                    <span>Keine aktuellen Aktivitäten</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="sidebar-section">
        <h6 class="sidebar-heading">Filter</h6>
        <div class="filter-options">
            <!-- Status Filter -->
            <div class="filter-group">
                <label class="filter-label">Status</label>
                <select class="form-select form-select-sm" id="statusFilter">
                    <option value="">Alle Status</option>
                    <option value="aktiv">Aktiv</option>
                    <option value="inaktiv">Inaktiv</option>
                    <option value="wartung">Wartung</option>
                    <option value="defekt">Defekt</option>
                </select>
            </div>

            <!-- Standort Filter -->
            <div class="filter-group">
                <label class="filter-label">Standort</label>
                <select class="form-select form-select-sm" id="standortFilter">
                    <option value="">Alle Standorte</option>
                    <?php if (isset($standorte) && !empty($standorte)): ?>
                        <?php foreach ($standorte as $standort): ?>
                        <option value="<?= $this->e($standort['id']) ?>"><?= $this->e($standort['name']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Kategorie Filter -->
            <div class="filter-group">
                <label class="filter-label">Kategorie</label>
                <select class="form-select form-select-sm" id="kategorieFilter">
                    <option value="">Alle Kategorien</option>
                    <?php if (isset($kategorien) && !empty($kategorien)): ?>
                        <?php foreach ($kategorien as $kategorie): ?>
                        <option value="<?= $this->e($kategorie['id']) ?>"><?= $this->e($kategorie['name']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Filter Actions -->
            <div class="filter-actions">
                <button type="button" class="btn btn-primary btn-sm" id="applyFilters">
                    <i class="bi bi-funnel"></i> Anwenden
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="clearFilters">
                    <i class="bi bi-x"></i> Zurücksetzen
                </button>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="sidebar-section">
        <h6 class="sidebar-heading">Nützliche Links</h6>
        <div class="quick-links">
            <a href="<?= $this->url('berichte/export') ?>" class="quick-link">
                <i class="bi bi-download"></i>
                <span>Daten exportieren</span>
            </a>
            <a href="<?= $this->url('verwaltung/backup') ?>" class="quick-link">
                <i class="bi bi-shield-check"></i>
                <span>Backup erstellen</span>
            </a>
            <a href="<?= $this->url('hilfe') ?>" class="quick-link">
                <i class="bi bi-question-circle"></i>
                <span>Hilfe & Support</span>
            </a>
            <a href="<?= $this->url('dokumentation') ?>" class="quick-link">
                <i class="bi bi-book"></i>
                <span>Dokumentation</span>
            </a>
        </div>
    </div>

    <!-- System Info -->
    <div class="sidebar-section">
        <h6 class="sidebar-heading">System</h6>
        <div class="system-info">
            <div class="info-item">
                <span class="info-label">Version:</span>
                <span class="info-value"><?= $this->e($app_version ?? '1.0.0') ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Letztes Update:</span>
                <span class="info-value"><?= $this->e($last_update ?? 'Unbekannt') ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Status:</span>
                <span class="info-value">
                    <span class="badge bg-success">Online</span>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Sidebar Toggle for Mobile -->
<button class="sidebar-toggle d-lg-none" type="button" id="sidebarToggle">
    <i class="bi bi-list"></i>
</button>
