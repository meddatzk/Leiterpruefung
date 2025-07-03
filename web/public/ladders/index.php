<?php
/**
 * Leiter-Übersicht
 * Zeigt alle Leitern mit Paginierung und Suchfunktion
 */

require_once __DIR__ . '/../../src/includes/bootstrap.php';

// Authentifizierung prüfen
requireAuth();

try {
    // Datenbankverbindung
    $pdo = getDatabaseConnection();
    $auditLogger = new AuditLogger($pdo);
    $ladderRepository = new LadderRepository($pdo, $auditLogger);
    
    // Parameter aus Request
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(10, min(100, (int)($_GET['limit'] ?? 25)));
    $offset = ($page - 1) * $limit;
    $orderBy = $_GET['order_by'] ?? 'ladder_number';
    $orderDir = strtoupper($_GET['order_dir'] ?? 'ASC');
    
    // Suchfilter
    $filters = [];
    if (!empty($_GET['search'])) {
        $search = trim($_GET['search']);
        $filters['ladder_number'] = $search;
        $filters['manufacturer'] = $search;
        $filters['location'] = $search;
    }
    
    if (!empty($_GET['ladder_type'])) {
        $filters['ladder_type'] = $_GET['ladder_type'];
    }
    
    if (!empty($_GET['material'])) {
        $filters['material'] = $_GET['material'];
    }
    
    if (!empty($_GET['status'])) {
        $filters['status'] = $_GET['status'];
    }
    
    if (!empty($_GET['location'])) {
        $filters['location'] = $_GET['location'];
    }
    
    if (!empty($_GET['needs_inspection'])) {
        $filters['needs_inspection'] = true;
    }
    
    if (!empty($_GET['inspection_due_days'])) {
        $filters['inspection_due_days'] = (int)$_GET['inspection_due_days'];
    }
    
    // Daten laden
    if (!empty($filters)) {
        $ladders = $ladderRepository->search($filters, $limit, $offset);
        $totalCount = $ladderRepository->count($filters);
    } else {
        $ladders = $ladderRepository->getAll($limit, $offset, $orderBy, $orderDir);
        $totalCount = $ladderRepository->count();
    }
    
    // Statistiken laden
    $statistics = $ladderRepository->getStatistics();
    
    // Paginierung berechnen
    $totalPages = ceil($totalCount / $limit);
    $startItem = $offset + 1;
    $endItem = min($offset + $limit, $totalCount);
    
    // Template-Daten vorbereiten
    $templateData = [
        'title' => 'Leitern verwalten',
        'page_title' => 'Leitern',
        'page_subtitle' => 'Übersicht aller registrierten Leitern',
        'breadcrumb' => [
            ['title' => 'Dashboard', 'url' => '../index.php'],
            ['title' => 'Leitern', 'url' => '']
        ],
        'page_actions' => '
            <div class="btn-group" role="group">
                <a href="create.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Neue Leiter
                </a>
                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#searchModal">
                    <i class="bi bi-search"></i> Erweiterte Suche
                </button>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="bi bi-download"></i> Export
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="?export=csv"><i class="bi bi-file-earmark-spreadsheet"></i> CSV Export</a></li>
                        <li><a class="dropdown-item" href="?export=pdf"><i class="bi bi-file-earmark-pdf"></i> PDF Export</a></li>
                    </ul>
                </div>
            </div>
        ',
        'ladders' => $ladders,
        'statistics' => $statistics,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_count' => $totalCount,
            'start_item' => $startItem,
            'end_item' => $endItem,
            'limit' => $limit
        ],
        'filters' => $filters,
        'current_filters' => $_GET,
        'order_by' => $orderBy,
        'order_dir' => $orderDir,
        'ladder_types' => Ladder::LADDER_TYPES,
        'materials' => Ladder::MATERIALS,
        'statuses' => Ladder::STATUSES
    ];
    
    // Template rendern
    $template = new TemplateEngine();
    $template->startSection('content');
    $template->partial('ladders/list', $templateData);
    $template->endSection();
    
    $template->startSection('scripts');
    ?>
    <script>
        // Tabellen-Sortierung
        document.addEventListener('DOMContentLoaded', function() {
            const sortableHeaders = document.querySelectorAll('.sortable');
            sortableHeaders.forEach(header => {
                header.addEventListener('click', function() {
                    const orderBy = this.dataset.orderBy;
                    const currentOrderBy = '<?= $orderBy ?>';
                    const currentOrderDir = '<?= $orderDir ?>';
                    
                    let newOrderDir = 'ASC';
                    if (orderBy === currentOrderBy && currentOrderDir === 'ASC') {
                        newOrderDir = 'DESC';
                    }
                    
                    const url = new URL(window.location);
                    url.searchParams.set('order_by', orderBy);
                    url.searchParams.set('order_dir', newOrderDir);
                    window.location.href = url.toString();
                });
            });
            
            // Schnellsuche
            const quickSearchInput = document.getElementById('quickSearch');
            if (quickSearchInput) {
                let searchTimeout;
                quickSearchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        const url = new URL(window.location);
                        if (this.value.trim()) {
                            url.searchParams.set('search', this.value.trim());
                        } else {
                            url.searchParams.delete('search');
                        }
                        url.searchParams.delete('page');
                        window.location.href = url.toString();
                    }, 500);
                });
            }
            
            // Filter zurücksetzen
            const resetFiltersBtn = document.getElementById('resetFilters');
            if (resetFiltersBtn) {
                resetFiltersBtn.addEventListener('click', function() {
                    window.location.href = 'index.php';
                });
            }
        });
    </script>
    <?php
    $template->endSection();
    
    echo $template->render('base', $templateData);
    
} catch (Exception $e) {
    error_log('Fehler in ladders/index.php: ' . $e->getMessage());
    
    $template = new TemplateEngine();
    echo $template->render('error', [
        'title' => 'Fehler',
        'error_title' => 'Fehler beim Laden der Leitern',
        'error_message' => 'Die Leitern konnten nicht geladen werden. Bitte versuchen Sie es später erneut.',
        'error_code' => 500
    ]);
}
?>
