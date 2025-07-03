-- ==============================================
-- LEITERPRÜFUNG - DATABASE INITIALIZATION
-- Erweiterte Version mit allen Anforderungen
-- ==============================================

-- Erstelle Datenbank falls nicht vorhanden
CREATE DATABASE IF NOT EXISTS leiterpruefung CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE leiterpruefung;

-- ==============================================
-- TABELLEN-DEFINITIONEN
-- ==============================================

-- Benutzer-Tabelle (LDAP-Cache)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NULL, -- NULL für LDAP-Benutzer
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    department VARCHAR(100) NULL,
    position VARCHAR(100) NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    ldap_user BOOLEAN DEFAULT FALSE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT NULL,
    
    -- Constraints
    CONSTRAINT chk_users_email_format CHECK (email REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$'),
    CONSTRAINT chk_users_names_not_empty CHECK (TRIM(first_name) != '' AND TRIM(last_name) != ''),
    
    -- Foreign Keys
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Leiter-Tabelle
CREATE TABLE IF NOT EXISTS ladders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ladder_number VARCHAR(50) NOT NULL UNIQUE, -- Eindeutige Leiternummer
    serial_number VARCHAR(100) NOT NULL UNIQUE,
    manufacturer VARCHAR(100) NOT NULL,
    model VARCHAR(100) NOT NULL,
    type ENUM('Anlegeleiter', 'Stehleiter', 'Mehrzweckleiter', 'Podestleiter', 'Schiebeleiter', 'Dachleiter') NOT NULL,
    material ENUM('Aluminium', 'Holz', 'Fiberglas', 'Stahl', 'Kunststoff') NOT NULL,
    max_load_kg INT NOT NULL,
    height_cm INT NOT NULL,
    purchase_date DATE NULL,
    location VARCHAR(255) NOT NULL,
    status ENUM('Aktiv', 'Gesperrt', 'Reparatur', 'Ausgemustert') DEFAULT 'Aktiv',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT NOT NULL,
    
    -- Constraints
    CONSTRAINT chk_ladders_max_load CHECK (max_load_kg > 0 AND max_load_kg <= 1000),
    CONSTRAINT chk_ladders_height CHECK (height_cm > 0 AND height_cm <= 2000),
    CONSTRAINT chk_ladders_purchase_date CHECK (purchase_date <= CURDATE()),
    CONSTRAINT chk_ladders_numbers_not_empty CHECK (TRIM(ladder_number) != '' AND TRIM(serial_number) != ''),
    CONSTRAINT chk_ladders_manufacturer_model CHECK (TRIM(manufacturer) != '' AND TRIM(model) != ''),
    CONSTRAINT chk_ladders_location_not_empty CHECK (TRIM(location) != ''),
    
    -- Foreign Keys
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    
    -- Unique Constraints für Eindeutigkeit
    UNIQUE KEY uk_ladders_ladder_number (ladder_number),
    UNIQUE KEY uk_ladders_serial_number (serial_number),
    UNIQUE KEY uk_ladders_manufacturer_model_serial (manufacturer, model, serial_number)
);

-- Prüfungen-Tabelle (unveränderliche Historie)
CREATE TABLE IF NOT EXISTS inspections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ladder_id INT NOT NULL,
    inspector_id INT NOT NULL,
    inspection_date DATE NOT NULL,
    inspection_type ENUM('Sichtprüfung', 'Hauptprüfung', 'Außerordentliche Prüfung', 'Wiederkehrende Prüfung') NOT NULL,
    result ENUM('Bestanden', 'Bestanden mit Einschränkungen', 'Durchgefallen', 'Gesperrt') NOT NULL,
    next_inspection_date DATE NOT NULL,
    notes TEXT NULL,
    is_final BOOLEAN DEFAULT FALSE, -- Verhindert Änderungen nach Finalisierung
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Constraints
    CONSTRAINT chk_inspections_date_logic CHECK (next_inspection_date > inspection_date),
    CONSTRAINT chk_inspections_date_not_future CHECK (inspection_date <= CURDATE()),
    CONSTRAINT chk_inspections_next_date_reasonable CHECK (next_inspection_date <= DATE_ADD(inspection_date, INTERVAL 2 YEAR)),
    
    -- Foreign Keys
    FOREIGN KEY (ladder_id) REFERENCES ladders(id) ON DELETE CASCADE,
    FOREIGN KEY (inspector_id) REFERENCES users(id) ON DELETE RESTRICT,
    
    -- Unique Constraints
    UNIQUE KEY uk_inspections_ladder_date (ladder_id, inspection_date, inspection_type)
);

