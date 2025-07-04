<?php

require_once __DIR__ . '/../setup/TestDatabase.php';
require_once __DIR__ . '/../../web/src/includes/Ladder.php';
require_once __DIR__ . '/../../web/src/includes/Inspection.php';
require_once __DIR__ . '/../../web/src/includes/InspectionItem.php';
require_once __DIR__ . '/../../web/src/includes/User.php';
require_once __DIR__ . '/../../web/src/includes/LadderRepository.php';
require_once __DIR__ . '/../../web/src/includes/InspectionRepository.php';
require_once __DIR__ . '/../../web/src/config/database.php';

use PHPUnit\Framework\TestCase;

/**
 * Funktionale Tests für CRUD-Operationen
 * 
 * Testet:
 * - Vollständige Leiter-Verwaltung
 * - Prüfungs-Workflows
 * - Benutzer-Management
 * - Datenvalidierung im Web-Interface
 * - Komplexe Geschäftsprozesse
 */
class CrudTest extends TestCase
{
    private TestDatabase $testDb;
    private PDO $pdo;
    private User $testUser;

    protected function setUp(): void
    {
        $this->testDb = TestDatabase::getInstance();
        $this->testDb->resetDatabase();
        $this->pdo = $this->testDb->getPdo();

        // Test-Benutzer für alle Tests erstellen
        $this->testUser = User::createOrUpdateFromLdap([
            'dn' => 'cn=Test User,ou=users,dc=test,dc=com',
            'username' => 'test.crud',
            'email' => 'test.crud@example.com',
            'first_name' => 'Test',
            'last_name' => 'Crud',
            'display_name' => 'Test Crud',
            'groups' => ['users', 'inspectors', 'admins']
        ]);
    }

    protected function tearDown(): void
    {
        $this->testDb->cleanupTestData();
    }

    // ===== LADDER CRUD TESTS =====

    public function testLadderCreateReadUpdateDelete()
    {
        // CREATE - Neue Leiter erstellen
        $ladderData = [
            'ladder_number' => 'CRUD-TEST-001',
            'manufacturer' => 'CRUD Test Manufacturer',
            'model' => 'CRUD Test Model',
            'ladder_type' => 'Stehleiter',
            'material' => 'Aluminium',
            'max_load_kg' => 150,
            'height_cm' => 200,
            'purchase_date' => '2023-01-15',
            'location' => 'CRUD Test Location',
            'department' => 'CRUD Test Department',
            'responsible_person' => 'CRUD Test Person',
            'serial_number' => 'CRUD-SN-001',
            'notes' => 'CRUD Test Notes',
            'status' => 'active',
            'next_inspection_date' => '2024-01-15',
            'inspection_interval_months' => 12
        ];

        $ladder = new Ladder($ladderData);
        $errors = $ladder->validate();
        $this->assertEmpty($errors, 'Leiter-Daten sollten gültig sein');

        // Leiter in DB speichern
        $ladderId = $this->saveLadder($ladder);
        $this->assertGreaterThan(0, $ladderId);

        // READ - Leiter aus DB laden
        $loadedLadder = $this->loadLadder($ladderId);
        $this->assertInstanceOf(Ladder::class, $loadedLadder);
        $this->assertEquals('CRUD-TEST-001', $loadedLadder->getLadderNumber());
        $this->assertEquals('CRUD Test Manufacturer', $loadedLadder->getManufacturer());
        $this->assertEquals('Stehleiter', $loadedLadder->getLadderType());

        // UPDATE - Leiter-Daten ändern
        $loadedLadder->setManufacturer('Updated Manufacturer');
        $loadedLadder->setStatus('inactive');
        $loadedLadder->setNotes('Updated notes');

        $this->updateLadder($loadedLadder);

        // Änderungen prüfen
        $updatedLadder = $this->loadLadder($ladderId);
        $this->assertEquals('Updated Manufacturer', $updatedLadder->getManufacturer());
        $this->assertEquals('inactive', $updatedLadder->getStatus());
        $this->assertEquals('Updated notes', $updatedLadder->getNotes());

        // DELETE - Leiter löschen
        $this->deleteLadder($ladderId);

        // Prüfen ob gelöscht
        $deletedLadder = $this->loadLadder($ladderId);
        $this->assertNull($deletedLadder);
    }

