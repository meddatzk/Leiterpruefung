-- =====================================================
-- Leiterprüfung - Datenbankschema
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Datenbank erstellen falls nicht vorhanden
CREATE DATABASE IF NOT EXISTS leiterprüfung 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE leiterprüfung;

-- =====================================================
-- Tabelle: users (Benutzer-Cache)
-- =====================================================
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    full_name VARCHAR(255) NOT NULL,
    role ENUM('admin', 'inspector', 'viewer') NOT NULL DEFAULT 'viewer',
    department VARCHAR(100),
    phone VARCHAR(50),
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_active (is_active)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- =====================================================
-- Tabelle: ladders (Leitern-Stammdaten)
-- =====================================================
DROP TABLE IF EXISTS ladders;
CREATE TABLE ladders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ladder_number VARCHAR(50) NOT NULL UNIQUE COMMENT 'Eindeutige Leiternummer',
    manufacturer VARCHAR(100) NOT NULL,
    model VARCHAR(100),
    ladder_type ENUM('Anlegeleiter', 'Stehleiter', 'Mehrzweckleiter', 'Podestleiter', 'Schiebeleiter') NOT NULL,
    material ENUM('Aluminium', 'Holz', 'Fiberglas', 'Stahl') NOT NULL DEFAULT 'Aluminium',
    max_load_kg INT NOT NULL DEFAULT 150,
    height_cm INT NOT NULL,
    purchase_date DATE,
    location VARCHAR(255) NOT NULL COMMENT 'Standort der Leiter',
    department VARCHAR(100),
    responsible_person VARCHAR(255),
    serial_number VARCHAR(100),
    notes TEXT,
    status ENUM('active', 'inactive', 'defective', 'disposed') NOT NULL DEFAULT 'active',
    next_inspection_date DATE NOT NULL,
    inspection_interval_months INT NOT NULL DEFAULT 12,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_ladder_number (ladder_number),
    INDEX idx_status (status),
    INDEX idx_next_inspection (next_inspection_date),
    INDEX idx_location (location),
    INDEX idx_department (department),
    INDEX idx_ladder_type (ladder_type)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- =====================================================
-- Tabelle: inspections (Prüfungen)
-- =====================================================
DROP TABLE IF EXISTS inspections;
CREATE TABLE inspections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ladder_id INT NOT NULL,
    inspector_id INT NOT NULL,
    inspection_date DATE NOT NULL,
    inspection_type ENUM('routine', 'initial', 'after_incident', 'special') NOT NULL DEFAULT 'routine',
    overall_result ENUM('passed', 'failed', 'conditional') NOT NULL,
    next_inspection_date DATE NOT NULL,
    inspection_duration_minutes INT,
    weather_conditions VARCHAR(100),
    temperature_celsius INT,
    general_notes TEXT,
    recommendations TEXT,
    defects_found TEXT,
    actions_required TEXT,
    inspector_signature VARCHAR(255),
    supervisor_approval_id INT NULL,
    approval_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (ladder_id) REFERENCES ladders(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (inspector_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (supervisor_approval_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    
    INDEX idx_ladder_inspection (ladder_id, inspection_date),
    INDEX idx_inspector (inspector_id),
    INDEX idx_inspection_date (inspection_date),
    INDEX idx_result (overall_result),
    INDEX idx_next_inspection (next_inspection_date),
    INDEX idx_type (inspection_type)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- =====================================================
-- Tabelle: inspection_items (Prüfpunkte)
-- =====================================================
DROP TABLE IF EXISTS inspection_items;
CREATE TABLE inspection_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inspection_id INT NOT NULL,
    category ENUM('structure', 'safety', 'function', 'marking', 'accessories') NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    description TEXT,
    result ENUM('ok', 'defect', 'wear', 'not_applicable') NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NULL,
    notes TEXT,
    photo_path VARCHAR(500),
    repair_required BOOLEAN NOT NULL DEFAULT FALSE,
    repair_deadline DATE NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (inspection_id) REFERENCES inspections(id) ON DELETE CASCADE ON UPDATE CASCADE,
    
    INDEX idx_inspection_category (inspection_id, category),
    INDEX idx_result (result),
    INDEX idx_severity (severity),
    INDEX idx_repair_required (repair_required),
    INDEX idx_sort_order (sort_order)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- =====================================================
-- Tabelle: audit_log (Audit-Trail)
-- =====================================================
DROP TABLE IF EXISTS audit_log;
CREATE TABLE audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(100) NOT NULL,
    record_id INT NOT NULL,
    action ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    user_id INT NULL,
    user_ip VARCHAR(45),
    user_agent TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    
    INDEX idx_table_record (table_name, record_id),
    INDEX idx_action (action),
    INDEX idx_user (user_id),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- =====================================================
-- Tabelle: system_config (System-Konfiguration)
-- =====================================================
DROP TABLE IF EXISTS system_config;
CREATE TABLE system_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) NOT NULL UNIQUE,
    config_value TEXT,
    config_type ENUM('string', 'integer', 'boolean', 'json', 'date') NOT NULL DEFAULT 'string',
    description TEXT,
    is_editable BOOLEAN NOT NULL DEFAULT TRUE,
    category VARCHAR(50) NOT NULL DEFAULT 'general',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_key (config_key),
    INDEX idx_category (category),
    INDEX idx_editable (is_editable)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- Standard-Konfigurationswerte
-- =====================================================
INSERT INTO system_config (config_key, config_value, config_type, description, category) VALUES
('app_name', 'Leiterprüfung System', 'string', 'Name der Anwendung', 'general'),
('app_version', '1.0.0', 'string', 'Version der Anwendung', 'general'),
('default_inspection_interval', '12', 'integer', 'Standard-Prüfintervall in Monaten', 'inspection'),
('reminder_days_before', '30', 'integer', 'Erinnerung X Tage vor Prüftermin', 'inspection'),
('max_file_upload_mb', '10', 'integer', 'Maximale Dateigröße für Uploads in MB', 'system'),
('enable_email_notifications', 'true', 'boolean', 'E-Mail-Benachrichtigungen aktiviert', 'notification'),
('company_name', 'Musterfirma GmbH', 'string', 'Name des Unternehmens', 'general'),
('company_address', 'Musterstraße 1, 12345 Musterstadt', 'string', 'Adresse des Unternehmens', 'general'),
('audit_retention_days', '2555', 'integer', 'Aufbewahrungszeit für Audit-Logs in Tagen (7 Jahre)', 'audit'),
('backup_retention_days', '90', 'integer', 'Aufbewahrungszeit für Backups in Tagen', 'system');
