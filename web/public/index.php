<?php
/**
 * Eingangsseite der Leiterpruefung-Anwendung
 * Lädt das Bootstrap und zeigt eine einfache Startseite
 */

// Bootstrap laden
require_once dirname(__DIR__) . '/src/includes/bootstrap.php';

// Basis-HTML-Template
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Leiterprüfung - Verwaltungssystem</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="../src/assets/css/main.css" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../src/assets/images/favicon.ico">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-clipboard-check me-2"></i>
                Leiterprüfung
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-home me-1"></i>
                            Startseite
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="showSystemInfo()">
                            <i class="fas fa-info-circle me-1"></i>
                            System-Info
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php if (hasFlashMessages()): ?>
        <div class="container mt-3">
            <?php foreach (getFlashMessages() as $message): ?>
                <div class="alert alert-<?php echo $message['type'] === 'error' ? 'danger' : $message['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo escape($message['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="container mt-4">
        <div class="row">
            <div class="col-12">
                <!-- Welcome Section -->
                <div class="jumbotron bg-light p-5 rounded-3 mb-4">
                    <div class="container-fluid py-5">
                        <h1 class="display-5 fw-bold">Willkommen zum Leiterprüfung-System</h1>
                        <p class="col-md-8 fs-4">
                            Dieses System verwaltet die Prüfung und Dokumentation von Leitern und Tritten 
                            gemäß den geltenden Sicherheitsvorschriften.
                        </p>
                        <div class="mt-4">
                            <span class="badge bg-success me-2">
                                <i class="fas fa-check-circle me-1"></i>
                                System initialisiert
                            </span>
                            <span class="badge bg-info me-2">
                                <i class="fas fa-database me-1"></i>
                                Datenbank: <?php echo Config::get('database.name'); ?>
                            </span>
                            <span class="badge bg-secondary">
                                <i class="fas fa-clock me-1"></i>
                                <?php echo formatDate(time()); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- System Status Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-cogs fa-3x text-primary mb-3"></i>
                                <h5 class="card-title">Konfiguration</h5>
                                <p class="card-text">
                                    System erfolgreich konfiguriert und einsatzbereit.
                                </p>
                                <div class="mt-3">
                                    <small class="text-muted">
                                        Environment: <?php echo Config::get('app.environment'); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-database fa-3x text-success mb-3"></i>
                                <h5 class="card-title">Datenbank</h5>
                                <p class="card-text">
                                    Datenbankverbindung konfiguriert und bereit.
                                </p>
                                <div class="mt-3">
                                    <small class="text-muted">
                                        Host: <?php echo Config::get('database.host'); ?>:<?php echo Config::get('database.port'); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-shield-alt fa-3x text-warning mb-3"></i>
                                <h5 class="card-title">Sicherheit</h5>
                                <p class="card-text">
                                    CSRF-Schutz und Session-Management aktiv.
                                </p>
                                <div class="mt-3">
                                    <small class="text-muted">
                                        Session: <?php echo session_id() ? 'Aktiv' : 'Inaktiv'; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Development Info (nur im Debug-Modus) -->
                <?php if (Config::get('app.debug', false)): ?>
                    <div class="alert alert-warning" role="alert">
                        <h5 class="alert-heading">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Debug-Modus aktiv
                        </h5>
                        <p class="mb-0">
                            Das System läuft im Debug-Modus. Detaillierte Fehlerinformationen werden angezeigt.
                            <strong>Nicht für Produktionsumgebungen geeignet!</strong>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-light mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">
                        <strong>Leiterprüfung-System</strong><br>
                        <small class="text-muted">Verwaltung und Dokumentation von Leiterprüfungen</small>
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">
                        <small class="text-muted">
                            Version 1.0.0 | 
                            PHP <?php echo PHP_VERSION; ?> | 
                            <?php echo formatDate(time(), 'Y'); ?>
                        </small>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- System Info Modal -->
    <div class="modal fade" id="systemInfoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-info-circle me-2"></i>
                        System-Informationen
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Anwendung</h6>
                            <ul class="list-unstyled">
                                <li><strong>Environment:</strong> <?php echo Config::get('app.environment'); ?></li>
                                <li><strong>Debug:</strong> <?php echo Config::get('app.debug') ? 'Aktiv' : 'Inaktiv'; ?></li>
                                <li><strong>Zeitzone:</strong> <?php echo Config::get('app.timezone'); ?></li>
                                <li><strong>Session-Timeout:</strong> <?php echo Config::get('app.session_timeout'); ?>s</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Server</h6>
                            <ul class="list-unstyled">
                                <li><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></li>
                                <li><strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unbekannt'; ?></li>
                                <li><strong>Session-ID:</strong> <?php echo session_id(); ?></li>
                                <li><strong>CSRF-Token:</strong> <?php echo substr(getCSRFToken(), 0, 16) . '...'; ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
    
    <!-- Custom JS -->
    <script src="../src/assets/js/main.js"></script>
    
    <script>
        // System-Info Modal anzeigen
        function showSystemInfo() {
            const modal = new bootstrap.Modal(document.getElementById('systemInfoModal'));
            modal.show();
        }
        
        // CSRF-Token für AJAX-Requests verfügbar machen
        window.csrfToken = '<?php echo getCSRFToken(); ?>';
    </script>
</body>
</html>