    public function testLadderValidationInCrud()
    {
        // Ungültige Leiter-Daten testen
        $invalidLadderData = [
            'ladder_number' => '', // Leer - sollte Fehler verursachen
            'manufacturer' => '',  // Leer - sollte Fehler verursachen
            'ladder_type' => 'InvalidType', // Ungültiger Typ
            'material' => 'InvalidMaterial', // Ungültiges Material
            'max_load_kg' => -10, // Negativ - sollte Fehler verursachen
            'height_cm' => 0,     // Null - sollte Fehler verursachen
            'location' => '',     // Leer - sollte Fehler verursachen
            'next_inspection_date' => '2024-13-01', // Ungültiges Datum
            'inspection_interval_months' => 0 // Null - sollte Fehler verursachen
        ];

        $ladder = new Ladder($invalidLadderData);
        $errors = $ladder->validate();

        $this->assertNotEmpty($errors);
        $this->assertContains('Leiternummer ist erforderlich', $errors);
        $this->assertContains('Hersteller ist erforderlich', $errors);
        $this->assertContains('Standort ist erforderlich', $errors);
        $this->assertContains('Ungültiger Leitertyp', $errors);
        $this->assertContains('Ungültiges Material', $errors);
        $this->assertContains('Maximale Belastung muss größer als 0 sein', $errors);
        $this->assertContains('Höhe muss größer als 0 sein', $errors);
        $this->assertContains('Prüfintervall muss größer als 0 sein', $errors);
    }

    public function testLadderSearch()
    {
        // Mehrere Test-Leitern erstellen
        $ladders = [
            ['ladder_number' => 'SEARCH-001', 'manufacturer' => 'Hailo', 'ladder_type' => 'Stehleiter', 'status' => 'active'],
            ['ladder_number' => 'SEARCH-002', 'manufacturer' => 'Zarges', 'ladder_type' => 'Anlegeleiter', 'status' => 'active'],
            ['ladder_number' => 'SEARCH-003', 'manufacturer' => 'Hailo', 'ladder_type' => 'Mehrzweckleiter', 'status' => 'inactive'],
        ];

        foreach ($ladders as $ladderData) {
            $this->createTestLadder($ladderData);
        }

        // Suche nach Hersteller
        $hailoLadders = $this->searchLadders(['manufacturer' => 'Hailo']);
        $this->assertCount(2, $hailoLadders);

        // Suche nach Status
        $activeLadders = $this->searchLadders(['status' => 'active']);
        $this->assertCount(2, $activeLadders);

        // Suche nach Leitertyp
        $stehleiterLadders = $this->searchLadders(['ladder_type' => 'Stehleiter']);
        $this->assertCount(1, $stehleiterLadders);

        // Kombinierte Suche
        $hailoActiveLadders = $this->searchLadders(['manufacturer' => 'Hailo', 'status' => 'active']);
        $this->assertCount(1, $hailoActiveLadders);
    }

    // ===== INSPECTION CRUD TESTS =====

