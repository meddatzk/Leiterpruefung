-- ==============================================
-- LEITERPRÜFUNG - ERWEITERTE TESTDATEN UND BEISPIELABFRAGEN
-- ==============================================

USE leiterpruefung;

-- ==============================================
-- ERWEITERTE TESTDATEN
-- ==============================================

-- Weitere Benutzer für realistische Tests
INSERT INTO users (username, email, first_name, last_name, department, position, is_admin, ldap_user, last_login, created_by) 
VALUES 
    ('becker.stefan', 'stefan.becker@firma.de', 'Stefan', 'Becker', 'Arbeitssicherheit', 'Sicherheitsbeauftragter', TRUE, TRUE, '2024-12-20 14:30:00', 1),
    ('wagner.petra', 'petra.wagner@firma.de', 'Petra', 'Wagner', 'Instandhaltung', 'Teamleiterin', FALSE, TRUE, '2024-12-18 09:15:00', 1),
    ('hoffmann.klaus', 'klaus.hoffmann@firma.de', 'Klaus', 'Hoffmann', 'Produktion', 'Schichtleiter', FALSE, TRUE, '2024-12-19 16:45:00', 1),
    ('klein.sabine', 'sabine.klein@firma.de', 'Sabine', 'Klein', 'Qualitätssicherung', 'Prüferin', FALSE, TRUE, '2024-12-21 11:20:00', 1),
    ('neumann.frank', 'frank.neumann@firma.de', 'Frank', 'Neumann', 'Wartung', 'Techniker', FALSE, TRUE, '2024-12-17 13:10:00', 1)
ON DUPLICATE KEY UPDATE username = VALUES(username);

-- Weitere Leitern für umfassende Tests
INSERT INTO ladders (ladder_number, serial_number, manufacturer, model, type, material, max_load_kg, height_cm, purchase_date, location, status, notes, created_by) 
VALUES 
    ('L-006', 'DL-006-2023', 'Zarges', 'Z600', 'Dachleiter', 'Aluminium', 120, 350, '2023-05-15', 'Dach - Gebäude A', 'Aktiv', 'Fest installiert auf Gebäudedach', 1),
    ('L-007', 'AL-007-2022', 'Hailo', 'L80 ComfortLine', 'Anlegeleiter', 'Aluminium', 150, 280, '2022-08-20', 'Lager B - Außenbereich', 'Reparatur', 'Defekte Sprosse - in Reparatur', 1),
    ('L-008', 'ST-008-2024', 'Günzburger', 'Steigtechnik Pro', 'Stehleiter', 'Fiberglas', 120, 220, '2024-04-10', 'Elektrowerkstatt', 'Aktiv', 'Isolierte Leiter für Elektroarbeiten', 1),
    ('L-009', 'MZ-009-2023', 'Krause', 'Corda MultiMatic', 'Mehrzweckleiter', 'Aluminium', 150, 450, '2023-12-01', 'Wartung - Fahrzeughalle', 'Gesperrt', 'Sicherheitsmangel - gesperrt bis Reparatur', 1),
    ('L-010', 'PL-010-2022', 'Layher', 'Uni Standard', 'Podestleiter', 'Stahl', 200, 200, '2022-03-15', 'Schweißerei', 'Aktiv', 'Schwere Ausführung für Industrieeinsatz', 1)
ON DUPLICATE KEY UPDATE ladder_number = VALUES(ladder_number);

-- Weitere Prüfungen für realistische Historie
INSERT INTO inspections (ladder_id, inspector_id, inspection_date, inspection_type, result, next_inspection_date, notes, is_final) 
VALUES 
    -- Historische Prüfungen für L-001
    (1, 3, '2023-06-15', 'Hauptprüfung', 'Bestanden', '2024-06-15', 'Erste Hauptprüfung nach Anschaffung', TRUE),
    (1, 4, '2023-09-15', 'Sichtprüfung', 'Bestanden', '2023-12-15', 'Routineprüfung - alles OK', TRUE),
    (1, 3, '2023-12-15', 'Sichtprüfung', 'Bestanden', '2024-03-15', 'Winterprüfung - keine Mängel', TRUE),
    
    -- Prüfungen für neue Leitern
    (6, 6, '2024-05-20', 'Hauptprüfung', 'Bestanden', '2025-05-20', 'Dachleiter - alle Befestigungen OK', FALSE),
    (7, 7, '2024-08-25', 'Hauptprüfung', 'Durchgefallen', '2024-09-01', 'Sprosse beschädigt - Reparatur erforderlich', TRUE),
    (8, 8, '2024-04-15', 'Hauptprüfung', 'Bestanden', '2024-10-15', 'Isolierte Leiter - alle Isolationswerte OK', FALSE),
    (9, 9, '2024-12-05', 'Hauptprüfung', 'Gesperrt', '2025-01-05', 'Kritischer Sicherheitsmangel - sofort gesperrt', TRUE),
    (10, 6, '2024-03-20', 'Hauptprüfung', 'Bestanden mit Einschränkungen', '2024-09-20', 'Leichte Korrosion - Überwachung erforderlich', FALSE)