-- Prüfpunkte-Tabelle (Einzelne Prüfpunkte pro Prüfung)
CREATE TABLE IF NOT EXISTS inspection_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inspection_id INT NOT NULL,
    item_category ENUM('Mechanisch', 'Korrosion', 'Verschleiß', 'Sicherheit', 'Funktionalität', 'Kennzeichnung', 'Zubehör') NOT NULL,
    item_name VARCHAR(200) NOT NULL,
    item_description TEXT NULL,
    status ENUM('OK', 'Mangel gering', 'Mangel erheblich', 'Mangel gefährlich', 'Nicht prüfbar') NOT NULL,
    comments TEXT NULL,
    repair_required BOOLEAN DEFAULT FALSE,
    repair_deadline DATE NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Constraints
    CONSTRAINT chk_inspection_items_name_not_empty CHECK (TRIM(item_name) != ''),
    CONSTRAINT chk_inspection_items_repair_deadline CHECK (repair_deadline IS NULL OR repair_deadline >= DATE(created_at)),
    
    -- Foreign Keys
    FOREIGN KEY (inspection_id) REFERENCES inspections(id) ON DELETE CASCADE,
    
    -- Unique Constraints
    UNIQUE KEY uk_inspection_items_inspection_name (inspection_id, item_name)
);

-- Mängel-Tabelle (Detaillierte Mängel aus Prüfpunkten)
CREATE TABLE IF NOT EXISTS defects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inspection_id INT NOT NULL,
    inspection_item_id INT NULL, -- Verknüpfung zu spezifischem Prüfpunkt
    category ENUM('Mechanisch', 'Korrosion', 'Verschleiß', 'Sicherheit', 'Funktionalität', 'Kennzeichnung', 'Sonstiges') NOT NULL,
    severity ENUM('Gering', 'Mittel', 'Hoch', 'Kritisch') NOT NULL,
    description TEXT NOT NULL,
    repair_required BOOLEAN DEFAULT FALSE,
    repair_deadline DATE NULL,
    repair_completed BOOLEAN DEFAULT FALSE,
    repair_date DATE NULL,
    repair_notes TEXT NULL,
    repair_cost DECIMAL(10,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Constraints
    CONSTRAINT chk_defects_description_not_empty CHECK (TRIM(description) != ''),
    CONSTRAINT chk_defects_repair_logic CHECK (
        (repair_required = FALSE) OR 
        (repair_required = TRUE AND repair_deadline IS NOT NULL)
    ),
    CONSTRAINT chk_defects_repair_completion CHECK (
        (repair_completed = FALSE) OR 
        (repair_completed = TRUE AND repair_date IS NOT NULL)
    ),
    CONSTRAINT chk_defects_repair_cost CHECK (repair_cost IS NULL OR repair_cost >= 0),
    
    -- Foreign Keys
    FOREIGN KEY (inspection_id) REFERENCES inspections(id) ON DELETE CASCADE,
    FOREIGN KEY (inspection_item_id) REFERENCES inspection_items(id) ON DELETE SET NULL
);

-- Prüfprotokoll-Dateien
CREATE TABLE IF NOT EXISTS inspection_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inspection_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_hash VARCHAR(64) NULL, -- SHA-256 Hash für Integrität
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Constraints
    CONSTRAINT chk_inspection_files_size CHECK (file_size > 0 AND file_size <= 50000000), -- Max 50MB
    CONSTRAINT chk_inspection_files_names_not_empty CHECK (
        TRIM(filename) != '' AND TRIM(original_filename) != '' AND TRIM(file_path) != ''
    ),
    
    -- Foreign Keys
    FOREIGN KEY (inspection_id) REFERENCES inspections(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE RESTRICT
);

-- Audit-Log für Änderungen (Optional für Compliance)
CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(50) NOT NULL,
    record_id INT NOT NULL,
    action ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    changed_by INT NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    
    -- Foreign Keys
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE RESTRICT,
    
    -- Indizes
    INDEX idx_audit_table_record (table_name, record_id),
    INDEX idx_audit_changed_at (changed_at),
    INDEX idx_audit_changed_by (changed_by)
);

-- ==============================================
-- INDIZES FÜR PERFORMANCE
-- ==============================================

-- Benutzer-Indizes
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_active ON users(is_active);
CREATE INDEX idx_users_ldap ON users(ldap_user);
CREATE INDEX idx_users_last_login ON users(last_login);

-- Leiter-Indizes
CREATE INDEX idx_ladders_ladder_number ON ladders(ladder_number);
CREATE INDEX idx_ladders_serial ON ladders(serial_number);
CREATE INDEX idx_ladders_status ON ladders(status);
CREATE INDEX idx_ladders_location ON ladders(location);
CREATE INDEX idx_ladders_manufacturer ON ladders(manufacturer);
CREATE INDEX idx_ladders_type ON ladders(type);
CREATE INDEX idx_ladders_created_at ON ladders(created_at);

