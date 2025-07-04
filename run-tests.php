#!/usr/bin/env php
<?php
/**
 * Test-Runner für das Leiterprüfung-System
 * 
 * Führt PHPUnit-Tests aus und generiert Reports
 * 
 * Verwendung:
 * php run-tests.php [options]
 * 
 * Optionen:
 * --unit          Nur Unit-Tests ausführen
 * --integration   Nur Integration-Tests ausführen
 * --functional    Nur Funktionale Tests ausführen
 * --coverage      Code-Coverage generieren
 * --verbose       Ausführliche Ausgabe
 * --help          Diese Hilfe anzeigen
 */

// Farben für Terminal-Ausgabe
class Colors {
    const RED = "\033[31m";
    const GREEN = "\033[32m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const MAGENTA = "\033[35m";
    const CYAN = "\033[36m";
    const WHITE = "\033[37m";
    const RESET = "\033[0m";
    const BOLD = "\033[1m";
}

class TestRunner {
    private $options = [];
    private $startTime;
    
    public function __construct($argv) {
        $this->parseArguments($argv);
        $this->startTime = microtime(true);
    }
    
    private function parseArguments($argv) {
        $this->options = [
            'unit' => false,
            'integration' => false,
            'functional' => false,
            'coverage' => false,
            'verbose' => false,
            'help' => false
        ];
        
        foreach ($argv as $arg) {
            switch ($arg) {
                case '--unit':
                    $this->options['unit'] = true;
                    break;
                case '--integration':
                    $this->options['integration'] = true;
                    break;
                case '--functional':
                    $this->options['functional'] = true;
                    break;
                case '--coverage':
                    $this->options['coverage'] = true;
                    break;
                case '--verbose':
                    $this->options['verbose'] = true;
                    break;
                case '--help':
                    $this->options['help'] = true;
                    break;
            }
        }
    }
    
    public function run() {
        $this->printHeader();
        
        if ($this->options['help']) {
            $this->printHelp();
            return;
        }
        
        $this->checkRequirements();
        $this->setupDirectories();
        
        if ($this->options['unit']) {
            $this->runUnitTests();
        } elseif ($this->options['integration']) {
            $this->runIntegrationTests();
        } elseif ($this->options['functional']) {
            $this->runFunctionalTests();
        } else {
            $this->runAllTests();
        }
        
        $this->printSummary();
    }
    
    private function printHeader() {
        echo Colors::BOLD . Colors::CYAN . "\n";
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║                    LEITERPRÜFUNG TEST-SUITE                 ║\n";
        echo "║                                                              ║\n";
        echo "║  Umfassende Tests für das Leiterprüfung-Management-System   ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n";
        echo Colors::RESET . "\n";
    }
    
    private function printHelp() {
        echo Colors::YELLOW . "Verwendung:\n" . Colors::RESET;
        echo "  php run-tests.php [optionen]\n\n";
        
        echo Colors::YELLOW . "Optionen:\n" . Colors::RESET;
        echo "  --unit          Nur Unit-Tests ausführen\n";
        echo "  --integration   Nur Integration-Tests ausführen\n";
        echo "  --functional    Nur Funktionale Tests ausführen\n";
        echo "  --coverage      Code-Coverage generieren\n";
        echo "  --verbose       Ausführliche Ausgabe\n";
        echo "  --help          Diese Hilfe anzeigen\n\n";
        
        echo Colors::YELLOW . "Beispiele:\n" . Colors::RESET;
        echo "  php run-tests.php                    # Alle Tests ausführen\n";
        echo "  php run-tests.php --unit             # Nur Unit-Tests\n";
        echo "  php run-tests.php --coverage         # Mit Code-Coverage\n";
        echo "  php run-tests.php --verbose          # Ausführliche Ausgabe\n\n";
    }
    
    private function checkRequirements() {
        echo Colors::BLUE . "🔍 Überprüfe Anforderungen...\n" . Colors::RESET;
        
        // PHP-Version prüfen
        if (version_compare(PHP_VERSION, '8.0.0', '<')) {
            $this->error("PHP 8.0 oder höher erforderlich. Aktuelle Version: " . PHP_VERSION);
        }
        
        // PHPUnit prüfen
        if (!$this->commandExists('phpunit')) {
            $this->error("PHPUnit ist nicht installiert oder nicht im PATH verfügbar");
        }
        
        // SQLite-Extension prüfen
        if (!extension_loaded('sqlite3')) {
            $this->error("SQLite3-Extension ist nicht verfügbar");
        }
        
        // PDO-Extension prüfen
        if (!extension_loaded('pdo')) {
            $this->error("PDO-Extension ist nicht verfügbar");
        }
        
        echo Colors::GREEN . "✓ Alle Anforderungen erfüllt\n\n" . Colors::RESET;
    }
    