    public function testInspectionCreateReadUpdateDelete()
    {
        // Leiter für Prüfung erstellen
        $ladderId = $this->createTestLadder([
            'ladder_number' => 'INSP-TEST-001',
            'manufacturer' => 'Inspection Test',
            'ladder_type' => 'Stehleiter',
            'location' => 'Inspection Location'
        ]);

        // CREATE - Neue Prüfung erstellen
        $inspectionData = [
            'ladder_id' => $ladderId,
            'inspector_id' => $this->testUser->getId(),
            'inspection_date' => '2024-01-15',
            'inspection_type' => 'routine',
            'overall_result' => 'passed',
            'next_inspection_date' => '2025-01-15',
            'inspection_duration_minutes' => 30,
            'weather_conditions' => 'trocken',
            'temperature_celsius' => 20,
            'general_notes' => 'Prüfung erfolgreich',
            'inspector_signature' => 'Test Inspector'
        ];

        $inspection = new Inspection($inspectionData);
        $errors = $inspection->validate();
        $this->assertEmpty($errors, 'Prüfungs-Daten sollten gültig sein');

        // Prüfung in DB speichern
        $inspectionId = $this->saveInspection($inspection);
        $this->assertGreaterThan(0, $inspectionId);

        // READ - Prüfung aus DB laden
        $loadedInspection = $this->loadInspection($inspectionId);
        $this->assertInstanceOf(Inspection::class, $loadedInspection);
        $this->assertEquals($ladderId, $loadedInspection->getLadderId());
        $this->assertEquals('routine', $loadedInspection->getInspectionType());
        $this->assertEquals('passed', $loadedInspection->getOverallResult());

        // UPDATE - Prüfung sollte unveränderlich sein nach dem Speichern
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Prüfung ist unveränderlich nach dem Speichern');
        
        $loadedInspection->setOverallResult('failed');
    }

    public function testInspectionWithItems()
    {
        // Leiter erstellen
        $ladderId = $this->createTestLadder([
            'ladder_number' => 'ITEMS-TEST-001',
            'manufacturer' => 'Items Test',
            'ladder_type' => 'Stehleiter',
            'location' => 'Items Location'
        ]);

        // Prüfung erstellen
        $inspectionId = $this->testDb->createTestInspection([
            'ladder_id' => $ladderId,
            'inspector_id' => $this->testUser->getId(),
            'inspection_date' => '2024-01-15',
            'overall_result' => 'conditional',
            'next_inspection_date' => '2025-01-15'
        ]);

        // Prüfpunkte erstellen
        $items = [
            ['category' => 'structure', 'item_name' => 'Holme', 'result' => 'ok'],
            ['category' => 'structure', 'item_name' => 'Sprossen', 'result' => 'defect', 'severity' => 'medium'],
            ['category' => 'safety', 'item_name' => 'Sicherheitshaken', 'result' => 'ok'],
            ['category' => 'function', 'item_name' => 'Gelenke', 'result' => 'wear'],
            ['category' => 'marking', 'item_name' => 'Typenschild', 'result' => 'ok']
        ];

        $itemIds = [];
        foreach ($items as $itemData) {
            $itemData['inspection_id'] = $inspectionId;
            $item = new InspectionItem($itemData);
            $errors = $item->validate();
            $this->assertEmpty($errors, 'Prüfpunkt-Daten sollten gültig sein');
            
            $itemId = $this->saveInspectionItem($item);
            $itemIds[] = $itemId;
        }

        $this->assertCount(5, $itemIds);

        // Prüfung mit Items laden
        $inspection = $this->loadInspection($inspectionId);
        $inspectionItems = $this->loadInspectionItems($inspectionId);
        
        $this->assertCount(5, $inspectionItems);

        // Defekte zählen
        $defects = array_filter($inspectionItems, function($item) {
            return $item->getResult() === 'defect';
        });
        $this->assertCount(1, $defects);

        // Gesamtergebnis basierend auf Items berechnen
        $inspection->setInspectionItems($inspectionItems);
        $calculatedResult = $inspection->calculateOverallResult();
        $this->assertEquals('conditional', $calculatedResult);
    }