ON DUPLICATE KEY UPDATE id = VALUES(id);

-- Erweiterte Prüfpunkte für neue Prüfungen
INSERT INTO inspection_items (inspection_id, item_category, item_name, item_description, status, comments, repair_required, sort_order) 
VALUES 
    -- Prüfung 7 (L-006 - Dachleiter)
    (10, 'Mechanisch', 'Befestigungspunkte', 'Überprüfung aller Dachbefestigungen', 'OK', 'Alle Befestigungen fest und korrosionsfrei', FALSE, 1),
    (10, 'Sicherheit', 'Absturzsicherung', 'Zustand der Absturzsicherung am Dachrand', 'OK', 'Sicherheitssystem vollständig funktional', FALSE, 2),
    (10, 'Korrosion', 'Witterungsschutz', 'Zustand des Korrosionsschutzes', 'OK', 'Beschichtung intakt', FALSE, 3),
    
    -- Prüfung 8 (L-007 - Defekte Anlegeleiter)
    (11, 'Mechanisch', 'Sprossen-Integrität', 'Überprüfung aller Sprossen auf Risse', 'Mangel gefährlich', 'Sprosse 8 von unten gebrochen', TRUE, 1),
    (11, 'Mechanisch', 'Holme', 'Zustand der Leiterholme', 'Mangel erheblich', 'Verformung am linken Holm durch Überlastung', TRUE, 2),
    (11, 'Sicherheit', 'Gesamtstabilität', 'Strukturelle Integrität der Leiter', 'Mangel gefährlich', 'Leiter nicht mehr sicher verwendbar', TRUE, 3),
    
    -- Prüfung 9 (L-008 - Fiberglas-Stehleiter)
    (12, 'Sicherheit', 'Isolationswiderstand', 'Elektrische Isolationsprüfung', 'OK', 'Isolationswerte im Normbereich', FALSE, 1),
    (12, 'Mechanisch', 'Fiberglas-Struktur', 'Überprüfung auf Risse im Fiberglas', 'OK', 'Keine Beschädigungen der Isolierung', FALSE, 2),
    (12, 'Verschleiß', 'Oberflächenzustand', 'Zustand der Oberflächen und Kanten', 'OK', 'Normale Gebrauchsspuren', FALSE, 3),
    
    -- Prüfung 10 (L-009 - Gesperrte Mehrzweckleiter)
    (13, 'Mechanisch', 'Gelenkverbindungen', 'Zustand aller Gelenkverbindungen', 'Mangel kritisch', 'Gelenk komplett ausgeleiert - Bruchgefahr', TRUE, 1),
    (13, 'Sicherheit', 'Verriegelungssystem', 'Funktion der Sicherheitsverriegelung', 'Mangel kritisch', 'Verriegelung funktionslos', TRUE, 2),
    (13, 'Mechanisch', 'Strukturelle Integrität', 'Gesamtstabilität der Konstruktion', 'Mangel kritisch', 'Leiter instabil - akute Unfallgefahr', TRUE, 3)
ON DUPLICATE KEY UPDATE id = VALUES(id);

-- Erweiterte Mängel mit verschiedenen Schweregraden
INSERT INTO defects (inspection_id, inspection_item_id, category, severity, description, repair_required, repair_deadline, repair_completed, repair_cost, repair_notes) 
VALUES 
    -- Kritische Mängel
    (11, 19, 'Mechanisch', 'Kritisch', 'Sprosse 8 komplett gebrochen - akute Absturzgefahr', TRUE, '2024-08-26', TRUE, 180.00, 'Sprosse ersetzt, Leiter wieder einsatzbereit'),
    (11, 20, 'Mechanisch', 'Hoch', 'Verformung am linken Holm durch Überlastung - Tragfähigkeit reduziert', TRUE, '2024-08-30', TRUE, 320.00, 'Holm begradigt und verstärkt'),
    (13, 22, 'Mechanisch', 'Kritisch', 'Gelenkverbindung komplett verschlissen - Bruchgefahr bei Belastung', TRUE, '2024-12-10', FALSE, 450.00, NULL),
    (13, 23, 'Sicherheit', 'Kritisch', 'Verriegelungsmechanismus funktionslos - Leiter kann zusammenklappen', TRUE, '2024-12-08', FALSE, 280.00, NULL),
    
    -- Mittlere und geringe Mängel
    (12, 21, 'Verschleiß', 'Mittel', 'Oberflächliche Kratzer an mehreren Stellen - präventive Behandlung empfohlen', TRUE, '2025-04-30', FALSE, 75.00, NULL),
    (14, NULL, 'Korrosion', 'Mittel', 'Beginnende Korrosion an Schweißnähten - Behandlung vor nächster Prüfung', TRUE, '2025-03-20', FALSE, 120.00, NULL),
    (10, 16, 'Verschleiß', 'Gering', 'Leichte Abnutzung der Befestigungsschrauben', TRUE, '2025-05-31', FALSE, 35.00, NULL)