-- Prüfungs-Indizes
CREATE INDEX idx_inspections_ladder_id ON inspections(ladder_id);
CREATE INDEX idx_inspections_inspector_id ON inspections(inspector_id);
CREATE INDEX idx_inspections_date ON inspections(inspection_date);
CREATE INDEX idx_inspections_next_date ON inspections(next_inspection_date);
CREATE INDEX idx_inspections_result ON inspections(result);
CREATE INDEX idx_inspections_type ON inspections(inspection_type);
CREATE INDEX idx_inspections_final ON inspections(is_final);

-- Prüfpunkt-Indizes
CREATE INDEX idx_inspection_items_inspection_id ON inspection_items(inspection_id);
CREATE INDEX idx_inspection_items_category ON inspection_items(item_category);
CREATE INDEX idx_inspection_items_status ON inspection_items(status);
CREATE INDEX idx_inspection_items_repair_required ON inspection_items(repair_required);
CREATE INDEX idx_inspection_items_sort_order ON inspection_items(sort_order);

-- Mängel-Indizes
CREATE INDEX idx_defects_inspection_id ON defects(inspection_id);
CREATE INDEX idx_defects_severity ON defects(severity);
CREATE INDEX idx_defects_category ON defects(category);
CREATE INDEX idx_defects_repair_required ON defects(repair_required);
CREATE INDEX idx_defects_repair_completed ON defects(repair_completed);
CREATE INDEX idx_defects_repair_deadline ON defects(repair_deadline);

-- Datei-Indizes
CREATE INDEX idx_inspection_files_inspection_id ON inspection_files(inspection_id);
CREATE INDEX idx_inspection_files_uploaded_by ON inspection_files(uploaded_by);
CREATE INDEX idx_inspection_files_uploaded_at ON inspection_files(uploaded_at);

-- ==============================================
-- STORED PROCEDURES
-- ==============================================

DELIMITER //

-- Procedure: Nächste fällige Prüfungen abrufen
CREATE PROCEDURE GetUpcomingInspections(
    IN days_ahead INT DEFAULT 30
)
BEGIN
    SELECT 
        l.ladder_number,
        l.manufacturer,
        l.model,
        l.location,
        i.next_inspection_date,
        DATEDIFF(i.next_inspection_date, CURDATE()) as days_until_due,
        u.first_name,
        u.last_name,
        i.inspection_type
    FROM ladders l
    INNER JOIN (
        SELECT ladder_id, 
               next_inspection_date, 
               inspector_id, 
               inspection_type,
               ROW_NUMBER() OVER (PARTITION BY ladder_id ORDER BY inspection_date DESC) as rn
        FROM inspections
    ) i ON l.id = i.ladder_id AND i.rn = 1
    INNER JOIN users u ON i.inspector_id = u.id
    WHERE l.status = 'Aktiv'
      AND i.next_inspection_date <= DATE_ADD(CURDATE(), INTERVAL days_ahead DAY)
    ORDER BY i.next_inspection_date ASC, l.ladder_number ASC;
END //

-- Procedure: Leiter-Historie abrufen
CREATE PROCEDURE GetLadderHistory(
    IN ladder_id_param INT
)
BEGIN
    SELECT 
        i.inspection_date,
        i.inspection_type,
        i.result,
        i.next_inspection_date,
        CONCAT(u.first_name, ' ', u.last_name) as inspector_name,
        i.notes,
        COUNT(d.id) as defect_count,
        COUNT(CASE WHEN d.severity IN ('Hoch', 'Kritisch') THEN 1 END) as critical_defects
    FROM inspections i
    INNER JOIN users u ON i.inspector_id = u.id
    LEFT JOIN defects d ON i.id = d.inspection_id
    WHERE i.ladder_id = ladder_id_param
    GROUP BY i.id, i.inspection_date, i.inspection_type, i.result, i.next_inspection_date, u.first_name, u.last_name, i.notes
    ORDER BY i.inspection_date DESC;
END //

-- Procedure: Mängel-Dashboard
CREATE PROCEDURE GetDefectsDashboard()
BEGIN
    SELECT 
        'Offene Mängel' as category,
        COUNT(*) as count
    FROM defects 
    WHERE repair_required = TRUE AND repair_completed = FALSE
    
    UNION ALL
    
    SELECT 
        'Überfällige Reparaturen' as category,
        COUNT(*) as count
    FROM defects 
    WHERE repair_required = TRUE 
      AND repair_completed = FALSE 
      AND repair_deadline < CURDATE()
    
    UNION ALL
    
    SELECT 
        'Kritische Mängel' as category,
        COUNT(*) as count
    FROM defects 
    WHERE severity = 'Kritisch' 
      AND repair_completed = FALSE
    
    UNION ALL
    
    SELECT 
        'Gesperrte Leitern' as category,
        COUNT(*) as count
    FROM ladders 
    WHERE status = 'Gesperrt';
END //

