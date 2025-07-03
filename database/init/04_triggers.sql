-- =====================================================
-- Audit-Triggers für Leiterprüfung
-- =====================================================

USE leiterprüfung;

DELIMITER //

-- =====================================================
-- Trigger für users Tabelle
-- =====================================================

-- INSERT Trigger für users
DROP TRIGGER IF EXISTS users_audit_insert//
CREATE TRIGGER users_audit_insert
    AFTER INSERT ON users
    FOR EACH ROW
BEGIN
    INSERT INTO audit_log (
        table_name,
        record_id,
        action,
        new_values,
        user_id,
        user_ip,
        timestamp
    ) VALUES (
        'users',
        NEW.id,
        'INSERT',
        JSON_OBJECT(
            'id', NEW.id,
            'username', NEW.username,
            'email', NEW.email,
            'full_name', NEW.full_name,
            'role', NEW.role,
            'department', NEW.department,
            'phone', NEW.phone,
            'is_active', NEW.is_active,
            'last_login', NEW.last_login,
            'created_at', NEW.created_at,
            'updated_at', NEW.updated_at
        ),
        @audit_user_id,
        @audit_user_ip,
        CURRENT_TIMESTAMP
    );
END//

-- UPDATE Trigger für users
DROP TRIGGER IF EXISTS users_audit_update//
CREATE TRIGGER users_audit_update
    AFTER UPDATE ON users
    FOR EACH ROW
BEGIN
    INSERT INTO audit_log (
        table_name,
        record_id,
        action,
        old_values,
        new_values,
        user_id,
        user_ip,
        timestamp
    ) VALUES (
        'users',
        NEW.id,
        'UPDATE',
        JSON_OBJECT(
            'id', OLD.id,
            'username', OLD.username,
            'email', OLD.email,
            'full_name', OLD.full_name,
            'role', OLD.role,
            'department', OLD.department,
            'phone', OLD.phone,
            'is_active', OLD.is_active,
            'last_login', OLD.last_login,
            'created_at', OLD.created_at,
            'updated_at', OLD.updated_at
        ),
        JSON_OBJECT(
            'id', NEW.id,
            'username', NEW.username,
            'email', NEW.email,
            'full_name', NEW.full_name,
            'role', NEW.role,
            'department', NEW.department,
            'phone', NEW.phone,
            'is_active', NEW.is_active,
            'last_login', NEW.last_login,
            'created_at', NEW.created_at,
            'updated_at', NEW.updated_at
        ),
        @audit_user_id,
        @audit_user_ip,
        CURRENT_TIMESTAMP
    );
END//

-- DELETE Trigger für users
DROP TRIGGER IF EXISTS users_audit_delete//
CREATE TRIGGER users_audit_delete
    AFTER DELETE ON users
    FOR EACH ROW
BEGIN
    INSERT INTO audit_log (
        table_name,
        record_id,
        action,
        old_values,
        user_id,
        user_ip,
        timestamp
    ) VALUES (
        'users',
        OLD.id,
        'DELETE',
        JSON_OBJECT(
            'id', OLD.id,
            'username', OLD.username,
            'email', OLD.email,
            'full_name', OLD.full_name,
            'role', OLD.role,
            'department', OLD.department,
            'phone', OLD.phone,
            'is_active', OLD.is_active,
            'last_login', OLD.last_login,
            'created_at', OLD.created_at,
            'updated_at', OLD.updated_at
        ),
        @audit_user_id,
        @audit_user_ip,
        CURRENT_TIMESTAMP
    );
END//

-- =====================================================
-- Trigger für ladders Tabelle
-- =====================================================

-- INSERT Trigger für ladders
DROP TRIGGER IF EXISTS ladders_audit_insert//
CREATE TRIGGER ladders_audit_insert
    AFTER INSERT ON ladders
    FOR EACH ROW
