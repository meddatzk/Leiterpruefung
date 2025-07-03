<?php
/**
 * Bootstrap-Datei
 * Initialisiert die Anwendung mit Session-Start, Autoloader und Error-Handling
 */

// Fehlerberichterstattung basierend auf Umgebung
if (!defined('APP_STARTED')) {
    define('APP_STARTED', true);
    
    // Basis-Pfade definieren
    define('ROOT_PATH', dirname(dirname(__DIR__)));
    define('SRC_PATH', ROOT_PATH . '/src');
    define('CONFIG_PATH', SRC_PATH . '/config');
    define('INCLUDES_PATH', SRC_PATH . '/includes');
    define('ASSETS_PATH', SRC_PATH . '/assets');
    define('PUBLIC_PATH', ROOT_PATH . '/public');

    // Autoloader registrieren
    spl_autoload_register(function ($className) {
        $directories = [
            SRC_PATH . '/config/',
            SRC_PATH . '/includes/',
            SRC_PATH . '/classes/',
            SRC_PATH . '/models/',
            SRC_PATH . '/controllers/',
            SRC_PATH . '/services/',
            SRC_PATH . '/middleware/',
            SRC_PATH . '/exceptions/'
        ];

        foreach ($directories as $directory) {
            $file = $directory . $className . '.php';
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    });

    // Konfiguration laden
    require_once CONFIG_PATH . '/config.php';
    require_once CONFIG_PATH . '/database.php';
    require_once INCLUDES_PATH . '/functions.php';
    
    // Authentifizierungsklassen laden
    require_once INCLUDES_PATH . '/LdapAuth.php';
    require_once INCLUDES_PATH . '/SessionManager.php';
    require_once INCLUDES_PATH . '/User.php';
    require_once INCLUDES_PATH . '/auth_middleware.php';
    require_once INCLUDES_PATH . '/TemplateEngine.php';

    // Konfiguration initialisieren
    Config::load();

    // Error-Handling konfigurieren
    setupErrorHandling();

    // Timezone setzen
    date_default_timezone_set(Config::get('app.timezone', 'Europe/Berlin'));

    // Session konfigurieren und starten
    setupSession();

    // CSRF-Token generieren falls nicht vorhanden
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateCSRFToken();
    }
}

/**
 * Error-Handling konfigurieren
 */
function setupErrorHandling()
{
    $debug = Config::get('app.debug', false);
    
    if ($debug) {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
    } else {
        error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
        ini_set('display_errors', 0);
        ini_set('display_startup_errors', 0);
    }

    // Custom Error Handler
    set_error_handler(function($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        $errorTypes = [
            E_ERROR => 'Fatal Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_STRICT => 'Strict Notice',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated'
        ];

        $errorType = $errorTypes[$severity] ?? 'Unknown Error';
        $logMessage = sprintf(
            "[%s] %s: %s in %s on line %d",
            date('Y-m-d H:i:s'),
            $errorType,
            $message,
            $file,
            $line
        );

        error_log($logMessage);

        if (Config::get('app.debug', false)) {
            echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 10px; margin: 10px; border-radius: 4px;'>";
            echo "<strong>{$errorType}:</strong> {$message}<br>";
            echo "<strong>File:</strong> {$file}<br>";
            echo "<strong>Line:</strong> {$line}";
            echo "</div>";
        }

        return true;
    });

    // Exception Handler
    set_exception_handler(function($exception) {
        $logMessage = sprintf(
            "[%s] Uncaught Exception: %s in %s on line %d\nStack trace:\n%s",
            date('Y-m-d H:i:s'),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );

        error_log($logMessage);

        if (Config::get('app.debug', false)) {
            echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 15px; margin: 10px; border-radius: 4px;'>";
            echo "<h3>Uncaught Exception</h3>";
            echo "<strong>Message:</strong> " . htmlspecialchars($exception->getMessage()) . "<br>";
            echo "<strong>File:</strong> " . htmlspecialchars($exception->getFile()) . "<br>";
            echo "<strong>Line:</strong> " . $exception->getLine() . "<br>";
            echo "<strong>Stack Trace:</strong><pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
            echo "</div>";
        } else {
            echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 15px; margin: 10px; border-radius: 4px;'>";
            echo "<h3>Ein Fehler ist aufgetreten</h3>";
            echo "<p>Bitte versuchen Sie es später erneut oder kontaktieren Sie den Administrator.</p>";
            echo "</div>";
        }
    });

    // Fatal Error Handler
    register_shutdown_function(function() {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $logMessage = sprintf(
                "[%s] Fatal Error: %s in %s on line %d",
                date('Y-m-d H:i:s'),
                $error['message'],
                $error['file'],
                $error['line']
            );
            error_log($logMessage);

            if (!Config::get('app.debug', false)) {
                echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 15px; margin: 10px; border-radius: 4px;'>";
                echo "<h3>Ein schwerwiegender Fehler ist aufgetreten</h3>";
                echo "<p>Die Anwendung konnte nicht ordnungsgemäß geladen werden.</p>";
                echo "</div>";
            }
        }
    });
}

/**
 * Session konfigurieren und starten
 */
function setupSession()
{
    // Session-Konfiguration
    $sessionConfig = [
        'cookie_lifetime' => Config::get('app.session_timeout', 3600),
        'cookie_path' => '/',
        'cookie_domain' => '',
        'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true,
        'use_cookies' => true,
        'use_only_cookies' => true,
        'use_trans_sid' => false,
        'cache_limiter' => 'nocache',
        'gc_maxlifetime' => Config::get('app.session_timeout', 3600)
    ];

    foreach ($sessionConfig as $key => $value) {
        ini_set("session.{$key}", $value);
    }

    // Session-Name setzen
    session_name(Config::get('app.session_name', 'LEITERPRUEFUNG_SESSION'));

    // Session starten falls noch nicht gestartet
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Session-Sicherheit
    if (!isset($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
        $_SESSION['created'] = time();
        $_SESSION['last_activity'] = time();
    }

    // Session-Timeout prüfen
    $timeout = Config::get('app.session_timeout', 3600);
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['timeout'] = true;
    } else {
        $_SESSION['last_activity'] = time();
    }

    // Session-ID regelmäßig erneuern (alle 30 Minuten)
    if (isset($_SESSION['created']) && (time() - $_SESSION['created']) > 1800) {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

/**
 * CSRF-Token generieren
 * 
 * @return string
 */
function generateCSRFToken()
{
    return bin2hex(random_bytes(32));
}

/**
 * Anwendung herunterfahren
 */
function shutdownApplication()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
}

// Shutdown-Handler registrieren
register_shutdown_function('shutdownApplication');
