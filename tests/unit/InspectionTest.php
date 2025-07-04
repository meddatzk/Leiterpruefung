<?php

require_once __DIR__ . '/../setup/TestDatabase.php';
require_once __DIR__ . '/../../web/src/includes/Inspection.php';
require_once __DIR__ . '/../../web/src/includes/InspectionItem.php';
require_once __DIR__ . '/../../web/src/includes/Ladder.php';
require_once __DIR__ . '/../../web/src/includes/User.php';

use PHPUnit\Framework\TestCase;

/**
 * Unit-Tests für die Inspection-Klasse
 * 
 * Testet alle Aspekte des Inspection-Models:
 * - Konstruktor und Dateninitialisierung
 * - Unveränderlichkeit nach setId()
 * - Getter/Setter-Validierung
 * - Enum-Validierung
 * - Geschäftslogik-Methoden
 * - Beziehungen zu anderen Models
 * - Validierungsregeln
 * - Array/JSON-Konvertierung
 */
class InspectionTest extends TestCase
{
    private Inspection $inspection;
    private array $validInspectionData;

    protected function setUp(): void
    {
        $this->validInspectionData = [
            'ladder_id' => 1,
            'inspector_id' => 1,
            'inspection_date' => '2024-01-15',
            'inspection_type' => 'routine',
            'overall_result' => 'passed',
            'next_inspection_date' => '2025-01-15',
            'inspection_duration_minutes' => 30,
            'weather_conditions' => 'trocken',
            'temperature_celsius' => 20,
            'general_notes' => 'Leiter in gutem Zustand',
            'recommendations' => 'Regelmäßige Reinigung',
            'defects_found' => null,
            'actions_required' => null,
            'inspector_signature' => 'Max Mustermann'
        ];

        $this->inspection = new Inspection();
    }

    protected function tearDown(): void
    {
        unset($this->inspection);
    }

    // ===== KONSTRUKTOR-TESTS =====

    public function testConstructorWithEmptyData()
    {
        $inspection = new Inspection();
        $this->assertNull($inspection->getId());
        $this->assertEquals('routine', $inspection->getInspectionType());
        $this->assertFalse($inspection->isImmutable());
    }

    public function testConstructorWithValidData()
    {
        $inspection = new Inspection($this->validInspectionData);
        
        $this->assertEquals(1, $inspection->getLadderId());
        $this->assertEquals(1, $inspection->getInspectorId());
        $this->assertEquals('2024-01-15', $inspection->getInspectionDate());
        $this->assertEquals('routine', $inspection->getInspectionType());
        $this->assertEquals('passed', $inspection->getOverallResult());
        $this->assertEquals('2025-01-15', $inspection->getNextInspectionDate());
        $this->assertEquals(30, $inspection->getInspectionDurationMinutes());
        $this->assertEquals('trocken', $inspection->getWeatherConditions());
        $this->assertEquals(20, $inspection->getTemperatureCelsius());
        $this->assertEquals('Leiter in gutem Zustand', $inspection->getGeneralNotes());
        $this->assertEquals('Regelmäßige Reinigung', $inspection->getRecommendations());
        $this->assertNull($inspection->getDefectsFound());
        $this->assertNull($inspection->getActionsRequired());
        $this->assertEquals('Max Mustermann', $inspection->getInspectorSignature());
        $this->assertFalse($inspection->isImmutable());
    }

    // ===== UNVERÄNDERLICHKEITS-TESTS =====

    public function testImmutabilityAfterSetId()
    {
        $this->inspection->fillFromArray($this->validInspectionData);
        $this->assertFalse($this->inspection->isImmutable());
        
        // Nach setId wird die Prüfung unveränderlich
        $this->inspection->setId(1);
        $this->assertTrue($this->inspection->isImmutable());
    }

    public function testImmutabilityPreventsChanges()
    {
        $this->inspection->fillFromArray($this->validInspectionData);
        $this->inspection->setId(1);
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Prüfung ist unveränderlich nach dem Speichern');
        
        $this->inspection->setLadderId(2);
    }

    public function testImmutabilityPreventsSetId()
    {
        $this->inspection->fillFromArray($this->validInspectionData);
        $this->inspection->setId(1);
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Prüfung ist unveränderlich nach dem Speichern');
        
        $this->inspection->setId(2);
    }