    public function testInspectionWorkflow()
    {
        // Vollständiger Prüfungs-Workflow
        $ladderId = $this->createTestLadder([
            'ladder_number' => 'WORKFLOW-001',
            'manufacturer' => 'Workflow Test',
            'ladder_type' => 'Mehrzweckleiter',
            'location' => 'Workflow Location',
            'status' => 'active'
        ]);

        // 1. Prüfung planen
        $ladder = $this->loadLadder($ladderId);
        $this->assertTrue($ladder->needsInspection());

        // 2. Prüfung durchführen
        $inspectionId = $this->testDb->createTestInspection([
            'ladder_id' => $ladderId,
            'inspector_id' => $this->testUser->getId(),
            'inspection_date' => date('Y-m-d'),
            'overall_result' => 'passed',
            'next_inspection_date' => date('Y-m-d', strtotime('+1 year'))
        ]);

        // 3. Prüfpunkte hinzufügen
        $this->addInspectionItems($inspectionId, [
            ['structure', 'Holme', 'ok'],
            ['structure', 'Sprossen', 'ok'],
            ['safety', 'Sicherheitshaken', 'ok'],
            ['function', 'Gelenke', 'ok'],
            ['marking', 'Typenschild', 'ok']
        ]);

        // 4. Leiter-Status nach Prüfung aktualisieren
        $this->updateLadderAfterInspection($ladderId, date('Y-m-d', strtotime('+1 year')));

        // 5. Ergebnis validieren
        $updatedLadder = $this->loadLadder($ladderId);
        $this->assertFalse($updatedLadder->needsInspection());
        $this->assertEquals(date('Y-m-d', strtotime('+1 year')), $updatedLadder->getNextInspectionDate());
    }

    // ===== USER MANAGEMENT TESTS =====

    public function testUserManagement()
    {
        // Neuen Benutzer aus LDAP-Daten erstellen
        $ldapData = [
            'dn' => 'cn=New User,ou=users,dc=test,dc=com',
            'username' => 'new.user',
            'email' => 'new.user@example.com',
            'first_name' => 'New',
            'last_name' => 'User',
            'display_name' => 'New User',
            'groups' => ['users']
        ];

        $user = User::createOrUpdateFromLdap($ldapData);
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('new.user', $user->getUsername());
        $this->assertTrue($user->isActive());

        // Benutzer deaktivieren
        $user->deactivate();
        $this->assertFalse($user->isActive());

        // Benutzer wieder aktivieren
        $user->activate();
        $this->assertTrue($user->isActive());

        // Benutzer-Gruppen aktualisieren
        $updatedLdapData = $ldapData;
        $updatedLdapData['groups'] = ['users', 'inspectors'];

        $updatedUser = User::createOrUpdateFromLdap($updatedLdapData);
        $this->assertEquals($user->getId(), $updatedUser->getId());
        $this->assertEquals(['users', 'inspectors'], $updatedUser->getGroups());
    }

    // ===== COMPLEX BUSINESS LOGIC TESTS =====

