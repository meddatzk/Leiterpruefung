<nav class="navbar navbar-expand-lg main-navigation">
    <div class="container-fluid">
        <!-- Mobile Menu Toggle -->
        <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavigation" aria-controls="mainNavigation" aria-expanded="false" aria-label="Navigation umschalten">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Navigation Menu -->
        <div class="collapse navbar-collapse" id="mainNavigation">
            <ul class="navbar-nav me-auto">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a class="nav-link <?= $this->isActive('/dashboard') ? 'active' : '' ?>" href="<?= $this->url('dashboard') ?>">
                        <i class="bi bi-house-door"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <!-- Leitern -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= $this->isActive('/leitern') ? 'active' : '' ?>" href="#" id="leiternDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-ladder"></i>
                        <span>Leitern</span>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="leiternDropdown">
                        <li><a class="dropdown-item" href="<?= $this->url('leitern') ?>">
                            <i class="bi bi-list"></i> Alle Leitern
                        </a></li>
                        <li><a class="dropdown-item" href="<?= $this->url('leitern/neu') ?>">
                            <i class="bi bi-plus-circle"></i> Neue Leiter
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= $this->url('leitern/kategorien') ?>">
                            <i class="bi bi-tags"></i> Kategorien
                        </a></li>
                    </ul>
                </li>
                
                <!-- Prüfungen -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= $this->isActive('/pruefungen') ? 'active' : '' ?>" href="#" id="pruefungenDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-clipboard-check"></i>
                        <span>Prüfungen</span>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="pruefungenDropdown">
                        <li><a class="dropdown-item" href="<?= $this->url('pruefungen') ?>">
                            <i class="bi bi-list"></i> Alle Prüfungen
                        </a></li>
                        <li><a class="dropdown-item" href="<?= $this->url('pruefungen/neu') ?>">
                            <i class="bi bi-plus-circle"></i> Neue Prüfung
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= $this->url('pruefungen/faellig') ?>">
                            <i class="bi bi-exclamation-triangle"></i> Fällige Prüfungen
                        </a></li>
                        <li><a class="dropdown-item" href="<?= $this->url('pruefungen/ueberfaellig') ?>">
                            <i class="bi bi-x-circle"></i> Überfällige Prüfungen
                        </a></li>
                    </ul>
                </li>
                
                <!-- Berichte -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= $this->isActive('/berichte') ? 'active' : '' ?>" href="#" id="berichteDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-graph-up"></i>
                        <span>Berichte</span>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="berichteDropdown">
                        <li><a class="dropdown-item" href="<?= $this->url('berichte/uebersicht') ?>">
                            <i class="bi bi-bar-chart"></i> Übersicht
                        </a></li>
                        <li><a class="dropdown-item" href="<?= $this->url('berichte/pruefstatus') ?>">
                            <i class="bi bi-pie-chart"></i> Prüfstatus
                        </a></li>
                        <li><a class="dropdown-item" href="<?= $this->url('berichte/export') ?>">
                            <i class="bi bi-download"></i> Export
                        </a></li>
                    </ul>
                </li>
                
                <!-- Verwaltung -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= $this->isActive('/verwaltung') ? 'active' : '' ?>" href="#" id="verwaltungDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-gear"></i>
                        <span>Verwaltung</span>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="verwaltungDropdown">
                        <li><a class="dropdown-item" href="<?= $this->url('verwaltung/benutzer') ?>">
                            <i class="bi bi-people"></i> Benutzer
                        </a></li>
                        <li><a class="dropdown-item" href="<?= $this->url('verwaltung/standorte') ?>">
                            <i class="bi bi-geo-alt"></i> Standorte
                        </a></li>
                        <li><a class="dropdown-item" href="<?= $this->url('verwaltung/einstellungen') ?>">
                            <i class="bi bi-sliders"></i> Einstellungen
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= $this->url('verwaltung/backup') ?>">
                            <i class="bi bi-shield-check"></i> Backup
                        </a></li>
                    </ul>
                </li>
            </ul>
            
            <!-- Search -->
            <form class="d-flex search-form" role="search">
                <div class="input-group">
                    <input class="form-control" type="search" placeholder="Suchen..." aria-label="Suchen" id="globalSearch">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</nav>

<!-- Mobile Navigation Overlay -->
<div class="mobile-nav-overlay d-lg-none" id="mobileNavOverlay"></div>
