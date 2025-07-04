# Test-Suite für das Leiterprüfung-System

Umfassende Test-Suite für das Leiterprüfung-Management-System mit PHPUnit.

## 📋 Übersicht

Diese Test-Suite bietet vollständige Abdeckung aller Systemkomponenten:

- **Unit-Tests**: Testen einzelne Klassen und Methoden isoliert
- **Integration-Tests**: Testen das Zusammenspiel zwischen Komponenten
- **Funktionale Tests**: Testen komplette Workflows und Geschäftsprozesse

## 🏗️ Struktur

```
tests/
├── setup/
│   └── TestDatabase.php          # Test-Datenbank Setup (SQLite In-Memory)
├── unit/
│   ├── LadderTest.php           # Tests für Ladder-Model
│   ├── InspectionTest.php       # Tests für Inspection-Model
│   └── AuthTest.php             # Tests für Authentifizierung
├── integration/
│   └── DatabaseTest.php        # Datenbank-Integration Tests
├── functional/
│   ├── LoginTest.php           # Login-Workflow Tests
│   └── CrudTest.php            # CRUD-Operations Tests
├── coverage/                   # Code-Coverage Reports
├── logs/                      # Test-Logs
└── README.md                  # Diese Datei
```

## 🚀 Schnellstart

### Voraussetzungen

- PHP 8.0 oder höher
- PHPUnit (installiert via Composer oder global)
- SQLite3-Extension
- PDO-Extension

### Installation

```bash
# PHPUnit installieren (falls nicht vorhanden)
composer require --dev phpunit/phpunit

# Oder global installieren
composer global require phpunit/phpunit
```

### Tests ausführen

```bash
# Alle Tests ausführen
php run-tests.php

# Nur bestimmte Test-Kategorien
php run-tests.php --unit
php run-tests.php --integration
php run-tests.php --functional

# Mit Code-Coverage
php run-tests.php --coverage

# Ausführliche Ausgabe
php run-tests.php --verbose

# Hilfe anzeigen
php run-tests.php --help
```

### Direkte PHPUnit-Ausführung

```bash
# Alle Tests
phpunit

# Bestimmte Test-Suite
phpunit --testsuite="Unit Tests"
phpunit --testsuite="Integration Tests"
phpunit --testsuite="Functional Tests"

# Mit Coverage
phpunit --coverage-html tests/coverage/html
```

## 📊 Test-Kategorien

### Unit-Tests

Testen einzelne Klassen isoliert mit Mocking externer Abhängigkeiten:

- **LadderTest.php**: 
  - Ladder-Model Validierung
  - Getter/Setter Funktionalität
  - Geschäftslogik (needsInspection, etc.)
  - Edge Cases und Fehlerbehandlung

- **InspectionTest.php**:
  - Inspection-Model mit Unveränderlichkeit
  - InspectionItem-Verwaltung
  - Ergebnis-Berechnung
  - Validierung und Constraints

- **AuthTest.php**:
  - User-Model LDAP-Integration
  - LdapAuth-Klasse (gemockt)
  - Gruppen- und Berechtigungstests
  - Session-Management

### Integration-Tests

Testen das Zusammenspiel zwischen Komponenten:

- **DatabaseTest.php**:
  - Datenbankverbindung und Transaktionen
  - CRUD-Operationen für alle Models
  - Foreign Key Constraints
  - Performance bei größeren Datenmengen
  - JSON-Datenhandling

### Funktionale Tests

Testen komplette Workflows:

- **LoginTest.php**:
  - LDAP-Login-Prozess
  - Session-Management
  - Berechtigungsprüfungen
  - Sicherheitsaspekte (Session-Hijacking, Brute-Force)

- **CrudTest.php**:
  - Vollständige Leiter-Verwaltung
  - Prüfungs-Workflows
  - Komplexe Geschäftsprozesse
  - Datenvalidierung im Web-Interface

## 🛠️ Test-Datenbank

Die Test-Suite verwendet eine SQLite In-Memory-Datenbank:

- **Isolation**: Jeder Test läuft in einer sauberen Umgebung
- **Performance**: In-Memory für schnelle Ausführung
- **Fixtures**: Vordefinierte Testdaten verfügbar
- **Reset**: Automatische Bereinigung nach jedem Test

### TestDatabase-Klasse

```php
// Test-Datenbank Setup
$testDb = TestDatabase::getInstance();
$testDb->resetDatabase();

// Test-Daten erstellen
$ladderId = $testDb->createTestLadder([
    'ladder_number' => 'TEST-001',
    'manufacturer' => 'Test Manufacturer'
]);

// Bereinigung
$testDb->cleanupTestData();
```