BEGIN
    INSERT INTO audit_log (
        table_name,
        record_id,
        action,
        new_values,
        user_id,
        user_ip,
        timestamp
    ) VALUES (
        'ladders',
        NEW.id,
        'INSERT',
        JSON_OBJECT(
            'id', NEW.id,
            'ladder_number', NEW.ladder_number,
            'manufacturer', NEW.manufacturer,
            'model', NEW.model,
            'ladder_type', NEW.ladder_type,
            'material', NEW.material,
            'max_load_kg', NEW.max_load_kg,
            'height_cm', NEW.height_cm,
            'purchase_date', NEW.purchase_date,
            'location', NEW.location,
            'department', NEW.department,
            'responsible_person', NEW.responsible_person,
            'serial_number', NEW.serial_number,
            'notes', NEW.notes,
            'status', NEW.status,
            'next_inspection_date', NEW.next_inspection_date,
            'inspection_interval_months', NEW.inspection_interval_months,
            'created_at', NEW.created_at,
            'updated_at', NEW.updated_at
        ),
        @audit_user_id,
        @audit_user_ip,
        CURRENT_TIMESTAMP
    );
END//

-- UPDATE Trigger für ladders
DROP TRIGGER IF EXISTS ladders_audit_update//
CREATE TRIGGER ladders_audit_update
    AFTER UPDATE ON ladders
    FOR EACH ROW
BEGIN
    INSERT INTO audit_log (
        table_name,
        record_id,
        action,
        old_values,
        new_values,
        user_id,
        user_ip,
        timestamp
    ) VALUES (
        'ladders',
        NEW.id,
        'UPDATE',
        JSON_OBJECT(
            'id', OLD.id,
            'ladder_number', OLD.ladder_number,
            'manufacturer', OLD.manufacturer,
            'model', OLD.model,
            'ladder_type', OLD.ladder_type,
            'material', OLD.material,
            'max_load_kg', OLD.max_load_kg,
            'height_cm', OLD.height_cm,
            'purchase_date', OLD.purchase_date,
            'location', OLD.location,
            'department', OLD.department,
            'responsible_person', OLD.responsible_person,
            'serial_number', OLD.serial_number,
            'notes', OLD.notes,
            'status', OLD.status,
            'next_inspection_date', OLD.next_inspection_date,
            'inspection_interval_months', OLD.inspection_interval_months,
            'created_at', OLD.created_at,
            'updated_at', OLD.updated_at
        ),
        JSON_OBJECT(
            'id', NEW.id,
            'ladder_number', NEW.ladder_number,
            'manufacturer', NEW.manufacturer,
            'model', NEW.model,
            'ladder_type', NEW.ladder_type,
            'material', NEW.material,
            'max_load_kg', NEW.max_load_kg,
            'height_cm', NEW.height_cm,
            'purchase_date', NEW.purchase_date,
            'location', NEW.location,
            'department', NEW.department,
            'responsible_person', NEW.responsible_person,
            'serial_number', NEW.serial_number,
            'notes', NEW.notes,
            'status', NEW.status,
            'next_inspection_date', NEW.next_inspection_date,
            'inspection_interval_months', NEW.inspection_interval_months,
            'created_at', NEW.created_at,
            'updated_at', NEW.updated_at
        ),
        @audit_user_id,
        @audit_user_ip,
        CURRENT_TIMESTAMP
    );
END//

-- DELETE Trigger für ladders
DROP TRIGGER IF EXISTS ladders_audit_delete//
CREATE TRIGGER ladders_audit_delete
    AFTER DELETE ON ladders
    FOR EACH ROW
BEGIN
    INSERT INTO audit_log (
        table_name,
        record_id,
        action,
        old_values,
        user_id,
        user_ip,
        timestamp
    ) VALUES (
        'ladders',
        OLD.id,
        'DELETE',
        JSON_OBJECT(
            'id', OLD.id,
            'ladder_number', OLD.ladder_number,
            'manufacturer', OLD.manufacturer,
            'model', OLD.model,
            'ladder_type', OLD.ladder_type,
            'material', OLD.material,
            'max_load_kg', OLD.max_load_kg,
            'height_cm', OLD.height_cm,
            'purchase_date', OLD.purchase_date,
            'location', OLD.location,
            'department', OLD.department,
            'responsible_person', OLD.responsible_person,
            'serial_number', OLD.serial_number,
            'notes', OLD.notes,
            'status', OLD.status,
            'next_inspection_date', OLD.next_inspection_date,
            'inspection_interval_months', OLD.inspection_interval_months,
            'created_at', OLD.created_at,
            'updated_at', OLD.updated_at
        ),
        @audit_user_id,
        @audit_user_ip,
        CURRENT_TIMESTAMP
    );
