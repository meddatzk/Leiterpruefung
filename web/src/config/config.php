<?php
/**
 * Zentrale Konfigurationsverwaltung
 * Lädt alle Konfigurationen aus Umgebungsvariablen
 */

class Config
{
    private static $config = [];
    private static $loaded = false;

    /**
     * Lädt die Konfiguration aus Umgebungsvariablen
     */
    public static function load()
    {
        if (self::$loaded) {
            return;
        }

        // Database Configuration
        self::$config['database'] = [
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'name' => $_ENV['DB_NAME'] ?? 'leiterpruefung',
            'user' => $_ENV['DB_USER'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4'
        ];

        // LDAP Configuration
        self::$config['ldap'] = [
            'server' => $_ENV['LDAP_SERVER'] ?? 'localhost',
            'port' => $_ENV['LDAP_PORT'] ?? '389',
            'base_dn' => $_ENV['LDAP_BASE_DN'] ?? '',
            'bind_dn' => $_ENV['LDAP_BIND_DN'] ?? '',
            'bind_password' => $_ENV['LDAP_BIND_PASSWORD'] ?? '',
            'user_filter' => $_ENV['LDAP_USER_FILTER'] ?? '(uid=%s)',
            'group_filter' => $_ENV['LDAP_GROUP_FILTER'] ?? '(memberUid=%s)',
            'use_tls' => filter_var($_ENV['LDAP_USE_TLS'] ?? 'false', FILTER_VALIDATE_BOOLEAN)
        ];

        // Application Configuration
        self::$config['app'] = [
            'debug' => filter_var($_ENV['APP_DEBUG'] ?? 'false', FILTER_VALIDATE_BOOLEAN),
            'timezone' => $_ENV['APP_TIMEZONE'] ?? 'Europe/Berlin',
            'session_timeout' => (int)($_ENV['SESSION_TIMEOUT'] ?? 3600),
            'session_name' => $_ENV['SESSION_NAME'] ?? 'LEITERPRUEFUNG_SESSION',
            'base_url' => $_ENV['APP_BASE_URL'] ?? 'http://localhost',
            'environment' => $_ENV['APP_ENV'] ?? 'production'
        ];

        // Security Configuration
        self::$config['security'] = [
            'csrf_secret' => $_ENV['CSRF_SECRET'] ?? bin2hex(random_bytes(32)),
            'encryption_key' => $_ENV['ENCRYPTION_KEY'] ?? bin2hex(random_bytes(32)),
            'password_min_length' => (int)($_ENV['PASSWORD_MIN_LENGTH'] ?? 8),
            'max_login_attempts' => (int)($_ENV['MAX_LOGIN_ATTEMPTS'] ?? 5),
            'lockout_duration' => (int)($_ENV['LOCKOUT_DURATION'] ?? 900) // 15 Minuten
        ];

        self::$loaded = true;
    }

    /**
     * Holt einen Konfigurationswert
     * 
     * @param string $key Schlüssel im Format 'section.key' oder 'section'
     * @param mixed $default Standardwert falls Schlüssel nicht existiert
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        self::load();

        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Setzt einen Konfigurationswert zur Laufzeit
     * 
     * @param string $key Schlüssel im Format 'section.key'
     * @param mixed $value Wert
     */
    public static function set($key, $value)
    {
        self::load();

        $keys = explode('.', $key);
        $config = &self::$config;

        foreach ($keys as $k) {
            if (!isset($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }

        $config = $value;
    }

    /**
     * Prüft ob ein Konfigurationswert existiert
     * 
     * @param string $key Schlüssel im Format 'section.key'
     * @return bool
     */
    public static function has($key)
    {
        self::load();

        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return false;
            }
            $value = $value[$k];
        }

        return true;
    }

    /**
     * Gibt die komplette Konfiguration zurück
     * 
     * @return array
     */
    public static function all()
    {
        self::load();
        return self::$config;
    }
}
