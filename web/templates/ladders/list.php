<?php
/**
 * Leiter-Listen-Template
 * Zeigt die Tabelle mit allen Leitern und Suchfunktionen
 */
?>

<!-- Statistiken Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Gesamt</h5>
                        <h2 class="mb-0"><?= number_format($statistics['total']) ?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-ladder fs-1"></i>
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
                        <h5 class="card-title">Aktiv</h5>
                        <h2 class="mb-0"><?= number_format($statistics['active']) ?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-check-circle fs-1"></i>
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
                        <h5 class="card-title">Prüfung fällig</h5>
                        <h2 class="mb-0"><?= number_format($statistics['needs_inspection']) ?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-exclamation-triangle fs-1"></i>
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
                        <h5 class="card-title">Defekt</h5>
                        <h2 class="mb-0"><?= number_format($statistics['defective']) ?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-x-circle fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Suchbereich -->
<div class="card mb-4">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="card-title mb-0">
                    <i class="bi bi-search"></i> Suche und Filter
                </h5>
            </div>
            <div class="col-md-6 text-end">
                <?php if (!empty($current_filters)): ?>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="resetFilters">
                    <i class="bi bi-x-circle"></i> Filter zurücksetzen
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="quickSearch" class="form-label">Schnellsuche</label>
                <input type="text" class="form-control" id="quickSearch" name="search" 
                       value="<?= $this->e($current_filters['search'] ?? '') ?>"
                       placeholder="Nummer, Hersteller oder Standort...">
            </div>
            <div class="col-md-2">
                <label for="ladder_type" class="form-label">Typ</label>
                <select class="form-select" id="ladder_type" name="ladder_type">
                    <option value="">Alle Typen</option>
                    <?php foreach ($ladder_types as $type): ?>
                    <option value="<?= $this->e($type) ?>" <?= ($current_filters['ladder_type'] ?? '') === $type ? 'selected' : '' ?>>
                        <?= $this->e($type) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="material" class="form-label">Material</label>
                <select class="form-select" id="material" name="material">
                    <option value="">Alle Materialien</option>
                    <?php foreach ($materials as $material): ?>
                    <option value="<?= $this->e($material) ?>" <?= ($current_filters['material'] ?? '') === $material ? 'selected' : '' ?>>
                        <?= $this->e($material) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Alle Status</option>
                    <?php foreach ($statuses as $status): ?>
                    <option value="<?= $this->e($status) ?>" <?= ($current_filters['status'] ?? '') === $status ? 'selected' : '' ?>>
                        <?= $this->e(ucfirst($status)) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="location" class="form-label">Standort</label>
                <input type="text" class="form-control" id="location" name="location" 
                       value="<?= $this->e($current_filters['location'] ?? '') ?>"
                       placeholder="Standort...">
            </div>
        </form>
        
        <div class="row mt-3">
            <div class="col-md-12">
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="needs_inspection" name="needs_inspection" value="1"
                           <?= !empty($current_filters['needs_inspection']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="needs_inspection">
                        Nur prüfpflichtige Leitern
                    </label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="inspection_30_days" name="inspection_due_days" value="30"
                           <?= ($current_filters['inspection_due_days'] ?? '') == '30' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="inspection_30_days">
                        Prüfung in 30 Tagen fällig
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ergebnisse -->
<div class="card">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="card-title mb-0">
                    <i class="bi bi-list-ul"></i> 
                    Leitern (<?= number_format($pagination['total_count']) ?>)
                </h5>
            </div>
            <div class="col-md-6 text-end">
                <div class="btn-group btn-group-sm" role="group">
                    <input type="radio" class="btn-check" name="limit" id="limit10" value="10" <?= $pagination['limit'] == 10 ? 'checked' : '' ?>>
                    <label class="btn btn-outline-secondary" for="limit10">10</label>
                    
                    <input type="radio" class="btn-check" name="limit" id="limit25" value="25" <?= $pagination['limit'] == 25 ? 'checked' : '' ?>>
                    <label class="btn btn-outline-secondary" for="limit25">25</label>
                    
                    <input type="radio" class="btn-check" name="limit" id="limit50" value="50" <?= $pagination['limit'] == 50 ? 'checked' : '' ?>>
                    <label class="btn btn-outline-secondary" for="limit50">50</label>
                    
                    <input type="radio" class="btn-check" name="limit" id="limit100" value="100" <?= $pagination['limit'] == 100 ? 'checked' : '' ?>>
                    <label class="btn btn-outline-secondary" for="limit100">100</label>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (empty($ladders)): ?>
    <div class="card-body text-center py-5">
        <i class="bi bi-search display-1 text-muted"></i>
        <h4 class="mt-3">Keine Leitern gefunden</h4>
        <p class="text-muted">
            <?php if (!empty($current_filters)): ?>
                Keine Leitern entsprechen den aktuellen Suchkriterien.
            <?php else: ?>
                Es sind noch keine Leitern registriert.
            <?php endif; ?>
        </p>
        <?php if (!empty($current_filters)): ?>
        <button type="button" class="btn btn-outline-primary" id="resetFilters">
            Filter zurücksetzen
        </button>
        <?php else: ?>
        <a href="create.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Erste Leiter hinzufügen
        </a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    
    <!-- Responsive Tabelle -->
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th class="sortable" data-order-by="ladder_number">
                        Nummer
                        <?php if ($order_by === 'ladder_number'): ?>
                            <i class="bi bi-arrow-<?= $order_dir === 'ASC' ? 'up' : 'down' ?>"></i>
                        <?php endif; ?>
                    </th>
                    <th class="sortable" data-order-by="manufacturer">
                        Hersteller
                        <?php if ($order_by === 'manufacturer'): ?>
                            <i class="bi bi-arrow-<?= $order_dir === 'ASC' ? 'up' : 'down' ?>"></i>
                        <?php endif; ?>
                    </th>
                    <th class="sortable" data-order-by="ladder_type">
                        Typ
                        <?php if ($order_by === 'ladder_type'): ?>
                            <i class="bi bi-arrow-<?= $order_dir === 'ASC' ? 'up' : 'down' ?>"></i>
                        <?php endif; ?>
                    </th>
                    <th class="sortable" data-order-by="location">
                        Standort
                        <?php if ($order_by === 'location'): ?>
                            <i class="bi bi-arrow-<?= $order_dir === 'ASC' ? 'up' : 'down' ?>"></i>
                        <?php endif; ?>
                    </th>
                    <th class="sortable" data-order-by="status">
                        Status
                        <?php if ($order_by === 'status'): ?>
                            <i class="bi bi-arrow-<?= $order_dir === 'ASC' ? 'up' : 'down' ?>"></i>
                        <?php endif; ?>
                    </th>
                    <th class="sortable" data-order-by="next_inspection_date">
                        Nächste Prüfung
                        <?php if ($order_by === 'next_inspection_date'): ?>
                            <i class="bi bi-arrow-<?= $order_dir === 'ASC' ? 'up' : 'down' ?>"></i>
                        <?php endif; ?>
                    </th>
                    <th width="120">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ladders as $ladder): ?>
                <tr class="<?= $ladder->needsInspection() ? 'table-warning' : '' ?>">
                    <td>
                        <strong><?= $this->e($ladder->getLadderNumber()) ?></strong>
                        <?php if ($ladder->getSerialNumber()): ?>
                        <br><small class="text-muted">SN: <?= $this->e($ladder->getSerialNumber()) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= $this->e($ladder->getManufacturer()) ?>
                        <?php if ($ladder->getModel()): ?>
                        <br><small class="text-muted"><?= $this->e($ladder->getModel()) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-secondary"><?= $this->e($ladder->getLadderType()) ?></span>
                        <br><small class="text-muted"><?= $this->e($ladder->getMaterial()) ?> - <?= $ladder->getHeightCm() ?>cm</small>
                    </td>
                    <td>
                        <?= $this->e($ladder->getLocation()) ?>
                        <?php if ($ladder->getDepartment()): ?>
                        <br><small class="text-muted"><?= $this->e($ladder->getDepartment()) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $statusClass = match($ladder->getStatus()) {
                            'active' => 'bg-success',
                            'inactive' => 'bg-secondary',
                            'defective' => 'bg-danger',
                            'disposed' => 'bg-dark',
                            default => 'bg-secondary'
                        };
                        ?>
                        <span class="badge <?= $statusClass ?>"><?= $this->e(ucfirst($ladder->getStatus())) ?></span>
                    </td>
                    <td>
                        <?php
                        $daysUntil = $ladder->getDaysUntilInspection();
                        $inspectionDate = new DateTime($ladder->getNextInspectionDate());
                        ?>
                        <span class="<?= $daysUntil <= 0 ? 'text-danger fw-bold' : ($daysUntil <= 30 ? 'text-warning fw-bold' : '') ?>">
                            <?= $inspectionDate->format('d.m.Y') ?>
                        </span>
                        <br>
                        <small class="<?= $daysUntil <= 0 ? 'text-danger' : ($daysUntil <= 30 ? 'text-warning' : 'text-muted') ?>">
                            <?php if ($daysUntil <= 0): ?>
                                <?= abs($daysUntil) ?> Tage überfällig
                            <?php else: ?>
                                in <?= $daysUntil ?> Tagen
                            <?php endif; ?>
                        </small>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm" role="group">
                            <a href="view.php?id=<?= $ladder->getId() ?>" class="btn btn-outline-primary" title="Details anzeigen">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="edit.php?id=<?= $ladder->getId() ?>" class="btn btn-outline-secondary" title="Bearbeiten">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button type="button" class="btn btn-outline-danger" title="Löschen" 
                                    onclick="confirmDelete(<?= $ladder->getId() ?>, '<?= $this->e($ladder->getLadderNumber()) ?>')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Paginierung -->
    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="card-footer">
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="mb-0 text-muted">
                    Zeige <?= number_format($pagination['start_item']) ?> bis <?= number_format($pagination['end_item']) ?> 
                    von <?= number_format($pagination['total_count']) ?> Einträgen
                </p>
            </div>
            <div class="col-md-6">
                <nav aria-label="Seitennummerierung">
                    <ul class="pagination pagination-sm justify-content-end mb-0">
                        <?php if ($pagination['current_page'] > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($current_filters, ['page' => 1])) ?>">
                                <i class="bi bi-chevron-double-left"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($current_filters, ['page' => $pagination['current_page'] - 1])) ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php
                        $startPage = max(1, $pagination['current_page'] - 2);
                        $endPage = min($pagination['total_pages'], $pagination['current_page'] + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                        <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($current_filters, ['page' => $i])) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($current_filters, ['page' => $pagination['current_page'] + 1])) ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($current_filters, ['page' => $pagination['total_pages']])) ?>">
                                <i class="bi bi-chevron-double-right"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php endif; ?>
</div>

<!-- Lösch-Bestätigung Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Leiter löschen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Möchten Sie die Leiter <strong id="deleteItemName"></strong> wirklich löschen?</p>
                <p class="text-muted">Diese Aktion kann nicht rückgängig gemacht werden.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <form method="POST" action="delete.php" style="display: inline;">
                    <?= $this->csrfField() ?>
                    <input type="hidden" name="id" id="deleteItemId">
                    <button type="submit" class="btn btn-danger">Löschen</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Lösch-Bestätigung
function confirmDelete(id, name) {
    document.getElementById('deleteItemId').value = id;
    document.getElementById('deleteItemName').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Limit-Änderung
document.addEventListener('DOMContentLoaded', function() {
    const limitRadios = document.querySelectorAll('input[name="limit"]');
    limitRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const url = new URL(window.location);
            url.searchParams.set('limit', this.value);
            url.searchParams.delete('page');
            window.location.href = url.toString();
        });
    });
    
    // Filter-Formular Auto-Submit
    const filterInputs = document.querySelectorAll('#ladder_type, #material, #status, #needs_inspection, #inspection_30_days');
    filterInputs.forEach(input => {
        input.addEventListener('change', function() {
            this.form.submit();
        });
    });
});
</script>