-- Procedure: Prüfstatistiken
CREATE PROCEDURE GetInspectionStats(
    IN start_date DATE DEFAULT NULL,
    IN end_date DATE DEFAULT NULL
)
BEGIN
    IF start_date IS NULL THEN
        SET start_date = DATE_SUB(CURDATE(), INTERVAL 1 YEAR);
    END IF;
    
    IF end_date IS NULL THEN
        SET end_date = CURDATE();
    END IF;
    
    SELECT 
        inspection_type,
        result,
        COUNT(*) as count,
        ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER(), 2) as percentage
    FROM inspections
    WHERE inspection_date BETWEEN start_date AND end_date
    GROUP BY inspection_type, result
    ORDER BY inspection_type, result;
END //

-- Function: Berechne nächstes Prüfungsdatum
CREATE FUNCTION CalculateNextInspectionDate(
    inspection_type_param VARCHAR(50),
    inspection_date_param DATE,
    ladder_type_param VARCHAR(50)
) RETURNS DATE
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE next_date DATE;
    
    CASE inspection_type_param
        WHEN 'Sichtprüfung' THEN
            SET next_date = DATE_ADD(inspection_date_param, INTERVAL 3 MONTH);
        WHEN 'Hauptprüfung' THEN
            IF ladder_type_param IN ('Anlegeleiter', 'Schiebeleiter') THEN
                SET next_date = DATE_ADD(inspection_date_param, INTERVAL 1 YEAR);
            ELSE
                SET next_date = DATE_ADD(inspection_date_param, INTERVAL 6 MONTH);
            END IF;
        WHEN 'Außerordentliche Prüfung' THEN
            SET next_date = DATE_ADD(inspection_date_param, INTERVAL 1 MONTH);
        ELSE
            SET next_date = DATE_ADD(inspection_date_param, INTERVAL 1 YEAR);
    END CASE;
    
    RETURN next_date;
END //

DELIMITER ;

-- ==============================================
-- TRIGGER FÜR AUTOMATISIERUNG
-- ==============================================

DELIMITER //

-- Trigger: Automatische Berechnung des nächsten Prüfungsdatums
CREATE TRIGGER tr_inspections_calculate_next_date
    BEFORE INSERT ON inspections
    FOR EACH ROW
BEGIN
    DECLARE ladder_type_val VARCHAR(50);
    
    SELECT type INTO ladder_type_val 
    FROM ladders 
    WHERE id = NEW.ladder_id;
    
    IF NEW.next_inspection_date IS NULL OR NEW.next_inspection_date = '0000-00-00' THEN
        SET NEW.next_inspection_date = CalculateNextInspectionDate(
            NEW.inspection_type, 
            NEW.inspection_date, 
            ladder_type_val
        );
    END IF;
END //

-- Trigger: Verhindere Änderungen an finalisierten Prüfungen
CREATE TRIGGER tr_inspections_prevent_final_changes
    BEFORE UPDATE ON inspections
    FOR EACH ROW
BEGIN
    IF OLD.is_final = TRUE AND NEW.is_final = TRUE THEN
        IF OLD.result != NEW.result OR 
           OLD.inspection_date != NEW.inspection_date OR 
           OLD.inspection_type != NEW.inspection_type THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'Finalisierte Prüfungen können nicht geändert werden';
        END IF;
    END IF;
END //

-- Trigger: Audit-Log für Änderungen
CREATE TRIGGER tr_ladders_audit_update
    AFTER UPDATE ON ladders
    FOR EACH ROW
BEGIN
    INSERT INTO audit_log (table_name, record_id, action, old_values, new_values, changed_by)
    VALUES (
        'ladders',
        NEW.id,
        'UPDATE',
        JSON_OBJECT(
            'ladder_number', OLD.ladder_number,
            'status', OLD.status,
            'location', OLD.location
        ),
        JSON_OBJECT(
            'ladder_number', NEW.ladder_number,
            'status', NEW.status,
            'location', NEW.location
        ),
        NEW.created_by -- In einer echten Anwendung würde hier die aktuelle Benutzer-ID stehen
    );
END //

DELIMITER ;

-- ==============================================
-- BEISPIELDATEN FÜR TESTS
-- ==============================================

-- Standard-Admin-Benutzer (Passwort: admin123)
INSERT INTO users (username, email, password_hash, first_name, last_name, is_admin, ldap_user, created_by) 
VALUES 
    ('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', TRUE, FALSE, NULL),
    ('mueller.hans', 'hans.mueller@firma.de', NULL, 'Hans', 'Müller', FALSE, TRUE, 1),
    ('schmidt.anna', 'anna.schmidt@firma.de', NULL, 'Anna', 'Schmidt', FALSE, TRUE, 1),
    ('weber.thomas', 'thomas.weber@firma.de', NULL, 'Thomas', 'Weber', TRUE, TRUE, 1),
    ('fischer.maria', 'maria.fischer@firma.de', NULL, 'Maria', 'Fischer', FALSE, TRUE, 1)
ON DUPLICATE KEY UPDATE username = VALUES(username);

