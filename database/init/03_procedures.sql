-- =====================================================
-- Stored Procedures für Leiterprüfung
-- =====================================================

USE leiterprüfung;

DELIMITER //

-- =====================================================
-- Procedure: GetLadderHistory
-- Beschreibung: Ruft die komplette Prüfhistorie einer Leiter ab
-- =====================================================
DROP PROCEDURE IF EXISTS GetLadderHistory//
CREATE PROCEDURE GetLadderHistory(
    IN p_ladder_id INT,
    IN p_limit INT DEFAULT 50
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    SELECT 
        i.id,
        i.inspection_date,
        i.inspection_type,
        i.overall_result,
        i.next_inspection_date,
        i.inspection_duration_minutes,
        i.weather_conditions,
        i.temperature_celsius,
        i.general_notes,
        i.recommendations,
        i.defects_found,
        i.actions_required,
        u.full_name AS inspector_name,
        u.department AS inspector_department,
        supervisor.full_name AS supervisor_name,
        i.approval_date,
        COUNT(ii.id) AS total_items,
        SUM(CASE WHEN ii.result = 'defect' THEN 1 ELSE 0 END) AS defect_count,
        SUM(CASE WHEN ii.severity = 'critical' THEN 1 ELSE 0 END) AS critical_count,
        SUM(CASE WHEN ii.repair_required = 1 THEN 1 ELSE 0 END) AS repair_count
    FROM inspections i
    JOIN users u ON i.inspector_id = u.id
    LEFT JOIN users supervisor ON i.supervisor_approval_id = supervisor.id
    LEFT JOIN inspection_items ii ON i.id = ii.inspection_id
    WHERE i.ladder_id = p_ladder_id
    GROUP BY i.id, i.inspection_date, i.inspection_type, i.overall_result, 
             i.next_inspection_date, i.inspection_duration_minutes, i.weather_conditions,
             i.temperature_celsius, i.general_notes, i.recommendations, i.defects_found,
             i.actions_required, u.full_name, u.department, supervisor.full_name, i.approval_date
    ORDER BY i.inspection_date DESC
    LIMIT p_limit;
END//

-- =====================================================
-- Procedure: GetUpcomingInspections
-- Beschreibung: Ruft anstehende Prüfungen ab
-- =====================================================
DROP PROCEDURE IF EXISTS GetUpcomingInspections//
CREATE PROCEDURE GetUpcomingInspections(
    IN p_days_ahead INT DEFAULT 30,
    IN p_department VARCHAR(100) DEFAULT NULL,
    IN p_status VARCHAR(20) DEFAULT 'active'
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    SELECT 
        l.id,
        l.ladder_number,
        l.manufacturer,
        l.model,
        l.ladder_type,
        l.location,
        l.department,
        l.responsible_person,
        l.next_inspection_date,
        l.status,
        DATEDIFF(l.next_inspection_date, CURDATE()) AS days_until_inspection,
        CASE 
            WHEN l.next_inspection_date < CURDATE() THEN 'Überfällig'
            WHEN DATEDIFF(l.next_inspection_date, CURDATE()) <= 7 THEN 'Dringend'
            WHEN DATEDIFF(l.next_inspection_date, CURDATE()) <= 30 THEN 'Bald fällig'
            ELSE 'Normal'
        END AS priority,
        last_inspection.inspection_date AS last_inspection_date,
        last_inspection.overall_result AS last_result
    FROM ladders l
    LEFT JOIN (
        SELECT 
            ladder_id,
            inspection_date,
            overall_result,
            ROW_NUMBER() OVER (PARTITION BY ladder_id ORDER BY inspection_date DESC) as rn
        FROM inspections
    ) last_inspection ON l.id = last_inspection.ladder_id AND last_inspection.rn = 1
    WHERE l.status = p_status
        AND l.next_inspection_date <= DATE_ADD(CURDATE(), INTERVAL p_days_ahead DAY)
        AND (p_department IS NULL OR l.department = p_department)
    ORDER BY l.next_inspection_date ASC, l.ladder_number;
END//

-- =====================================================
-- Procedure: CreateInspection
-- Beschreibung: Erstellt eine neue Prüfung mit Standard-Prüfpunkten
-- =====================================================
DROP PROCEDURE IF EXISTS CreateInspection//
CREATE PROCEDURE CreateInspection(
    IN p_ladder_id INT,
    IN p_inspector_id INT,
    IN p_inspection_date DATE,
    IN p_inspection_type ENUM('routine', 'initial', 'after_incident', 'special'),
    IN p_weather_conditions VARCHAR(100) DEFAULT NULL,
    IN p_temperature_celsius INT DEFAULT NULL,
    OUT p_inspection_id INT
)
BEGIN
    DECLARE v_next_inspection_date DATE;
    DECLARE v_inspection_interval INT;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- Prüfintervall aus Leiter-Daten holen
    SELECT inspection_interval_months INTO v_inspection_interval
    FROM ladders 
    WHERE id = p_ladder_id;

    -- Nächsten Prüftermin berechnen
    SET v_next_inspection_date = DATE_ADD(p_inspection_date, INTERVAL v_inspection_interval MONTH);

    -- Prüfung erstellen
    INSERT INTO inspections (
        ladder_id,
        inspector_id,
        inspection_date,
        inspection_type,
        overall_result,
        next_inspection_date,
        weather_conditions,
        temperature_celsius
    ) VALUES (
        p_ladder_id,
        p_inspector_id,
        p_inspection_date,
        p_inspection_type,
        'passed', -- Standardwert, wird später aktualisiert
        v_next_inspection_date,
        p_weather_conditions,
        p_temperature_celsius
    );

    SET p_inspection_id = LAST_INSERT_ID();

    -- Standard-Prüfpunkte erstellen
    INSERT INTO inspection_items (inspection_id, category, item_name, description, result, sort_order) VALUES
    -- Struktur
    (p_inspection_id, 'structure', 'Holme/Profile', 'Prüfung auf Risse, Verformungen, Korrosion', 'ok', 1),
    (p_inspection_id, 'structure', 'Sprossen/Stufen', 'Befestigung, Zustand, Rutschfestigkeit', 'ok', 2),
    (p_inspection_id, 'structure', 'Verbindungen', 'Schrauben, Nieten, Schweißnähte', 'ok', 3),
    (p_inspection_id, 'structure', 'Gelenke/Scharniere', 'Beweglichkeit, Verschleiß, Spiel', 'ok', 4),
    
    -- Sicherheit
    (p_inspection_id, 'safety', 'Spreizen/Ketten', 'Zustand, Funktion, Befestigung', 'ok', 5),
    (p_inspection_id, 'safety', 'Leiterfüße', 'Rutschfestigkeit, Zustand, Befestigung', 'ok', 6),
    (p_inspection_id, 'safety', 'Plattform/Bühne', 'Zustand, Befestigung, Geländer', 'ok', 7),
    (p_inspection_id, 'safety', 'Absturzsicherung', 'Haken, Ösen, Sicherheitsgurte', 'ok', 8),
    
    -- Funktion
    (p_inspection_id, 'function', 'Ausziehbarkeit', 'Leichtgängigkeit, Arretierung', 'ok', 9),
    (p_inspection_id, 'function', 'Standsicherheit', 'Stabilität in verschiedenen Positionen', 'ok', 10),
    (p_inspection_id, 'function', 'Klappfunktion', 'Mechanismus, Arretierung', 'ok', 11),
    
    -- Kennzeichnung
    (p_inspection_id, 'marking', 'CE-Kennzeichnung', 'Vorhandensein, Lesbarkeit', 'ok', 12),
    (p_inspection_id, 'marking', 'Typenschild', 'Vollständigkeit, Lesbarkeit', 'ok', 13),
    (p_inspection_id, 'marking', 'Prüfplakette', 'Aktualität, Befestigung', 'ok', 14),
    
    -- Zubehör
    (p_inspection_id, 'accessories', 'Werkzeugablage', 'Zustand, Befestigung', 'ok', 15),
    (p_inspection_id, 'accessories', 'Transportrollen', 'Funktion, Befestigung', 'ok', 16);

    COMMIT;
END//

-- =====================================================
-- Procedure: GetUserStatistics
-- Beschreibung: Ruft Benutzerstatistiken ab
-- =====================================================
DROP PROCEDURE IF EXISTS GetUserStatistics//
CREATE PROCEDURE GetUserStatistics(
    IN p_user_id INT DEFAULT NULL,
    IN p_date_from DATE DEFAULT NULL,
    IN p_date_to DATE DEFAULT NULL
)
BEGIN
    DECLARE v_date_from DATE;
    DECLARE v_date_to DATE;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    -- Standardwerte setzen wenn nicht angegeben
    SET v_date_from = COALESCE(p_date_from, DATE_SUB(CURDATE(), INTERVAL 1 YEAR));
    SET v_date_to = COALESCE(p_date_to, CURDATE());

    SELECT 
        u.id,
        u.username,
        u.full_name,
        u.department,
        u.role,
        COUNT(i.id) AS total_inspections,
        SUM(CASE WHEN i.overall_result = 'passed' THEN 1 ELSE 0 END) AS passed_inspections,
        SUM(CASE WHEN i.overall_result = 'failed' THEN 1 ELSE 0 END) AS failed_inspections,
        SUM(CASE WHEN i.overall_result = 'conditional' THEN 1 ELSE 0 END) AS conditional_inspections,
        ROUND(AVG(i.inspection_duration_minutes), 2) AS avg_duration_minutes,
        COUNT(DISTINCT i.ladder_id) AS unique_ladders_inspected,
        MIN(i.inspection_date) AS first_inspection_date,
        MAX(i.inspection_date) AS last_inspection_date,
        SUM(defect_stats.total_defects) AS total_defects_found,
        SUM(defect_stats.critical_defects) AS critical_defects_found
    FROM users u
    LEFT JOIN inspections i ON u.id = i.inspector_id 
        AND i.inspection_date BETWEEN v_date_from AND v_date_to
    LEFT JOIN (
        SELECT 
            ii.inspection_id,
            COUNT(*) AS total_defects,
            SUM(CASE WHEN ii.severity = 'critical' THEN 1 ELSE 0 END) AS critical_defects
        FROM inspection_items ii
        WHERE ii.result = 'defect'
        GROUP BY ii.inspection_id
    ) defect_stats ON i.id = defect_stats.inspection_id
    WHERE (p_user_id IS NULL OR u.id = p_user_id)
        AND u.role IN ('inspector', 'admin')
    GROUP BY u.id, u.username, u.full_name, u.department, u.role
    ORDER BY total_inspections DESC;
END//

-- =====================================================
-- Procedure: UpdateLadderNextInspection
-- Beschreibung: Aktualisiert den nächsten Prüftermin einer Leiter
-- =====================================================
DROP PROCEDURE IF EXISTS UpdateLadderNextInspection//
CREATE PROCEDURE UpdateLadderNextInspection(
    IN p_ladder_id INT,
    IN p_last_inspection_date DATE
)
BEGIN
    DECLARE v_interval_months INT;
    DECLARE v_next_date DATE;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- Prüfintervall holen
    SELECT inspection_interval_months INTO v_interval_months
    FROM ladders 
    WHERE id = p_ladder_id;

    -- Nächsten Termin berechnen
    SET v_next_date = DATE_ADD(p_last_inspection_date, INTERVAL v_interval_months MONTH);

    -- Leiter aktualisieren
    UPDATE ladders 
    SET next_inspection_date = v_next_date,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_ladder_id;

    COMMIT;
END//

-- =====================================================
-- Procedure: GetInspectionSummary
-- Beschreibung: Ruft eine Zusammenfassung einer Prüfung ab
-- =====================================================
DROP PROCEDURE IF EXISTS GetInspectionSummary//
CREATE PROCEDURE GetInspectionSummary(
    IN p_inspection_id INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    -- Hauptdaten der Prüfung
    SELECT 
        i.id,
        i.inspection_date,
        i.inspection_type,
        i.overall_result,
        i.next_inspection_date,
        i.general_notes,
        i.recommendations,
        i.defects_found,
        i.actions_required,
        l.ladder_number,
        l.manufacturer,
        l.model,
        l.ladder_type,
        l.location,
        l.department,
        u.full_name AS inspector_name,
        supervisor.full_name AS supervisor_name,
        i.approval_date
    FROM inspections i
    JOIN ladders l ON i.ladder_id = l.id
    JOIN users u ON i.inspector_id = u.id
    LEFT JOIN users supervisor ON i.supervisor_approval_id = supervisor.id
    WHERE i.id = p_inspection_id;

    -- Prüfpunkte-Zusammenfassung
    SELECT 
        category,
        COUNT(*) AS total_items,
        SUM(CASE WHEN result = 'ok' THEN 1 ELSE 0 END) AS ok_count,
        SUM(CASE WHEN result = 'defect' THEN 1 ELSE 0 END) AS defect_count,
        SUM(CASE WHEN result = 'wear' THEN 1 ELSE 0 END) AS wear_count,
        SUM(CASE WHEN result = 'not_applicable' THEN 1 ELSE 0 END) AS na_count,
        SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) AS critical_count,
        SUM(CASE WHEN repair_required = 1 THEN 1 ELSE 0 END) AS repair_count
    FROM inspection_items
    WHERE inspection_id = p_inspection_id
    GROUP BY category
    ORDER BY 
        CASE category
            WHEN 'structure' THEN 1
            WHEN 'safety' THEN 2
            WHEN 'function' THEN 3
            WHEN 'marking' THEN 4
            WHEN 'accessories' THEN 5
        END;
END//

DELIMITER ;

-- =====================================================
-- Berechtigungen für Stored Procedures
-- =====================================================

-- Hier können spezifische Berechtigungen für die Procedures vergeben werden
-- GRANT EXECUTE ON PROCEDURE GetLadderHistory TO 'app_user'@'%';
-- GRANT EXECUTE ON PROCEDURE GetUpcomingInspections TO 'app_user'@'%';
-- GRANT EXECUTE ON PROCEDURE CreateInspection TO 'app_user'@'%';
-- GRANT EXECUTE ON PROCEDURE GetUserStatistics TO 'app_user'@'%';
