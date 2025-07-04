<?php

require_once __DIR__ . '/../setup/TestDatabase.php';
require_once __DIR__ . '/../../web/src/includes/Ladder.php';

use PHPUnit\Framework\TestCase;

/**
 * Unit-Tests für die Ladder-Klasse
 * 
 * Testet alle Aspekte des Ladder-Models:
 * - Konstruktor und Dateninitialisierung
 * - Getter/Setter-Validierung
 * - Enum-Validierung
 * - Geschäftslogik-Methoden
 * - Validierungsregeln
 * - Array/JSON-Konvertierung
 */
class LadderTest extends TestCase
{
    private Ladder $ladder;
    private array $validLadderData;

    protected function setUp(): void
    {
        $this->validLadderData = [
            'ladder_number' => 'TEST-001',
            'manufacturer' => 'Test Manufacturer',
            'model' => 'Test Model',
            'ladder_type' => 'Stehleiter',
            'material' => 'Aluminium',
            'max_load_kg' => 150,
            'height_cm' => 200,
            'purchase_date' => '2023-01-15',
            'location' => 'Test Location',
            'department' => 'Test Department',
            'responsible_person' => 'Test Person',
            'serial_number' => 'SN-123456',
            'notes' => 'Test notes',
            'status' => 'active',
            'next_inspection_date' => '2024-01-15',
            'inspection_interval_months' => 12
        ];

        $this->ladder = new Ladder();
    }

    protected function tearDown(): void
    {
        unset($this->ladder);
    }

    // ===== KONSTRUKTOR-TESTS =====

    public function testConstructorWithEmptyData()
    {
        $ladder = new Ladder();
        $this->assertNull($ladder->getId());
        $this->assertEquals('Aluminium', $ladder->getMaterial());
        $this->assertEquals(150, $ladder->getMaxLoadKg());
        $this->assertEquals('active', $ladder->getStatus());
        $this->assertEquals(12, $ladder->getInspectionIntervalMonths());
    }

    public function testConstructorWithValidData()
    {
        $ladder = new Ladder($this->validLadderData);
        
        $this->assertEquals('TEST-001', $ladder->getLadderNumber());
        $this->assertEquals('Test Manufacturer', $ladder->getManufacturer());
        $this->assertEquals('Test Model', $ladder->getModel());
        $this->assertEquals('Stehleiter', $ladder->getLadderType());
        $this->assertEquals('Aluminium', $ladder->getMaterial());
        $this->assertEquals(150, $ladder->getMaxLoadKg());
        $this->assertEquals(200, $ladder->getHeightCm());
        $this->assertEquals('2023-01-15', $ladder->getPurchaseDate());
        $this->assertEquals('Test Location', $ladder->getLocation());
        $this->assertEquals('Test Department', $ladder->getDepartment());
        $this->assertEquals('Test Person', $ladder->getResponsiblePerson());
        $this->assertEquals('SN-123456', $ladder->getSerialNumber());
        $this->assertEquals('Test notes', $ladder->getNotes());
        $this->assertEquals('active', $ladder->getStatus());
        $this->assertEquals('2024-01-15', $ladder->getNextInspectionDate());
        $this->assertEquals(12, $ladder->getInspectionIntervalMonths());
    }

    // ===== GETTER/SETTER-TESTS =====

    public function testSetAndGetId()
    {
        $this->ladder->setId(123);
        $this->assertEquals(123, $this->ladder->getId());
        
        $this->ladder->setId(null);
        $this->assertNull($this->ladder->getId());
    }

    public function testSetAndGetLadderNumber()
    {
        $this->ladder->setLadderNumber('  L-001  ');
        $this->assertEquals('L-001', $this->ladder->getLadderNumber());
    }

    public function testSetAndGetManufacturer()
    {
        $this->ladder->setManufacturer('  Hailo  ');
        $this->assertEquals('Hailo', $this->ladder->getManufacturer());
    }

    public function testSetAndGetModel()
    {
        $this->ladder->setModel('  ProfiStep  ');
        $this->assertEquals('ProfiStep', $this->ladder->getModel());
        
        $this->ladder->setModel(null);
        $this->assertNull($this->ladder->getModel());
        
        $this->ladder->setModel('');
        $this->assertNull($this->ladder->getModel());
    }

    // ===== ENUM-VALIDIERUNG TESTS =====

    public function testValidLadderTypes()
    {
        $validTypes = Ladder::LADDER_TYPES;
        
        foreach ($validTypes as $type) {
            $this->ladder->setLadderType($type);
            $this->assertEquals($type, $this->ladder->getLadderType());
        }
    }

    public function testInvalidLadderType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ungültiger Leitertyp: InvalidType');
        