ON DUPLICATE KEY UPDATE id = VALUES(id);

-- Beispiel-Dateien für Prüfprotokolle
INSERT INTO inspection_files (inspection_id, filename, original_filename, file_path, file_size, mime_type, file_hash, uploaded_by) 
VALUES 
    (1, 'inspection_1_20240615.pdf', 'Prüfprotokoll_L-001_Hauptprüfung.pdf', '/uploads/inspections/2024/06/inspection_1_20240615.pdf', 245760, 'application/pdf', 'a1b2c3d4e5f6789012345678901234567890abcdef1234567890abcdef123456', 2),
    (4, 'inspection_4_20240810.pdf', 'Prüfprotokoll_L-003_Durchgefallen.pdf', '/uploads/inspections/2024/08/inspection_4_20240810.pdf', 312450, 'application/pdf', 'b2c3d4e5f6789012345678901234567890abcdef1234567890abcdef1234567', 4),
    (11, 'inspection_11_repair_photos.zip', 'Reparatur_Fotos_L-007.zip', '/uploads/inspections/2024/08/inspection_11_repair_photos.zip', 1024000, 'application/zip', 'c3d4e5f6789012345678901234567890abcdef1234567890abcdef12345678', 7),
    (13, 'inspection_13_critical_defects.pdf', 'Kritische_Mängel_L-009.pdf', '/uploads/inspections/2024/12/inspection_13_critical_defects.pdf', 189320, 'application/pdf', 'd4e5f6789012345678901234567890abcdef1234567890abcdef123456789', 9)
ON DUPLICATE KEY UPDATE id = VALUES(id);

-- ==============================================
-- BEISPIELABFRAGEN FÜR HÄUFIGE ANWENDUNGSFÄLLE
-- ==============================================

-- Abfrage 1: Alle überfälligen Prüfungen
SELECT 
    'Überfällige Prüfungen' as query_name,
    ladder_number,
    manufacturer,
    model,
    location,
    next_inspection_date,
    days_until_due,
    urgency,
    last_inspector
FROM v_inspections_due 
WHERE urgency = 'Überfällig'
ORDER BY days_until_due ASC;

-- Abfrage 2: Kritische Mängel nach Priorität
SELECT 
    'Kritische Mängel' as query_name,
    ladder_number,
    location,
    severity,
    description,
    repair_deadline,
    days_until_repair_due,
    repair_status
FROM v_defects_overview 
WHERE severity = 'Kritisch' AND repair_completed = FALSE
ORDER BY repair_deadline ASC;

-- Abfrage 3: Leiter-Auslastung nach Standort
SELECT 
    'Leiter nach Standort' as query_name,
    SUBSTRING_INDEX(location, ' - ', 1) as standort,
    COUNT(*) as anzahl_leitern,
    SUM(CASE WHEN status = 'Aktiv' THEN 1 ELSE 0 END) as aktive_leitern,
    SUM(CASE WHEN status = 'Gesperrt' THEN 1 ELSE 0 END) as gesperrte_leitern,
    SUM(CASE WHEN status = 'Reparatur' THEN 1 ELSE 0 END) as in_reparatur
FROM ladders
GROUP BY SUBSTRING_INDEX(location, ' - ', 1)
ORDER BY anzahl_leitern DESC;

-- Abfrage 4: Prüfungshistorie einer spezifischen Leiter
SELECT 
    'Prüfungshistorie L-001' as query_name,
    inspection_date,
    inspection_type,
    result,
    next_inspection_date,
    inspector_name,
    notes,
    defect_count,
    critical_defects
FROM (
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
    WHERE i.ladder_id = 1  -- L-001
    GROUP BY i.id, i.inspection_date, i.inspection_type, i.result, i.next_inspection_date, u.first_name, u.last_name, i.notes
    ORDER BY i.inspection_date DESC
) as history;

