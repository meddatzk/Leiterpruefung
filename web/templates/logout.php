<?php $this->layout('base', [
    'title' => $title ?? 'Abmelden',
    'body_class' => 'logout-page',
    'hide_sidebar' => true,
    'user_name' => $content['display_name'] ?? 'Benutzer'
]); ?>

<?php $this->start('head'); ?>
<style>
.logout-page {
    background: linear-gradient(135deg, #ff7b7b 0%, #667eea 100%);
    min-height: 100vh;
}

.logout-page .header {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.logout-page .footer {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-top: 1px solid rgba(255, 255, 255, 0.2);
}

.logout-container {
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
    justify-content: center;
}

.logout-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 15px;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    max-width: 500px;
    width: 100%;
    padding: 2.5rem;
}

.logout-header {
    text-align: center;
    margin-bottom: 2rem;
}

.logout-icon {
    font-size: 3.5rem;
    color: #ff7b7b;
    margin-bottom: 1rem;
}

.logout-title {
    color: #333;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.logout-subtitle {
    color: #666;
    font-size: 0.95rem;
}

.user-info {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    text-align: center;
}

.user-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    color: white;
    font-size: 1.5rem;
    font-weight: 600;
}

.user-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 0.25rem;
}

.user-username {
    color: #666;
    font-size: 0.9rem;
}

.session-info {
    background: #e3f2fd;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 2rem;
    font-size: 0.85rem;
}

.session-info-title {
    font-weight: 600;
    color: #1976d2;
    margin-bottom: 0.5rem;
}

.session-info-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.25rem;
    color: #555;
}

.logout-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.btn-logout {
    background: linear-gradient(135deg, #ff7b7b 0%, #ff6b6b 100%);
    border: none;
    border-radius: 10px;
    padding: 0.75rem 2rem;
    font-weight: 600;
    color: white;
    transition: all 0.3s ease;
}

.btn-logout:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 123, 123, 0.4);
    color: white;
}

.btn-cancel {
    background: #6c757d;
    border: none;
    border-radius: 10px;
    padding: 0.75rem 2rem;
    font-weight: 600;
    color: white;
    text-decoration: none;
    transition: all 0.3s ease;
}

.btn-cancel:hover {
    background: #5a6268;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(108, 117, 125, 0.4);
    color: white;
    text-decoration: none;
}

.alert {
    border-radius: 10px;
    border: none;
    margin-bottom: 1.5rem;
}

.confirmation-text {
    text-align: center;
    color: #666;
    margin-bottom: 2rem;
    line-height: 1.6;
}
</style>
<?php $this->stop(); ?>

<?php $this->start('content'); ?>
<div class="logout-container">
    <div class="logout-card">
        <div class="logout-header">
            <div class="logout-icon">
                <i class="bi bi-box-arrow-right"></i>
            </div>
            <h2 class="logout-title">Abmelden</h2>
            <p class="logout-subtitle">Möchten Sie sich wirklich abmelden?</p>
        </div>

        <?php if (!empty($content['message'])): ?>
            <div class="alert alert-<?= $content['message_type'] ?? 'info' ?>" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i>
                <?= $this->e($content['message']) ?>
            </div>
        <?php endif; ?>

        <div class="user-info">
            <div class="user-avatar">
                <?php
                $displayName = $content['display_name'] ?? 'Benutzer';
                $initials = '';
                $nameParts = explode(' ', $displayName);
                foreach ($nameParts as $part) {
                    if (!empty($part)) {
                        $initials .= strtoupper(substr($part, 0, 1));
                        if (strlen($initials) >= 2) break;
                    }
                }
                if (empty($initials)) {
                    $initials = strtoupper(substr($content['username'] ?? 'U', 0, 2));
                }
                echo $this->e($initials);
                ?>
            </div>
            <div class="user-name"><?= $this->e($content['display_name'] ?? 'Benutzer') ?></div>
            <div class="user-username">@<?= $this->e($content['username'] ?? 'unbekannt') ?></div>
        </div>

        <?php if (!empty($content['session_info'])): ?>
        <div class="session-info">
            <div class="session-info-title">
                <i class="bi bi-clock me-2"></i>Sitzungsinformationen
            </div>
            <?php if (isset($content['session_info']['login_time'])): ?>
            <div class="session-info-item">
                <span>Angemeldet seit:</span>
                <span><?= date('d.m.Y H:i', $content['session_info']['login_time']) ?> Uhr</span>
            </div>
            <?php endif; ?>
            <?php if (isset($content['session_info']['time_remaining']) && $content['session_info']['time_remaining'] > 0): ?>
            <div class="session-info-item">
                <span>Verbleibende Zeit:</span>
                <span><?= gmdate('H:i:s', $content['session_info']['time_remaining']) ?></span>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="confirmation-text">
            Durch die Abmeldung wird Ihre aktuelle Sitzung beendet und Sie werden zur Anmeldeseite weitergeleitet.
            Alle nicht gespeicherten Änderungen gehen verloren.
        </div>

        <div class="logout-actions">
            <form method="POST" action="logout.php" style="display: inline;">
                <input type="hidden" name="csrf_token" value="<?= $this->e($content['csrf_token']) ?>">
                <button type="submit" class="btn btn-logout">
                    <i class="bi bi-box-arrow-right me-2"></i>
                    Jetzt abmelden
                </button>
            </form>
            
            <a href="index.php" class="btn btn-cancel">
                <i class="bi bi-arrow-left me-2"></i>
                Abbrechen
            </a>
        </div>
    </div>
</div>
<?php $this->stop(); ?>

<?php $this->start('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Countdown für verbleibende Sitzungszeit
    const timeRemainingElement = document.querySelector('.session-info-item span:last-child');
    if (timeRemainingElement && timeRemainingElement.textContent.includes(':')) {
        let timeRemaining = <?= $content['session_info']['time_remaining'] ?? 0 ?>;
        
        if (timeRemaining > 0) {
            const countdown = setInterval(function() {
                timeRemaining--;
                
                if (timeRemaining <= 0) {
                    clearInterval(countdown);
                    timeRemainingElement.textContent = 'Abgelaufen';
                    timeRemainingElement.style.color = '#dc3545';
                    
                    // Automatische Weiterleitung zur Login-Seite
                    setTimeout(function() {
                        window.location.href = 'login.php';
                    }, 2000);
                } else {
                    const hours = Math.floor(timeRemaining / 3600);
                    const minutes = Math.floor((timeRemaining % 3600) / 60);
                    const seconds = timeRemaining % 60;
                    
                    timeRemainingElement.textContent = 
                        String(hours).padStart(2, '0') + ':' +
                        String(minutes).padStart(2, '0') + ':' +
                        String(seconds).padStart(2, '0');
                }
            }, 1000);
        }
    }
    
    // Bestätigung vor Abmeldung
    const logoutForm = document.querySelector('form');
    const logoutButton = logoutForm.querySelector('button[type="submit"]');
    
    logoutForm.addEventListener('submit', function(e) {
        // Button-Status ändern
        logoutButton.disabled = true;
        logoutButton.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Abmelden...';
    });
    
    // Keyboard-Shortcuts
    document.addEventListener('keydown', function(e) {
        // ESC = Abbrechen
        if (e.key === 'Escape') {
            window.location.href = 'index.php';
        }
        
        // Enter = Abmelden bestätigen
        if (e.key === 'Enter' && !e.shiftKey && !e.ctrlKey && !e.altKey) {
            e.preventDefault();
            logoutForm.submit();
        }
    });
});
</script>
<?php $this->stop(); ?>