END//

-- =====================================================
-- Trigger für inspections Tabelle
-- =====================================================

-- INSERT Trigger für inspections
DROP TRIGGER IF EXISTS inspections_audit_insert//
CREATE TRIGGER inspections_audit_insert
    AFTER INSERT ON inspections
    FOR EACH ROW
BEGIN
    INSERT INTO audit_log (
        table_name,
        record_id,
        action,
        new_values,
        user_id,
        user_ip,
        timestamp
    ) VALUES (
        'inspections',
        NEW.id,
        'INSERT',
        JSON_OBJECT(
            'id', NEW.id,
            'ladder_id', NEW.ladder_id,
            'inspector_id', NEW.inspector_id,
            'inspection_date', NEW.inspection_date,
            'inspection_type', NEW.inspection_type,
            'overall_result', NEW.overall_result,
            'next_inspection_date', NEW.next_inspection_date,
            'inspection_duration_minutes', NEW.inspection_duration_minutes,
            'weather_conditions', NEW.weather_conditions,
            'temperature_celsius', NEW.temperature_celsius,
            'general_notes', NEW.general_notes,
            'recommendations', NEW.recommendations,
            'defects_found', NEW.defects_found,
            'actions_required', NEW.actions_required,
            'inspector_signature', NEW.inspector_signature,
            'supervisor_approval_id', NEW.supervisor_approval_id,
            'approval_date', NEW.approval_date,
            'created_at', NEW.created_at,
            'updated_at', NEW.updated_at
        ),
        @audit_user_id,
        @audit_user_ip,
        CURRENT_TIMESTAMP
    );
END//

-- UPDATE Trigger für inspections
DROP TRIGGER IF EXISTS inspections_audit_update//
CREATE TRIGGER inspections_audit_update
    AFTER UPDATE ON inspections
    FOR EACH ROW
BEGIN
    INSERT INTO audit_log (
        table_name,
        record_id,
        action,
        old_values,
        new_values,
        user_id,
        user_ip,
        timestamp
    ) VALUES (
        'inspections',
        NEW.id,
        'UPDATE',
        JSON_OBJECT(
            'id', OLD.id,
            'ladder_id', OLD.ladder_id,
            'inspector_id', OLD.inspector_id,
            'inspection_date', OLD.inspection_date,
            'inspection_type', OLD.inspection_type,
            'overall_result', OLD.overall_result,
            'next_inspection_date', OLD.next_inspection_date,
            'inspection_duration_minutes', OLD.inspection_duration_minutes,
            'weather_conditions', OLD.weather_conditions,
            'temperature_celsius', OLD.temperature_celsius,
            'general_notes', OLD.general_notes,
            'recommendations', OLD.recommendations,
            'defects_found', OLD.defects_found,
            'actions_required', OLD.actions_required,
            'inspector_signature', OLD.inspector_signature,
            'supervisor_approval_id', OLD.supervisor_approval_id,
            'approval_date', OLD.approval_date,
            'created_at', OLD.created_at,
            'updated_at', OLD.updated_at
        ),
        JSON_OBJECT(
            'id', NEW.id,
            'ladder_id', NEW.ladder_id,
            'inspector_id', NEW.inspector_id,
            'inspection_date', NEW.inspection_date,
            'inspection_type', NEW.inspection_type,
            'overall_result', NEW.overall_result,
            'next_inspection_date', NEW.next_inspection_date,
            'inspection_duration_minutes', NEW.inspection_duration_minutes,
            'weather_conditions', NEW.weather_conditions,
            'temperature_celsius', NEW.temperature_celsius,
            'general_notes', NEW.general_notes,
            'recommendations', NEW.recommendations,
            'defects_found', NEW.defects_found,
            'actions_required', NEW.actions_required,
            'inspector_signature', NEW.inspector_signature,
            'supervisor_approval_id', NEW.supervisor_approval_id,
            'approval_date', NEW.approval_date,
            'created_at', NEW.created_at,
            'updated_at', NEW.updated_at
        ),
        @audit_user_id,
        @audit_user_ip,
        CURRENT_TIMESTAMP
    );
