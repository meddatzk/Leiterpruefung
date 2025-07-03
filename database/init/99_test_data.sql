-- =====================================================
-- Testdaten für Leiterprüfung
-- =====================================================

USE leiterprüfung;

-- Audit-Kontext für Testdaten setzen
SET @audit_user_id = 1;
SET @audit_user_ip = '127.0.0.1';

-- =====================================================
-- Testbenutzer
-- =====================================================

INSERT INTO users (username, email, full_name, role, department, phone, is_active) VALUES
('admin', 'admin@musterfirma.de', 'Max Mustermann', 'admin', 'IT', '+49 123 456789', TRUE),
('inspector1', 'mueller@musterfirma.de', 'Hans Müller', 'inspector', 'Arbeitssicherheit', '+49 123 456790', TRUE),
('inspector2', 'schmidt@musterfirma.de', 'Anna Schmidt', 'inspector', 'Arbeitssicherheit', '+49 123 456791', TRUE),
('viewer1', 'weber@musterfirma.de', 'Klaus Weber', 'viewer', 'Produktion', '+49 123 456792', TRUE),
('inspector3', 'fischer@musterfirma.de', 'Maria Fischer', 'inspector', 'Wartung', '+49 123 456793', TRUE);

-- =====================================================
-- Testleitern (5 Leitern wie gefordert)
-- =====================================================

INSERT INTO ladders (
    ladder_number, manufacturer, model, ladder_type, material, max_load_kg, height_cm, 
    purchase_date, location, department, responsible_person, serial_number, notes, 
    status, next_inspection_date, inspection_interval_months
) VALUES
-- Leiter 1: Anlegeleiter
('L-001', 'Hailo', 'ProfiStep duo', 'Anlegeleiter', 'Aluminium', 150, 280, 
 '2023-01-15', 'Werkstatt A - Halle 1', 'Produktion', 'Klaus Weber', 'HL-2023-001', 
 'Neue Leiter, sehr guter Zustand', 'active', '2025-01-15', 12),

-- Leiter 2: Stehleiter
('L-002', 'Günzburger', 'Steigtechnik S-Line', 'Stehleiter', 'Aluminium', 120, 200, 
 '2022-06-10', 'Lager B - Regal 15', 'Logistik', 'Thomas Bauer', 'GS-2022-045', 
 'Regelmäßig in Verwendung', 'active', '2024-12-10', 12),

-- Leiter 3: Mehrzweckleiter
('L-003', 'Krause', 'MultiMatic', 'Mehrzweckleiter', 'Aluminium', 150, 350, 
 '2021-03-22', 'Außenbereich - Gebäude C', 'Wartung', 'Maria Fischer', 'KR-2021-078', 
 'Vielseitig einsetzbar, häufig verwendet', 'active', '2025-03-22', 12),

-- Leiter 4: Podestleiter
('L-004', 'Zarges', 'Z600 Stufen-Stehleiter', 'Podestleiter', 'Aluminium', 150, 180, 
 '2023-09-05', 'Montage - Arbeitsplatz 7', 'Produktion', 'Stefan Richter', 'ZG-2023-156', 
 'Mit Plattform und Geländer', 'active', '2025-09-05', 12),

-- Leiter 5: Schiebeleiter (älter, nächste Prüfung überfällig)
('L-005', 'Layher', 'Topic Schiebeleiter', 'Schiebeleiter', 'Aluminium', 120, 450, 
 '2020-11-18', 'Außenlager - Container 3', 'Wartung', 'Peter Hoffmann', 'LH-2020-234', 
 'Ältere Leiter, benötigt regelmäßige Wartung', 'active', '2024-11-18', 12);

-- =====================================================
-- Testprüfungen (15 Prüfungen wie gefordert)
-- =====================================================

-- Prüfungen für Leiter L-001 (3 Prüfungen)
INSERT INTO inspections (
    ladder_id, inspector_id, inspection_date, inspection_type, overall_result, 
    next_inspection_date, inspection_duration_minutes, weather_conditions, 
    temperature_celsius, general_notes, recommendations, defects_found, actions_required
) VALUES
-- L-001 Prüfung 1 (Erstprüfung)
(1, 2, '2023-01-20', 'initial', 'passed', '2024-01-20', 45, 'Trocken, bewölkt', 18,
 'Erstprüfung nach Anschaffung. Leiter in einwandfreiem Zustand.',
 'Regelmäßige Sichtprüfung vor Gebrauch empfohlen.',
 'Keine Mängel festgestellt.',
 'Keine Maßnahmen erforderlich.'),

