<?php

class TemplateEngine
{
    private $templatePath;
    private $variables = [];
    private $sections = [];
    private $currentSection = null;
    
    public function __construct($templatePath = null)
    {
        $this->templatePath = $templatePath ?: __DIR__ . '/../../templates/';
    }
    
    /**
     * Setzt eine Variable für das Template
     */
    public function set($key, $value)
    {
        $this->variables[$key] = $value;
    }
    
    /**
     * Setzt mehrere Variablen auf einmal
     */
    public function setMultiple(array $variables)
    {
        $this->variables = array_merge($this->variables, $variables);
    }
    
    /**
     * Rendert ein Template
     */
    public function render($template, $variables = [])
    {
        // Merge variables
        $templateVars = array_merge($this->variables, $variables);
        
        // Extract variables to current scope
        extract($templateVars, EXTR_SKIP);
        
        // Start output buffering
        ob_start();
        
        // Include template file
        $templateFile = $this->templatePath . $template . '.php';
        if (file_exists($templateFile)) {
            include $templateFile;
        } else {
            throw new Exception("Template nicht gefunden: " . $templateFile);
        }
        
        // Return rendered content
        return ob_get_clean();
    }
    
    /**
     * Rendert ein Template und gibt es direkt aus
     */
    public function display($template, $variables = [])
    {
        echo $this->render($template, $variables);
    }
    
    /**
     * Inkludiert ein Partial-Template
     */
    public function partial($template, $variables = [])
    {
        echo $this->render($template, $variables);
    }
    
    /**
     * Startet eine Section
     */
    public function startSection($name)
    {
        $this->currentSection = $name;
        ob_start();
    }
    
    /**
     * Beendet eine Section
     */
    public function endSection()
    {
        if ($this->currentSection) {
            $this->sections[$this->currentSection] = ob_get_clean();
            $this->currentSection = null;
        }
    }
    
    /**
     * Gibt den Inhalt einer Section aus
     */
    public function section($name, $default = '')
    {
        return isset($this->sections[$name]) ? $this->sections[$name] : $default;
    }
    
    /**
     * Escaped HTML-Output
     */
    public function e($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Generiert eine URL
     */
    public function url($path = '')
    {
        $baseUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }
    
    /**
     * Generiert einen Asset-URL
     */
    public function asset($path)
    {
        return $this->url('src/assets/' . ltrim($path, '/'));
    }
    
    /**
     * Prüft ob der aktuelle Pfad aktiv ist (für Navigation)
     */
    public function isActive($path)
    {
        $currentPath = $_SERVER['REQUEST_URI'];
        return strpos($currentPath, $path) !== false;
    }
    
    /**
     * Generiert CSRF Token
     */
    public function csrfToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Generiert CSRF Input Field
     */
    public function csrfField()
    {
        return '<input type="hidden" name="csrf_token" value="' . $this->csrfToken() . '">';
    }
    
    /**
     * Flash Messages anzeigen
     */
    public function flashMessages()
    {
        if (!isset($_SESSION['flash_messages'])) {
            return '';
        }
        
        $html = '';
        foreach ($_SESSION['flash_messages'] as $type => $messages) {
            foreach ($messages as $message) {
                $html .= '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">';
                $html .= $this->e($message);
                $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                $html .= '</div>';
            }
        }
        
        // Clear flash messages after displaying
        unset($_SESSION['flash_messages']);
        
        return $html;
    }
    
    /**
     * Breadcrumb Navigation
     */
    public function breadcrumb($items = [])
    {
        if (empty($items)) {
            return '';
        }
        
        $html = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';
        
        $lastKey = array_key_last($items);
        foreach ($items as $key => $item) {
            if ($key === $lastKey) {
                $html .= '<li class="breadcrumb-item active" aria-current="page">' . $this->e($item['title']) . '</li>';
            } else {
                $html .= '<li class="breadcrumb-item"><a href="' . $this->e($item['url']) . '">' . $this->e($item['title']) . '</a></li>';
            }
        }
        
        $html .= '</ol></nav>';
        return $html;
    }
}
