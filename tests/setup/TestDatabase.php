<?php

/**
 * Test-Datenbank Setup-Klasse
 * Verwaltet Test-Datenbank und Fixtures für PHPUnit-Tests
 */
class TestDatabase
{
    private static $instance = null;
    private $pdo = null;
    private $testDbPath = ':memory:'; // SQLite In-Memory für Tests

    /**
     * Singleton-Instanz abrufen
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private Konstruktor
     */
    private function __construct()
    {
        $this->createTestDatabase();
    }

    /**
     * Test-Datenbank erstellen (SQLite In-Memory)
     */
    public function createTestDatabase()
    {
        try {
            $this->pdo = new PDO('sqlite:' . $this->testDbPath, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);

            $this->createTables();
            return true;
        } catch (PDOException $e) {
            throw new Exception("Test-Datenbank konnte nicht erstellt werden: " . $e->getMessage());
        }
    }

    /**
     * Tabellen für Tests erstellen
     */
    private function createTables()
    {
        $sql = "
        -- Users Tabelle
        CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(100) NOT NULL UNIQUE,
            email VARCHAR(255),
            first_name VARCHAR(100),
            last_name VARCHAR(100),
            display_name VARCHAR(255),
            groups TEXT, -- JSON als TEXT in SQLite
            ldap_dn VARCHAR(500),
            role VARCHAR(20) DEFAULT 'viewer',
            department VARCHAR(100),
            phone VARCHAR(50),
            is_active BOOLEAN DEFAULT 1,
            last_login TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        -- Ladders Tabelle
        CREATE TABLE ladders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ladder_number VARCHAR(50) NOT NULL UNIQUE,
            manufacturer VARCHAR(100) NOT NULL,
            model VARCHAR(100),
            ladder_type VARCHAR(50) NOT NULL,
            material VARCHAR(50) DEFAULT 'Aluminium',
            max_load_kg INTEGER DEFAULT 150,
            height_cm INTEGER NOT NULL,
            purchase_date DATE,
            location VARCHAR(255) NOT NULL,
            department VARCHAR(100),
            responsible_person VARCHAR(255),
            serial_number VARCHAR(100),
            notes TEXT,
            status VARCHAR(20) DEFAULT 'active',
            next_inspection_date DATE NOT NULL,
            inspection_interval_months INTEGER DEFAULT 12,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        -- Inspections Tabelle
        CREATE TABLE inspections (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ladder_id INTEGER NOT NULL,
            inspector_id INTEGER NOT NULL,
            inspection_date DATE NOT NULL,
            inspection_type VARCHAR(20) DEFAULT 'routine',
            overall_result VARCHAR(20) NOT NULL,
            next_inspection_date DATE NOT NULL,
            inspection_duration_minutes INTEGER,
            weather_conditions VARCHAR(100),
            temperature_celsius INTEGER,
            general_notes TEXT,
            recommendations TEXT,
            defects_found TEXT,
            actions_required TEXT,
            inspector_signature VARCHAR(255),
            supervisor_approval_id INTEGER,
            approval_date TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ladder_id) REFERENCES ladders(id),
            FOREIGN KEY (inspector_id) REFERENCES users(id),
            FOREIGN KEY (supervisor_approval_id) REFERENCES users(id)
        );