-- L-001 Prüfung 2 (Routineprüfung)
(1, 2, '2024-01-25', 'routine', 'passed', '2025-01-25', 35, 'Sonnig', 12,
 'Routineprüfung planmäßig durchgeführt. Leiter zeigt normale Gebrauchsspuren.',
 'Leiterfüße regelmäßig auf Verschleiß prüfen.',
 'Leichte Kratzer an den Holmen, funktional unbedenklich.',
 'Keine Maßnahmen erforderlich.'),

-- L-001 Prüfung 3 (Aktuelle Prüfung)
(1, 3, '2024-12-15', 'routine', 'conditional', '2025-12-15', 50, 'Regnerisch', 8,
 'Prüfung ergab kleinere Mängel, die behoben werden sollten.',
 'Spreizen sollten in den nächsten 3 Monaten ersetzt werden.',
 'Spreizen zeigen Verschleißerscheinungen, noch funktionsfähig.',
 'Spreizen bis März 2025 ersetzen.'),

-- Prüfungen für Leiter L-002 (3 Prüfungen)
-- L-002 Prüfung 1
(2, 2, '2022-06-15', 'initial', 'passed', '2023-06-15', 40, 'Sonnig', 25,
 'Erstprüfung nach Anschaffung erfolgreich.',
 'Keine besonderen Empfehlungen.',
 'Keine Mängel.',
 'Keine Maßnahmen erforderlich.'),

-- L-002 Prüfung 2
(2, 3, '2023-06-20', 'routine', 'passed', '2024-06-20', 30, 'Bewölkt', 20,
 'Routineprüfung ohne Beanstandungen.',
 'Weiterhin regelmäßig verwenden.',
 'Keine Mängel festgestellt.',
 'Keine Maßnahmen erforderlich.'),

-- L-002 Prüfung 3
(2, 2, '2024-06-25', 'routine', 'passed', '2025-06-25', 32, 'Leicht bewölkt', 22,
 'Leiter in gutem Zustand, regelmäßige Nutzung erkennbar.',
 'Fortsetzung der bisherigen Nutzung.',
 'Minimale Abnutzung an Stufen, unbedenklich.',
 'Keine Maßnahmen erforderlich.'),

-- Prüfungen für Leiter L-003 (3 Prüfungen)
-- L-003 Prüfung 1
(3, 5, '2021-04-01', 'initial', 'passed', '2022-04-01', 55, 'Windig', 15,
 'Erstprüfung der Mehrzweckleiter. Alle Funktionen getestet.',
 'Besondere Aufmerksamkeit auf Gelenke bei häufiger Nutzung.',
 'Keine Mängel.',
 'Keine Maßnahmen erforderlich.'),

-- L-003 Prüfung 2
(3, 5, '2022-04-05', 'routine', 'passed', '2023-04-05', 48, 'Sonnig', 18,
 'Routineprüfung. Mehrzweckfunktion einwandfrei.',
 'Gelenke regelmäßig schmieren.',
 'Leichte Verschmutzung, keine technischen Mängel.',
 'Reinigung empfohlen.'),

-- L-003 Prüfung 3
(3, 2, '2023-04-10', 'routine', 'failed', '2024-04-10', 65, 'Regnerisch', 10,
 'Prüfung ergab sicherheitsrelevante Mängel an den Gelenken.',
 'Sofortige Reparatur erforderlich, Nutzung bis dahin untersagt.',
 'Gelenk defekt, Arretierung funktioniert nicht zuverlässig.',
 'Gelenk reparieren oder Leiter ersetzen. Nutzungsstopp bis Reparatur.'),

-- Prüfungen für Leiter L-004 (3 Prüfungen)
-- L-004 Prüfung 1
(4, 3, '2023-09-10', 'initial', 'passed', '2024-09-10', 42, 'Sonnig', 24,
 'Erstprüfung der Podestleiter mit Plattform.',
 'Plattform und Geländer regelmäßig auf festen Sitz prüfen.',
 'Keine Mängel.',
 'Keine Maßnahmen erforderlich.'),

-- L-004 Prüfung 2
(4, 2, '2024-09-15', 'routine', 'passed', '2025-09-15', 38, 'Bewölkt', 19,
 'Routineprüfung erfolgreich. Plattform stabil.',
 'Weiterhin sorgfältig verwenden.',
 'Keine Mängel festgestellt.',
 'Keine Maßnahmen erforderlich.'),

-- L-004 Prüfung 3 (Sonderprüfung nach Vorfall)
(4, 5, '2024-11-20', 'after_incident', 'conditional', '2025-11-20', 60, 'Kalt, trocken', 5,
 'Sonderprüfung nach gemeldeter unsicherer Nutzung.',
 'Schulung der Mitarbeiter zur korrekten Nutzung empfohlen.',
 'Geländer leicht gelockert, Plattform zeigt Kratzer.',
 'Geländer nachziehen, Plattform überprüfen lassen.'),