## 📈 Code-Coverage

Code-Coverage-Reports werden in verschiedenen Formaten generiert:

- **HTML**: `tests/coverage/html/index.html` (detaillierte Ansicht)
- **Text**: `tests/coverage/coverage.txt` (Konsolen-Output)
- **XML**: `tests/coverage/xml/` (für CI/CD-Integration)

### Coverage-Ziele

- **Gesamt**: > 80%
- **Models**: > 90%
- **Controllers**: > 75%
- **Utilities**: > 85%

## 🔧 Konfiguration

### phpunit.xml

Zentrale Konfiguration für PHPUnit:

```xml
<phpunit bootstrap="tests/setup/TestDatabase.php">
    <testsuites>
        <testsuite name="Unit Tests">
            <directory>tests/unit</directory>
        </testsuite>
        <!-- ... weitere Suites ... -->
    </testsuites>
</phpunit>
```

### Environment-Variablen

Test-spezifische Konfiguration:

```xml
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
    <env name="LDAP_ENABLED" value="false"/>
</php>
```

## 🎯 Best Practices

### Test-Struktur

```php
class ExampleTest extends TestCase
{
    private TestDatabase $testDb;
    
    protected function setUp(): void
    {
        $this->testDb = TestDatabase::getInstance();
        $this->testDb->resetDatabase();
    }
    
    protected function tearDown(): void
    {
        $this->testDb->cleanupTestData();
    }
    
    public function testSomething(): void
    {
        // Arrange
        $data = ['key' => 'value'];
        
        // Act
        $result = $this->performAction($data);
        
        // Assert
        $this->assertEquals('expected', $result);
    }
}
```

### Naming Conventions

- **Test-Klassen**: `{ClassName}Test.php`
- **Test-Methoden**: `test{MethodName}()` oder `test{Scenario}()`
- **Deskriptive Namen**: `testUserCannotLoginWithInvalidCredentials()`

### Assertions

```php
// Basis-Assertions
$this->assertTrue($condition);
$this->assertEquals($expected, $actual);
$this->assertInstanceOf(ClassName::class, $object);

// Array-Assertions
$this->assertCount(5, $array);
$this->assertContains('value', $array);

// Exception-Assertions
$this->expectException(InvalidArgumentException::class);
$this->expectExceptionMessage('Expected message');
```

## 🚨 Troubleshooting

### Häufige Probleme

1. **PHPUnit nicht gefunden**:
   ```bash
   composer install
   # oder
   composer global require phpunit/phpunit
   ```

2. **SQLite-Extension fehlt**:
   ```bash
   # Ubuntu/Debian
   sudo apt-get install php-sqlite3
   
   # Windows (in php.ini)
   extension=sqlite3
   ```

3. **Memory-Limit-Fehler**:
   ```bash
   php -d memory_limit=512M run-tests.php
   ```

4. **Permission-Fehler**:
   ```bash
   chmod +x run-tests.php
   chmod -R 755 tests/
   ```

### Debug-Modus

```bash
# Ausführliche Ausgabe
php run-tests.php --verbose

# Einzelnen Test debuggen
phpunit --filter testSpecificMethod

# Mit Xdebug
php -d xdebug.mode=debug run-tests.php
```

## 📝 Logs

Test-Logs werden in `tests/logs/` gespeichert:

- `junit.xml`: JUnit-Format für CI/CD
- `teamcity.txt`: TeamCity-Format
- Fehler-Logs bei fehlgeschlagenen Tests

## 🔄 CI/CD-Integration

### GitHub Actions

```yaml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
          extensions: sqlite3, pdo
      - name: Install dependencies
        run: composer install
      - name: Run tests
        run: php run-tests.php --coverage
```

### Docker

```dockerfile
FROM php:8.1-cli
RUN docker-php-ext-install pdo_sqlite
COPY . /app
WORKDIR /app
RUN composer install
CMD ["php", "run-tests.php"]
```

## 📚 Weitere Ressourcen

- [PHPUnit Dokumentation](https://phpunit.de/documentation.html)
- [Test-Driven Development](https://en.wikipedia.org/wiki/Test-driven_development)
- [PHP Testing Best Practices](https://phpunit.de/best-practices.html)

## 🤝 Beitragen

1. Tests für neue Features hinzufügen
2. Bestehende Tests bei Änderungen aktualisieren
3. Code-Coverage über 80% halten
4. Dokumentation aktualisieren

## 📄 Lizenz

Diese Test-Suite ist Teil des Leiterprüfung-Systems und unterliegt derselben Lizenz.
