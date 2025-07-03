<?php
/**
 * Dashboard Übersichts-Template
 */

// Template-Basis laden
$this->extend('base.php');

// Zusätzliche CSS-Dateien
$this->push('styles', '<link rel="stylesheet" href="/src/assets/css/dashboard.css">');

// Zusätzliche JavaScript-Dateien
$this->push('scripts', '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>');
$this->push('scripts', '<script src="/src/assets/js/dashboard.js"></script>');

// Breadcrumb
$this->section('breadcrumb');
?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item active" aria-current="page">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </li>
    </ol>
</nav>
<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<div class="dashboard-container">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="dashboard-title">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </h1>
                <p class="dashboard-subtitle">Übersicht über das Leiterprüfungssystem</p>
            </div>
            <div class="col-md-6 text-end">
                <div class="dashboard-controls">
                    <button class="btn btn-outline-primary btn-sm" id="refreshDashboard">
                        <i class="fas fa-sync-alt"></i> Aktualisieren
                    </button>
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" data-period="today">Heute</a></li>
                            <li><a class="dropdown-item" href="#" data-period="week">Diese Woche</a></li>
                            <li><a class="dropdown-item" href="#" data-period="month">Dieser Monat</a></li>
                            <li><a class="dropdown-item" href="#" data-period="year">Dieses Jahr</a></li>
                        </ul>
                    </div>
                    <span class="last-update" id="lastUpdate">
                        Zuletzt aktualisiert: <?= date('H:i:s') ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik-Karten -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <?php $this->include('dashboard/widgets.php', [
                'type' => 'stat-card',
                'title' => 'Leitern Gesamt',
                'value' => $dashboardData['statistics']['ladders']['total'],
                'icon' => 'fas fa-ladder',
                'color' => 'primary',
                'subtitle' => $dashboardData['statistics']['ladders']['active'] . ' aktiv'
            ]); ?>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <?php $this->include('dashboard/widgets.php', [
                'type' => 'stat-card',
                'title' => 'Prüfungen fällig',
                'value' => $dashboardData['statistics']['ladders']['needs_inspection'],
                'icon' => 'fas fa-exclamation-triangle',
                'color' => 'warning',
                'subtitle' => 'Sofort prüfpflichtig'
            ]); ?>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <?php $this->include('dashboard/widgets.php', [
                'type' => 'stat-card',
                'title' => 'Prüfungen gesamt',
                'value' => $dashboardData['statistics']['inspections']['total'],
                'icon' => 'fas fa-clipboard-check',
                'color' => 'success',
                'subtitle' => $dashboardData['statistics']['inspections']['passed'] . ' bestanden'
            ]); ?>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <?php $this->include('dashboard/widgets.php', [
                'type' => 'stat-card',
                'title' => 'Defekte Leitern',
                'value' => $dashboardData['statistics']['ladders']['defective'],
                'icon' => 'fas fa-tools',
                'color' => 'danger',
                'subtitle' => 'Reparatur erforderlich'
            ]); ?>
        </div>
    </div>

    <!-- Hauptinhalt -->
    <div class="row">
        <!-- Linke Spalte -->
        <div class="col-lg-8">
            <!-- Überfällige Prüfungen -->
            <?php if (!empty($dashboardData['overdue_inspections'])): ?>
            <div class="card dashboard-card mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-exclamation-circle"></i>
                        Überfällige Prüfungen (<?= count($dashboardData['overdue_inspections']) ?>)
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php $this->include('dashboard/widgets.php', [
                        'type' => 'overdue-list',
                        'data' => $dashboardData['overdue_inspections']
                    ]); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Heute anstehende Prüfungen -->
            <?php if (!empty($dashboardData['today_inspections'])): ?>
            <div class="card dashboard-card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calendar-day"></i>
                        Heute anstehende Prüfungen (<?= count($dashboardData['today_inspections']) ?>)
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php $this->include('dashboard/widgets.php', [
                        'type' => 'today-list',
                        'data' => $dashboardData['today_inspections']
                    ]); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Anstehende Prüfungen (nächste 30 Tage) -->
            <div class="card dashboard-card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calendar-alt"></i>
                        Anstehende Prüfungen (nächste 30 Tage)
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php $this->include('dashboard/widgets.php', [
                        'type' => 'upcoming-list',
                        'data' => $dashboardData['upcoming_inspections']
                    ]); ?>
                </div>
            </div>

            <!-- Monatliche Statistiken Chart -->
            <div class="card dashboard-card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-line"></i>
                        Prüfungen der letzten 12 Monate
                    </h5>
                </div>
                <div class="card-body">
                    <?php $this->include('dashboard/charts.php', [
                        'type' => 'monthly-inspections',
                        'data' => $dashboardData['monthly_stats']
                    ]); ?>
                </div>
            </div>
        </div>

        <!-- Rechte Spalte -->
        <div class="col-lg-4">
            <!-- Benutzer-Übersicht -->
            <?php if ($userOverview): ?>
            <div class="card dashboard-card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user"></i>
                        Meine Übersicht
                    </h5>
                </div>
                <div class="card-body">
                    <?php $this->include('dashboard/widgets.php', [
                        'type' => 'user-overview',
                        'data' => $userOverview
                    ]); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Prüfungsergebnisse Chart -->
            <div class="card dashboard-card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-pie"></i>
                        Prüfungsergebnisse
                    </h5>
                </div>
                <div class="card-body">
                    <?php $this->include('dashboard/charts.php', [
                        'type' => 'inspection-results',
                        'data' => $dashboardData['statistics']['inspections']
                    ]); ?>
                </div>
            </div>

            <!-- Leiter-Status Chart -->
            <div class="card dashboard-card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-doughnut"></i>
                        Leiter-Status
                    </h5>
                </div>
                <div class="card-body">
                    <?php $this->include('dashboard/charts.php', [
                        'type' => 'ladder-status',
                        'data' => $dashboardData['statistics']['ladders']
                    ]); ?>
                </div>
            </div>

            <!-- Letzte Aktivitäten -->
            <div class="card dashboard-card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-history"></i>
                        Letzte Aktivitäten
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php $this->include('dashboard/widgets.php', [
                        'type' => 'recent-activity',
                        'data' => $dashboardData['recent_activity']
                    ]); ?>
                </div>
            </div>

            <!-- Abteilungs-Übersicht -->
            <?php if (!empty($dashboardData['department_stats'])): ?>
            <div class="card dashboard-card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-building"></i>
                        Abteilungen
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php $this->include('dashboard/widgets.php', [
                        'type' => 'department-stats',
                        'data' => $dashboardData['department_stats']
                    ]); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Erweiterte Statistiken (ausklappbar) -->
    <div class="card dashboard-card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <button class="btn btn-link p-0 text-decoration-none" type="button" data-bs-toggle="collapse" data-bs-target="#extendedStats">
                    <i class="fas fa-chart-bar"></i>
                    Erweiterte Statistiken
                    <i class="fas fa-chevron-down ms-2"></i>
                </button>
            </h5>
        </div>
        <div class="collapse" id="extendedStats">
            <div class="card-body">
                <div class="row">
                    <!-- Prüfungstypen -->
                    <div class="col-md-4 mb-3">
                        <h6>Prüfungstypen</h6>
                        <?php $this->include('dashboard/charts.php', [
                            'type' => 'inspection-types',
                            'data' => $inspectionTypeStats
                        ]); ?>
                    </div>
                    
                    <!-- Leitertypen -->
                    <div class="col-md-4 mb-3">
                        <h6>Leitertypen</h6>
                        <?php $this->include('dashboard/charts.php', [
                            'type' => 'ladder-types',
                            'data' => $ladderTypeStats
                        ]); ?>
                    </div>
                    
                    <!-- Top Standorte -->
                    <div class="col-md-4 mb-3">
                        <h6>Top Standorte</h6>
                        <?php $this->include('dashboard/widgets.php', [
                            'type' => 'location-stats',
                            'data' => $locationStats
                        ]); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Auto-Refresh Modal -->
<div class="modal fade" id="autoRefreshModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Auto-Aktualisierung</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="autoRefreshToggle" checked>
                    <label class="form-check-label" for="autoRefreshToggle">
                        Automatische Aktualisierung (5 Min.)
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Dashboard-Daten für JavaScript verfügbar machen
window.dashboardData = <?= json_encode($dashboardData) ?>;
window.dashboardConfig = {
    autoRefresh: true,
    refreshInterval: 300000, // 5 Minuten
    ajaxUrl: '/dashboard.php?ajax=1'
};
</script>
<?php $this->endSection(); ?>