-- Prüfungen für Leiter L-005 (3 Prüfungen)
-- L-005 Prüfung 1
(5, 5, '2020-12-01', 'initial', 'passed', '2021-12-01', 50, 'Kalt', 2,
 'Erstprüfung der Schiebeleiter.',
 'Schiebemechanismus regelmäßig warten.',
 'Keine Mängel.',
 'Keine Maßnahmen erforderlich.'),

-- L-005 Prüfung 2
(5, 2, '2021-12-05', 'routine', 'passed', '2022-12-05', 45, 'Bewölkt', 8,
 'Routineprüfung ohne Probleme.',
 'Schiebemechanismus funktioniert einwandfrei.',
 'Keine Mängel.',
 'Keine Maßnahmen erforderlich.'),

-- L-005 Prüfung 3 (letzte Prüfung - überfällig)
(5, 3, '2023-12-10', 'routine', 'conditional', '2024-12-10', 55, 'Neblig', 6,
 'Prüfung zeigt Verschleißerscheinungen durch Alter und Witterung.',
 'Intensive Wartung erforderlich, nächste Prüfung in 6 Monaten.',
 'Korrosionsspuren, Schiebemechanismus schwergängig.',
 'Wartung des Schiebemechanismus, Korrosionsschutz erneuern.');

-- =====================================================
-- Prüfpunkte für ausgewählte Prüfungen
-- =====================================================

-- Prüfpunkte für L-001, Prüfung 3 (ID 3) - mit Mängeln
INSERT INTO inspection_items (inspection_id, category, item_name, description, result, severity, notes, repair_required, repair_deadline, sort_order) VALUES
(3, 'structure', 'Holme/Profile', 'Prüfung auf Risse, Verformungen, Korrosion', 'ok', NULL, 'Holme in gutem Zustand', FALSE, NULL, 1),
(3, 'structure', 'Sprossen/Stufen', 'Befestigung, Zustand, Rutschfestigkeit', 'ok', NULL, 'Alle Sprossen fest und rutschfest', FALSE, NULL, 2),
(3, 'structure', 'Verbindungen', 'Schrauben, Nieten, Schweißnähte', 'ok', NULL, 'Verbindungen fest', FALSE, NULL, 3),
(3, 'safety', 'Spreizen/Ketten', 'Zustand, Funktion, Befestigung', 'wear', 'medium', 'Spreizen zeigen deutliche Verschleißspuren', TRUE, '2025-03-15', 4),
(3, 'safety', 'Leiterfüße', 'Rutschfestigkeit, Zustand, Befestigung', 'ok', NULL, 'Leiterfüße in Ordnung', FALSE, NULL, 5),
(3, 'function', 'Standsicherheit', 'Stabilität in verschiedenen Positionen', 'ok', NULL, 'Standsicherheit gegeben', FALSE, NULL, 6),
(3, 'marking', 'CE-Kennzeichnung', 'Vorhandensein, Lesbarkeit', 'ok', NULL, 'CE-Kennzeichnung vorhanden und lesbar', FALSE, NULL, 7),
(3, 'marking', 'Typenschild', 'Vollständigkeit, Lesbarkeit', 'ok', NULL, 'Typenschild vollständig', FALSE, NULL, 8);

-- Prüfpunkte für L-003, Prüfung 3 (ID 9) - mit kritischen Mängeln
INSERT INTO inspection_items (inspection_id, category, item_name, description, result, severity, notes, repair_required, repair_deadline, sort_order) VALUES
(9, 'structure', 'Holme/Profile', 'Prüfung auf Risse, Verformungen, Korrosion', 'ok', NULL, 'Holme ohne Beanstandung', FALSE, NULL, 1),
(9, 'structure', 'Sprossen/Stufen', 'Befestigung, Zustand, Rutschfestigkeit', 'ok', NULL, 'Sprossen in Ordnung', FALSE, NULL, 2),
(9, 'structure', 'Gelenke/Scharniere', 'Beweglichkeit, Verschleiß, Spiel', 'defect', 'critical', 'Gelenk arretiert nicht zuverlässig - SICHERHEITSRISIKO!', TRUE, '2023-04-15', 3),
(9, 'safety', 'Spreizen/Ketten', 'Zustand, Funktion, Befestigung', 'ok', NULL, 'Spreizen funktionsfähig', FALSE, NULL, 4),
(9, 'function', 'Ausziehbarkeit', 'Leichtgängigkeit, Arretierung', 'defect', 'high', 'Arretierung funktioniert nicht in allen Positionen', TRUE, '2023-04-15', 5),
(9, 'function', 'Klappfunktion', 'Mechanismus, Arretierung', 'defect', 'critical', 'Klappfunktion unsicher', TRUE, '2023-04-15', 6),
(9, 'marking', 'CE-Kennzeichnung', 'Vorhandensein, Lesbarkeit', 'ok', NULL, 'Kennzeichnung vorhanden', FALSE, NULL, 7);

