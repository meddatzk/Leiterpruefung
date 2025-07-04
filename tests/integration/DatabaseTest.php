<?php

require_once __DIR__ . '/../setup/TestDatabase.php';
require_once __DIR__ . '/../../web/src/includes/Ladder.php';
require_once __DIR__ . '/../../web/src/includes/Inspection.php';
require_once __DIR__ . '/../../web/src/includes/InspectionItem.php';
require_once __DIR__ . '/../../web/src/includes/User.php';
require_once __DIR__ . '/../../web/src/config/database.php';

use PHPUnit\Framework\TestCase;

/**
 * Integration-Tests für Datenbank-Operationen
 * 
 * Testet:
 * - Datenbankverbindung und Transaktionen
 * - CRUD-Operationen für alle Models
 * - Foreign Key Constraints
 * - Datenintegrität bei komplexen Operationen
 * - Performance bei größeren Datenmengen
 */
class DatabaseTest extends TestCase
{
    private TestDatabase $testDb;
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->testDb = TestDatabase::getInstance();
        $this->testDb->resetDatabase();
        $this->pdo = $this->testDb->getPdo();
    }

    protected function tearDown(): void
    {
        $this->testDb->cleanupTestData();
    }

    // ===== DATENBANKVERBINDUNG TESTS =====

    public function testDatabaseConnection()
    {
        $this->assertInstanceOf(PDO::class, $this->pdo);
        $this->assertEquals('sqlite', $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
    }

    public function testDatabaseTables()
    {
        $tables = ['users', 'ladders', 'inspections', 'inspection_items', 'audit_log', 'system_config'];
        
        foreach ($tables as $table) {
            $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='{$table}'");
            $result = $stmt->fetch();
            $this->assertNotFalse($result, "Tabelle {$table} sollte existieren");
        }
    }

    // ===== TRANSAKTIONS-TESTS =====

    public function testTransactionCommit()
    {
        $this->pdo->beginTransaction();
        
        $stmt = $this->pdo->prepare("INSERT INTO users (username, email) VALUES (?, ?)");
        $stmt->execute(['test.transaction', 'test@transaction.com']);
        
        $this->pdo->commit();
        
        // Prüfen ob Daten gespeichert wurden
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute(['test.transaction']);
        $count = $stmt->fetchColumn();
        
        $this->assertEquals(1, $count);
    }

    public function testTransactionRollback()
    {
        $this->pdo->beginTransaction();
        
        $stmt = $this->pdo->prepare("INSERT INTO users (username, email) VALUES (?, ?)");
        $stmt->execute(['test.rollback', 'test@rollback.com']);
        
        $this->pdo->rollback();
        
        // Prüfen ob Daten NICHT gespeichert wurden
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute(['test.rollback']);
        $count = $stmt->fetchColumn();
        
        $this->assertEquals(0, $count);
    }

    // ===== USER CRUD TESTS =====

    public function testUserCrudOperations()
    {
        // CREATE
        $stmt = $this->pdo->prepare("
            INSERT INTO users (username, email, first_name, last_name, display_name, groups, ldap_dn, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            'test.crud',
            'test@crud.com',
            'Test',
            'Crud',
            'Test Crud',
            json_encode(['users']),
            'cn=Test Crud,ou=users,dc=test,dc=com',
            1
        ]);
        $userId = $this->pdo->lastInsertId();
        $this->assertGreaterThan(0, $userId);

        // READ
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertNotFalse($user);
        $this->assertEquals('test.crud', $user['username']);
        $this->assertEquals('test@crud.com', $user['email']);
        $this->assertEquals('Test', $user['first_name']);
        $this->assertEquals('Crud', $user['last_name']);

        // UPDATE
        $stmt = $this->pdo->prepare("UPDATE users SET email = ?, first_name = ? WHERE id = ?");
        $stmt->execute(['updated@crud.com', 'Updated', $userId]);
        
        $stmt = $this->pdo->prepare("SELECT email, first_name FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $updated = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertEquals('updated@crud.com', $updated['email']);
        $this->assertEquals('Updated', $updated['first_name']);

        // DELETE
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $count = $stmt->fetchColumn();
        
        $this->assertEquals(0, $count);
    }

    // ===== LADDER CRUD TESTS =====

    public function testLadderCrudOperations()
    {
        // CREATE
        $stmt = $this->pdo->prepare("
            INSERT INTO ladders (ladder_number, manufacturer, model, ladder_type, material, max_load_kg, height_cm, location, status, next_inspection_date, inspection_interval_months)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            'TEST-CRUD-001',
            'Test Manufacturer',
            'Test Model',
            'Stehleiter',
            'Aluminium',
            150,
            200,
            'Test Location',
            'active',
            '2024-12-31',
            12
        ]);
        $ladderId = $this->pdo->lastInsertId();
        $this->assertGreaterThan(0, $ladderId);

        // READ
        $stmt = $this->pdo->prepare("SELECT * FROM ladders WHERE id = ?");
        $stmt->execute([$ladderId]);
        $ladder = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertNotFalse($ladder);
        $this->assertEquals('TEST-CRUD-001', $ladder['ladder_number']);
        $this->assertEquals('Test Manufacturer', $ladder['manufacturer']);
        $this->assertEquals('Stehleiter', $ladder['ladder_type']);

        // UPDATE
        $stmt = $this->pdo->prepare("UPDATE ladders SET manufacturer = ?, status = ? WHERE id = ?");
        $stmt->execute(['Updated Manufacturer', 'inactive', $ladderId]);
        
        $stmt = $this->pdo->prepare("SELECT manufacturer, status FROM ladders WHERE id = ?");
        $stmt->execute([$ladderId]);
        $updated = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertEquals('Updated Manufacturer', $updated['manufacturer']);
        $this->assertEquals('inactive', $updated['status']);

        // DELETE
        $stmt = $this->pdo->prepare("DELETE FROM ladders WHERE id = ?");
        $stmt->execute([$ladderId]);
        
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM ladders WHERE id = ?");
        $stmt->execute([$ladderId]);
        $count = $stmt->fetchColumn();
        
        $this->assertEquals(0, $count);
    }

    // ===== FOREIGN KEY CONSTRAINT TESTS =====

    public function testForeignKeyConstraints()
    {
        // Benutzer und Leiter erstellen
        $userId = $this->testDb->createTestUser([
            'username' => 'test.fk',
            'email' => 'test@fk.com',
            'is_active' => true
        ]);
        
        $ladderId = $this->testDb->createTestLadder([
            'ladder_number' => 'FK-TEST-001',
            'manufacturer' => 'FK Test',
            'ladder_type' => 'Stehleiter',
            'location' => 'FK Location'
        ]);

        // Prüfung erstellen
        $stmt = $this->pdo->prepare("
            INSERT INTO inspections (ladder_id, inspector_id, inspection_date, inspection_type, overall_result, next_inspection_date)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $ladderId,
            $userId,
            '2024-01-15',
            'routine',
            'passed',
            '2025-01-15'
        ]);
        $inspectionId = $this->pdo->lastInsertId();

        // Prüfpunkt erstellen
        $stmt = $this->pdo->prepare("
            INSERT INTO inspection_items (inspection_id, category, item_name, result)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $inspectionId,
            'structure',
            'Test Item',
            'ok'
        ]);
        $itemId = $this->pdo->lastInsertId();

        // Prüfen ob alle Datensätze erstellt wurden
        $this->assertGreaterThan(0, $inspectionId);
        $this->assertGreaterThan(0, $itemId);

        // Prüfen ob Foreign Keys funktionieren
        $stmt = $this->pdo->prepare("
            SELECT i.id, i.ladder_id, i.inspector_id, ii.inspection_id
            FROM inspections i
            JOIN inspection_items ii ON i.id = ii.inspection_id
            WHERE i.id = ?
        ");
        $stmt->execute([$inspectionId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertNotFalse($result);
        $this->assertEquals($ladderId, $result['ladder_id']);
        $this->assertEquals($userId, $result['inspector_id']);
        $this->assertEquals($inspectionId, $result['inspection_id']);
    }

    public function testCascadeDelete()
    {
        // Leiter mit Prüfung und Prüfpunkten erstellen
        $ladderId = $this->testDb->createTestLadder([
            'ladder_number' => 'CASCADE-001',
            'manufacturer' => 'Cascade Test',
            'ladder_type' => 'Stehleiter',
            'location' => 'Cascade Location'
        ]);

        $inspectionId = $this->testDb->createTestInspection([
            'ladder_id' => $ladderId,
            'inspector_id' => 1, // Aus Seed-Daten
            'inspection_date' => '2024-01-15',
            'overall_result' => 'passed',
            'next_inspection_date' => '2025-01-15'
        ]);

        // Prüfpunkt hinzufügen
        $stmt = $this->pdo->prepare("
            INSERT INTO inspection_items (inspection_id, category, item_name, result)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$inspectionId, 'structure', 'Cascade Item', 'ok']);

        // Prüfen ob Daten vorhanden sind
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM inspections WHERE ladder_id = ?");
        $stmt->execute([$ladderId]);
        $this->assertEquals(1, $stmt->fetchColumn());

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM inspection_items WHERE inspection_id = ?");
        $stmt->execute([$inspectionId]);
        $this->assertEquals(1, $stmt->fetchColumn());

        // Leiter löschen (sollte Cascade-Delete auslösen)
        $stmt = $this->pdo->prepare("DELETE FROM ladders WHERE id = ?");
        $stmt->execute([$ladderId]);

        // Prüfen ob abhängige Datensätze gelöscht wurden
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM inspections WHERE ladder_id = ?");
        $stmt->execute([$ladderId]);
        $this->assertEquals(0, $stmt->fetchColumn());

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM inspection_items WHERE inspection_id = ?");
        $stmt->execute([$inspectionId]);
        $this->assertEquals(0, $stmt->fetchColumn());
    }

    // ===== DATENINTEGRITÄT TESTS =====

    public function testUniqueConstraints()
    {
        // Ersten Benutzer erstellen
        $stmt = $this->pdo->prepare("INSERT INTO users (username, email) VALUES (?, ?)");
        $stmt->execute(['unique.test', 'unique@test.com']);

        // Versuch, zweiten Benutzer mit gleichem Username zu erstellen
        $this->expectException(PDOException::class);
        $stmt->execute(['unique.test', 'different@test.com']);
    }

    public function testLadderNumberUnique()
    {
        // Erste Leiter erstellen
        $stmt = $this->pdo->prepare("
            INSERT INTO ladders (ladder_number, manufacturer, ladder_type, location, next_inspection_date)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute(['UNIQUE-001', 'Test', 'Stehleiter', 'Location', '2024-12-31']);

        // Versuch, zweite Leiter mit gleicher Nummer zu erstellen
        $this->expectException(PDOException::class);
        $stmt->execute(['UNIQUE-001', 'Other', 'Anlegeleiter', 'Other Location', '2024-12-31']);
    }

    // ===== KOMPLEXE OPERATIONEN TESTS =====

    public function testComplexInspectionWorkflow()
    {
        $this->pdo->beginTransaction();

        try {
            // 1. Leiter erstellen
            $ladderId = $this->testDb->createTestLadder([
                'ladder_number' => 'WORKFLOW-001',
                'manufacturer' => 'Workflow Test',
                'ladder_type' => 'Mehrzweckleiter',
                'location' => 'Workflow Location'
            ]);

            // 2. Prüfung erstellen
            $inspectionId = $this->testDb->createTestInspection([
                'ladder_id' => $ladderId,
                'inspector_id' => 1,
                'inspection_date' => '2024-01-15',
                'overall_result' => 'conditional',
                'next_inspection_date' => '2025-01-15'
            ]);

            // 3. Mehrere Prüfpunkte erstellen
            $items = [
                ['structure', 'Holme', 'ok'],
                ['structure', 'Sprossen', 'defect'],
                ['safety', 'Sicherheitshaken', 'ok'],
                ['function', 'Gelenke', 'wear'],
                ['marking', 'Typenschild', 'ok']
            ];

            foreach ($items as $item) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO inspection_items (inspection_id, category, item_name, result, severity)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $severity = $item[2] === 'defect' ? 'medium' : null;
                $stmt->execute([$inspectionId, $item[0], $item[1], $item[2], $severity]);
            }

            // 4. Audit-Log-Eintrag erstellen
            $stmt = $this->pdo->prepare("
                INSERT INTO audit_log (table_name, record_id, action, new_values, user_id)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                'inspections',
                $inspectionId,
                'INSERT',
                json_encode(['workflow' => 'test']),
                1
            ]);

            $this->pdo->commit();

            // 5. Ergebnisse validieren
            $stmt = $this->pdo->prepare("
                SELECT 
                    l.ladder_number,
                    i.overall_result,
                    COUNT(ii.id) as item_count,
                    SUM(CASE WHEN ii.result = 'defect' THEN 1 ELSE 0 END) as defect_count
                FROM ladders l
                JOIN inspections i ON l.id = i.ladder_id
                JOIN inspection_items ii ON i.id = ii.inspection_id
                WHERE l.id = ?
                GROUP BY l.id, i.id
            ");
            $stmt->execute([$ladderId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $this->assertEquals('WORKFLOW-001', $result['ladder_number']);
            $this->assertEquals('conditional', $result['overall_result']);
            $this->assertEquals(5, $result['item_count']);
            $this->assertEquals(1, $result['defect_count']);

        } catch (Exception $e) {
            $this->pdo->rollback();
            throw $e;
        }
    }

    // ===== PERFORMANCE TESTS =====

    public function testBulkInsertPerformance()
    {
        $startTime = microtime(true);

        $this->pdo->beginTransaction();

        // 100 Leitern erstellen
        $stmt = $this->pdo->prepare("
            INSERT INTO ladders (ladder_number, manufacturer, ladder_type, location, next_inspection_date)
            VALUES (?, ?, ?, ?, ?)
        ");

        for ($i = 1; $i <= 100; $i++) {
            $stmt->execute([
                sprintf('BULK-%03d', $i),
                'Bulk Manufacturer',
                'Stehleiter',
                'Bulk Location',
                '2024-12-31'
            ]);
        }

        $this->pdo->commit();

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Performance-Assertion (sollte unter 1 Sekunde dauern)
        $this->assertLessThan(1.0, $duration, "Bulk-Insert sollte unter 1 Sekunde dauern");

        // Prüfen ob alle Datensätze erstellt wurden
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM ladders WHERE ladder_number LIKE 'BULK-%'");
        $count = $stmt->fetchColumn();
        $this->assertEquals(100, $count);
    }

    public function testComplexQueryPerformance()
    {
        // Testdaten erstellen
        $this->createPerformanceTestData();

        $startTime = microtime(true);

        // Komplexe Abfrage ausführen
        $stmt = $this->pdo->query("
            SELECT 
                l.ladder_number,
                l.manufacturer,
                l.status,
                COUNT(i.id) as inspection_count,
                MAX(i.inspection_date) as last_inspection,
                COUNT(ii.id) as total_items,
                SUM(CASE WHEN ii.result = 'defect' THEN 1 ELSE 0 END) as defect_count
            FROM ladders l
            LEFT JOIN inspections i ON l.id = i.ladder_id
            LEFT JOIN inspection_items ii ON i.id = ii.inspection_id
            GROUP BY l.id
            ORDER BY defect_count DESC, last_inspection DESC
        ");

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Performance-Assertion
        $this->assertLessThan(0.5, $duration, "Komplexe Abfrage sollte unter 0.5 Sekunden dauern");
        $this->assertGreaterThan(0, count($results));
    }

    // ===== JSON HANDLING TESTS =====

    public function testJsonDataHandling()
    {
        // Benutzer mit JSON-Gruppen erstellen
        $groups = ['users', 'inspectors', 'admins'];
        $stmt = $this->pdo->prepare("
            INSERT INTO users (username, email, groups)
            VALUES (?, ?, ?)
        ");
        $stmt->execute(['json.test', 'json@test.com', json_encode($groups)]);
        $userId = $this->pdo->lastInsertId();

        // JSON-Daten lesen und validieren
        $stmt = $this->pdo->prepare("SELECT groups FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $jsonGroups = $stmt->fetchColumn();

        $decodedGroups = json_decode($jsonGroups, true);
        $this->assertEquals($groups, $decodedGroups);

        // Audit-Log mit JSON-Daten
        $auditData = [
            'old_username' => 'old.name',
            'new_username' => 'json.test',
            'changed_fields' => ['username', 'email']
        ];

        $stmt = $this->pdo->prepare("
            INSERT INTO audit_log (table_name, record_id, action, new_values, user_id)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            'users',
            $userId,
            'UPDATE',
            json_encode($auditData),
            $userId
        ]);

        // Audit-Daten validieren
        $stmt = $this->pdo->prepare("SELECT new_values FROM audit_log WHERE record_id = ? AND table_name = 'users'");
        $stmt->execute([$userId]);
        $auditJson = $stmt->fetchColumn();

        $decodedAudit = json_decode($auditJson, true);
        $this->assertEquals($auditData, $decodedAudit);
    }

    // ===== HELPER METHODS =====

    private function createPerformanceTestData()
    {
        $this->pdo->beginTransaction();

        // 10 Leitern erstellen
        for ($i = 1; $i <= 10; $i++) {
            $ladderId = $this->testDb->createTestLadder([
                'ladder_number' => sprintf('PERF-%03d', $i),
                'manufacturer' => 'Performance Test',
                'ladder_type' => 'Stehleiter',
                'location' => 'Performance Location'
            ]);

            // 2-3 Prüfungen pro Leiter
            for ($j = 1; $j <= rand(2, 3); $j++) {
                $inspectionId = $this->testDb->createTestInspection([
                    'ladder_id' => $ladderId,
                    'inspector_id' => 1,
                    'inspection_date' => date('Y-m-d', strtotime("-{$j} months")),
                    'overall_result' => rand(0, 1) ? 'passed' : 'conditional',
                    'next_inspection_date' => date('Y-m-d', strtotime("+12 months"))
                ]);

                // 3-5 Prüfpunkte pro Prüfung
                for ($k = 1; $k <= rand(3, 5); $k++) {
                    $results = ['ok', 'ok', 'ok', 'defect', 'wear']; // Mehr OK als Defekte
                    $result = $results[array_rand($results)];
                    
                    $stmt = $this->pdo->prepare("
                        INSERT INTO inspection_items (inspection_id, category, item_name, result, severity)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $inspectionId,
                        'structure',
                        "Item {$k}",
                        $result,
                        $result === 'defect' ? 'medium' : null
                    ]);
                }
            }
        }

        $this->pdo->commit();
    }
}