    public function testImmutabilityPreventsFillFromArray()
    {
        $this->inspection->fillFromArray($this->validInspectionData);
        $this->inspection->setId(1);
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Prüfung ist unveränderlich nach dem Speichern');
        
        $this->inspection->fillFromArray(['ladder_id' => 2]);
    }

    public function testSetIdWithNullDoesNotMakeImmutable()
    {
        $this->inspection->fillFromArray($this->validInspectionData);
        $this->inspection->setId(null);
        
        $this->assertFalse($this->inspection->isImmutable());
        
        // Änderungen sollten noch möglich sein
        $this->inspection->setLadderId(2);
        $this->assertEquals(2, $this->inspection->getLadderId());
    }

    // ===== GETTER/SETTER-TESTS =====

    public function testSetAndGetLadderId()
    {
        $this->inspection->setLadderId(123);
        $this->assertEquals(123, $this->inspection->getLadderId());
    }

    public function testSetAndGetInspectorId()
    {
        $this->inspection->setInspectorId(456);
        $this->assertEquals(456, $this->inspection->getInspectorId());
    }

    public function testSetAndGetInspectionDate()
    {
        $this->inspection->setInspectionDate('2024-06-15');
        $this->assertEquals('2024-06-15', $this->inspection->getInspectionDate());
    }

    public function testSetAndGetNextInspectionDate()
    {
        $this->inspection->setNextInspectionDate('2025-06-15');
        $this->assertEquals('2025-06-15', $this->inspection->getNextInspectionDate());
    }

    // ===== ENUM-VALIDIERUNG TESTS =====

    public function testValidInspectionTypes()
    {
        $validTypes = Inspection::INSPECTION_TYPES;
        
        foreach ($validTypes as $type) {
            $this->inspection->setInspectionType($type);
            $this->assertEquals($type, $this->inspection->getInspectionType());
        }
    }

    public function testInvalidInspectionType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ungültiger Prüfungstyp: invalid_type');
        