        $this->ladder->setLadderType('InvalidType');
    }

    public function testValidMaterials()
    {
        $validMaterials = Ladder::MATERIALS;
        
        foreach ($validMaterials as $material) {
            $this->ladder->setMaterial($material);
            $this->assertEquals($material, $this->ladder->getMaterial());
        }
    }

    public function testInvalidMaterial()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ungültiges Material: InvalidMaterial');
        
        $this->ladder->setMaterial('InvalidMaterial');
    }

    public function testValidStatuses()
    {
        $validStatuses = Ladder::STATUSES;
        
        foreach ($validStatuses as $status) {
            $this->ladder->setStatus($status);
            $this->assertEquals($status, $this->ladder->getStatus());
        }
    }

    public function testInvalidStatus()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ungültiger Status: invalid_status');
        
        $this->ladder->setStatus('invalid_status');
    }

    // ===== ZAHLEN-VALIDIERUNG TESTS =====

    public function testValidMaxLoadKg()
    {
        $this->ladder->setMaxLoadKg(100);
        $this->assertEquals(100, $this->ladder->getMaxLoadKg());
        
        $this->ladder->setMaxLoadKg(1);
        $this->assertEquals(1, $this->ladder->getMaxLoadKg());
        
        $this->ladder->setMaxLoadKg(1000);
        $this->assertEquals(1000, $this->ladder->getMaxLoadKg());
    }

    public function testInvalidMaxLoadKg()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Maximale Belastung muss größer als 0 sein');
        
        $this->ladder->setMaxLoadKg(0);
    }

    public function testNegativeMaxLoadKg()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Maximale Belastung muss größer als 0 sein');
        
        $this->ladder->setMaxLoadKg(-10);
    }

    public function testValidHeightCm()
    {
        $this->ladder->setHeightCm(100);
        $this->assertEquals(100, $this->ladder->getHeightCm());
        
        $this->ladder->setHeightCm(1);
        $this->assertEquals(1, $this->ladder->getHeightCm());
        
        $this->ladder->setHeightCm(5000);
        $this->assertEquals(5000, $this->ladder->getHeightCm());
    }

    public function testInvalidHeightCm()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Höhe muss größer als 0 sein');
        
        $this->ladder->setHeightCm(0);
    }

    public function testValidInspectionIntervalMonths()
    {
        $this->ladder->setInspectionIntervalMonths(6);
        $this->assertEquals(6, $this->ladder->getInspectionIntervalMonths());
        
        $this->ladder->setInspectionIntervalMonths(24);
        $this->assertEquals(24, $this->ladder->getInspectionIntervalMonths());
    }

    public function testInvalidInspectionIntervalMonths()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Prüfintervall muss größer als 0 sein');
        
        $this->ladder->setInspectionIntervalMonths(0);
    }

    // ===== DATUMS-VALIDIERUNG TESTS =====

    public function testValidPurchaseDate()
    {
        $this->ladder->setPurchaseDate('2023-01-15');
        $this->assertEquals('2023-01-15', $this->ladder->getPurchaseDate());
        
        $this->ladder->setPurchaseDate(null);
        $this->assertNull($this->ladder->getPurchaseDate());
    }

    public function testInvalidPurchaseDate()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ungültiges Kaufdatum: 2023-13-01');
        
        $this->ladder->setPurchaseDate('2023-13-01');
    }

    public function testInvalidPurchaseDateFormat()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ungültiges Kaufdatum: 15.01.2023');
        
        $this->ladder->setPurchaseDate('15.01.2023');
    }

    public function testValidNextInspectionDate()
    {
        $this->ladder->setNextInspectionDate('2024-12-31');
        $this->assertEquals('2024-12-31', $this->ladder->getNextInspectionDate());
    }

    public function testInvalidNextInspectionDate()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ungültiges Prüfdatum: 2024-02-30');
        
        $this->ladder->setNextInspectionDate('2024-02-30');
    }

    // ===== STRING-TRIMMING TESTS =====

    public function testStringTrimming()
    {
        $this->ladder->setLocation('  Test Location  ');
        $this->assertEquals('Test Location', $this->ladder->getLocation());
        
        $this->ladder->setDepartment('  Test Dept  ');
        $this->assertEquals('Test Dept', $this->ladder->getDepartment());
        
        $this->ladder->setResponsiblePerson('  John Doe  ');
        $this->assertEquals('John Doe', $this->ladder->getResponsiblePerson());
        
        $this->ladder->setSerialNumber('  SN123  ');
        $this->assertEquals('SN123', $this->ladder->getSerialNumber());
        
        $this->ladder->setNotes('  Some notes  ');
        $this->assertEquals('Some notes', $this->ladder->getNotes());
    }

    // ===== VALIDIERUNG TESTS =====

    public function testValidateWithValidData()
    {
        $ladder = new Ladder($this->validLadderData);
        $errors = $ladder->validate();
        
        $this->assertEmpty($errors);
    }

    public function testValidateWithMissingRequiredFields()
    {
        $ladder = new Ladder();
        $errors = $ladder->validate();
        
        $this->assertContains('Leiternummer ist erforderlich', $errors);
        $this->assertContains('Hersteller ist erforderlich', $errors);
        $this->assertContains('Leitertyp ist erforderlich', $errors);
        $this->assertContains('Standort ist erforderlich', $errors);
        $this->assertContains('Nächstes Prüfdatum ist erforderlich', $errors);
    }

    public function testValidateWithInvalidValues()
    {
        $ladder = new Ladder([
            'ladder_number' => 'TEST-001',
            'manufacturer' => 'Test',
            'ladder_type' => 'Stehleiter',
            'location' => 'Test',
            'next_inspection_date' => '2024-01-01',
            'max_load_kg' => -10,
            'height_cm' => 0,
            'inspection_interval_months' => -5
        ]);
        
        $errors = $ladder->validate();
        
        $this->assertContains('Maximale Belastung muss größer als 0 sein', $errors);
        $this->assertContains('Höhe muss größer als 0 sein', $errors);
        $this->assertContains('Prüfintervall muss größer als 0 sein', $errors);
    }

    // ===== GESCHÄFTSLOGIK TESTS =====

    public function testNeedsInspectionTrue()
    {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $this->ladder->setNextInspectionDate($yesterday);
        
        $this->assertTrue($this->ladder->needsInspection());
    }

    public function testNeedsInspectionFalse()
    {
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $this->ladder->setNextInspectionDate($tomorrow);
        
        $this->assertFalse($this->ladder->needsInspection());
    }

    public function testNeedsInspectionToday()
    {
        $today = date('Y-m-d');
        $this->ladder->setNextInspectionDate($today);
        
        $this->assertTrue($this->ladder->needsInspection());
    }

    public function testGetDaysUntilInspectionPositive()
    {
        $futureDate = date('Y-m-d', strtotime('+5 days'));
        $this->ladder->setNextInspectionDate($futureDate);
        
        $this->assertEquals(5, $this->ladder->getDaysUntilInspection());
    }

    public function testGetDaysUntilInspectionNegative()
    {
        $pastDate = date('Y-m-d', strtotime('-3 days'));
        $this->ladder->setNextInspectionDate($pastDate);
        
        $this->assertEquals(-3, $this->ladder->getDaysUntilInspection());
    }

    public function testCalculateNextInspectionDate()
    {
        $this->ladder->setInspectionIntervalMonths(12);
        $baseDate = new DateTime('2024-01-15');
        
        $nextDate = $this->ladder->calculateNextInspectionDate($baseDate);
        
        $this->assertEquals('2025-01-15', $nextDate);
    }

    public function testCalculateNextInspectionDateWithDifferentInterval()
    {
        $this->ladder->setInspectionIntervalMonths(6);
        $baseDate = new DateTime('2024-06-15');
        
        $nextDate = $this->ladder->calculateNextInspectionDate($baseDate);
        
        $this->assertEquals('2024-12-15', $nextDate);
    }

    public function testCalculateNextInspectionDateWithoutBaseDate()
    {
        $this->ladder->setInspectionIntervalMonths(12);
        
        $nextDate = $this->ladder->calculateNextInspectionDate();
        $expectedDate = date('Y-m-d', strtotime('+12 months'));
        
        $this->assertEquals($expectedDate, $nextDate);
    }

    public function testGetDescription()
    {
        $ladder = new Ladder([
            'manufacturer' => 'Hailo',
            'model' => 'ProfiStep',
            'ladder_type' => 'Stehleiter',
            'height_cm' => 200,
            'material' => 'Aluminium'
        ]);
        
        $expected = 'Hailo - ProfiStep - Stehleiter - 200cm - Aluminium';
        $this->assertEquals($expected, $ladder->getDescription());
    }

    public function testGetDescriptionWithoutModel()
    {
        $ladder = new Ladder([
            'manufacturer' => 'Zarges',
            'ladder_type' => 'Anlegeleiter',
            'height_cm' => 300,
            'material' => 'Aluminium'
        ]);
        
        $expected = 'Zarges - Anlegeleiter - 300cm - Aluminium';
        $this->assertEquals($expected, $ladder->getDescription());
    }

    // ===== ARRAY/JSON KONVERTIERUNG TESTS =====

    public function testToArray()
    {
        $ladder = new Ladder($this->validLadderData);
        $array = $ladder->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals('TEST-001', $array['ladder_number']);
        $this->assertEquals('Test Manufacturer', $array['manufacturer']);
        $this->assertEquals('Test Model', $array['model']);
        $this->assertEquals('Stehleiter', $array['ladder_type']);
        $this->assertEquals('Aluminium', $array['material']);
        $this->assertEquals(150, $array['max_load_kg']);
        $this->assertEquals(200, $array['height_cm']);
        $this->assertEquals('2023-01-15', $array['purchase_date']);
        $this->assertEquals('Test Location', $array['location']);
        $this->assertEquals('Test Department', $array['department']);
        $this->assertEquals('Test Person', $array['responsible_person']);
        $this->assertEquals('SN-123456', $array['serial_number']);
        $this->assertEquals('Test notes', $array['notes']);
        $this->assertEquals('active', $array['status']);
        $this->assertEquals('2024-01-15', $array['next_inspection_date']);
        $this->assertEquals(12, $array['inspection_interval_months']);
        $this->assertNull($array['id']);
        $this->assertNull($array['created_at']);
        $this->assertNull($array['updated_at']);
    }

    public function testToJson()
    {
        $ladder = new Ladder($this->validLadderData);
        $json = $ladder->toJson();
        
        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertEquals('TEST-001', $decoded['ladder_number']);
        $this->assertEquals('Test Manufacturer', $decoded['manufacturer']);
    }

    public function testFillFromArray()
    {
        $data = [
            'ladder_number' => 'FILL-001',
            'manufacturer' => 'Fill Manufacturer',
            'ladder_type' => 'Mehrzweckleiter',
            'height_cm' => 250
        ];
        
        $this->ladder->fillFromArray($data);
        
        $this->assertEquals('FILL-001', $this->ladder->getLadderNumber());
        $this->assertEquals('Fill Manufacturer', $this->ladder->getManufacturer());
        $this->assertEquals('Mehrzweckleiter', $this->ladder->getLadderType());
        $this->assertEquals(250, $this->ladder->getHeightCm());
    }

    // ===== EDGE CASES TESTS =====

    public function testEmptyStringHandling()
    {
        $this->ladder->setModel('');
        $this->assertNull($this->ladder->getModel());
        
        $this->ladder->setDepartment('');
        $this->assertNull($this->ladder->getDepartment());
        
        $this->ladder->setResponsiblePerson('');
        $this->assertNull($this->ladder->getResponsiblePerson());
        
        $this->ladder->setSerialNumber('');
        $this->assertNull($this->ladder->getSerialNumber());
        
        $this->ladder->setNotes('');
        $this->assertNull($this->ladder->getNotes());
    }

    public function testWhitespaceOnlyStringHandling()
    {
        $this->ladder->setModel('   ');
        $this->assertNull($this->ladder->getModel());
        
        $this->ladder->setDepartment('   ');
        $this->assertNull($this->ladder->getDepartment());
    }

    public function testDateEdgeCases()
    {
        // Schaltjahr-Test
        $this->ladder->setPurchaseDate('2024-02-29');
        $this->assertEquals('2024-02-29', $this->ladder->getPurchaseDate());
        
        // Kein Schaltjahr
        $this->expectException(InvalidArgumentException::class);
        $this->ladder->setPurchaseDate('2023-02-29');
    }

    public function testLargeNumbers()
    {
        $this->ladder->setMaxLoadKg(999999);
        $this->assertEquals(999999, $this->ladder->getMaxLoadKg());
        
        $this->ladder->setHeightCm(999999);
        $this->assertEquals(999999, $this->ladder->getHeightCm());
        
        $this->ladder->setInspectionIntervalMonths(999);
        $this->assertEquals(999, $this->ladder->getInspectionIntervalMonths());
    }

    // ===== KONSTANTEN TESTS =====

    public function testLadderTypesConstant()
    {
        $expectedTypes = [
            'Anlegeleiter',
            'Stehleiter', 
            'Mehrzweckleiter',
            'Podestleiter',
            'Schiebeleiter'
        ];
        
        $this->assertEquals($expectedTypes, Ladder::LADDER_TYPES);
    }

    public function testMaterialsConstant()
    {
        $expectedMaterials = [
            'Aluminium',
            'Holz',
            'Fiberglas',
            'Stahl'
        ];
        
        $this->assertEquals($expectedMaterials, Ladder::MATERIALS);
    }

    public function testStatusesConstant()
    {
        $expectedStatuses = [
            'active',
            'inactive',
            'defective',
            'disposed'
        ];
        
        $this->assertEquals($expectedStatuses, Ladder::STATUSES);
    }
}
