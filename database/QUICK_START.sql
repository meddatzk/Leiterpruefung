-- ==============================================
-- LEITERPRÜFUNG - QUICK START GUIDE
-- Wichtigste SQL-Befehle für den Einstieg
-- ==============================================

USE leiterpruefung;

-- ==============================================
-- GRUNDLEGENDE ABFRAGEN
-- ==============================================

-- 1. Alle aktiven Leitern anzeigen
SELECT ladder_number, manufacturer, model, location, status 
FROM ladders 
WHERE status = 'Aktiv' 
ORDER BY ladder_number;

-- 2. Überfällige Prüfungen anzeigen
SELECT * FROM v_inspections_due 
WHERE urgency = 'Überfällig';

-- 3. Kritische Mängel anzeigen
SELECT * FROM v_defects_overview 
WHERE severity = 'Kritisch' AND repair_completed = FALSE;

-- 4. Aktuelle Leiter-Übersicht mit letzter Prüfung
SELECT * FROM v_ladders_current 
ORDER BY inspection_status DESC, days_until_inspection ASC;

-- ==============================================
-- HÄUFIGE DASHBOARD-ABFRAGEN
-- ==============================================

-- Dashboard-Statistiken abrufen
CALL GetLadderDashboardStats();

-- Fällige Prüfungen der nächsten 30 Tage
CALL GetUpcomingInspections(30);

-- Mängel-Dashboard
CALL GetDefectsDashboard();

-- ==============================================
-- NEUE DATEN EINFÜGEN
-- ==============================================

-- Neue Leiter hinzufügen
INSERT INTO ladders (ladder_number, serial_number, manufacturer, model, type, material, max_load_kg, height_cm, location, created_by)
VALUES ('L-999', 'TEST-999-2025', 'Test-Hersteller', 'Test-Modell', 'Stehleiter', 'Aluminium', 120, 200, 'Test-Standort', 1);

-- Neuen Benutzer hinzufügen (LDAP)
INSERT INTO users (username, email, first_name, last_name, department, ldap_user, created_by)
VALUES ('test.user', 'test.user@firma.de', 'Test', 'Benutzer', 'Test-Abteilung', TRUE, 1);

-- Neue Prüfung mit Standard-Prüfpunkten erstellen
SET @new_inspection_id = 0;
CALL CreateInspectionWithItems(1, 2, CURDATE(), 'Sichtprüfung', @new_inspection_id);
SELECT @new_inspection_id as neue_pruefung_id;

-- ==============================================
-- PRÜFUNGS-WORKFLOW
-- ==============================================

-- 1. Prüfung durchführen - Prüfpunkt als mangelhaft markieren
UPDATE inspection_items 
SET status = 'Mangel erheblich', 
    comments = 'Beschädigung festgestellt', 
    repair_required = TRUE,
    repair_deadline = DATE_ADD(CURDATE(), INTERVAL 30 DAY)
WHERE inspection_id = 1 AND item_name = 'Sprossen-Verbindungen';

-- 2. Mangel dokumentieren
INSERT INTO defects (inspection_id, inspection_item_id, category, severity, description, repair_required, repair_deadline)
VALUES (1, 1, 'Mechanisch', 'Hoch', 'Sprosse gelockert - Nachziehen erforderlich', TRUE, DATE_ADD(CURDATE(), INTERVAL 14 DAY));

-- 3. Prüfung finalisieren (verhindert weitere Änderungen)
UPDATE inspections 
SET is_final = TRUE, result = 'Bestanden mit Einschränkungen'
WHERE id = 1;

-- ==============================================
-- REPARATUR-WORKFLOW
-- ==============================================

-- Reparatur als abgeschlossen markieren
UPDATE defects 
SET repair_completed = TRUE, 
    repair_date = CURDATE(),
    repair_notes = 'Sprosse nachgezogen und gesichert',
    repair_cost = 25.00
WHERE id = 1;

-- Leiter nach Reparatur wieder freigeben
UPDATE ladders 
SET status = 'Aktiv', 
    notes = 'Nach Reparatur wieder einsatzbereit'
WHERE id = 1;

-- ==============================================
-- SUCHFUNKTIONEN
-- ==============================================

-- Leiter nach Nummer suchen
SELECT * FROM v_ladders_current 
WHERE ladder_number = 'L-001';

-- Leiter nach Standort suchen
SELECT * FROM ladders 
WHERE location LIKE '%Lager%';

-- Prüfungen eines bestimmten Prüfers
SELECT l.ladder_number, i.inspection_date, i.inspection_type, i.result
FROM inspections i
JOIN ladders l ON i.ladder_id = l.id
JOIN users u ON i.inspector_id = u.id
WHERE u.username = 'mueller.hans'
ORDER BY i.inspection_date DESC;

-- ==============================================
-- BERICHTE UND STATISTIKEN
-- ==============================================

-- Prüfungsstatistiken des letzten Jahres
CALL GetInspectionStats('2024-01-01', '2024-12-31');

-- Historie einer spezifischen Leiter
CALL GetLadderHistory(1);

-- Leiter nach Alter gruppiert
SELECT 
    CASE 
        WHEN DATEDIFF(CURDATE(), purchase_date) <= 365 THEN 'Neu (0-1 Jahre)'
        WHEN DATEDIFF(CURDATE(), purchase_date) <= 1095 THEN 'Mittel (1-3 Jahre)'
        ELSE 'Alt (>3 Jahre)'
    END as altersgruppe,
    COUNT(*) as anzahl