END//

-- DELETE Trigger für inspections
DROP TRIGGER IF EXISTS inspections_audit_delete//
CREATE TRIGGER inspections_audit_delete
    AFTER DELETE ON inspections
    FOR EACH ROW
BEGIN
    INSERT INTO audit_log (
        table_name,
        record_id,
        action,
        old_values,
        user_id,
        user_ip,
        timestamp
    ) VALUES (
        'inspections',
        OLD.id,
        'DELETE',
        JSON_OBJECT(
            'id', OLD.id,
            'ladder_id', OLD.ladder_id,
            'inspector_id', OLD.inspector_id,
            'inspection_date', OLD.inspection_date,
            'inspection_type', OLD.inspection_type,
            'overall_result', OLD.overall_result,
            'next_inspection_date', OLD.next_inspection_date,
            'inspection_duration_minutes', OLD.inspection_duration_minutes,
            'weather_conditions', OLD.weather_conditions,
            'temperature_celsius', OLD.temperature_celsius,
            'general_notes', OLD.general_notes,
            'recommendations', OLD.recommendations,
            'defects_found', OLD.defects_found,
            'actions_required', OLD.actions_required,
            'inspector_signature', OLD.inspector_signature,
            'supervisor_approval_id', OLD.supervisor_approval_id,
            'approval_date', OLD.approval_date,
            'created_at', OLD.created_at,
            'updated_at', OLD.updated_at
        ),
        @audit_user_id,
        @audit_user_ip,
        CURRENT_TIMESTAMP
    );
END//

-- =====================================================
-- Trigger für inspection_items Tabelle
-- =====================================================

-- INSERT Trigger für inspection_items
DROP TRIGGER IF EXISTS inspection_items_audit_insert//
CREATE TRIGGER inspection_items_audit_insert
    AFTER INSERT ON inspection_items
    FOR EACH ROW
BEGIN
    INSERT INTO audit_log (
        table_name,
        record_id,
        action,
        new_values,
        user_id,
        user_ip,
        timestamp
    ) VALUES (
        'inspection_items',
        NEW.id,
        'INSERT',
        JSON_OBJECT(
            'id', NEW.id,
            'inspection_id', NEW.inspection_id,
            'category', NEW.category,
            'item_name', NEW.item_name,
            'description', NEW.description,
            'result', NEW.result,
            'severity', NEW.severity,
            'notes', NEW.notes,
            'photo_path', NEW.photo_path,
            'repair_required', NEW.repair_required,
            'repair_deadline', NEW.repair_deadline,
            'sort_order', NEW.sort_order,
            'created_at', NEW.created_at,
            'updated_at', NEW.updated_at
        ),
        @audit_user_id,
        @audit_user_ip,
        CURRENT_TIMESTAMP
    );
END//

-- UPDATE Trigger für inspection_items
DROP TRIGGER IF EXISTS inspection_items_audit_update//
CREATE TRIGGER inspection_items_audit_update
    AFTER UPDATE ON inspection_items
    FOR EACH ROW
BEGIN
    INSERT INTO audit_log (
        table_name,
        record_id,
        action,
        old_values,
        new_values,
        user_id,
        user_ip,
        timestamp
    ) VALUES (
        'inspection_items',
        NEW.id,
        'UPDATE',
        JSON_OBJECT(
            'id', OLD.id,
            'inspection_id', OLD.inspection_id,
            'category', OLD.category,
            'item_name', OLD.item_name,
            'description', OLD.description,
            'result', OLD.result,
            'severity', OLD.severity,
            'notes', OLD.notes,
            'photo_path', OLD.photo_path,
            'repair_required', OLD.repair_required,
            'repair_deadline', OLD.repair_deadline,
            'sort_order', OLD.sort_order,
            'created_at', OLD.created_at,
            'updated_at', OLD.updated_at
        ),
        JSON_OBJECT(
            'id', NEW.id,
            'inspection_id', NEW.inspection_id,
            'category', NEW.category,
            'item_name', NEW.item_name,
            'description', NEW.description,
            'result', NEW.result,
            'severity', NEW.severity,
            'notes', NEW.notes,
            'photo_path', NEW.photo_path,
            'repair_required', NEW.repair_required,
            'repair_deadline', NEW.repair_deadline,
            'sort_order', NEW.sort_order,
            'created_at', NEW.created_at,
            'updated_at', NEW.updated_at
        ),
        @audit_user_id,
        @audit_user_ip,
        CURRENT_TIMESTAMP
    );