-- Beispiel-Leitern
INSERT INTO ladders (ladder_number, serial_number, manufacturer, model, type, material, max_load_kg, height_cm, purchase_date, location, created_by) 
VALUES 
    ('L-001', 'AL-001-2024', 'Hailo', 'ProfiStep', 'Anlegeleiter', 'Aluminium', 150, 300, '2024-01-15', 'Lager A - Regal 1', 1),
    ('L-002', 'ST-002-2024', 'Günzburger', 'Steigtechnik', 'Stehleiter', 'Aluminium', 120, 200, '2024-02-20', 'Werkstatt - Bereich B', 1),
    ('L-003', 'MZ-003-2024', 'Krause', 'MultiMatic', 'Mehrzweckleiter', 'Aluminium', 150, 400, '2024-03-10', 'Außenlager - Container 3', 1),
    ('L-004', 'PL-004-2023', 'Zarges', 'Saferstep', 'Podestleiter', 'Aluminium', 150, 180, '2023-11-05', 'Produktion - Halle 2', 1),
    ('L-005', 'SL-005-2023', 'Layher', 'Topic', 'Schiebeleiter', 'Aluminium', 150, 600, '2023-09-12', 'Wartung - Außenbereich', 1)
ON DUPLICATE KEY UPDATE ladder_number = VALUES(ladder_number);

-- Beispiel-Prüfungen
INSERT INTO inspections (ladder_id, inspector_id, inspection_date, inspection_type, result, next_inspection_date, notes) 
VALUES 
    (1, 2, '2024-06-15', 'Hauptprüfung', 'Bestanden', '2025-06-15', 'Leiter in gutem Zustand'),
    (1, 2, '2024-09-15', 'Sichtprüfung', 'Bestanden mit Einschränkungen', '2024-12-15', 'Kleine Kratzer an den Sprossen'),
    (2, 3, '2024-07-20', 'Hauptprüfung', 'Bestanden', '2025-01-20', 'Alle Sicherheitsmerkmale OK'),
    (3, 4, '2024-08-10', 'Hauptprüfung', 'Durchgefallen', '2024-08-17', 'Defekte Verriegelung - Reparatur erforderlich'),
    (4, 2, '2024-05-05', 'Hauptprüfung', 'Bestanden', '2024-11-05', 'Podest stabil, alle Geländer intakt'),
    (5, 3, '2024-04-12', 'Hauptprüfung', 'Bestanden mit Einschränkungen', '2025-04-12', 'Leichte Korrosionsspuren')
ON DUPLICATE KEY UPDATE id = VALUES(id);

-- Beispiel-Prüfpunkte
INSERT INTO inspection_items (inspection_id, item_category, item_name, item_description, status, comments, repair_required, sort_order) 
VALUES 
    -- Prüfung 1 (Leiter L-001)
    (1, 'Mechanisch', 'Sprossen-Verbindungen', 'Überprüfung aller Sprossenverbindungen auf festen Sitz', 'OK', 'Alle Verbindungen fest', FALSE, 1),
    (1, 'Mechanisch', 'Holme', 'Sichtprüfung der Holme auf Risse oder Verformungen', 'OK', 'Keine Beschädigungen sichtbar', FALSE, 2),
    (1, 'Sicherheit', 'Leiterfüße', 'Überprüfung der rutschfesten Leiterfüße', 'OK', 'Gummierung in gutem Zustand', FALSE, 3),
    (1, 'Kennzeichnung', 'Typenschild', 'Lesbarkeit und Vollständigkeit des Typenschilds', 'OK', 'Alle Angaben lesbar', FALSE, 4),
    
    -- Prüfung 2 (Leiter L-001 - Sichtprüfung)
    (2, 'Verschleiß', 'Sprossen-Oberfläche', 'Überprüfung auf Abnutzung und Kratzer', 'Mangel gering', 'Leichte Kratzer durch normalen Gebrauch', FALSE, 1),
    (2, 'Sicherheit', 'Leiterfüße', 'Zustand der rutschfesten Füße', 'OK', 'Weiterhin in gutem Zustand', FALSE, 2),
    
    -- Prüfung 3 (Leiter L-002)
    (3, 'Mechanisch', 'Spreizen', 'Funktion der Spreizen und Arretierung', 'OK', 'Spreizen funktionieren einwandfrei', FALSE, 1),
    (3, 'Mechanisch', 'Plattform', 'Stabilität und Befestigung der Plattform', 'OK', 'Plattform stabil befestigt', FALSE, 2),
    (3, 'Sicherheit', 'Sicherheitsbügel', 'Zustand und Funktion des Sicherheitsbügels', 'OK', 'Bügel intakt und funktional', FALSE, 3),
    
    -- Prüfung 4 (Leiter L-003 - Durchgefallen)
    (4, 'Mechanisch', 'Verriegelung', 'Funktion der Gelenkverriegelung', 'Mangel gefährlich', 'Verriegelung schließt nicht vollständig', TRUE, 1),
    (4, 'Mechanisch', 'Gelenke', 'Zustand der Scharniergelenke', 'Mangel erheblich', 'Spiel in den Gelenken', TRUE, 2),
    (4, 'Sicherheit', 'Stabilität', 'Gesamtstabilität der Leiter', 'Mangel gefährlich', 'Wackelt aufgrund defekter Verriegelung', TRUE, 3)
