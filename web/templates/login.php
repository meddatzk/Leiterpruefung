<?php $this->layout('base', [
    'title' => $title ?? 'Anmeldung',
    'body_class' => 'login-page',
    'hide_sidebar' => true,
    'user_name' => null
]); ?>

<?php $this->start('head'); ?>
<style>
.login-page {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
}

.login-page .header {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.login-page .footer {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-top: 1px solid rgba(255, 255, 255, 0.2);
}

.login-container {
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
    justify-content: center;
}

.login-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 15px;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    max-width: 400px;
    width: 100%;
    padding: 2rem;
}

.login-header {
    text-align: center;
    margin-bottom: 2rem;
}

.login-icon {
    font-size: 3rem;
    color: #667eea;
    margin-bottom: 1rem;
}

.login-title {
    color: #333;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.login-subtitle {
    color: #666;
    font-size: 0.9rem;
}

.form-floating {
    margin-bottom: 1rem;
}

.form-control {
    border-radius: 10px;
    border: 1px solid #ddd;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.btn-login {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 10px;
    padding: 0.75rem 2rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
}

.btn-login:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.btn-login:disabled {
    opacity: 0.6;
    transform: none;
    box-shadow: none;
}

.alert {
    border-radius: 10px;
    border: none;
    margin-bottom: 1.5rem;
}

.system-status {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #eee;
    text-align: center;
}

.status-indicator {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    color: #666;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.status-dot.online {
    background-color: #28a745;
}

.status-dot.offline {
    background-color: #dc3545;
}

.lockout-info {
    background-color: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 1rem;
    text-align: center;
}

.lockout-timer {
    font-weight: 600;
    color: #856404;
}
</style>
<?php $this->stop(); ?>

<?php $this->start('content'); ?>
<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <div class="login-icon">
                <i class="bi bi-shield-lock"></i>
            </div>
            <h2 class="login-title">Anmeldung</h2>
            <p class="login-subtitle">Melden Sie sich mit Ihren LDAP-Zugangsdaten an</p>
        </div>

        <?php if (!empty($content['error'])): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= $this->e($content['error']) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($content['success'])): ?>
            <div class="alert alert-success" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?= $this->e($content['success']) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['logout_message'])): ?>
            <div class="alert alert-<?= $_SESSION['logout_message_type'] ?? 'info' ?>" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i>
                <?= $this->e($_SESSION['logout_message']) ?>
            </div>
            <?php unset($_SESSION['logout_message'], $_SESSION['logout_message_type']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['auth_error'])): ?>
            <div class="alert alert-warning" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= $this->e($_SESSION['auth_error']) ?>
            </div>
            <?php unset($_SESSION['auth_error']); ?>
        <?php endif; ?>

        <form method="POST" action="login.php<?= !empty($content['redirect_url']) ? '?redirect=' . urlencode($content['redirect_url']) : '' ?>" novalidate>
            <input type="hidden" name="csrf_token" value="<?= $this->e($content['csrf_token']) ?>">
            
            <div class="form-floating">
                <input type="text" 
                       class="form-control" 
                       id="username" 
                       name="username" 
                       placeholder="Benutzername"
                       value="<?= $this->e($content['username']) ?>"
                       required
                       autocomplete="username"
                       autofocus>
                <label for="username">
                    <i class="bi bi-person me-2"></i>Benutzername
                </label>
            </div>

            <div class="form-floating">
                <input type="password" 
                       class="form-control" 
                       id="password" 
                       name="password" 
                       placeholder="Passwort"
                       required
                       autocomplete="current-password">
                <label for="password">
                    <i class="bi bi-lock me-2"></i>Passwort
                </label>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-login">
                    <i class="bi bi-box-arrow-in-right me-2"></i>
                    Anmelden
                </button>
            </div>
        </form>

        <div class="system-status">
            <div class="status-indicator">
                <span class="status-dot <?= $content['ldap_available'] ? 'online' : 'offline' ?>"></span>
                LDAP-Dienst: <?= $content['ldap_available'] ? 'Verfügbar' : 'Nicht verfügbar' ?>
            </div>
        </div>
    </div>
</div>
<?php $this->stop(); ?>

<?php $this->start('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-focus auf erstes leeres Feld
    const usernameField = document.getElementById('username');
    const passwordField = document.getElementById('password');
    
    if (usernameField.value === '') {
        usernameField.focus();
    } else {
        passwordField.focus();
    }
    
    // Form-Validierung
    const form = document.querySelector('form');
    const submitButton = form.querySelector('button[type="submit"]');
    
    form.addEventListener('submit', function(e) {
        const username = usernameField.value.trim();
        const password = passwordField.value;
        
        if (!username || !password) {
            e.preventDefault();
            
            // Fehlende Felder markieren
            if (!username) {
                usernameField.classList.add('is-invalid');
            } else {
                usernameField.classList.remove('is-invalid');
            }
            
            if (!password) {
                passwordField.classList.add('is-invalid');
            } else {
                passwordField.classList.remove('is-invalid');
            }
            
            return false;
        }
        
        // Submit-Button deaktivieren
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Anmelden...';
    });
    
    // Eingabefelder bei Änderung zurücksetzen
    [usernameField, passwordField].forEach(field => {
        field.addEventListener('input', function() {
            this.classList.remove('is-invalid');
        });
    });
    
    // Enter-Taste in Benutzername-Feld
    usernameField.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            passwordField.focus();
        }
    });
});
</script>
<?php $this->stop(); ?>