-- Prüfpunkte für L-005, Prüfung 3 (ID 15) - mit Verschleiß
INSERT INTO inspection_items (inspection_id, category, item_name, description, result, severity, notes, repair_required, repair_deadline, sort_order) VALUES
(15, 'structure', 'Holme/Profile', 'Prüfung auf Risse, Verformungen, Korrosion', 'wear', 'medium', 'Leichte Korrosionsspuren durch Witterung', TRUE, '2024-06-10', 1),
(15, 'structure', 'Sprossen/Stufen', 'Befestigung, Zustand, Rutschfestigkeit', 'ok', NULL, 'Sprossen noch in Ordnung', FALSE, NULL, 2),
(15, 'structure', 'Verbindungen', 'Schrauben, Nieten, Schweißnähte', 'wear', 'low', 'Schrauben leicht korrodiert', FALSE, NULL, 3),
(15, 'function', 'Ausziehbarkeit', 'Leichtgängigkeit, Arretierung', 'wear', 'medium', 'Schiebemechanismus schwergängig', TRUE, '2024-03-10', 4),
(15, 'safety', 'Leiterfüße', 'Rutschfestigkeit, Zustand, Befestigung', 'ok', NULL, 'Leiterfüße noch ausreichend', FALSE, NULL, 5),
(15, 'marking', 'Prüfplakette', 'Aktualität, Befestigung', 'wear', 'low', 'Prüfplakette verblasst aber lesbar', FALSE, NULL, 6);

-- =====================================================
-- Zusätzliche realistische Daten
-- =====================================================

-- Supervisor-Genehmigungen für einige Prüfungen
UPDATE inspections SET supervisor_approval_id = 1, approval_date = DATE_ADD(inspection_date, INTERVAL 1 DAY) 
WHERE id IN (9, 12, 15); -- Kritische Prüfungen werden vom Admin genehmigt

-- Letzte Login-Zeiten für Benutzer aktualisieren
UPDATE users SET last_login = DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 30) DAY) WHERE id > 1;

-- =====================================================
-- Statistik-Hilfsdaten
-- =====================================================

-- Einige ältere Prüfungen für bessere Statistiken
INSERT INTO inspections (ladder_id, inspector_id, inspection_date, inspection_type, overall_result, next_inspection_date, inspection_duration_minutes, general_notes) VALUES
(1, 2, '2022-01-15', 'routine', 'passed', '2023-01-15', 35, 'Ältere Routineprüfung - alles in Ordnung'),
(2, 3, '2021-06-10', 'routine', 'passed', '2022-06-10', 30, 'Ältere Routineprüfung - keine Probleme'),
(4, 5, '2022-09-05', 'routine', 'conditional', '2023-09-05', 45, 'Kleinere Mängel behoben');

-- Audit-Kontext zurücksetzen
SET @audit_user_id = NULL;
SET @audit_user_ip = NULL;

-- =====================================================
-- Datenbank-Statistiken anzeigen
-- =====================================================

SELECT 'Testdaten erfolgreich eingefügt!' as Status;

SELECT 
    'Benutzer' as Tabelle, 
    COUNT(*) as Anzahl 
FROM users
UNION ALL
SELECT 
    'Leitern' as Tabelle, 
    COUNT(*) as Anzahl 
FROM ladders
UNION ALL
SELECT 
    'Prüfungen' as Tabelle, 
    COUNT(*) as Anzahl 
FROM inspections
UNION ALL
SELECT 
    'Prüfpunkte' as Tabelle, 
    COUNT(*) as Anzahl 
FROM inspection_items
UNION ALL
SELECT 
    'Audit-Einträge' as Tabelle, 
    COUNT(*) as Anzahl 
FROM audit_log;

-- Übersicht über Prüfstatus
SELECT 
    l.ladder_number,
    l.manufacturer,
    l.ladder_type,
    l.status,
    l.next_inspection_date,
    CASE 
        WHEN l.next_inspection_date < CURDATE() THEN 'Überfällig'
        WHEN DATEDIFF(l.next_inspection_date, CURDATE()) <= 30 THEN 'Bald fällig'
        ELSE 'Aktuell'
    END as Prüfstatus,
    COUNT(i.id) as Anzahl_Prüfungen
FROM ladders l
LEFT JOIN inspections i ON l.id = i.ladder_id
GROUP BY l.id, l.ladder_number, l.manufacturer, l.ladder_type, l.status, l.next_inspection_date
ORDER BY l.ladder_number;