        $this->inspection->setInspectionType('invalid_type');
    }

    public function testValidOverallResults()
    {
        $validResults = Inspection::OVERALL_RESULTS;
        
        foreach ($validResults as $result) {
            $this->inspection->setOverallResult($result);
            $this->assertEquals($result, $this->inspection->getOverallResult());
        }
    }

    public function testInvalidOverallResult()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ungültiges Prüfungsergebnis: invalid_result');
        
        $this->inspection->setOverallResult('invalid_result');
    }

    // ===== DATUMS-VALIDIERUNG TESTS =====

    public function testValidInspectionDate()
    {
        $this->inspection->setInspectionDate('2024-02-29'); // Schaltjahr
        $this->assertEquals('2024-02-29', $this->inspection->getInspectionDate());
    }

    public function testInvalidInspectionDate()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ungültiges Prüfdatum: 2024-13-01');
        
        $this->inspection->setInspectionDate('2024-13-01');
    }

    public function testInvalidInspectionDateFormat()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ungültiges Prüfdatum: 15.01.2024');
        
        $this->inspection->setInspectionDate('15.01.2024');
    }

    public function testValidNextInspectionDate()
    {
        $this->inspection->setNextInspectionDate('2025-12-31');
        $this->assertEquals('2025-12-31', $this->inspection->getNextInspectionDate());
    }

    public function testInvalidNextInspectionDate()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ungültiges nächstes Prüfdatum: 2024-02-30');
        
        $this->inspection->setNextInspectionDate('2024-02-30');
    }

    public function testValidApprovalDate()
    {
        $this->inspection->setApprovalDate('2024-01-15 14:30:00');
        $this->assertEquals('2024-01-15 14:30:00', $this->inspection->getApprovalDate());
        
        $this->inspection->setApprovalDate(null);
        $this->assertNull($this->inspection->getApprovalDate());
    }

    public function testInvalidApprovalDate()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ungültiges Genehmigungsdatum: 2024-01-15 25:00:00');
        
        $this->inspection->setApprovalDate('2024-01-15 25:00:00');
    }

    // ===== ZAHLEN-VALIDIERUNG TESTS =====

    public function testValidInspectionDurationMinutes()
    {
        $this->inspection->setInspectionDurationMinutes(30);
        $this->assertEquals(30, $this->inspection->getInspectionDurationMinutes());
        
        $this->inspection->setInspectionDurationMinutes(1);
        $this->assertEquals(1, $this->inspection->getInspectionDurationMinutes());
        
        $this->inspection->setInspectionDurationMinutes(null);
        $this->assertNull($this->inspection->getInspectionDurationMinutes());
    }

    public function testInvalidInspectionDurationMinutes()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Prüfdauer muss größer als 0 sein');
        
        $this->inspection->setInspectionDurationMinutes(0);
    }

    public function testNegativeInspectionDurationMinutes()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Prüfdauer muss größer als 0 sein');
        
        $this->inspection->setInspectionDurationMinutes(-10);
    }

    public function testValidTemperatureCelsius()
    {
        $this->inspection->setTemperatureCelsius(20);
        $this->assertEquals(20, $this->inspection->getTemperatureCelsius());
        
        $this->inspection->setTemperatureCelsius(-50);
        $this->assertEquals(-50, $this->inspection->getTemperatureCelsius());
        
        $this->inspection->setTemperatureCelsius(60);
        $this->assertEquals(60, $this->inspection->getTemperatureCelsius());
        
        $this->inspection->setTemperatureCelsius(null);
        $this->assertNull($this->inspection->getTemperatureCelsius());
    }

    public function testInvalidTemperatureCelsiusTooLow()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Temperatur muss zwischen -50°C und 60°C liegen');
        
        $this->inspection->setTemperatureCelsius(-51);
    }

    public function testInvalidTemperatureCelsiusTooHigh()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Temperatur muss zwischen -50°C und 60°C liegen');
        
        $this->inspection->setTemperatureCelsius(61);
    }

    // ===== STRING-TRIMMING TESTS =====

    public function testStringTrimming()
    {
        $this->inspection->setWeatherConditions('  trocken  ');
        $this->assertEquals('trocken', $this->inspection->getWeatherConditions());
        
        $this->inspection->setGeneralNotes('  Test notes  ');
        $this->assertEquals('Test notes', $this->inspection->getGeneralNotes());
        
        $this->inspection->setRecommendations('  Test recommendations  ');
        $this->assertEquals('Test recommendations', $this->inspection->getRecommendations());
        
        $this->inspection->setDefectsFound('  Test defects  ');
        $this->assertEquals('Test defects', $this->inspection->getDefectsFound());
        
        $this->inspection->setActionsRequired('  Test actions  ');
        $this->assertEquals('Test actions', $this->inspection->getActionsRequired());
        
        $this->inspection->setInspectorSignature('  John Doe  ');
        $this->assertEquals('John Doe', $this->inspection->getInspectorSignature());
    }

    public function testEmptyStringHandling()
    {
        $this->inspection->setWeatherConditions('');
        $this->assertNull($this->inspection->getWeatherConditions());
        
        $this->inspection->setGeneralNotes('');
        $this->assertNull($this->inspection->getGeneralNotes());
        
        $this->inspection->setRecommendations('');
        $this->assertNull($this->inspection->getRecommendations());
        
        $this->inspection->setDefectsFound('');
        $this->assertNull($this->inspection->getDefectsFound());
        
        $this->inspection->setActionsRequired('');
        $this->assertNull($this->inspection->getActionsRequired());
        
        $this->inspection->setInspectorSignature('');
        $this->assertNull($this->inspection->getInspectorSignature());
    }

    // ===== BEZIEHUNGS-TESTS =====

    public function testSetAndGetLadder()
    {
        $ladder = new Ladder(['ladder_number' => 'TEST-001', 'manufacturer' => 'Test']);
        $this->inspection->setLadder($ladder);
        
        $this->assertSame($ladder, $this->inspection->getLadder());
    }

    public function testSetAndGetInspector()
    {
        $inspector = new User(['username' => 'test.inspector']);
        $this->inspection->setInspector($inspector);
        
        $this->assertSame($inspector, $this->inspection->getInspector());
    }

    public function testSetAndGetSupervisorApproval()
    {
        $supervisor = new User(['username' => 'test.supervisor']);
        $this->inspection->setSupervisorApproval($supervisor);
        
        $this->assertSame($supervisor, $this->inspection->getSupervisorApproval());
    }

    public function testSetAndGetInspectionItems()
    {
        $item1 = new InspectionItem(['inspection_id' => 1, 'category' => 'structure', 'item_name' => 'Test 1', 'result' => 'ok']);
        $item2 = new InspectionItem(['inspection_id' => 1, 'category' => 'safety', 'item_name' => 'Test 2', 'result' => 'defect']);
        
        $items = [$item1, $item2];
        $this->inspection->setInspectionItems($items);
        
        $this->assertEquals($items, $this->inspection->getInspectionItems());
    }

    // ===== GESCHÄFTSLOGIK TESTS =====

    public function testCalculateOverallResultWithNoItems()
    {
        $result = $this->inspection->calculateOverallResult();
        $this->assertEquals('conditional', $result);
    }

    public function testCalculateOverallResultWithAllOkItems()
    {
        $item1 = new InspectionItem(['inspection_id' => 1, 'category' => 'structure', 'item_name' => 'Test 1', 'result' => 'ok']);
        $item2 = new InspectionItem(['inspection_id' => 1, 'category' => 'safety', 'item_name' => 'Test 2', 'result' => 'ok']);
        
        $this->inspection->setInspectionItems([$item1, $item2]);
        
        $result = $this->inspection->calculateOverallResult();
        $this->assertEquals('passed', $result);
    }

    public function testCalculateOverallResultWithNonCriticalDefects()
    {
        $item1 = new InspectionItem(['inspection_id' => 1, 'category' => 'structure', 'item_name' => 'Test 1', 'result' => 'ok']);
        $item2 = new InspectionItem(['inspection_id' => 1, 'category' => 'safety', 'item_name' => 'Test 2', 'result' => 'defect', 'severity' => 'medium']);
        
        $this->inspection->setInspectionItems([$item1, $item2]);
        
        $result = $this->inspection->calculateOverallResult();
        $this->assertEquals('conditional', $result);
    }

    public function testCalculateOverallResultWithCriticalDefects()
    {
        $item1 = new InspectionItem(['inspection_id' => 1, 'category' => 'structure', 'item_name' => 'Test 1', 'result' => 'ok']);
        $item2 = new InspectionItem(['inspection_id' => 1, 'category' => 'safety', 'item_name' => 'Test 2', 'result' => 'defect', 'severity' => 'critical']);
        
        $this->inspection->setInspectionItems([$item1, $item2]);
        
        $result = $this->inspection->calculateOverallResult();
        $this->assertEquals('failed', $result);
    }

    public function testIsApprovedTrue()
    {
        $this->inspection->setSupervisorApprovalId(1);
        $this->inspection->setApprovalDate('2024-01-15 14:30:00');
        
        $this->assertTrue($this->inspection->isApproved());
    }

    public function testIsApprovedFalseWithoutApprovalId()
    {
        $this->inspection->setApprovalDate('2024-01-15 14:30:00');
        
        $this->assertFalse($this->inspection->isApproved());
    }

    public function testIsApprovedFalseWithoutApprovalDate()
    {
        $this->inspection->setSupervisorApprovalId(1);
        
        $this->assertFalse($this->inspection->isApproved());
    }

    public function testIsCompleteTrue()
    {
        $item = new InspectionItem(['inspection_id' => 1, 'category' => 'structure', 'item_name' => 'Test', 'result' => 'ok']);
        $this->inspection->setInspectionItems([$item]);
        $this->inspection->setOverallResult('passed');
        $this->inspection->setInspectorSignature('John Doe');
        
        $this->assertTrue($this->inspection->isComplete());
    }

    public function testIsCompleteFalseWithoutItems()
    {
        $this->inspection->setOverallResult('passed');
        $this->inspection->setInspectorSignature('John Doe');
        
        $this->assertFalse($this->inspection->isComplete());
    }

    public function testIsCompleteFalseWithoutResult()
    {
        $item = new InspectionItem(['inspection_id' => 1, 'category' => 'structure', 'item_name' => 'Test', 'result' => 'ok']);
        $this->inspection->setInspectionItems([$item]);
        $this->inspection->setInspectorSignature('John Doe');
        
        $this->assertFalse($this->inspection->isComplete());
    }

    public function testIsCompleteFalseWithoutSignature()
    {
        $item = new InspectionItem(['inspection_id' => 1, 'category' => 'structure', 'item_name' => 'Test', 'result' => 'ok']);
        $this->inspection->setInspectionItems([$item]);
        $this->inspection->setOverallResult('passed');
        
        $this->assertFalse($this->inspection->isComplete());
    }

    public function testGetCriticalDefects()
    {
        $item1 = new InspectionItem(['inspection_id' => 1, 'category' => 'structure', 'item_name' => 'Test 1', 'result' => 'ok']);
        $item2 = new InspectionItem(['inspection_id' => 1, 'category' => 'safety', 'item_name' => 'Test 2', 'result' => 'defect', 'severity' => 'medium']);
        $item3 = new InspectionItem(['inspection_id' => 1, 'category' => 'function', 'item_name' => 'Test 3', 'result' => 'defect', 'severity' => 'critical']);
        $item4 = new InspectionItem(['inspection_id' => 1, 'category' => 'marking', 'item_name' => 'Test 4', 'result' => 'defect', 'severity' => 'critical']);
        
        $this->inspection->setInspectionItems([$item1, $item2, $item3, $item4]);
        
        $criticalDefects = $this->inspection->getCriticalDefects();
        
        $this->assertCount(2, $criticalDefects);
        $this->assertSame($item3, $criticalDefects[0]);
        $this->assertSame($item4, $criticalDefects[1]);
    }

    public function testGetAllDefects()
    {
        $item1 = new InspectionItem(['inspection_id' => 1, 'category' => 'structure', 'item_name' => 'Test 1', 'result' => 'ok']);
        $item2 = new InspectionItem(['inspection_id' => 1, 'category' => 'safety', 'item_name' => 'Test 2', 'result' => 'defect', 'severity' => 'medium']);
        $item3 = new InspectionItem(['inspection_id' => 1, 'category' => 'function', 'item_name' => 'Test 3', 'result' => 'defect', 'severity' => 'critical']);
        $item4 = new InspectionItem(['inspection_id' => 1, 'category' => 'marking', 'item_name' => 'Test 4', 'result' => 'wear']);
        
        $this->inspection->setInspectionItems([$item1, $item2, $item3, $item4]);
        
        $allDefects = $this->inspection->getAllDefects();
        
        $this->assertCount(2, $allDefects);
        $this->assertSame($item2, $allDefects[0]);
        $this->assertSame($item3, $allDefects[1]);
    }

    // ===== VALIDIERUNG TESTS =====

    public function testValidateWithValidData()
    {
        $inspection = new Inspection($this->validInspectionData);
        $errors = $inspection->validate();
        
        $this->assertEmpty($errors);
    }

    public function testValidateWithMissingRequiredFields()
    {
        $inspection = new Inspection();
        $errors = $inspection->validate();
        
        $this->assertContains('Leiter-ID ist erforderlich', $errors);
        $this->assertContains('Prüfer-ID ist erforderlich', $errors);
        $this->assertContains('Prüfdatum ist erforderlich', $errors);
        $this->assertContains('Gesamtergebnis ist erforderlich', $errors);
        $this->assertContains('Nächstes Prüfdatum ist erforderlich', $errors);
    }

    public function testValidateWithInvalidDateLogic()
    {
        $inspection = new Inspection([
            'ladder_id' => 1,
            'inspector_id' => 1,
            'inspection_date' => '2024-06-15',
            'overall_result' => 'passed',
            'next_inspection_date' => '2024-01-15' // Vor dem Prüfdatum
        ]);
        
        $errors = $inspection->validate();
        
        $this->assertContains('Nächstes Prüfdatum muss nach dem Prüfdatum liegen', $errors);
    }

    public function testValidateWithInvalidTemperature()
    {
        $inspection = new Inspection([
            'ladder_id' => 1,
            'inspector_id' => 1,
            'inspection_date' => '2024-01-15',
            'overall_result' => 'passed',
            'next_inspection_date' => '2025-01-15',
            'temperature_celsius' => 70 // Zu hoch
        ]);
        
        $errors = $inspection->validate();
        
        $this->assertContains('Temperatur muss zwischen -50°C und 60°C liegen', $errors);
    }

    // ===== ARRAY/JSON KONVERTIERUNG TESTS =====

    public function testToArray()
    {
        $inspection = new Inspection($this->validInspectionData);
        $array = $inspection->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals(1, $array['ladder_id']);
        $this->assertEquals(1, $array['inspector_id']);
        $this->assertEquals('2024-01-15', $array['inspection_date']);
        $this->assertEquals('routine', $array['inspection_type']);
        $this->assertEquals('passed', $array['overall_result']);
        $this->assertEquals('2025-01-15', $array['next_inspection_date']);
        $this->assertEquals(30, $array['inspection_duration_minutes']);
        $this->assertEquals('trocken', $array['weather_conditions']);
        $this->assertEquals(20, $array['temperature_celsius']);
        $this->assertEquals('Leiter in gutem Zustand', $array['general_notes']);
        $this->assertEquals('Regelmäßige Reinigung', $array['recommendations']);
        $this->assertNull($array['defects_found']);
        $this->assertNull($array['actions_required']);
        $this->assertEquals('Max Mustermann', $array['inspector_signature']);
        $this->assertNull($array['supervisor_approval_id']);
        $this->assertNull($array['approval_date']);
        $this->assertNull($array['id']);
        $this->assertNull($array['created_at']);
        $this->assertNull($array['updated_at']);
    }

    public function testToJson()
    {
        $inspection = new Inspection($this->validInspectionData);
        $json = $inspection->toJson();
        
        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertEquals(1, $decoded['ladder_id']);
        $this->assertEquals('routine', $decoded['inspection_type']);
    }

    public function testFillFromArray()
    {
        $data = [
            'ladder_id' => 99,
            'inspector_id' => 88,
            'inspection_type' => 'special',
            'overall_result' => 'failed'
        ];
        
        $this->inspection->fillFromArray($data);
        
        $this->assertEquals(99, $this->inspection->getLadderId());
        $this->assertEquals(88, $this->inspection->getInspectorId());
        $this->assertEquals('special', $this->inspection->getInspectionType());
        $this->assertEquals('failed', $this->inspection->getOverallResult());
    }

    public function testGetDescription()
    {
        $ladder = new Ladder(['ladder_number' => 'L-001']);
        $inspection = new Inspection([
            'inspection_date' => '2024-01-15',
            'inspection_type' => 'routine',
            'overall_result' => 'passed'
        ]);
        $inspection->setLadder($ladder);
        
        $expected = 'L-001 - 15.01.2024 - Routine - Passed';
        $this->assertEquals($expected, $inspection->getDescription());
    }

    public function testGetDescriptionWithoutLadder()
    {
        $inspection = new Inspection([
            'ladder_id' => 123,
            'inspection_date' => '2024-01-15',
            'inspection_type' => 'initial',
            'overall_result' => 'conditional'
        ]);
        
        $expected = 'Leiter-ID: 123 - 15.01.2024 - Initial - Conditional';
        $this->assertEquals($expected, $inspection->getDescription());
    }

    // ===== KONSTANTEN TESTS =====

    public function testInspectionTypesConstant()
    {
        $expectedTypes = [
            'routine',
            'initial',
            'after_incident',
            'special'
        ];
        
        $this->assertEquals($expectedTypes, Inspection::INSPECTION_TYPES);
    }

    public function testOverallResultsConstant()
    {
        $expectedResults = [
            'passed',
            'failed',
            'conditional'
        ];
        
        $this->assertEquals($expectedResults, Inspection::OVERALL_RESULTS);
    }

    // ===== EDGE CASES TESTS =====

    public function testDateEdgeCases()
    {
        // Schaltjahr-Test
        $this->inspection->setInspectionDate('2024-02-29');
        $this->assertEquals('2024-02-29', $this->inspection->getInspectionDate());
        
        // Kein Schaltjahr
        $this->expectException(InvalidArgumentException::class);
        $this->inspection->setInspectionDate('2023-02-29');
    }

    public function testTemperatureEdgeCases()
    {
        // Grenzwerte testen
        $this->inspection->setTemperatureCelsius(-50);
        $this->assertEquals(-50, $this->inspection->getTemperatureCelsius());
        
        $this->inspection->setTemperatureCelsius(60);
        $this->assertEquals(60, $this->inspection->getTemperatureCelsius());
    }

    public function testLargeInspectionDuration()
    {
        $this->inspection->setInspectionDurationMinutes(999999);
        $this->assertEquals(999999, $this->inspection->getInspectionDurationMinutes());
    }
}
