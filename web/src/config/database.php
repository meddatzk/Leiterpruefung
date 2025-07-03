<?php
/**
 * Datenbankverbindungsklasse
 * Verwaltet PDO-Verbindungen zur Datenbank
 */

class Database
{
    private static $instance = null;
    private $pdo = null;
    private $config = [];

    /**
     * Private Konstruktor für Singleton-Pattern
     */
    private function __construct()
    {
        $this->config = Config::get('database');
        $this->connect();
    }

    /**
     * Singleton-Instanz abrufen
     * 
     * @return Database
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Stellt die Datenbankverbindung her
     * 
     * @throws PDOException
     */
    private function connect()
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $this->config['host'],
                $this->config['port'],
                $this->config['name'],
                $this->config['charset']
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->config['charset']} COLLATE {$this->config['charset']}_unicode_ci"
            ];

            $this->pdo = new PDO($dsn, $this->config['user'], $this->config['password'], $options);
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new PDOException("Datenbankverbindung fehlgeschlagen");
        }
    }

    /**
     * PDO-Instanz abrufen
     * 
     * @return PDO
     */
    public function getPdo()
    {
        if ($this->pdo === null) {
            $this->connect();
        }
        return $this->pdo;
    }

    /**
     * Prepared Statement ausführen
     * 
     * @param string $sql SQL-Query
     * @param array $params Parameter für das Statement
     * @return PDOStatement
     * @throws PDOException
     */
    public function query($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database query failed: " . $e->getMessage() . " SQL: " . $sql);
            throw $e;
        }
    }

    /**
     * Einzelnen Datensatz abrufen
     * 
     * @param string $sql SQL-Query
     * @param array $params Parameter für das Statement
     * @return array|false
     */
    public function fetch($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    /**
     * Alle Datensätze abrufen
     * 
     * @param string $sql SQL-Query
     * @param array $params Parameter für das Statement
     * @return array
     */
    public function fetchAll($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * INSERT-Statement ausführen und ID zurückgeben
     * 
     * @param string $sql SQL-Query
     * @param array $params Parameter für das Statement
     * @return string Last Insert ID
     */
    public function insert($sql, $params = [])
    {
        $this->query($sql, $params);
        return $this->pdo->lastInsertId();
    }

    /**
     * UPDATE/DELETE-Statement ausführen
     * 
     * @param string $sql SQL-Query
     * @param array $params Parameter für das Statement
     * @return int Anzahl betroffener Zeilen
     */
    public function execute($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Transaktion starten
     */
    public function beginTransaction()
    {
        $this->pdo->beginTransaction();
    }

    /**
     * Transaktion bestätigen
     */
    public function commit()
    {
        $this->pdo->commit();
    }

    /**
     * Transaktion zurückrollen
     */
    public function rollback()
    {
        $this->pdo->rollback();
    }

    /**
     * Prüft ob eine Transaktion aktiv ist
     * 
     * @return bool
     */
    public function inTransaction()
    {
        return $this->pdo->inTransaction();
    }

    /**
     * Verbindung testen
     * 
     * @return bool
     */
    public function testConnection()
    {
        try {
            $this->pdo->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Verhindert Klonen der Instanz
     */
    private function __clone() {}

    /**
     * Verhindert Unserialisierung der Instanz
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}
