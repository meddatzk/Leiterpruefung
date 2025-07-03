-- ==============================================
-- LEITERPRÜFUNG - DATABASE INITIALIZATION
-- ==============================================

-- Erstelle Datenbank falls nicht vorhanden
CREATE DATABASE IF NOT EXISTS leiterpruefung CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE leiterpruefung;

-- Benutzer-Tabelle
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Leiter-Tabelle
CREATE TABLE IF NOT EXISTS ladders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    serial_number VARCHAR(100) NOT NULL UNIQUE,
    manufacturer VARCHAR(100) NOT NULL,
    model VARCHAR(100) NOT NULL,
    type ENUM('Anlegeleiter', 'Stehleiter', 'Mehrzweckleiter', 'Podestleiter') NOT NULL,
    material ENUM('Aluminium', 'Holz', 'Fiberglas', 'Stahl') NOT NULL,
    max_load_kg INT NOT NULL,
    height_cm INT NOT NULL,
    purchase_date DATE NULL,
    location VARCHAR(255) NOT NULL,
    status ENUM('Aktiv', 'Gesperrt', 'Reparatur', 'Ausgemustert') DEFAULT 'Aktiv',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Prüfungen-Tabelle
CREATE TABLE IF NOT EXISTS inspections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ladder_id INT NOT NULL,
    inspector_id INT NOT NULL,
    inspection_date DATE NOT NULL,
    inspection_type ENUM('Sichtprüfung', 'Hauptprüfung', 'Außerordentliche Prüfung') NOT NULL,
    result ENUM('Bestanden', 'Bestanden mit Mängeln', 'Nicht bestanden') NOT NULL,
    next_inspection_date DATE NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ladder_id) REFERENCES ladders(id) ON DELETE CASCADE,
    FOREIGN KEY (inspector_id) REFERENCES users(id) ON DELETE RESTRICT
);

-- Mängel-Tabelle
CREATE TABLE IF NOT EXISTS defects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inspection_id INT NOT NULL,
    category ENUM('Mechanisch', 'Korrosion', 'Verschleiß', 'Sicherheit', 'Sonstiges') NOT NULL,
    severity ENUM('Gering', 'Mittel', 'Hoch', 'Kritisch') NOT NULL,
    description TEXT NOT NULL,
    repair_required BOOLEAN DEFAULT FALSE,
    repair_deadline DATE NULL,
    repair_completed BOOLEAN DEFAULT FALSE,
    repair_date DATE NULL,
    repair_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (inspection_id) REFERENCES inspections(id) ON DELETE CASCADE
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
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inspection_id) REFERENCES inspections(id) ON DELETE CASCADE
);

-- Erstelle Indizes für bessere Performance
CREATE INDEX idx_ladders_serial ON ladders(serial_number);
CREATE INDEX idx_ladders_status ON ladders(status);
CREATE INDEX idx_inspections_date ON inspections(inspection_date);
CREATE INDEX idx_inspections_next_date ON inspections(next_inspection_date);
CREATE INDEX idx_defects_severity ON defects(severity);
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);

-- Erstelle Standard-Admin-Benutzer (Passwort: admin123)
INSERT INTO users (username, email, password_hash, first_name, last_name, is_admin, ldap_user) 
VALUES ('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', TRUE, FALSE)
ON DUPLICATE KEY UPDATE username = username;

-- Beispiel-Leiter einfügen
INSERT INTO ladders (serial_number, manufacturer, model, type, material, max_load_kg, height_cm, location) 
VALUES 
    ('AL-001-2024', 'Hailo', 'ProfiStep', 'Anlegeleiter', 'Aluminium', 150, 300, 'Lager A - Regal 1'),
    ('ST-002-2024', 'Günzburger', 'Steigtechnik', 'Stehleiter', 'Aluminium', 120, 200, 'Werkstatt - Bereich B'),
    ('MZ-003-2024', 'Krause', 'MultiMatic', 'Mehrzweckleiter', 'Aluminium', 150, 400, 'Außenlager - Container 3')
ON DUPLICATE KEY UPDATE serial_number = serial_number;
