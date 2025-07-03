-- =====================================================
-- Performance-Indizes für Leiterprüfung
-- =====================================================

USE leiterprüfung;

-- =====================================================
-- Zusätzliche Performance-Indizes
-- =====================================================

-- Composite Index für häufige Abfragen auf ladders
CREATE INDEX idx_ladders_status_next_inspection ON ladders(status, next_inspection_date);
CREATE INDEX idx_ladders_location_status ON ladders(location, status);
CREATE INDEX idx_ladders_department_type ON ladders(department, ladder_type);
CREATE INDEX idx_ladders_type_material ON ladders(ladder_type, material);

-- Composite Index für inspections
CREATE INDEX idx_inspections_date_result ON inspections(inspection_date, overall_result);
CREATE INDEX idx_inspections_ladder_type_date ON inspections(ladder_id, inspection_type, inspection_date);
CREATE INDEX idx_inspections_inspector_date ON inspections(inspector_id, inspection_date DESC);
CREATE INDEX idx_inspections_approval ON inspections(supervisor_approval_id, approval_date);

-- Index für inspection_items Performance
CREATE INDEX idx_inspection_items_result_severity ON inspection_items(result, severity);
CREATE INDEX idx_inspection_items_repair_deadline ON inspection_items(repair_required, repair_deadline);
CREATE INDEX idx_inspection_items_category_result ON inspection_items(category, result);

-- Audit Log Performance
CREATE INDEX idx_audit_log_table_timestamp ON audit_log(table_name, timestamp DESC);
CREATE INDEX idx_audit_log_user_timestamp ON audit_log(user_id, timestamp DESC);
CREATE INDEX idx_audit_log_record_action ON audit_log(table_name, record_id, action);

-- System Config Performance
CREATE INDEX idx_system_config_category_key ON system_config(category, config_key);

-- =====================================================
-- Fulltext-Indizes für Suchfunktionen
-- =====================================================

-- Volltext-Suche in Leitern
ALTER TABLE ladders ADD FULLTEXT(manufacturer, model, location, notes);

-- Volltext-Suche in Prüfungen
ALTER TABLE inspections ADD FULLTEXT(general_notes, recommendations, defects_found, actions_required);

-- Volltext-Suche in Prüfpunkten
ALTER TABLE inspection_items ADD FULLTEXT(item_name, description, notes);

-- =====================================================
-- Spezielle Performance-Indizes für Reports
-- =====================================================

-- Index für Statistiken nach Abteilung und Zeitraum
CREATE INDEX idx_inspections_dept_period ON inspections(inspection_date, overall_result) 
    WHERE inspection_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR);

-- Index für überfällige Prüfungen
CREATE INDEX idx_ladders_overdue ON ladders(next_inspection_date, status) 
    WHERE status = 'active' AND next_inspection_date < CURDATE();

-- Index für kritische Mängel
CREATE INDEX idx_critical_defects ON inspection_items(severity, repair_required, repair_deadline)
    WHERE severity IN ('high', 'critical');

-- =====================================================
-- Partitionierung für große Tabellen (optional)
-- =====================================================

-- Audit Log Partitionierung nach Jahr (für bessere Performance bei großen Datenmengen)
-- ALTER TABLE audit_log PARTITION BY RANGE (YEAR(timestamp)) (
--     PARTITION p2024 VALUES LESS THAN (2025),
--     PARTITION p2025 VALUES LESS THAN (2026),
--     PARTITION p2026 VALUES LESS THAN (2027),
--     PARTITION p_future VALUES LESS THAN MAXVALUE
-- );

-- =====================================================
-- Index-Statistiken und Optimierung
-- =====================================================

-- Analysiere Tabellen für bessere Query-Performance
ANALYZE TABLE users, ladders, inspections, inspection_items, audit_log, system_config;

-- Optimiere Tabellen
OPTIMIZE TABLE users, ladders, inspections, inspection_items, audit_log, system_config;