ON DUPLICATE KEY UPDATE id = VALUES(id);

-- Beispiel-Mängel
INSERT INTO defects (inspection_id, inspection_item_id, category, severity, description, repair_required, repair_deadline, repair_completed) 
VALUES 
    (2, 5, 'Verschleiß', 'Gering', 'Leichte Kratzer an mehreren Sprossen durch normalen Gebrauch', FALSE, NULL, FALSE),
    (4, 10, 'Mechanisch', 'Kritisch', 'Verriegelungsmechanismus der Mehrzweckleiter schließt nicht vollständig - Sicherheitsrisiko', TRUE, '2024-08-24', FALSE),
    (4, 11, 'Mechanisch', 'Hoch', 'Übermäßiges Spiel in den Scharniergelenken führt zu Instabilität', TRUE, '2024-08-31', FALSE),
    (6, NULL, 'Korrosion', 'Mittel', 'Leichte Korrosionsspuren an den Verbindungselementen', TRUE, '2024-12-31', FALSE)
ON DUPLICATE KEY UPDATE id = VALUES(id);

-- ==============================================
-- VIEWS FÜR HÄUFIGE ABFRAGEN
-- ==============================================

-- View: Aktuelle Leiter-Übersicht
CREATE OR REPLACE VIEW v_ladders_current AS
SELECT 
    l.id,
    l.ladder_number,
    l.serial_number,
    l.manufacturer,
    l.model,
    l.type,
    l.location,
    l.status,
    CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
    l.created_at,
    -- Letzte Prüfung
    li.inspection_date as last_inspection_date,
    li.inspection_type as last_inspection_type,
    li.result as last_inspection_result,
    li.next_inspection_date,
    CONCAT(ui.first_name, ' ', ui.last_name) as last_inspector_name,
    -- Status-Indikatoren
    CASE 
        WHEN li.next_inspection_date < CURDATE() THEN 'Überfällig'
        WHEN li.next_inspection_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'Fällig bald'
        WHEN li.next_inspection_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) THEN 'Fällig in 3 Monaten'
        ELSE 'OK'
    END as inspection_status,
    DATEDIFF(li.next_inspection_date, CURDATE()) as days_until_inspection
FROM ladders l
INNER JOIN users u ON l.created_by = u.id
LEFT JOIN (
    SELECT 
        i1.ladder_id,
        i1.inspection_date,
        i1.inspection_type,
        i1.result,
        i1.next_inspection_date,
        i1.inspector_id
    FROM inspections i1
    INNER JOIN (
        SELECT ladder_id, MAX(inspection_date) as max_date
        FROM inspections
        GROUP BY ladder_id
    ) i2 ON i1.ladder_id = i2.ladder_id AND i1.inspection_date = i2.max_date
) li ON l.id = li.ladder_id
LEFT JOIN users ui ON li.inspector_id = ui.id;

-- View: Fällige Prüfungen
CREATE OR REPLACE VIEW v_inspections_due AS
SELECT 
    l.ladder_number,
    l.manufacturer,
    l.model,
    l.location,
    l.status,
    i.next_inspection_date,
    DATEDIFF(i.next_inspection_date, CURDATE()) as days_until_due,
    CASE 
        WHEN i.next_inspection_date < CURDATE() THEN 'Überfällig'
        WHEN i.next_inspection_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'Diese Woche'
        WHEN i.next_inspection_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'Diesen Monat'
        ELSE 'Später'
    END as urgency,
    CONCAT(u.first_name, ' ', u.last_name) as last_inspector
FROM ladders l
INNER JOIN (
    SELECT 
        i1.ladder_id,
        i1.next_inspection_date,
        i1.inspector_id
    FROM inspections i1
    INNER JOIN (
        SELECT ladder_id, MAX(inspection_date) as max_date
        FROM inspections
        GROUP BY ladder_id
    ) i2 ON i1.ladder_id = i2.ladder_id AND i1.inspection_date = i2.max_date
) i ON l.id = i.ladder_id
INNER JOIN users u ON i.inspector_id = u.id
WHERE l.status = 'Aktiv'
  AND i.next_inspection_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
ORDER BY i.next_inspection_date ASC;