FROM ladders 
WHERE purchase_date IS NOT NULL
GROUP BY altersgruppe;

-- Mängel nach Schweregrad
SELECT severity, COUNT(*) as anzahl, 
       SUM(CASE WHEN repair_completed = TRUE THEN 1 ELSE 0 END) as repariert
FROM defects 
GROUP BY severity 
ORDER BY FIELD(severity, 'Kritisch', 'Hoch', 'Mittel', 'Gering');

-- ==============================================
-- WARTUNG UND MONITORING
-- ==============================================

-- Leitern ohne Prüfungen finden
SELECT l.ladder_number, l.manufacturer, l.model, l.created_at
FROM ladders l
LEFT JOIN inspections i ON l.id = i.ladder_id
WHERE i.id IS NULL;

-- Überfällige Reparaturen
SELECT l.ladder_number, d.description, d.repair_deadline, 
       DATEDIFF(CURDATE(), d.repair_deadline) as tage_ueberfaellig
FROM defects d
JOIN inspections i ON d.inspection_id = i.id
JOIN ladders l ON i.ladder_id = l.id
WHERE d.repair_required = TRUE 
  AND d.repair_completed = FALSE 
  AND d.repair_deadline < CURDATE()
ORDER BY tage_ueberfaellig DESC;

-- Inaktive Benutzer (länger als 6 Monate nicht eingeloggt)
SELECT username, email, last_login, 
       DATEDIFF(CURDATE(), last_login) as tage_inaktiv
FROM users 
WHERE ldap_user = TRUE 
  AND (last_login IS NULL OR last_login < DATE_SUB(CURDATE(), INTERVAL 6 MONTH))
  AND is_active = TRUE;

-- ==============================================
-- NÜTZLICHE FUNKTIONEN TESTEN
-- ==============================================

-- Nächstes Prüfungsdatum berechnen
SELECT 
    'Anlegeleiter Hauptprüfung' as typ,
    CalculateNextInspectionDate('Hauptprüfung', CURDATE(), 'Anlegeleiter') as naechster_termin
UNION ALL
SELECT 
    'Stehleiter Sichtprüfung' as typ,
    CalculateNextInspectionDate('Sichtprüfung', CURDATE(), 'Stehleiter') as naechster_termin;

-- ==============================================
-- BACKUP UND WIEDERHERSTELLUNG
-- ==============================================

-- Backup erstellen (Beispiel-Befehl für mysqldump)
-- mysqldump -u root -p leiterpruefung > backup_leiterpruefung_$(date +%Y%m%d).sql

-- Datenbank wiederherstellen (Beispiel)
-- mysql -u root -p leiterpruefung < backup_leiterpruefung_20250103.sql

-- ==============================================
-- PERFORMANCE-OPTIMIERUNG
-- ==============================================

-- Langsame Abfragen identifizieren
-- SHOW PROCESSLIST;

-- Index-Nutzung prüfen
EXPLAIN SELECT * FROM ladders WHERE ladder_number = 'L-001';
EXPLAIN SELECT * FROM inspections WHERE ladder_id = 1 ORDER BY inspection_date DESC;

-- Tabellen-Statistiken anzeigen
SHOW TABLE STATUS FROM leiterpruefung;

-- ==============================================
-- HÄUFIGE FEHLERBEHANDLUNG
-- ==============================================

-- Constraint-Verletzungen prüfen
-- Bei Fehlern wie "Cannot add or update a child row: a foreign key constraint fails"

-- Prüfen welche Referenzen existieren
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE REFERENCED_TABLE_SCHEMA = 'leiterpruefung'
  AND REFERENCED_TABLE_NAME = 'users';

-- Dateninkonsistenzen finden
SELECT 'Prüfungen ohne gültige Leiter' as problem, COUNT(*) as anzahl
FROM inspections i
LEFT JOIN ladders l ON i.ladder_id = l.id
WHERE l.id IS NULL

UNION ALL

SELECT 'Mängel ohne gültige Prüfungen' as problem, COUNT(*) as anzahl
FROM defects d
LEFT JOIN inspections i ON d.inspection_id = i.id
WHERE i.id IS NULL;

-- ==============================================
-- TIPPS UND TRICKS
-- ==============================================

/*
WICHTIGE HINWEISE:

1. Verwenden Sie immer Transaktionen für zusammenhängende Operationen:
   START TRANSACTION;
   -- Ihre SQL-Befehle hier
   COMMIT; -- oder ROLLBACK bei Fehlern

2. Nutzen Sie die Views für Standard-Abfragen:
   - v_ladders_current: Aktuelle Leiter-Übersicht
   - v_inspections_due: Fällige Prüfungen
   - v_defects_overview: Mängel-Übersicht

3. Stored Procedures für häufige Operationen:
   - GetUpcomingInspections(tage)
   - GetLadderHistory(leiter_id)
   - CreateInspectionWithItems(...)

4. Beachten Sie die Trigger:
   - Prüfungstermine werden automatisch berechnet
   - Finalisierte Prüfungen können nicht geändert werden
   - Änderungen werden im Audit-Log protokolliert

5. Performance-Tipps:
   - Nutzen Sie die vorhandenen Indizes
   - Verwenden Sie LIMIT bei großen Ergebnismengen
   - Vermeiden Sie SELECT * in Produktionsabfragen

6. Sicherheit:
   - Verwenden Sie Prepared Statements in der Anwendung
   - Validieren Sie Eingaben
   - Implementieren Sie Rollen-basierte Zugriffskontrolle
*/