END//

-- DELETE Trigger für inspection_items
DROP TRIGGER IF EXISTS inspection_items_audit_delete//
CREATE TRIGGER inspection_items_audit_delete
    AFTER DELETE ON inspection_items
    FOR EACH ROW
BEGIN
    INSERT INTO audit_log (
        table_name,
        record_id,
        action,
        old_values,
        user_id,
        user_ip,
        timestamp
    ) VALUES (
        'inspection_items',
        OLD.id,
        'DELETE',
        JSON_OBJECT(
            'id', OLD.id,
            'inspection_id', OLD.inspection_id,
            'category', OLD.category,
            'item_name', OLD.item_name,
            'description', OLD.description,
            'result', OLD.result,
            'severity', OLD.severity,
            'notes', OLD.notes,
            'photo_path', OLD.photo_path,
            'repair_required', OLD.repair_required,
            'repair_deadline', OLD.repair_deadline,
            'sort_order', OLD.sort_order,
            'created_at', OLD.created_at,
            'updated_at', OLD.updated_at
        ),
        @audit_user_id,
        @audit_user_ip,
        CURRENT_TIMESTAMP
    );
END//

-- =====================================================
-- Trigger für system_config Tabelle
-- =====================================================

-- UPDATE Trigger für system_config (nur Updates, da Inserts bereits im Schema sind)
DROP TRIGGER IF EXISTS system_config_audit_update//
CREATE TRIGGER system_config_audit_update
    AFTER UPDATE ON system_config
    FOR EACH ROW
BEGIN
    INSERT INTO audit_log (
        table_name,
        record_id,
        action,
        old_values,
        new_values,
        user_id,
        user_ip,
        timestamp
    ) VALUES (
        'system_config',
        NEW.id,
        'UPDATE',
        JSON_OBJECT(
            'id', OLD.id,
            'config_key', OLD.config_key,
            'config_value', OLD.config_value,
            'config_type', OLD.config_type,
            'description', OLD.description,
            'is_editable', OLD.is_editable,
            'category', OLD.category,
            'created_at', OLD.created_at,
            'updated_at', OLD.updated_at
        ),
        JSON_OBJECT(
            'id', NEW.id,
            'config_key', NEW.config_key,
            'config_value', NEW.config_value,
            'config_type', NEW.config_type,
            'description', NEW.description,
            'is_editable', NEW.is_editable,
            'category', NEW.category,
            'created_at', NEW.created_at,
            'updated_at', NEW.updated_at
        ),
        @audit_user_id,
        @audit_user_ip,
        CURRENT_TIMESTAMP
    );
END//

DELIMITER ;

-- =====================================================
-- Hilfsfunktionen für Audit-Kontext
-- =====================================================

-- Funktion zum Setzen des Audit-Kontexts
-- Diese sollte von der Anwendung vor jeder Operation aufgerufen werden:
-- SET @audit_user_id = 1;
-- SET @audit_user_ip = '192.168.1.100';
-- SET @audit_user_agent = 'Mozilla/5.0...';

-- =====================================================
-- Cleanup-Procedure für alte Audit-Logs
-- =====================================================

DELIMITER //

DROP PROCEDURE IF EXISTS CleanupAuditLogs//
CREATE PROCEDURE CleanupAuditLogs(
    IN p_retention_days INT DEFAULT 2555
)
BEGIN
    DECLARE v_cutoff_date DATE;
    DECLARE v_deleted_count INT DEFAULT 0;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;
    
    SET v_cutoff_date = DATE_SUB(CURDATE(), INTERVAL p_retention_days DAY);
    
    DELETE FROM audit_log 
    WHERE timestamp < v_cutoff_date;
    
    SET v_deleted_count = ROW_COUNT();
    
    SELECT CONCAT('Deleted ', v_deleted_count, ' audit log entries older than ', v_cutoff_date) AS result;
    
    COMMIT;
END//

DELIMITER ;