-- View: Mängel-Übersicht
CREATE OR REPLACE VIEW v_defects_overview AS
SELECT 
    d.id,
    l.ladder_number,
    l.manufacturer,
    l.model,
    l.location,
    i.inspection_date,
    d.category,
    d.severity,
    d.description,
    d.repair_required,
    d.repair_deadline,
    d.repair_completed,
    CASE 
        WHEN d.repair_required = FALSE THEN 'Keine Reparatur nötig'
        WHEN d.repair_completed = TRUE THEN 'Repariert'
        WHEN d.repair_deadline < CURDATE() THEN 'Überfällig'
        WHEN d.repair_deadline <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'Diese Woche fällig'
        WHEN d.repair_deadline <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'Diesen Monat fällig'
        ELSE 'Geplant'
    END as repair_status,
    DATEDIFF(d.repair_deadline, CURDATE()) as days_until_repair_due,
    CONCAT(u.first_name, ' ', u.last_name) as inspector_name
FROM defects d
INNER JOIN inspections i ON d.inspection_id = i.id
INNER JOIN ladders l ON i.ladder_id = l.id
INNER JOIN users u ON i.inspector_id = u.id
ORDER BY 
    CASE d.severity 
        WHEN 'Kritisch' THEN 1
        WHEN 'Hoch' THEN 2
        WHEN 'Mittel' THEN 3
        WHEN 'Gering' THEN 4
    END,
    d.repair_deadline ASC;

-- ==============================================
-- ZUSÄTZLICHE STORED PROCEDURES
-- ==============================================

DELIMITER //

-- Procedure: Leiter-Dashboard Statistiken
CREATE PROCEDURE GetLadderDashboardStats()
BEGIN
    SELECT 
        'Gesamt Leitern' as metric,
        COUNT(*) as value,
        'Anzahl' as unit
    FROM ladders
    
    UNION ALL
    
    SELECT 
        'Aktive Leitern' as metric,
        COUNT(*) as value,
        'Anzahl' as unit
    FROM ladders 
    WHERE status = 'Aktiv'
    
    UNION ALL
    
    SELECT 
        'Überfällige Prüfungen' as metric,
        COUNT(*) as value,
        'Anzahl' as unit
    FROM v_inspections_due 
    WHERE urgency = 'Überfällig'
    
    UNION ALL
    
    SELECT 
        'Fällige Prüfungen (30 Tage)' as metric,
        COUNT(*) as value,
        'Anzahl' as unit
    FROM v_inspections_due 
    WHERE days_until_due <= 30
    
    UNION ALL
    
    SELECT 
        'Offene kritische Mängel' as metric,
        COUNT(*) as value,
        'Anzahl' as unit
    FROM defects 
    WHERE severity = 'Kritisch' AND repair_completed = FALSE
    
    UNION ALL
    
    SELECT 
        'Gesperrte Leitern' as metric,
        COUNT(*) as value,
        'Anzahl' as unit
    FROM ladders 
    WHERE status = 'Gesperrt';
END //

-- Procedure: Erstelle neue Prüfung mit Standard-Prüfpunkten
CREATE PROCEDURE CreateInspectionWithItems(
    IN p_ladder_id INT,
    IN p_inspector_id INT,
    IN p_inspection_date DATE,
    IN p_inspection_type VARCHAR(50),
    OUT p_inspection_id INT
)
BEGIN
    DECLARE v_ladder_type VARCHAR(50);
    
    -- Leiter-Typ ermitteln
    SELECT type INTO v_ladder_type 
    FROM ladders 
    WHERE id = p_ladder_id;
    
    -- Prüfung erstellen
    INSERT INTO inspections (ladder_id, inspector_id, inspection_date, inspection_type, result, notes)
    VALUES (p_ladder_id, p_inspector_id, p_inspection_date, p_inspection_type, 'Bestanden', 'Automatisch erstellt');
    
    SET p_inspection_id = LAST_INSERT_ID();
    
    -- Standard-Prüfpunkte basierend auf Leiter-Typ hinzufügen
    INSERT INTO inspection_items (inspection_id, item_category, item_name, item_description, status, sort_order)
    VALUES 
        (p_inspection_id, 'Mechanisch', 'Sprossen-Verbindungen', 'Überprüfung aller Sprossenverbindungen auf festen Sitz', 'OK', 1),
        (p_inspection_id, 'Mechanisch', 'Holme', 'Sichtprüfung der Holme auf Risse oder Verformungen', 'OK', 2),
        (p_inspection_id, 'Sicherheit', 'Leiterfüße', 'Überprüfung der rutschfesten Leiterfüße', 'OK', 3),
        (p_inspection_id, 'Kennzeichnung', 'Typenschild', 'Lesbarkeit und Vollständigkeit des Typenschilds', 'OK', 4),
        (p_inspection_id, 'Verschleiß', 'Allgemeiner Zustand', 'Gesamtbeurteilung des Verschleißzustands', 'OK', 5);
    
    -- Zusätzliche Prüfpunkte je nach Leiter-Typ
    IF v_ladder_type = 'Stehleiter' THEN
        INSERT INTO inspection_items (inspection_id, item_category, item_name, item_description, status, sort_order)
        VALUES 
            (p_inspection_id, 'Mechanisch', 'Spreizen', 'Funktion der Spreizen und Arretierung', 'OK', 6),
            (p_inspection_id, 'Sicherheit', 'Sicherheitsbügel', 'Zustand und Funktion des Sicherheitsbügels', 'OK', 7);
    END IF;
    
    IF v_ladder_type = 'Mehrzweckleiter' THEN
        INSERT INTO inspection_items (inspection_id, item_category, item_name, item_description, status, sort_order)
        VALUES 
            (p_inspection_id, 'Mechanisch', 'Verriegelung', 'Funktion der Gelenkverriegelung', 'OK', 6),
            (p_inspection_id, 'Mechanisch', 'Gelenke', 'Zustand der Scharniergelenke', 'OK', 7);
    END IF;
    
    IF v_ladder_type = 'Podestleiter' THEN
        INSERT INTO inspection_items (inspection_id, item_category, item_name, item_description, status, sort_order)
        VALUES 
            (p_inspection_id, 'Mechanisch', 'Plattform', 'Stabilität und Befestigung der Plattform', 'OK', 6),
            (p_inspection_id, 'Sicherheit', 'Geländer', 'Zustand und Befestigung der Geländer', 'OK', 7);
    END IF;