    public function testLadderLifecycle()
    {
        // Vollständiger Lebenszyklus einer Leiter
        
        // 1. Leiter erstellen (Neukauf)
        $ladderId = $this->createTestLadder([
            'ladder_number' => 'LIFECYCLE-001',
            'manufacturer' => 'Lifecycle Test',
            'ladder_type' => 'Stehleiter',
            'location' => 'Lifecycle Location',
            'status' => 'active',
            'purchase_date' => date('Y-m-d')
        ]);

        // 2. Erstprüfung
        $initialInspectionId = $this->testDb->createTestInspection([
            'ladder_id' => $ladderId,
            'inspector_id' => $this->testUser->getId(),
            'inspection_date' => date('Y-m-d'),
            'inspection_type' => 'initial',
            'overall_result' => 'passed',
            'next_inspection_date' => date('Y-m-d', strtotime('+1 year'))
        ]);

        // 3. Routineprüfungen über mehrere Jahre
        for ($year = 1; $year <= 3; $year++) {
            $inspectionDate = date('Y-m-d', strtotime("+{$year} years"));
            $nextInspectionDate = date('Y-m-d', strtotime("+". ($year + 1) ." years"));
            
            $routineInspectionId = $this->testDb->createTestInspection([
                'ladder_id' => $ladderId,
                'inspector_id' => $this->testUser->getId(),
                'inspection_date' => $inspectionDate,
                'inspection_type' => 'routine',
                'overall_result' => $year < 3 ? 'passed' : 'conditional',
                'next_inspection_date' => $nextInspectionDate
            ]);

            // Im 3. Jahr Verschleiß feststellen
            if ($year === 3) {
                $this->addInspectionItems($routineInspectionId, [
                    ['structure', 'Holme', 'ok'],
                    ['structure', 'Sprossen', 'wear'],
                    ['safety', 'Sicherheitshaken', 'ok']
                ]);
            }
        }

        // 4. Defekt nach Zwischenfall
        $incidentInspectionId = $this->testDb->createTestInspection([
            'ladder_id' => $ladderId,
            'inspector_id' => $this->testUser->getId(),
            'inspection_date' => date('Y-m-d', strtotime('+3 years +6 months')),
            'inspection_type' => 'after_incident',
            'overall_result' => 'failed',
            'next_inspection_date' => date('Y-m-d', strtotime('+4 years'))
        ]);

        $this->addInspectionItems($incidentInspectionId, [
            ['structure', 'Holme', 'defect', 'critical'],
            ['structure', 'Sprossen', 'defect', 'high'],
            ['safety', 'Sicherheitshaken', 'ok']
        ]);

        // 5. Leiter als defekt markieren
        $ladder = $this->loadLadder($ladderId);
        $ladder->setStatus('defective');
        $this->updateLadder($ladder);

        // 6. Leiter entsorgen
        $ladder->setStatus('disposed');
        $this->updateLadder($ladder);

        // 7. Prüfungshistorie validieren
        $inspectionHistory = $this->getInspectionHistory($ladderId);
        $this->assertCount(5, $inspectionHistory); // 1 initial + 3 routine + 1 incident

        $inspectionTypes = array_column($inspectionHistory, 'inspection_type');
        $this->assertContains('initial', $inspectionTypes);
        $this->assertContains('routine', $inspectionTypes);
        $this->assertContains('after_incident', $inspectionTypes);
    }

    public function testDataIntegrityInComplexScenario()
    {
        // Komplexes Szenario mit mehreren Leitern, Prüfern und Prüfungen
        
        // Mehrere Prüfer erstellen
        $inspectors = [];
        for ($i = 1; $i <= 3; $i++) {
            $inspectors[] = User::createOrUpdateFromLdap([
                'dn' => "cn=Inspector {$i},ou=users,dc=test,dc=com",
                'username' => "inspector.{$i}",
                'email' => "inspector.{$i}@example.com",
                'first_name' => 'Inspector',
                'last_name' => (string)$i,
                'groups' => ['users', 'inspectors']
            ]);
        }

        // Mehrere Leitern erstellen
        $ladderIds = [];
        for ($i = 1; $i <= 5; $i++) {
            $ladderIds[] = $this->createTestLadder([
                'ladder_number' => sprintf('COMPLEX-%03d', $i),
                'manufacturer' => 'Complex Test',
                'ladder_type' => 'Stehleiter',
                'location' => "Complex Location {$i}"
            ]);
        }

        // Prüfungen mit verschiedenen Prüfern durchführen
        $inspectionCount = 0;
        foreach ($ladderIds as $ladderId) {
            foreach ($inspectors as $inspector) {
                $inspectionId = $this->testDb->createTestInspection([
                    'ladder_id' => $ladderId,
                    'inspector_id' => $inspector->getId(),
                    'inspection_date' => date('Y-m-d', strtotime("-{$inspectionCount} days")),
                    'overall_result' => ['passed', 'conditional', 'failed'][rand(0, 2)],
                    'next_inspection_date' => date('Y-m-d', strtotime('+1 year'))
                ]);

                // Zufällige Prüfpunkte hinzufügen
                $this->addRandomInspectionItems($inspectionId);
                $inspectionCount++;
            }
        }

        // Datenintegrität prüfen
        $this->assertEquals(15, $inspectionCount); // 5 Leitern × 3 Prüfer

        // Statistiken validieren
        $stats = $this->getInspectionStatistics();
        $this->assertEquals(15, $stats['total_inspections']);
        $this->assertEquals(5, $stats['total_ladders']);
        $this->assertEquals(3, $stats['total_inspectors']);
    }