        -- Inspection Items Tabelle
        CREATE TABLE inspection_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            inspection_id INTEGER NOT NULL,
            category VARCHAR(50) NOT NULL,
            item_name VARCHAR(255) NOT NULL,
            description TEXT,
            result VARCHAR(20) NOT NULL,
            severity VARCHAR(20),
            notes TEXT,
            photo_path VARCHAR(500),
            repair_required BOOLEAN DEFAULT 0,
            repair_deadline DATE,
            sort_order INTEGER DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (inspection_id) REFERENCES inspections(id)
        );

        -- Audit Log Tabelle
        CREATE TABLE audit_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            table_name VARCHAR(100) NOT NULL,
            record_id INTEGER NOT NULL,
            action VARCHAR(10) NOT NULL,
            old_values TEXT, -- JSON als TEXT
            new_values TEXT, -- JSON als TEXT
            user_id INTEGER,
            user_ip VARCHAR(45),
            user_agent TEXT,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        );

        -- System Config Tabelle
        CREATE TABLE system_config (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            config_key VARCHAR(100) NOT NULL UNIQUE,
            config_value TEXT,
            config_type VARCHAR(20) DEFAULT 'string',
            description TEXT,
            is_editable BOOLEAN DEFAULT 1,
            category VARCHAR(50) DEFAULT 'general',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        ";

        $this->pdo->exec($sql);
    }

    /**
     * Testdaten einfügen
     */
    public function seedTestData()
    {
        $this->createMockLdapUsers();
        $this->createTestLadders();
        $this->createTestInspections();
        $this->createSystemConfig();
    }

    /**
     * Mock LDAP-Benutzer erstellen
     */
    public function createMockLdapUsers()
    {
        $users = [
            [
                'username' => 'test.inspector',
                'email' => 'inspector@test.com',
                'first_name' => 'Max',
                'last_name' => 'Mustermann',
                'display_name' => 'Max Mustermann',
                'groups' => json_encode(['inspectors', 'users']),
                'ldap_dn' => 'cn=Max Mustermann,ou=users,dc=test,dc=com',
                'role' => 'inspector',
                'department' => 'Sicherheit',
                'is_active' => 1
            ],
            [
                'username' => 'test.admin',
                'email' => 'admin@test.com',
                'first_name' => 'Anna',
                'last_name' => 'Admin',
                'display_name' => 'Anna Admin',
                'groups' => json_encode(['admins', 'inspectors', 'users']),
                'ldap_dn' => 'cn=Anna Admin,ou=users,dc=test,dc=com',
                'role' => 'admin',
                'department' => 'IT',
                'is_active' => 1
            ],
            [
                'username' => 'test.viewer',
                'email' => 'viewer@test.com',
                'first_name' => 'Peter',
                'last_name' => 'Viewer',
                'display_name' => 'Peter Viewer',
                'groups' => json_encode(['users']),
                'ldap_dn' => 'cn=Peter Viewer,ou=users,dc=test,dc=com',
                'role' => 'viewer',
                'department' => 'Verwaltung',
                'is_active' => 1
            ]
        ];

        $stmt = $this->pdo->prepare("
            INSERT INTO users (username, email, first_name, last_name, display_name, groups, ldap_dn, role, department, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($users as $user) {
            $stmt->execute([
                $user['username'], $user['email'], $user['first_name'], $user['last_name'],
                $user['display_name'], $user['groups'], $user['ldap_dn'], $user['role'],
                $user['department'], $user['is_active']
            ]);
        }
    }

    /**
     * Test-Leitern erstellen
     */
    public function createTestLadders()
    {
        $ladders = [
            [
                'ladder_number' => 'L-001',
                'manufacturer' => 'Hailo',
                'model' => 'ProfiStep',
                'ladder_type' => 'Stehleiter',
                'material' => 'Aluminium',
                'max_load_kg' => 150,
                'height_cm' => 200,
                'purchase_date' => '2023-01-15',
                'location' => 'Lager A',
                'department' => 'Logistik',
                'responsible_person' => 'Hans Müller',
                'serial_number' => 'HAI-2023-001',
                'status' => 'active',
                'next_inspection_date' => '2024-01-15',
                'inspection_interval_months' => 12
            ],
            [
                'ladder_number' => 'L-002',
                'manufacturer' => 'Zarges',
                'model' => 'Skymaster',
                'ladder_type' => 'Anlegeleiter',
                'material' => 'Aluminium',
                'max_load_kg' => 120,
                'height_cm' => 300,
                'purchase_date' => '2022-06-10',
                'location' => 'Werkstatt',
                'department' => 'Wartung',
                'responsible_person' => 'Maria Schmidt',
                'serial_number' => 'ZAR-2022-002',
                'status' => 'active',
                'next_inspection_date' => '2024-06-10',
                'inspection_interval_months' => 12
            ],
            [
                'ladder_number' => 'L-003',
                'manufacturer' => 'Günzburger',
                'model' => 'Holz Classic',
                'ladder_type' => 'Anlegeleiter',
                'material' => 'Holz',
                'max_load_kg' => 100,
                'height_cm' => 250,
                'purchase_date' => '2020-03-20',
                'location' => 'Außenlager',
                'department' => 'Garten',
                'status' => 'defective',
                'next_inspection_date' => '2023-03-20',
                'inspection_interval_months' => 12,
                'notes' => 'Defekte Sprosse entdeckt'
            ]
        ];

        $stmt = $this->pdo->prepare("
            INSERT INTO ladders (ladder_number, manufacturer, model, ladder_type, material, max_load_kg, height_cm,
                               purchase_date, location, department, responsible_person, serial_number, status,
                               next_inspection_date, inspection_interval_months, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($ladders as $ladder) {
            $stmt->execute([
                $ladder['ladder_number'], $ladder['manufacturer'], $ladder['model'], $ladder['ladder_type'],
                $ladder['material'], $ladder['max_load_kg'], $ladder['height_cm'], $ladder['purchase_date'],
                $ladder['location'], $ladder['department'], $ladder['responsible_person'] ?? null,
                $ladder['serial_number'] ?? null, $ladder['status'], $ladder['next_inspection_date'],
                $ladder['inspection_interval_months'], $ladder['notes'] ?? null
            ]);
        }
    }

    /**
     * Test-Prüfungen erstellen
     */
    public function createTestInspections()
    {
        $inspections = [
            [
                'ladder_id' => 1,
                'inspector_id' => 1,
                'inspection_date' => '2024-01-15',
                'inspection_type' => 'routine',
                'overall_result' => 'passed',
                'next_inspection_date' => '2025-01-15',
                'inspection_duration_minutes' => 30,
                'weather_conditions' => 'trocken',
                'temperature_celsius' => 20,
                'general_notes' => 'Leiter in gutem Zustand',
                'inspector_signature' => 'Max Mustermann'
            ],
            [
                'ladder_id' => 2,
                'inspector_id' => 1,
                'inspection_date' => '2024-06-10',
                'inspection_type' => 'routine',
                'overall_result' => 'conditional',
                'next_inspection_date' => '2025-06-10',
                'inspection_duration_minutes' => 45,
                'weather_conditions' => 'feucht',
                'temperature_celsius' => 15,
                'general_notes' => 'Leichte Abnutzung festgestellt',
                'recommendations' => 'Regelmäßige Reinigung empfohlen',
                'inspector_signature' => 'Max Mustermann'
            ]
        ];

        $stmt = $this->pdo->prepare("
            INSERT INTO inspections (ladder_id, inspector_id, inspection_date, inspection_type, overall_result,
                                   next_inspection_date, inspection_duration_minutes, weather_conditions,
                                   temperature_celsius, general_notes, recommendations, inspector_signature)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($inspections as $inspection) {
            $stmt->execute([
                $inspection['ladder_id'], $inspection['inspector_id'], $inspection['inspection_date'],
                $inspection['inspection_type'], $inspection['overall_result'], $inspection['next_inspection_date'],
                $inspection['inspection_duration_minutes'], $inspection['weather_conditions'],
                $inspection['temperature_celsius'], $inspection['general_notes'],
                $inspection['recommendations'] ?? null, $inspection['inspector_signature']
            ]);
        }
    }

    /**
     * System-Konfiguration erstellen
     */
    public function createSystemConfig()
    {
        $configs = [
            ['app_name', 'Test Leiterprüfung', 'string', 'Test-Anwendungsname'],
            ['default_inspection_interval', '12', 'integer', 'Standard-Prüfintervall'],
            ['reminder_days_before', '30', 'integer', 'Erinnerung vor Prüftermin']
        ];

        $stmt = $this->pdo->prepare("
            INSERT INTO system_config (config_key, config_value, config_type, description)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($configs as $config) {
            $stmt->execute($config);
        }
    }

    /**
     * Testdaten löschen
     */
    public function cleanupTestData()
    {
        $tables = ['audit_log', 'inspection_items', 'inspections', 'ladders', 'users', 'system_config'];
        
        foreach ($tables as $table) {
            $this->pdo->exec("DELETE FROM {$table}");
        }
    }

    /**
     * Datenbank komplett zurücksetzen
     */
    public function resetDatabase()
    {
        $this->cleanupTestData();
        $this->seedTestData();
    }

    /**
     * PDO-Instanz für Tests abrufen
     */
    public function getPdo()
    {
        return $this->pdo;
    }

    /**
     * Spezifische Test-Leiter erstellen
     */
    public function createTestLadder($data = [])
    {
        $defaults = [
            'ladder_number' => 'TEST-' . uniqid(),
            'manufacturer' => 'Test Manufacturer',
            'ladder_type' => 'Stehleiter',
            'material' => 'Aluminium',
            'max_load_kg' => 150,
            'height_cm' => 200,
            'location' => 'Test Location',
            'status' => 'active',
            'next_inspection_date' => date('Y-m-d', strtotime('+1 year')),
            'inspection_interval_months' => 12
        ];

        $ladderData = array_merge($defaults, $data);

        $stmt = $this->pdo->prepare("
            INSERT INTO ladders (ladder_number, manufacturer, model, ladder_type, material, max_load_kg, height_cm,
                               purchase_date, location, department, responsible_person, serial_number, status,
                               next_inspection_date, inspection_interval_months, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $ladderData['ladder_number'], $ladderData['manufacturer'], $ladderData['model'] ?? null,
            $ladderData['ladder_type'], $ladderData['material'], $ladderData['max_load_kg'],
            $ladderData['height_cm'], $ladderData['purchase_date'] ?? null, $ladderData['location'],
            $ladderData['department'] ?? null, $ladderData['responsible_person'] ?? null,
            $ladderData['serial_number'] ?? null, $ladderData['status'],
            $ladderData['next_inspection_date'], $ladderData['inspection_interval_months'],
            $ladderData['notes'] ?? null
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Spezifische Test-Prüfung erstellen
     */
    public function createTestInspection($data = [])
    {
        $defaults = [
            'ladder_id' => 1,
            'inspector_id' => 1,
            'inspection_date' => date('Y-m-d'),
            'inspection_type' => 'routine',
            'overall_result' => 'passed',
            'next_inspection_date' => date('Y-m-d', strtotime('+1 year'))
        ];

        $inspectionData = array_merge($defaults, $data);

        $stmt = $this->pdo->prepare("
            INSERT INTO inspections (ladder_id, inspector_id, inspection_date, inspection_type, overall_result,
                                   next_inspection_date, inspection_duration_minutes, weather_conditions,
                                   temperature_celsius, general_notes, recommendations, defects_found,
                                   actions_required, inspector_signature)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $inspectionData['ladder_id'], $inspectionData['inspector_id'], $inspectionData['inspection_date'],
            $inspectionData['inspection_type'], $inspectionData['overall_result'], $inspectionData['next_inspection_date'],
            $inspectionData['inspection_duration_minutes'] ?? null, $inspectionData['weather_conditions'] ?? null,
            $inspectionData['temperature_celsius'] ?? null, $inspectionData['general_notes'] ?? null,
            $inspectionData['recommendations'] ?? null, $inspectionData['defects_found'] ?? null,
            $inspectionData['actions_required'] ?? null, $inspectionData['inspector_signature'] ?? null
        ]);

        return $this->pdo->lastInsertId();
    }
}