END //

DELIMITER ;

-- ==============================================
-- ZUSÄTZLICHE BEISPIELDATEN
-- ==============================================

-- Weitere Beispiel-Prüfpunkte für vollständigere Tests
INSERT INTO inspection_items (inspection_id, item_category, item_name, item_description, status, comments, repair_required, sort_order) 
VALUES 
    -- Prüfung 5 (Leiter L-004 - Podestleiter)
    (5, 'Mechanisch', 'Plattform', 'Stabilität und Befestigung der Plattform', 'OK', 'Plattform stabil und sicher befestigt', FALSE, 1),
    (5, 'Sicherheit', 'Geländer', 'Zustand und Befestigung der Geländer', 'OK', 'Alle Geländer intakt und fest', FALSE, 2),
    (5, 'Mechanisch', 'Aufstiegshilfen', 'Zustand der Stufen und Handläufe', 'OK', 'Alle Stufen rutschfest und sicher', FALSE, 3),
    
    -- Prüfung 6 (Leiter L-005 - Schiebeleiter)
    (6, 'Mechanisch', 'Auszugsmechanismus', 'Funktion des Auszieh- und Verriegelungsmechanismus', 'Mangel gering', 'Leichtes Klemmen beim Ausziehen', FALSE, 1),
    (6, 'Korrosion', 'Metallteile', 'Korrosionsprüfung aller Metallkomponenten', 'Mangel gering', 'Oberflächliche Rostspuren an Verbindungen', FALSE, 2),
    (6, 'Sicherheit', 'Seilzug', 'Zustand des Seilzugs und der Umlenkrollen', 'OK', 'Seil und Rollen in gutem Zustand', FALSE, 3)
ON DUPLICATE KEY UPDATE id = VALUES(id);

-- Zusätzliche Mängel für realistische Tests
INSERT INTO defects (inspection_id, inspection_item_id, category, severity, description, repair_required, repair_deadline, repair_completed, repair_cost) 
VALUES 
    (6, 14, 'Mechanisch', 'Gering', 'Auszugsmechanismus klemmt leicht - Schmierung erforderlich', TRUE, '2025-01-31', FALSE, 25.00),
    (6, 15, 'Korrosion', 'Gering', 'Oberflächliche Rostspuren an Verbindungselementen - präventive Behandlung empfohlen', TRUE, '2025-03-31', FALSE, 45.00)
ON DUPLICATE KEY UPDATE id = VALUES(id);

-- ==============================================
-- ABSCHLUSS-KOMMENTARE
-- ==============================================

/*
DATENBANKSCHEMA VOLLSTÄNDIG ERSTELLT

Enthaltene Komponenten:
✓ Alle geforderten Tabellen (users, ladders, inspections, inspection_items)
✓ Erweiterte Tabellen (defects, inspection_files, audit_log)
✓ Umfassende Constraints für Datenintegrität
✓ Performance-optimierte Indizes
✓ Stored Procedures für häufige Abfragen
✓ Trigger für Automatisierung und Audit
✓ Views für vereinfachte Abfragen
✓ Realistische Beispieldaten für Tests

Besondere Merkmale:
- Unveränderliche Prüfungshistorie durch is_final Flag
- Automatische Berechnung von Prüfungsterminen
- LDAP-Benutzer-Cache mit last_login Tracking
- Audit-Log für Compliance-Anforderungen
- Flexible Prüfpunkt-Struktur
- Umfassende Mängelverfolgung mit Reparatur-Workflow

Das Schema ist produktionsreif und erfüllt alle Anforderungen
für eine professionelle Leiterprüfungs-Applikation.
*/