    private function setupDirectories() {
        $directories = [
            'tests/logs',
            'tests/coverage',
            'tests/coverage/html',
            'tests/coverage/xml'
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    private function runUnitTests() {
        echo Colors::MAGENTA . "🧪 Führe Unit-Tests aus...\n" . Colors::RESET;
        $this->executePhpUnit('--testsuite="Unit Tests"');
    }
    
    private function runIntegrationTests() {
        echo Colors::MAGENTA . "🔗 Führe Integration-Tests aus...\n" . Colors::RESET;
        $this->executePhpUnit('--testsuite="Integration Tests"');
    }
    
    private function runFunctionalTests() {
        echo Colors::MAGENTA . "⚙️ Führe Funktionale Tests aus...\n" . Colors::RESET;
        $this->executePhpUnit('--testsuite="Functional Tests"');
    }
    
    private function runAllTests() {
        echo Colors::MAGENTA . "🚀 Führe alle Tests aus...\n" . Colors::RESET;
        
        // Unit-Tests
        echo Colors::CYAN . "\n--- Unit-Tests ---\n" . Colors::RESET;
        $this->executePhpUnit('--testsuite="Unit Tests"');
        
        // Integration-Tests
        echo Colors::CYAN . "\n--- Integration-Tests ---\n" . Colors::RESET;
        $this->executePhpUnit('--testsuite="Integration Tests"');
        
        // Funktionale Tests
        echo Colors::CYAN . "\n--- Funktionale Tests ---\n" . Colors::RESET;
        $this->executePhpUnit('--testsuite="Functional Tests"');
    }
    
    private function executePhpUnit($options = '') {
        $command = 'phpunit';
        
        if ($this->options['coverage']) {
            $command .= ' --coverage-html tests/coverage/html';
            $command .= ' --coverage-text';
        }
        
        if ($this->options['verbose']) {
            $command .= ' --verbose';
        }
        
        if ($options) {
            $command .= ' ' . $options;
        }
        
        echo Colors::YELLOW . "Ausführung: $command\n" . Colors::RESET;
        
        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);
        
        foreach ($output as $line) {
            if (strpos($line, 'OK') !== false) {
                echo Colors::GREEN . $line . "\n" . Colors::RESET;
            } elseif (strpos($line, 'FAILURES') !== false || strpos($line, 'ERRORS') !== false) {
                echo Colors::RED . $line . "\n" . Colors::RESET;
            } elseif (strpos($line, 'Tests:') !== false) {
                echo Colors::BLUE . $line . "\n" . Colors::RESET;
            } else {
                echo $line . "\n";
            }
        }
        
        if ($returnCode !== 0) {
            echo Colors::RED . "⚠️ Tests fehlgeschlagen (Exit-Code: $returnCode)\n" . Colors::RESET;
        }
        
        return $returnCode === 0;
    }
    
    private function printSummary() {
        $duration = microtime(true) - $this->startTime;
        
        echo Colors::BOLD . Colors::CYAN . "\n";
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║                        TEST-ZUSAMMENFASSUNG                 ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n";
        echo Colors::RESET;
        
        echo sprintf("⏱️  Ausführungszeit: %.2f Sekunden\n", $duration);
        
        if ($this->options['coverage']) {
            echo "📊 Code-Coverage-Report: tests/coverage/html/index.html\n";
        }
        
        echo "📋 Test-Logs: tests/logs/\n";
        
        echo Colors::GREEN . "\n✅ Test-Ausführung abgeschlossen!\n" . Colors::RESET;
        
        // Zusätzliche Informationen
        echo Colors::YELLOW . "\nVerfügbare Test-Kategorien:\n" . Colors::RESET;
        echo "• Unit-Tests: Testen einzelne Klassen und Methoden\n";
        echo "• Integration-Tests: Testen Zusammenspiel zwischen Komponenten\n";
        echo "• Funktionale Tests: Testen komplette Workflows\n\n";
        
        echo Colors::YELLOW . "Nächste Schritte:\n" . Colors::RESET;
        echo "• Code-Coverage analysieren: tests/coverage/html/index.html\n";
        echo "• Test-Logs überprüfen: tests/logs/\n";
        echo "• Bei Fehlern: Tests mit --verbose ausführen\n\n";
    }
    
    private function commandExists($command) {
        $whereIsCommand = (PHP_OS == 'WINNT') ? 'where' : 'which';
        $process = proc_open(
            "$whereIsCommand $command",
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w']
            ],
            $pipes
        );
        
        if ($process !== false) {
            $stdout = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
            return $stdout != '';
        }
        
        return false;
    }
    
    private function error($message) {
        echo Colors::RED . "❌ Fehler: $message\n" . Colors::RESET;
        exit(1);
    }
}

// Script ausführen
if (php_sapi_name() === 'cli') {
    $runner = new TestRunner($argv);
    $runner->run();
} else {
    echo "Dieses Script kann nur über die Kommandozeile ausgeführt werden.\n";
    exit(1);
}
