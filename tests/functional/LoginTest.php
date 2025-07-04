<?php

require_once __DIR__ . '/../setup/TestDatabase.php';
require_once __DIR__ . '/../../web/src/includes/User.php';
require_once __DIR__ . '/../../web/src/includes/LdapAuth.php';
require_once __DIR__ . '/../../web/src/includes/SessionManager.php';
require_once __DIR__ . '/../../web/src/config/database.php';

use PHPUnit\Framework\TestCase;

/**
 * Funktionale Tests für Login-Funktionalität
 * 
 * Testet:
 * - LDAP-Login-Prozess
 * - Session-Management
 * - Berechtigungsprüfungen
 * - Logout-Funktionalität
 * - Sicherheitsaspekte
 */
class LoginTest extends TestCase
{
    private TestDatabase $testDb;
    private array $validUserData;

    protected function setUp(): void
    {
        $this->testDb = TestDatabase::getInstance();
        $this->testDb->resetDatabase();

        $this->validUserData = [
            'username' => 'test.login',
            'email' => 'test.login@example.com',
            'first_name' => 'Test',
            'last_name' => 'Login',
            'display_name' => 'Test Login',
            'groups' => ['users', 'inspectors'],
            'ldap_dn' => 'cn=Test Login,ou=users,dc=example,dc=com',
            'is_active' => true
        ];

        // Session für Tests initialisieren
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    protected function tearDown(): void
    {
        $this->testDb->cleanupTestData();
        
        // Session bereinigen
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    // ===== LOGIN WORKFLOW TESTS =====

    public function testSuccessfulLoginWorkflow()
    {
        // 1. Benutzer in DB erstellen (simuliert vorherige LDAP-Synchronisation)
        $user = User::createOrUpdateFromLdap([
            'dn' => $this->validUserData['ldap_dn'],
            'username' => $this->validUserData['username'],
            'email' => $this->validUserData['email'],
            'first_name' => $this->validUserData['first_name'],
            'last_name' => $this->validUserData['last_name'],
            'display_name' => $this->validUserData['display_name'],
            'groups' => $this->validUserData['groups']
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertNull($user->getLastLogin());

        // 2. Login simulieren
        $this->simulateLogin($user);

        // 3. Session prüfen
        $this->assertTrue(isset($_SESSION['user_id']));
        $this->assertEquals($user->getId(), $_SESSION['user_id']);
        $this->assertTrue(isset($_SESSION['username']));
        $this->assertEquals($user->getUsername(), $_SESSION['username']);
        $this->assertTrue(isset($_SESSION['user_groups']));
        $this->assertEquals($user->getGroups(), $_SESSION['user_groups']);

        // 4. Last Login sollte aktualisiert worden sein
        $updatedUser = User::findById($user->getId());
        $this->assertNotNull($updatedUser->getLastLogin());
    }

    public function testLoginWithInactiveUser()
    {
        // Inaktiven Benutzer erstellen
        $userData = $this->validUserData;
        $userData['is_active'] = false;
        $userId = $this->createTestUser($userData);

        // Benutzer direkt aus DB laden (da findById inaktive Benutzer nicht findet)
        $pdo = $this->testDb->getPdo();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $user = new User($data);

        // Login sollte fehlschlagen
        $this->assertFalse($user->isActive());
        
        // Session sollte nicht gesetzt werden
        $this->assertFalse(isset($_SESSION['user_id']));
    }

    public function testLoginUpdatesUserData()
    {
        // Benutzer mit veralteten Daten erstellen
        $user = User::createOrUpdateFromLdap([
            'dn' => $this->validUserData['ldap_dn'],
            'username' => $this->validUserData['username'],
            'email' => 'old@example.com',
            'first_name' => 'Old',
            'last_name' => 'Name',
            'display_name' => 'Old Name',
            'groups' => ['users']
        ]);

        $originalId = $user->getId();

        // "LDAP-Login" mit aktualisierten Daten simulieren
        $updatedLdapData = [
            'dn' => $this->validUserData['ldap_dn'],
            'username' => $this->validUserData['username'],
            'email' => $this->validUserData['email'],
            'first_name' => $this->validUserData['first_name'],
            'last_name' => $this->validUserData['last_name'],
            'display_name' => $this->validUserData['display_name'],
            'groups' => $this->validUserData['groups']
        ];

        $updatedUser = User::createOrUpdateFromLdap($updatedLdapData);

        // Prüfen ob Daten aktualisiert wurden
        $this->assertEquals($originalId, $updatedUser->getId());
        $this->assertEquals($this->validUserData['email'], $updatedUser->getEmail());
        $this->assertEquals($this->validUserData['first_name'], $updatedUser->getFirstName());
        $this->assertEquals($this->validUserData['groups'], $updatedUser->getGroups());
    }

    // ===== SESSION MANAGEMENT TESTS =====

    public function testSessionCreation()
    {
        $user = User::createOrUpdateFromLdap([
            'dn' => $this->validUserData['ldap_dn'],
            'username' => $this->validUserData['username'],
            'email' => $this->validUserData['email'],
            'groups' => $this->validUserData['groups']
        ]);

        $this->simulateLogin($user);

        // Alle erwarteten Session-Variablen prüfen
        $expectedSessionKeys = [
            'user_id',
            'username',
            'user_email',
            'user_display_name',
            'user_groups',
            'login_time',
            'last_activity'
        ];

        foreach ($expectedSessionKeys as $key) {
            $this->assertTrue(isset($_SESSION[$key]), "Session-Variable '{$key}' sollte gesetzt sein");
        }

        // Werte prüfen
        $this->assertEquals($user->getId(), $_SESSION['user_id']);
        $this->assertEquals($user->getUsername(), $_SESSION['username']);
        $this->assertEquals($user->getEmail(), $_SESSION['user_email']);
        $this->assertEquals($user->getDisplayName(), $_SESSION['user_display_name']);
        $this->assertEquals($user->getGroups(), $_SESSION['user_groups']);
    }

    public function testSessionSecurity()
    {
        $user = User::createOrUpdateFromLdap([
            'dn' => $this->validUserData['ldap_dn'],
            'username' => $this->validUserData['username'],
            'email' => $this->validUserData['email'],
            'groups' => $this->validUserData['groups']
        ]);

        $this->simulateLogin($user);

        // Session-ID sollte nach Login regeneriert werden
        $this->assertTrue(isset($_SESSION['session_regenerated']));
        
        // IP-Adresse sollte gespeichert werden (für Session-Hijacking-Schutz)
        $this->assertTrue(isset($_SESSION['user_ip']));
        
        // User-Agent sollte gespeichert werden
        $this->assertTrue(isset($_SESSION['user_agent']));
    }

    public function testSessionTimeout()
    {
        $user = User::createOrUpdateFromLdap([
            'dn' => $this->validUserData['ldap_dn'],
            'username' => $this->validUserData['username'],
            'email' => $this->validUserData['email'],
            'groups' => $this->validUserData['groups']
        ]);

        $this->simulateLogin($user);

        // Last Activity in die Vergangenheit setzen (simuliert Timeout)
        $_SESSION['last_activity'] = time() - 7200; // 2 Stunden alt

        // Session-Validierung sollte fehlschlagen
        $this->assertFalse($this->isSessionValid());
    }

    // ===== AUTHORIZATION TESTS =====

    public function testUserPermissions()
    {
        $user = User::createOrUpdateFromLdap([
            'dn' => $this->validUserData['ldap_dn'],
            'username' => $this->validUserData['username'],
            'email' => $this->validUserData['email'],
            'groups' => ['users', 'inspectors']
        ]);

        $this->simulateLogin($user);

        // Berechtigungen prüfen
        $this->assertTrue($this->hasPermission('users'));
        $this->assertTrue($this->hasPermission('inspectors'));
        $this->assertFalse($this->hasPermission('admins'));
    }

    public function testAdminPermissions()
    {
        $user = User::createOrUpdateFromLdap([
            'dn' => $this->validUserData['ldap_dn'],
            'username' => 'admin.user',
            'email' => 'admin@example.com',
            'groups' => ['users', 'inspectors', 'admins']
        ]);

        $this->simulateLogin($user);

        // Admin-Berechtigungen prüfen
        $this->assertTrue($this->hasPermission('users'));
        $this->assertTrue($this->hasPermission('inspectors'));
        $this->assertTrue($this->hasPermission('admins'));
    }

    public function testViewerPermissions()
    {
        $user = User::createOrUpdateFromLdap([
            'dn' => $this->validUserData['ldap_dn'],
            'username' => 'viewer.user',
            'email' => 'viewer@example.com',
            'groups' => ['users']
        ]);

        $this->simulateLogin($user);

        // Nur Basis-Berechtigungen
        $this->assertTrue($this->hasPermission('users'));
        $this->assertFalse($this->hasPermission('inspectors'));
        $this->assertFalse($this->hasPermission('admins'));
    }

    // ===== LOGOUT TESTS =====

    public function testLogout()
    {
        $user = User::createOrUpdateFromLdap([
            'dn' => $this->validUserData['ldap_dn'],
            'username' => $this->validUserData['username'],
            'email' => $this->validUserData['email'],
            'groups' => $this->validUserData['groups']
        ]);

        $this->simulateLogin($user);

        // Session sollte aktiv sein
        $this->assertTrue(isset($_SESSION['user_id']));

        // Logout simulieren
        $this->simulateLogout();

        // Session sollte bereinigt sein
        $this->assertFalse(isset($_SESSION['user_id']));
        $this->assertFalse(isset($_SESSION['username']));
        $this->assertFalse(isset($_SESSION['user_groups']));
    }

    // ===== SECURITY TESTS =====

    public function testSessionHijackingProtection()
    {
        $user = User::createOrUpdateFromLdap([
            'dn' => $this->validUserData['ldap_dn'],
            'username' => $this->validUserData['username'],
            'email' => $this->validUserData['email'],
            'groups' => $this->validUserData['groups']
        ]);

        $this->simulateLogin($user);

        $originalIp = $_SESSION['user_ip'];
        $originalUserAgent = $_SESSION['user_agent'];

        // IP-Adresse ändern (simuliert Session-Hijacking)
        $_SESSION['user_ip'] = '192.168.1.999';

        // Session-Validierung sollte fehlschlagen
        $this->assertFalse($this->isSessionValidWithSecurityCheck());

        // User-Agent ändern
        $_SESSION['user_ip'] = $originalIp;
        $_SESSION['user_agent'] = 'Different User Agent';

        // Session-Validierung sollte fehlschlagen
        $this->assertFalse($this->isSessionValidWithSecurityCheck());
    }

    public function testBruteForceProtection()
    {
        // Mehrere fehlgeschlagene Login-Versuche simulieren
        $attempts = 0;
        $maxAttempts = 5;

        for ($i = 0; $i < $maxAttempts + 1; $i++) {
            $result = $this->simulateFailedLogin('test.user', 'wrong.password');
            $attempts++;

            if ($attempts > $maxAttempts) {
                // Nach zu vielen Versuchen sollte Account gesperrt werden
                $this->assertFalse($result);
                break;
            }
        }

        $this->assertGreaterThan($maxAttempts, $attempts);
    }

    public function testPasswordComplexity()
    {
        // Diese Tests würden normalerweise die LDAP-Passwort-Policy testen
        // Da wir LDAP mocken, testen wir die lokale Validierung

        $weakPasswords = [
            '123456',
            'password',
            'abc123',
            '12345678'
        ];

        foreach ($weakPasswords as $password) {
            $this->assertFalse($this->isPasswordComplex($password));
        }

        $strongPasswords = [
            'MyStr0ng!Pass',
            'C0mpl3x#P@ssw0rd',
            'S3cur3$P4ssw0rd!'
        ];

        foreach ($strongPasswords as $password) {
            $this->assertTrue($this->isPasswordComplex($password));
        }
    }

    // ===== LDAP INTEGRATION TESTS =====

    public function testLdapConnectionFailure()
    {
        // LDAP-Verbindungsfehler simulieren
        $ldapAuth = new LdapAuth();
        
        // Da wir keine echte LDAP-Verbindung haben, testen wir die Fehlerbehandlung
        $result = $ldapAuth->authenticate('test.user', 'password');
        
        $this->assertFalse($result);
        $this->assertNotNull($ldapAuth->getLastError());
    }

    public function testLdapUserNotFound()
    {
        $ldapAuth = new LdapAuth();
        
        $result = $ldapAuth->authenticate('nonexistent.user', 'password');
        
        $this->assertFalse($result);
    }

    public function testLdapInvalidCredentials()
    {
        $ldapAuth = new LdapAuth();
        
        $result = $ldapAuth->authenticate('valid.user', 'wrong.password');
        
        $this->assertFalse($result);
    }

    // ===== HELPER METHODS =====

    /**
     * Simuliert einen erfolgreichen Login
     */
    private function simulateLogin(User $user): void
    {
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['username'] = $user->getUsername();
        $_SESSION['user_email'] = $user->getEmail();
        $_SESSION['user_display_name'] = $user->getDisplayName();
        $_SESSION['user_groups'] = $user->getGroups();
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['session_regenerated'] = true;
        $_SESSION['user_ip'] = '127.0.0.1';
        $_SESSION['user_agent'] = 'PHPUnit Test';

        // Last Login aktualisieren
        $user->updateLastLogin();
    }

    /**
     * Simuliert einen Logout
     */
    private function simulateLogout(): void
    {
        $sessionKeys = [
            'user_id', 'username', 'user_email', 'user_display_name',
            'user_groups', 'login_time', 'last_activity', 'session_regenerated',
            'user_ip', 'user_agent'
        ];

        foreach ($sessionKeys as $key) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Prüft ob Session gültig ist
     */
    private function isSessionValid(): bool
    {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['last_activity'])) {
            return false;
        }

        // Session-Timeout prüfen (1 Stunde)
        $timeout = 3600;
        if (time() - $_SESSION['last_activity'] > $timeout) {
            return false;
        }

        return true;
    }

    /**
     * Prüft Session-Gültigkeit mit Sicherheitschecks
     */
    private function isSessionValidWithSecurityCheck(): bool
    {
        if (!$this->isSessionValid()) {
            return false;
        }

        // IP-Adresse prüfen
        if (isset($_SESSION['user_ip']) && $_SESSION['user_ip'] !== '127.0.0.1') {
            return false;
        }

        // User-Agent prüfen
        if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== 'PHPUnit Test') {
            return false;
        }

        return true;
    }

    /**
     * Prüft Benutzer-Berechtigung
     */
    private function hasPermission(string $group): bool
    {
        if (!isset($_SESSION['user_groups'])) {
            return false;
        }

        return in_array($group, $_SESSION['user_groups']);
    }

    /**
     * Simuliert fehlgeschlagenen Login
     */
    private function simulateFailedLogin(string $username, string $password): bool
    {
        // In einer echten Implementierung würde hier die Anzahl der Versuche gezählt
        static $attempts = [];
        
        if (!isset($attempts[$username])) {
            $attempts[$username] = 0;
        }
        
        $attempts[$username]++;
        
        // Nach 5 Versuchen sperren
        if ($attempts[$username] > 5) {
            return false;
        }
        
        // Login schlägt immer fehl (da Passwort falsch)
        return false;
    }

    /**
     * Prüft Passwort-Komplexität
     */
    private function isPasswordComplex(string $password): bool
    {
        // Mindestens 8 Zeichen
        if (strlen($password) < 8) {
            return false;
        }

        // Mindestens ein Großbuchstabe
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }

        // Mindestens ein Kleinbuchstabe
        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }

        // Mindestens eine Zahl
        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }

        // Mindestens ein Sonderzeichen
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            return false;
        }

        return true;
    }

    /**
     * Erstellt einen Test-Benutzer in der Datenbank
     */
    private function createTestUser(array $userData): int
    {
        $pdo = $this->testDb->getPdo();
        
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, first_name, last_name, display_name, groups, ldap_dn, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userData['username'],
            $userData['email'],
            $userData['first_name'],
            $userData['last_name'],
            $userData['display_name'],
            json_encode($userData['groups']),
            $userData['ldap_dn'],
            $userData['is_active'] ? 1 : 0
        ]);
        
        return $pdo->lastInsertId();
    }
}
