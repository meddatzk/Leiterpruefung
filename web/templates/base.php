<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= $this->e($meta_description ?? 'Leiterprüfung Management System') ?>">
    <title><?= $this->e($title ?? 'Leiterprüfung') ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= $this->asset('css/main.css') ?>" rel="stylesheet">
    <link href="<?= $this->asset('css/layout.css') ?>" rel="stylesheet">
    <link href="<?= $this->asset('css/components.css') ?>" rel="stylesheet">
    
    <?= $this->section('head') ?>
</head>
<body class="<?= $body_class ?? '' ?>">
    <!-- Skip to main content -->
    <a class="visually-hidden-focusable" href="#main-content">Zum Hauptinhalt springen</a>
    
    <!-- Header -->
    <header class="header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <div class="logo">
                        <a href="<?= $this->url() ?>" class="logo-link">
                            <i class="bi bi-ladder"></i>
                            <span class="logo-text">Leiterprüfung</span>
                        </a>
                    </div>
                </div>
                <div class="col-md-6">
                    <!-- Hauptnavigation -->
                    <?php $this->partial('navigation'); ?>
                </div>
                <div class="col-md-3">
                    <!-- User Menu Placeholder -->
                    <div class="user-menu">
                        <div class="dropdown">
                            <button class="btn btn-outline-light dropdown-toggle" type="button" id="userMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle"></i>
                                <span class="d-none d-md-inline"><?= $this->e($user_name ?? 'Benutzer') ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenuButton">
                                <li><a class="dropdown-item" href="#"><i class="bi bi-person"></i> Profil</a></li>
                                <li><a class="dropdown-item" href="#"><i class="bi bi-gear"></i> Einstellungen</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#"><i class="bi bi-box-arrow-right"></i> Abmelden</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Layout -->
    <div class="main-layout">
        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar -->
                <?php if (!isset($hide_sidebar) || !$hide_sidebar): ?>
                <aside class="col-md-3 col-lg-2 sidebar">
                    <?php $this->partial('sidebar'); ?>
                </aside>
                <?php endif; ?>
                
                <!-- Main Content -->
                <main class="<?= (!isset($hide_sidebar) || !$hide_sidebar) ? 'col-md-9 col-lg-10' : 'col-12' ?> main-content" id="main-content">
                    <!-- Breadcrumb -->
                    <?php if (isset($breadcrumb) && !empty($breadcrumb)): ?>
                    <div class="breadcrumb-container">
                        <?= $this->breadcrumb($breadcrumb) ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Flash Messages -->
                    <div class="flash-messages">
                        <?= $this->flashMessages() ?>
                    </div>
                    
                    <!-- Page Header -->
                    <?php if (isset($page_title) || isset($page_subtitle)): ?>
                    <div class="page-header">
                        <?php if (isset($page_title)): ?>
                        <h1 class="page-title"><?= $this->e($page_title) ?></h1>
                        <?php endif; ?>
                        <?php if (isset($page_subtitle)): ?>
                        <p class="page-subtitle text-muted"><?= $this->e($page_subtitle) ?></p>
                        <?php endif; ?>
                        <?php if (isset($page_actions)): ?>
                        <div class="page-actions">
                            <?= $page_actions ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Main Content Area -->
                    <div class="content-area">
                        <?= $this->section('content') ?>
                    </div>
                </main>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer mt-auto">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <p class="footer-text mb-0">
                        &copy; <?= date('Y') ?> Leiterprüfung Management System
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="footer-text mb-0">
                        Version <?= $app_version ?? '1.0.0' ?>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="<?= $this->asset('js/main.js') ?>"></script>
    <script src="<?= $this->asset('js/components.js') ?>"></script>
    
    <?= $this->section('scripts') ?>
</body>
</html>