-- Abfrage 5: Reparaturkosten-Übersicht
SELECT 
    'Reparaturkosten-Übersicht' as query_name,
    YEAR(created_at) as jahr,
    MONTH(created_at) as monat,
    COUNT(*) as anzahl_reparaturen,
    SUM(repair_cost) as gesamtkosten,
    AVG(repair_cost) as durchschnittskosten,
    SUM(CASE WHEN repair_completed = TRUE THEN repair_cost ELSE 0 END) as abgeschlossene_kosten,
    SUM(CASE WHEN repair_completed = FALSE THEN repair_cost ELSE 0 END) as geplante_kosten
FROM defects 
WHERE repair_required = TRUE AND repair_cost IS NOT NULL
GROUP BY YEAR(created_at), MONTH(created_at)
ORDER BY jahr DESC, monat DESC;

-- Abfrage 6: Prüfer-Leistungsübersicht
SELECT 
    'Prüfer-Leistung' as query_name,
    CONCAT(u.first_name, ' ', u.last_name) as pruefer_name,
    u.department,
    COUNT(i.id) as anzahl_pruefungen,
    COUNT(CASE WHEN i.result = 'Bestanden' THEN 1 END) as bestanden,
    COUNT(CASE WHEN i.result = 'Bestanden mit Einschränkungen' THEN 1 END) as mit_einschraenkungen,
    COUNT(CASE WHEN i.result = 'Durchgefallen' THEN 1 END) as durchgefallen,
    ROUND(COUNT(CASE WHEN i.result = 'Bestanden' THEN 1 END) * 100.0 / COUNT(i.id), 2) as erfolgsquote_prozent,
    MAX(i.inspection_date) as letzte_pruefung
FROM users u
INNER JOIN inspections i ON u.id = i.inspector_id
WHERE i.inspection_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
GROUP BY u.id, u.first_name, u.last_name, u.department
HAVING anzahl_pruefungen > 0
ORDER BY anzahl_pruefungen DESC;

-- Abfrage 7: Leiter-Altersstruktur und Wartungsbedarf
SELECT 
    'Leiter-Altersstruktur' as query_name,
    CASE 
        WHEN DATEDIFF(CURDATE(), purchase_date) <= 365 THEN '0-1 Jahre'
        WHEN DATEDIFF(CURDATE(), purchase_date) <= 730 THEN '1-2 Jahre'
        WHEN DATEDIFF(CURDATE(), purchase_date) <= 1095 THEN '2-3 Jahre'
        WHEN DATEDIFF(CURDATE(), purchase_date) <= 1460 THEN '3-4 Jahre'
        WHEN DATEDIFF(CURDATE(), purchase_date) <= 1825 THEN '4-5 Jahre'
        ELSE 'Über 5 Jahre'
    END as altersgruppe,
    COUNT(*) as anzahl_leitern,
    AVG(DATEDIFF(CURDATE(), purchase_date)) as durchschnittsalter_tage,
    COUNT(CASE WHEN status = 'Reparatur' THEN 1 END) as in_reparatur,
    COUNT(CASE WHEN status = 'Gesperrt' THEN 1 END) as gesperrt
FROM ladders
WHERE purchase_date IS NOT NULL
GROUP BY 
    CASE 
        WHEN DATEDIFF(CURDATE(), purchase_date) <= 365 THEN '0-1 Jahre'
        WHEN DATEDIFF(CURDATE(), purchase_date) <= 730 THEN '1-2 Jahre'
        WHEN DATEDIFF(CURDATE(), purchase_date) <= 1095 THEN '2-3 Jahre'
        WHEN DATEDIFF(CURDATE(), purchase_date) <= 1460 THEN '3-4 Jahre'
        WHEN DATEDIFF(CURDATE(), purchase_date) <= 1825 THEN '4-5 Jahre'
        ELSE 'Über 5 Jahre'
    END
ORDER BY durchschnittsalter_tage ASC;

-- ==============================================
-- TESTABFRAGEN FÜR STORED PROCEDURES
-- ==============================================

-- Test der Stored Procedures
CALL GetUpcomingInspections(60);
CALL GetLadderHistory(1);
CALL GetDefectsDashboard();
CALL GetInspectionStats('2024-01-01', '2024-12-31');
CALL GetLadderDashboardStats();

