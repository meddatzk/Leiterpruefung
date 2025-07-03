<?php $this->layout('base', [
    'title' => $content['error_title'] ?? 'Fehler',
    'body_class' => 'error-page ' . ($content['page_class'] ?? ''),
    'hide_sidebar' => true,
    'user_name' => $content['user_info']['display_name'] ?? null
]); ?>

<?php $this->start('head'); ?>
<style>
.error-page {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
    min-height: 100vh;
}

.error-page .header {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.error-page .footer {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-top: 1px solid rgba(255, 255, 255, 0.2);
}

.error-container {
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
    justify-content: center;
}

.error-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 15px;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    max-width: 600px;
    width: 100%;
    padding: 3rem;
    text-align: center;
}

.error-icon {
    font-size: 4rem;
    color: #ff6b6b;
    margin-bottom: 1.5rem;
}

.error-code {
    font-size: 6rem;
    font-weight: 700;
    color: #ff6b6b;
    line-height: 1;
    margin-bottom: 1rem;
}

.error-title {
    font-size: 2rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 1rem;
}

.error-message {
    font-size: 1.1rem;
    color: #666;
    margin-bottom: 2rem;
    line-height: 1.6;
}

.error-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 10px;
    padding: 0.75rem 2rem;
    font-weight: 600;
    color: white;
    text-decoration: none;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    color: white;
    text-decoration: none;
}

.btn-secondary {
    background: #6c757d;
    border: none;
    border-radius: 10px;
    padding: 0.75rem 2rem;
    font-weight: 600;
    color: white;
    text-decoration: none;
    transition: all 0.3s ease;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(108, 117, 125, 0.4);
    color: white;
    text-decoration: none;
}

.user-info {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 1.5rem;
    margin-top: 2rem;
    text-align: left;
}

.user-info-title {
    font-weight: 600;
    color: #333;
    margin-bottom: 1rem;
    text-align: center;
}

.user-info-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.user-info-label {
    color: #666;
    font-weight: 500;
}

.user-info-value {
    color: #333;
}

.user-groups {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
}

.group-badge {
    background: #e9ecef;
    color: #495057;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.access-denied .error-icon {
    color: #ffc107;
}

.access-denied .error-code {
    color: #ffc107;
}
</style>
<?php $this->stop(); ?>

<?php $this->start('content'); ?>
<div class="error-container">
    <div class="error-card">
        <div class="error-icon">
            <?php if (($content['error_code'] ?? 0) == 403): ?>
                <i class="bi bi-shield-exclamation"></i>
            <?php elseif (($content['error_code'] ?? 0) == 404): ?>
                <i class="bi bi-file-earmark-x"></i>
            <?php else: ?>
                <i class="bi bi-exclamation-triangle"></i>
            <?php endif; ?>
        </div>

        <?php if (!empty($content['error_code'])): ?>
            <div class="error-code"><?= $this->e($content['error_code']) ?></div>
        <?php endif; ?>

        <h1 class="error-title"><?= $this->e($content['error_title'] ?? 'Fehler') ?></h1>
        
        <p class="error-message">
            <?= $this->e($content['error_message'] ?? 'Ein unerwarteter Fehler ist aufgetreten.') ?>
        </p>

        <div class="error-actions">
            <?php if (($content['error_code'] ?? 0) == 403): ?>
                <a href="index.php" class="btn btn-primary">
                    <i class="bi bi-house me-2"></i>
                    Zur Startseite
                </a>
                <a href="javascript:history.back()" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-2"></i>
                    Zurück
                </a>
            <?php else: ?>
                <a href="index.php" class="btn btn-primary">
                    <i class="bi bi-house me-2"></i>
                    Zur Startseite
                </a>
                <a href="javascript:location.reload()" class="btn btn-secondary">
                    <i class="bi bi-arrow-clockwise me-2"></i>
                    Neu laden
                </a>
            <?php endif; ?>
        </div>

        <?php if (!empty($content['user_info']) && ($content['error_code'] ?? 0) == 403): ?>
        <div class="user-info">
            <div class="user-info-title">
                <i class="bi bi-person-circle me-2"></i>
                Ihre Benutzerinformationen
            </div>
            
            <?php if (!empty($content['user_info']['username'])): ?>
            <div class="user-info-item">
                <span class="user-info-label">Benutzername:</span>
                <span class="user-info-value"><?= $this->e($content['user_info']['username']) ?></span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($content['user_info']['display_name'])): ?>
            <div class="user-info-item">
                <span class="user-info-label">Name:</span>
                <span class="user-info-value"><?= $this->e($content['user_info']['display_name']) ?></span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($content['user_info']['groups'])): ?>
            <div class="user-info-item">
                <span class="user-info-label">Gruppen:</span>
                <div class="user-groups">
                    <?php foreach ($content['user_info']['groups'] as $group): ?>
                        <span class="group-badge"><?= $this->e($group) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php $this->stop(); ?>

<?php $this->start('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Keyboard-Shortcuts
    document.addEventListener('keydown', function(e) {
        // ESC oder Backspace = Zurück
        if (e.key === 'Escape' || e.key === 'Backspace') {
            e.preventDefault();
            history.back();
        }
        
        // Enter = Zur Startseite
        if (e.key === 'Enter' && !e.shiftKey && !e.ctrlKey && !e.altKey) {
            e.preventDefault();
            window.location.href = 'index.php';
        }
        
        // F5 = Neu laden
        if (e.key === 'F5') {
            e.preventDefault();
            location.reload();
        }
    });
    
    // Auto-Redirect nach 30 Sekunden bei 403-Fehlern
    <?php if (($content['error_code'] ?? 0) == 403): ?>
    setTimeout(function() {
        if (confirm('Möchten Sie zur Startseite weitergeleitet werden?')) {
            window.location.href = 'index.php';
        }
    }, 30000);
    <?php endif; ?>
});
</script>
<?php $this->stop(); ?>
