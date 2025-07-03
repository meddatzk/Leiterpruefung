<?php
/**
 * Dashboard Widget-Templates
 */

switch ($type) {
    case 'stat-card':
        ?>
        <div class="card dashboard-stat-card h-100 border-start border-<?= $color ?> border-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-xs font-weight-bold text-<?= $color ?> text-uppercase mb-1">
                            <?= htmlspecialchars($title) ?>
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= number_format($value) ?>
                        </div>
                        <?php if (isset($subtitle)): ?>
                        <div class="text-muted small">
                            <?= htmlspecialchars($subtitle) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-auto">
                        <i class="<?= $icon ?> fa-2x text-<?= $color ?>"></i>
                    </div>
                </div>
            </div>
        </div>
        <?php
        break;

    case 'overdue-list':
        ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Leiternummer</th>
                        <th>Standort</th>
                        <th>Überfällig seit</th>
                        <th>Letzte Prüfung</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($data, 0, 10) as $ladder): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($ladder['ladder_number']) ?></strong><br>
                            <small class="text-muted">
                                <?= htmlspecialchars($ladder['manufacturer']) ?> 
                                <?= htmlspecialchars($ladder['model']) ?>
                            </small>
                        </td>
                        <td><?= htmlspecialchars($ladder['location']) ?></td>
                        <td>
                            <span class="badge bg-danger">
                                <?= $ladder['days_overdue'] ?> Tage
                            </span>
                        </td>
                        <td>
                            <?php if ($ladder['last_inspection_date']): ?>
                                <?= date('d.m.Y', strtotime($ladder['last_inspection_date'])) ?><br>
                                <small class="badge bg-<?= $ladder['last_result'] === 'passed' ? 'success' : ($ladder['last_result'] === 'failed' ? 'danger' : 'warning') ?>">
                                    <?= ucfirst($ladder['last_result']) ?>
                                </small>
                            <?php else: ?>
                                <span class="text-muted">Keine Prüfung</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="/inspections/create.php?ladder_id=<?= $ladder['id'] ?>" 
                               class="btn btn-sm btn-primary">
                                <i class="fas fa-clipboard-check"></i> Prüfen
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (count($data) > 10): ?>
        <div class="card-footer text-center">
            <a href="/ladders/index.php?filter=overdue" class="btn btn-outline-danger btn-sm">
                Alle <?= count($data) ?> überfälligen Prüfungen anzeigen
            </a>
        </div>
        <?php endif; ?>
        <?php
        break;

    case 'today-list':
        ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Leiternummer</th>
                        <th>Standort</th>
                        <th>Typ</th>
                        <th>Letzte Prüfung</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $ladder): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($ladder['ladder_number']) ?></strong><br>
                            <small class="text-muted">
                                <?= htmlspecialchars($ladder['manufacturer']) ?> 
                                <?= htmlspecialchars($ladder['model']) ?>
                            </small>
                        </td>
                        <td><?= htmlspecialchars($ladder['location']) ?></td>
                        <td>
                            <span class="badge bg-secondary">
                                <?= htmlspecialchars($ladder['ladder_type']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($ladder['last_inspection_date']): ?>
                                <?= date('d.m.Y', strtotime($ladder['last_inspection_date'])) ?><br>
                                <small class="badge bg-<?= $ladder['last_result'] === 'passed' ? 'success' : ($ladder['last_result'] === 'failed' ? 'danger' : 'warning') ?>">
                                    <?= ucfirst($ladder['last_result']) ?>
                                </small>
                            <?php else: ?>
                                <span class="text-muted">Keine Prüfung</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="/inspections/create.php?ladder_id=<?= $ladder['id'] ?>" 
                               class="btn btn-sm btn-info">
                                <i class="fas fa-clipboard-check"></i> Prüfen
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        break;

    case 'upcoming-list':
        ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Leiternummer</th>
                        <th>Standort</th>
                        <th>Fällig am</th>
                        <th>Tage bis Fälligkeit</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($data, 0, 15) as $ladder): ?>
                    <?php 
                    $daysUntilDue = (strtotime($ladder['next_inspection_date']) - time()) / (60 * 60 * 24);
                    $badgeClass = $daysUntilDue <= 7 ? 'bg-warning' : 'bg-info';
                    ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($ladder['ladder_number']) ?></strong><br>
                            <small class="text-muted">
                                <?= htmlspecialchars($ladder['manufacturer']) ?> 
                                <?= htmlspecialchars($ladder['model']) ?>
                            </small>
                        </td>
                        <td><?= htmlspecialchars($ladder['location']) ?></td>
                        <td><?= date('d.m.Y', strtotime($ladder['next_inspection_date'])) ?></td>
                        <td>
                            <span class="badge <?= $badgeClass ?>">
                                <?= max(0, ceil($daysUntilDue)) ?> Tage
                            </span>
                        </td>
                        <td>
                            <a href="/ladders/view.php?id=<?= $ladder['id'] ?>" 
                               class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="/inspections/create.php?ladder_id=<?= $ladder['id'] ?>" 
                               class="btn btn-sm btn-primary">
                                <i class="fas fa-clipboard-check"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (count($data) > 15): ?>
        <div class="card-footer text-center">
            <a href="/ladders/index.php?filter=upcoming" class="btn btn-outline-primary btn-sm">
                Alle <?= count($data) ?> anstehenden Prüfungen anzeigen
            </a>
        </div>
        <?php endif; ?>
        <?php
        break;

    case 'recent-activity':
        ?>
        <div class="activity-list">
            <?php foreach (array_slice($data, 0, 8) as $activity): ?>
            <div class="activity-item d-flex align-items-start p-3 border-bottom">
                <div class="activity-icon me-3">
                    <i class="fas fa-clipboard-check text-<?= $activity['overall_result'] === 'passed' ? 'success' : ($activity['overall_result'] === 'failed' ? 'danger' : 'warning') ?>"></i>
                </div>
                <div class="activity-content flex-grow-1">
                    <div class="activity-title">
                        <strong><?= htmlspecialchars($activity['ladder_number']) ?></strong>
                        <span class="badge bg-<?= $activity['overall_result'] === 'passed' ? 'success' : ($activity['overall_result'] === 'failed' ? 'danger' : 'warning') ?> ms-2">
                            <?= ucfirst($activity['overall_result']) ?>
                        </span>
                    </div>
                    <div class="activity-details text-muted small">
                        <?= htmlspecialchars($activity['location']) ?> • 
                        <?= htmlspecialchars($activity['inspector_name']) ?> • 
                        <?= date('d.m.Y H:i', strtotime($activity['created_at'])) ?>
                    </div>
                </div>
                <div class="activity-actions">
                    <a href="/inspections/view.php?id=<?= $activity['id'] ?>" 
                       class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if (count($data) > 8): ?>
        <div class="card-footer text-center">
            <a href="/inspections/history.php" class="btn btn-outline-secondary btn-sm">
                Alle Aktivitäten anzeigen
            </a>
        </div>
        <?php endif; ?>
        <?php
        break;

    case 'user-overview':
        $stats = $data['statistics'];
        ?>
        <div class="user-stats">
            <div class="row text-center">
                <div class="col-6 mb-3">
                    <div class="stat-item">
                        <div class="stat-value h4 mb-1"><?= $stats['total_inspections'] ?></div>
                        <div class="stat-label text-muted small">Prüfungen gesamt</div>
                    </div>
                </div>
                <div class="col-6 mb-3">
                    <div class="stat-item">
                        <div class="stat-value h4 mb-1"><?= $stats['inspections_last_30_days'] ?></div>
                        <div class="stat-label text-muted small">Letzte 30 Tage</div>
                    </div>
                </div>
                <div class="col-6 mb-3">
                    <div class="stat-item">
                        <div class="stat-value h4 mb-1 text-success"><?= $stats['passed'] ?></div>
                        <div class="stat-label text-muted small">Bestanden</div>
                    </div>
                </div>
                <div class="col-6 mb-3">
                    <div class="stat-item">
                        <div class="stat-value h4 mb-1 text-danger"><?= $stats['failed'] ?></div>
                        <div class="stat-label text-muted small">Nicht bestanden</div>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($data['recent_inspections'])): ?>
            <hr>
            <h6 class="mb-3">Letzte Prüfungen</h6>
            <div class="recent-inspections">
                <?php foreach (array_slice($data['recent_inspections'], 0, 3) as $inspection): ?>
                <div class="inspection-item d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <div class="fw-bold"><?= htmlspecialchars($inspection['ladder_number']) ?></div>
                        <div class="text-muted small"><?= date('d.m.Y', strtotime($inspection['inspection_date'])) ?></div>
                    </div>
                    <span class="badge bg-<?= $inspection['overall_result'] === 'passed' ? 'success' : ($inspection['overall_result'] === 'failed' ? 'danger' : 'warning') ?>">
                        <?= ucfirst($inspection['overall_result']) ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        break;

    case 'department-stats':
        ?>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Abteilung</th>
                        <th class="text-center">Leitern</th>
                        <th class="text-center">Prüfpflichtig</th>
                        <th class="text-center">Defekt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($data, 0, 8) as $dept): ?>
                    <tr>
                        <td><?= htmlspecialchars($dept['department']) ?></td>
                        <td class="text-center">
                            <span class="badge bg-primary"><?= $dept['total_ladders'] ?></span>
                        </td>
                        <td class="text-center">
                            <?php if ($dept['needs_inspection'] > 0): ?>
                            <span class="badge bg-warning"><?= $dept['needs_inspection'] ?></span>
                            <?php else: ?>
                            <span class="text-muted">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($dept['defective_ladders'] > 0): ?>
                            <span class="badge bg-danger"><?= $dept['defective_ladders'] ?></span>
                            <?php else: ?>
                            <span class="text-muted">0</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        break;

    case 'location-stats':
        ?>
        <div class="location-stats">
            <?php foreach (array_slice($data, 0, 8) as $location): ?>
            <div class="location-item d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                <div>
                    <div class="fw-bold"><?= htmlspecialchars($location['location']) ?></div>
                    <div class="text-muted small">
                        <?= $location['active_ladders'] ?> aktive Leitern
                    </div>
                </div>
                <div class="text-end">
                    <?php if ($location['needs_inspection'] > 0): ?>
                    <span class="badge bg-warning"><?= $location['needs_inspection'] ?></span>
                    <?php endif; ?>
                    <?php if ($location['defective_ladders'] > 0): ?>
                    <span class="badge bg-danger"><?= $location['defective_ladders'] ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
        break;

    default:
        echo '<div class="alert alert-warning">Unbekannter Widget-Typ: ' . htmlspecialchars($type) . '</div>';
        break;
}
?>