-- Test der Function
SELECT 
    'Function Test' as test_name,
    CalculateNextInspectionDate('Hauptprüfung', '2024-12-01', 'Anlegeleiter') as next_date_anlegeleiter,
    CalculateNextInspectionDate('Hauptprüfung', '2024-12-01', 'Stehleiter') as next_date_stehleiter,
    CalculateNextInspectionDate('Sichtprüfung', '2024-12-01', 'Mehrzweckleiter') as next_date_sichtpruefung;

-- ==============================================
-- PERFORMANCE-TESTS
-- ==============================================

-- Test der Indizes mit EXPLAIN
EXPLAIN SELECT * FROM ladders WHERE ladder_number = 'L-001';
EXPLAIN SELECT * FROM inspections WHERE ladder_id = 1 ORDER BY inspection_date DESC;
EXPLAIN SELECT * FROM defects WHERE severity = 'Kritisch' AND repair_completed = FALSE;

-- ==============================================
-- DATENQUALITÄTS-CHECKS
-- ==============================================

-- Check 1: Leitern ohne Prüfungen
SELECT 
    'Leitern ohne Prüfungen' as check_name,
    l.ladder_number,
    l.manufacturer,
    l.model,
    l.created_at
FROM ladders l
LEFT JOIN inspections i ON l.id = i.ladder_id
WHERE i.id IS NULL;

-- Check 2: Prüfungen ohne Prüfpunkte
SELECT 
    'Prüfungen ohne Prüfpunkte' as check_name,
    i.id as inspection_id,
    l.ladder_number,
    i.inspection_date,
    i.inspection_type
FROM inspections i
INNER JOIN ladders l ON i.ladder_id = l.id
LEFT JOIN inspection_items ii ON i.id = ii.inspection_id
WHERE ii.id IS NULL;

-- Check 3: Inkonsistente Daten
SELECT 
    'Inkonsistente Prüfungsdaten' as check_name,
    i.id as inspection_id,
    l.ladder_number,
    i.inspection_date,
    i.next_inspection_date,
    DATEDIFF(i.next_inspection_date, i.inspection_date) as tage_differenz
FROM inspections i
INNER JOIN ladders l ON i.ladder_id = l.id
WHERE i.next_inspection_date <= i.inspection_date
   OR DATEDIFF(i.next_inspection_date, i.inspection_date) > 730; -- Mehr als 2 Jahre

-- ==============================================
-- WARTUNGS-SCRIPTS
-- ==============================================

-- Script: Alte Audit-Logs bereinigen (älter als 2 Jahre)
-- DELETE FROM audit_log WHERE changed_at < DATE_SUB(CURDATE(), INTERVAL 2 YEAR);

-- Script: Inaktive Benutzer identifizieren
SELECT 
    'Inaktive Benutzer' as maintenance_task,
    username,
    email,
    last_login,
    DATEDIFF(CURDATE(), last_login) as tage_seit_login
FROM users 
WHERE ldap_user = TRUE 
  AND (last_login IS NULL OR last_login < DATE_SUB(CURDATE(), INTERVAL 6 MONTH))
  AND is_active = TRUE;

-- Script: Überfällige Reparaturen eskalieren
SELECT 
    'Überfällige Reparaturen' as maintenance_task,
    l.ladder_number,
    d.description,
    d.repair_deadline,
    DATEDIFF(CURDATE(), d.repair_deadline) as tage_ueberfaellig,
    d.severity
FROM defects d
INNER JOIN inspections i ON d.inspection_id = i.id
INNER JOIN ladders l ON i.ladder_id = l.id
WHERE d.repair_required = TRUE 
  AND d.repair_completed = FALSE 
  AND d.repair_deadline < CURDATE()
ORDER BY tage_ueberfaellig DESC, d.severity DESC;

-- ==============================================
-- ABSCHLUSS-KOMMENTARE
-- ==============================================

/*
ERWEITERTE TESTDATEN UND BEISPIELABFRAGEN ERSTELLT

Enthaltene Komponenten:
✓ Zusätzliche realistische Testdaten
✓ Umfassende Beispielabfragen für typische Anwendungsfälle
✓ Tests für alle Stored Procedures und Functions
✓ Performance-Tests mit EXPLAIN
✓ Datenqualitäts-Checks
✓ Wartungs-Scripts für den Produktivbetrieb

Die Testdaten decken verschiedene Szenarien ab:
- Normale Prüfungen mit verschiedenen Ergebnissen
- Kritische Mängel und Reparaturen
- Historische Daten für Trend-Analysen
- Verschiedene Leiter-Typen und Standorte
- Realistische Benutzer-Aktivitäten

Diese Datei kann für Tests, Schulungen und als Referenz
für die Entwicklung der Anwendungslogik verwendet werden.
*/