    // ===== HELPER METHODS =====

    private function saveLadder(Ladder $ladder): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO ladders (ladder_number, manufacturer, model, ladder_type, material, max_load_kg, height_cm,
                               purchase_date, location, department, responsible_person, serial_number, notes, status,
                               next_inspection_date, inspection_interval_months)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $ladder->getLadderNumber(), $ladder->getManufacturer(), $ladder->getModel(),
            $ladder->getLadderType(), $ladder->getMaterial(), $ladder->getMaxLoadKg(),
            $ladder->getHeightCm(), $ladder->getPurchaseDate(), $ladder->getLocation(),
            $ladder->getDepartment(), $ladder->getResponsiblePerson(), $ladder->getSerialNumber(),
            $ladder->getNotes(), $ladder->getStatus(), $ladder->getNextInspectionDate(),
            $ladder->getInspectionIntervalMonths()
        ]);

        return $this->pdo->lastInsertId();
    }

    private function loadLadder(int $id): ?Ladder
    {
        $stmt = $this->pdo->prepare("SELECT * FROM ladders WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? new Ladder($data) : null;
    }

    private function updateLadder(Ladder $ladder): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE ladders SET manufacturer = ?, model = ?, ladder_type = ?, material = ?, max_load_kg = ?,
                             height_cm = ?, purchase_date = ?, location = ?, department = ?, responsible_person = ?,
                             serial_number = ?, notes = ?, status = ?, next_inspection_date = ?,
                             inspection_interval_months = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");

        $stmt->execute([
            $ladder->getManufacturer(), $ladder->getModel(), $ladder->getLadderType(),
            $ladder->getMaterial(), $ladder->getMaxLoadKg(), $ladder->getHeightCm(),
            $ladder->getPurchaseDate(), $ladder->getLocation(), $ladder->getDepartment(),
            $ladder->getResponsiblePerson(), $ladder->getSerialNumber(), $ladder->getNotes(),
            $ladder->getStatus(), $ladder->getNextInspectionDate(),
            $ladder->getInspectionIntervalMonths(), $ladder->getId()
        ]);
    }

    private function deleteLadder(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM ladders WHERE id = ?");
        $stmt->execute([$id]);
    }

    private function createTestLadder(array $data): int
    {
        $defaults = [
            'manufacturer' => 'Test Manufacturer',
            'ladder_type' => 'Stehleiter',
            'material' => 'Aluminium',
            'max_load_kg' => 150,
            'height_cm' => 200,
            'location' => 'Test Location',
            'status' => 'active',
            'next_inspection_date' => date('Y-m-d', strtotime('+1 year')),
            'inspection_interval_months' => 12
        ];

        $ladderData = array_merge($defaults, $data);
        $ladder = new Ladder($ladderData);
        return $this->saveLadder($ladder);
    }

    private function searchLadders(array $criteria): array
    {
        $where = [];
        $params = [];

        foreach ($criteria as $field => $value) {
            $where[] = "{$field} = ?";
            $params[] = $value;
        }

        $sql = "SELECT * FROM ladders";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = new Ladder($row);
        }

        return $results;
    }

    private function saveInspection(Inspection $inspection): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO inspections (ladder_id, inspector_id, inspection_date, inspection_type, overall_result,
                                   next_inspection_date, inspection_duration_minutes, weather_conditions,
                                   temperature_celsius, general_notes, recommendations, defects_found,
                                   actions_required, inspector_signature)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $inspection->getLadderId(), $inspection->getInspectorId(), $inspection->getInspectionDate(),
            $inspection->getInspectionType(), $inspection->getOverallResult(), $inspection->getNextInspectionDate(),
            $inspection->getInspectionDurationMinutes(), $inspection->getWeatherConditions(),
            $inspection->getTemperatureCelsius(), $inspection->getGeneralNotes(),
            $inspection->getRecommendations(), $inspection->getDefectsFound(),
            $inspection->getActionsRequired(), $inspection->getInspectorSignature()
        ]);

        return $this->pdo->lastInsertId();
    }

    private function loadInspection(int $id): ?Inspection
    {
        $stmt = $this->pdo->prepare("SELECT * FROM inspections WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        $inspection = new Inspection($data);
        $inspection->setId($data['id']); // Macht die Prüfung unveränderlich
        return $inspection;
    }

    private function saveInspectionItem(InspectionItem $item): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO inspection_items (inspection_id, category, item_name, description, result, severity,
                                        notes, photo_path, repair_required, repair_deadline, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $item->getInspectionId(), $item->getCategory(), $item->getItemName(),
            $item->getDescription(), $item->getResult(), $item->getSeverity(),
            $item->getNotes(), $item->getPhotoPath(), $item->isRepairRequired() ? 1 : 0,
            $item->getRepairDeadline(), $item->getSortOrder()
        ]);

        return $this->pdo->lastInsertId();
    }

    private function loadInspectionItems(int $inspectionId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM inspection_items WHERE inspection_id = ? ORDER BY sort_order");
        $stmt->execute([$inspectionId]);

        $items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $items[] = new InspectionItem($row);
        }

        return $items;
    }

    private function addInspectionItems(int $inspectionId, array $itemsData): void
    {
        foreach ($itemsData as $itemData) {
            $item = new InspectionItem([
                'inspection_id' => $inspectionId,
                'category' => $itemData[0],
                'item_name' => $itemData[1],
                'result' => $itemData[2],
                'severity' => $itemData[3] ?? null
            ]);
            $this->saveInspectionItem($item);
        }
    }

    private function addRandomInspectionItems(int $inspectionId): void
    {
        $categories = ['structure', 'safety', 'function', 'marking', 'accessories'];
        $results = ['ok', 'ok', 'ok', 'defect', 'wear']; // Mehr OK als Defekte
        $severities = ['low', 'medium', 'high', 'critical'];

        for ($i = 1; $i <= rand(3, 7); $i++) {
            $result = $results[array_rand($results)];
            $severity = $result === 'defect' ? $severities[array_rand($severities)] : null;

            $item = new InspectionItem([
                'inspection_id' => $inspectionId,
                'category' => $categories[array_rand($categories)],
                'item_name' => "Random Item {$i}",
                'result' => $result,
                'severity' => $severity,
                'sort_order' => $i
            ]);
            $this->saveInspectionItem($item);
        }
    }

    private function updateLadderAfterInspection(int $ladderId, string $nextInspectionDate): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE ladders SET next_inspection_date = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?
        ");
        $stmt->execute([$nextInspectionDate, $ladderId]);
    }

    private function getInspectionHistory(int $ladderId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM inspections WHERE ladder_id = ? ORDER BY inspection_date DESC
        ");
        $stmt->execute([$ladderId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getInspectionStatistics(): array
    {
        $stats = [];

        // Gesamtzahl Prüfungen
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM inspections");
        $stats['total_inspections'] = $stmt->fetchColumn();

        // Gesamtzahl Leitern
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM ladders");
        $stats['total_ladders'] = $stmt->fetchColumn();

        // Gesamtzahl Prüfer
        $stmt = $this->pdo->query("SELECT COUNT(DISTINCT inspector_id) FROM inspections");
        $stats['total_inspectors'] = $stmt->fetchColumn();

        // Prüfungen nach Ergebnis
        $stmt = $this->pdo->query("
            SELECT overall_result, COUNT(*) as count 
            FROM inspections 
            GROUP BY overall_result
        ");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($results as $result) {
            $stats['results'][$result['overall_result']] = $result['count'];
        }

        return $stats;
    }
}
