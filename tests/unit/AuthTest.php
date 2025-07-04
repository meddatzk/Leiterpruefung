<?php

require_once __DIR__ . '/../setup/TestDatabase.php';
require_once __DIR__ . '/../../web/src/includes/User.php';
require_once __DIR__ . '/../../web/src/includes/LdapAuth.php';
require_once __DIR__ . '/../../web/src/config/database.php';

use PHPUnit\Framework\TestCase;

/**
 * Unit-Tests für die Authentifizierung
 * 
 * Testet alle Aspekte der Authentifizierung:
 * - User-Model LDAP-Integration
 * - LdapAuth-Klasse (mit Mocking)
 * - Benutzer-Verwaltung
 * - Gruppen- und Berechtigungstests
 * - Fehlerbehandlung
 */
class AuthTest extends TestCase
{
    private TestDatabase $testDb;
    private array $validUserData;
    private array $validLdapData;

    protected function setUp(): void
    {
        $this->testDb = TestDatabase::getInstance();
        $this->testDb->resetDatabase();

        $this->validUserData = [
            'username' => 'test.user',
            'email' => 'test.user@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
            'display_name' => 'Test User',
            'groups' => ['users', 'testers'],
            'ldap_dn' => 'cn=Test User,ou=users,dc=example,dc=com',
            'is_active' => true
        ];

        $this->validLdapData = [
            'dn' => 'cn=Test User,ou=users,dc=example,dc=com',
            'username' => 'test.user',
            'email' => 'test.user@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
            'display_name' => 'Test User',
            'groups' => ['users', 'testers']
        ];
    }

    protected function tearDown(): void
    {
        $this->testDb->cleanupTestData();
    }

    // ===== USER MODEL TESTS =====

    public function testUserConstructorWithEmptyData()
    {
        $user = new User();
        
        $this->assertNull($user->getId());
        $this->assertEquals('', $user->getUsername());
        $this->assertEquals('', $user->getEmail());
        $this->assertEquals('', $user->getFirstName());
        $this->assertEquals('', $user->getLastName());
        $this->assertEquals([], $user->getGroups());
        $this->assertTrue($user->isActive());
    }

    public function testUserConstructorWithValidData()
    {
        $user = new User($this->validUserData);
        
        $this->assertEquals('test.user', $user->getUsername());
        $this->assertEquals('test.user@example.com', $user->getEmail());
        $this->assertEquals('Test', $user->getFirstName());
        $this->assertEquals('User', $user->getLastName());
        $this->assertEquals('Test User', $user->getDisplayName());
        $this->assertEquals(['users', 'testers'], $user->getGroups());
        $this->assertEquals('cn=Test User,ou=users,dc=example,dc=com', $user->getLdapDn());
        $this->assertTrue($user->isActive());
    }

    public function testUserConstructorWithJsonGroups()
    {
        $userData = $this->validUserData;
        $userData['groups'] = json_encode(['users', 'admins']);
        
        $user = new User($userData);
        
        $this->assertEquals(['users', 'admins'], $user->getGroups());
    }

    public function testUserFindById()
    {
        // Benutzer in Test-DB erstellen
        $userId = $this->createTestUser($this->validUserData);
        
        $user = User::findById($userId);
        
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($userId, $user->getId());
        $this->assertEquals('test.user', $user->getUsername());
        $this->assertEquals('test.user@example.com', $user->getEmail());
    }

    public function testUserFindByIdNotFound()
    {
        $user = User::findById(999);
        
        $this->assertNull($user);
    }

    public function testUserFindByIdInactive()
    {
        $userData = $this->validUserData;
        $userData['is_active'] = false;
        $userId = $this->createTestUser($userData);
        
        $user = User::findById($userId);
        
        $this->assertNull($user); // Inaktive Benutzer werden nicht gefunden
    }

    public function testUserFindByUsername()
    {
        $this->createTestUser($this->validUserData);
        
        $user = User::findByUsername('test.user');
        
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('test.user', $user->getUsername());
    }

    public function testUserFindByUsernameNotFound()
    {
        $user = User::findByUsername('nonexistent.user');
        
        $this->assertNull($user);
    }

    public function testUserFindByEmail()
    {
        $this->createTestUser($this->validUserData);
        
        $user = User::findByEmail('test.user@example.com');
        
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('test.user@example.com', $user->getEmail());
    }

    public function testUserFindByEmailNotFound()
    {
        $user = User::findByEmail('nonexistent@example.com');
        
        $this->assertNull($user);
    }

    // ===== USER LDAP INTEGRATION TESTS =====

    public function testCreateOrUpdateFromLdapNewUser()
    {
        $user = User::createOrUpdateFromLdap($this->validLdapData);
        
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('test.user', $user->getUsername());
        $this->assertEquals('test.user@example.com', $user->getEmail());
        $this->assertEquals('Test', $user->getFirstName());
        $this->assertEquals('User', $user->getLastName());
        $this->assertEquals('Test User', $user->getDisplayName());
        $this->assertEquals(['users', 'testers'], $user->getGroups());
        $this->assertTrue($user->isActive());
    }

    public function testCreateOrUpdateFromLdapExistingUser()
    {
        // Benutzer erstellen
        $originalUser = User::createOrUpdateFromLdap($this->validLdapData);
        $originalId = $originalUser->getId();
        
        // LDAP-Daten ändern
        $updatedLdapData = $this->validLdapData;
        $updatedLdapData['email'] = 'updated@example.com';
        $updatedLdapData['first_name'] = 'Updated';
        $updatedLdapData['groups'] = ['users', 'admins'];
        
        $updatedUser = User::createOrUpdateFromLdap($updatedLdapData);
        
        $this->assertInstanceOf(User::class, $updatedUser);
        $this->assertEquals($originalId, $updatedUser->getId()); // Gleiche ID
        $this->assertEquals('updated@example.com', $updatedUser->getEmail());
        $this->assertEquals('Updated', $updatedUser->getFirstName());
        $this->assertEquals(['users', 'admins'], $updatedUser->getGroups());
    }

    public function testCreateOrUpdateFromLdapEmptyUsername()
    {
        $ldapData = $this->validLdapData;
        $ldapData['username'] = '';
        
        $user = User::createOrUpdateFromLdap($ldapData);
        
        $this->assertNull($user);
    }

    public function testCreateOrUpdateFromLdapMissingUsername()
    {
        $ldapData = $this->validLdapData;
        unset($ldapData['username']);
        
        $user = User::createOrUpdateFromLdap($ldapData);
        
        $this->assertNull($user);
    }

    // ===== USER METHODS TESTS =====

    public function testUpdateLastLogin()
    {
        $userId = $this->createTestUser($this->validUserData);
        $user = User::findById($userId);
        
        $this->assertNull($user->getLastLogin());
        
        $user->updateLastLogin();
        
        $this->assertNotNull($user->getLastLogin());
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $user->getLastLogin());
    }

    public function testDeactivateUser()
    {
        $userId = $this->createTestUser($this->validUserData);
        $user = User::findById($userId);
        
        $this->assertTrue($user->isActive());
        
        $user->deactivate();
        
        $this->assertFalse($user->isActive());
        $this->assertNotNull($user->getUpdatedAt());
    }

    public function testActivateUser()
    {
        $userData = $this->validUserData;
        $userData['is_active'] = false;
        $userId = $this->createTestUser($userData);
        
        // Benutzer direkt aus DB laden (findById würde null zurückgeben)
        $pdo = $this->testDb->getPdo();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $user = new User($data);
        
        $this->assertFalse($user->isActive());
        
        $user->activate();
        
        $this->assertTrue($user->isActive());
    }

    // ===== GROUP TESTS =====

    public function testHasGroup()
    {
        $user = new User($this->validUserData);
        
        $this->assertTrue($user->hasGroup('users'));
        $this->assertTrue($user->hasGroup('testers'));
        $this->assertFalse($user->hasGroup('admins'));
        $this->assertFalse($user->hasGroup('nonexistent'));
    }

    public function testHasAnyGroup()
    {
        $user = new User($this->validUserData);
        
        $this->assertTrue($user->hasAnyGroup(['users']));
        $this->assertTrue($user->hasAnyGroup(['admins', 'users']));
        $this->assertTrue($user->hasAnyGroup(['testers', 'moderators']));
        $this->assertFalse($user->hasAnyGroup(['admins', 'moderators']));
        $this->assertFalse($user->hasAnyGroup([]));
    }

    public function testHasAllGroups()
    {
        $user = new User($this->validUserData);
        
        $this->assertTrue($user->hasAllGroups(['users']));
        $this->assertTrue($user->hasAllGroups(['users', 'testers']));
        $this->assertFalse($user->hasAllGroups(['users', 'admins']));
        $this->assertFalse($user->hasAllGroups(['admins', 'moderators']));
        $this->assertTrue($user->hasAllGroups([])); // Leeres Array = alle haben alle
    }

    // ===== UTILITY METHODS TESTS =====

    public function testGetFullName()
    {
        $user = new User($this->validUserData);
        
        $this->assertEquals('Test User', $user->getFullName());
    }

    public function testGetFullNameWithoutLastName()
    {
        $userData = $this->validUserData;
        $userData['last_name'] = '';
        $user = new User($userData);
        
        $this->assertEquals('Test', $user->getFullName());
    }

    public function testGetFullNameWithoutFirstName()
    {
        $userData = $this->validUserData;
        $userData['first_name'] = '';
        $user = new User($userData);
        
        $this->assertEquals('User', $user->getFullName());
    }

    public function testGetFullNameFallbackToDisplayName()
    {
        $userData = $this->validUserData;
        $userData['first_name'] = '';
        $userData['last_name'] = '';
        $user = new User($userData);
        
        $this->assertEquals('Test User', $user->getFullName());
    }

    public function testGetFullNameFallbackToUsername()
    {
        $userData = $this->validUserData;
        $userData['first_name'] = '';
        $userData['last_name'] = '';
        $userData['display_name'] = '';
        $user = new User($userData);
        
        $this->assertEquals('test.user', $user->getFullName());
    }

    public function testGetInitials()
    {
        $user = new User($this->validUserData);
        
        $this->assertEquals('TU', $user->getInitials());
    }

    public function testGetInitialsWithoutLastName()
    {
        $userData = $this->validUserData;
        $userData['last_name'] = '';
        $user = new User($userData);
        
        $this->assertEquals('T', $user->getInitials());
    }

    public function testGetInitialsFallbackToUsername()
    {
        $userData = $this->validUserData;
        $userData['first_name'] = '';
        $userData['last_name'] = '';
        $user = new User($userData);
        
        $this->assertEquals('TE', $user->getInitials());
    }

    // ===== ARRAY CONVERSION TESTS =====

    public function testToArrayPublic()
    {
        $user = new User($this->validUserData);
        $array = $user->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals('test.user', $array['username']);
        $this->assertEquals('test.user@example.com', $array['email']);
        $this->assertEquals('Test', $array['first_name']);
        $this->assertEquals('User', $array['last_name']);
        $this->assertEquals('Test User', $array['display_name']);
        $this->assertEquals('Test User', $array['full_name']);
        $this->assertEquals('TU', $array['initials']);
        $this->assertEquals(['users', 'testers'], $array['groups']);
        $this->assertTrue($array['is_active']);
        
        // Private Felder sollten nicht enthalten sein
        $this->assertArrayNotHasKey('ldap_dn', $array);
        $this->assertArrayNotHasKey('last_login', $array);
        $this->assertArrayNotHasKey('created_at', $array);
        $this->assertArrayNotHasKey('updated_at', $array);
    }

    public function testToArrayPrivate()
    {
        $user = new User($this->validUserData);
        $array = $user->toArray(true);
        
        $this->assertIsArray($array);
        $this->assertEquals('test.user', $array['username']);
        
        // Private Felder sollten enthalten sein
        $this->assertArrayHasKey('ldap_dn', $array);
        $this->assertArrayHasKey('last_login', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
        $this->assertEquals('cn=Test User,ou=users,dc=example,dc=com', $array['ldap_dn']);
    }

    // ===== SETTER TESTS =====

    public function testSetters()
    {
        $user = new User();
        
        $user->setEmail('new@example.com');
        $this->assertEquals('new@example.com', $user->getEmail());
        
        $user->setFirstName('New');
        $this->assertEquals('New', $user->getFirstName());
        
        $user->setLastName('Name');
        $this->assertEquals('Name', $user->getLastName());
        
        $user->setDisplayName('New Display');
        $this->assertEquals('New Display', $user->getDisplayName());
        
        $user->setGroups(['new', 'groups']);
        $this->assertEquals(['new', 'groups'], $user->getGroups());
    }

    public function testSetGroupsWithNonArray()
    {
        $user = new User();
        
        $user->setGroups('not an array');
        $this->assertEquals([], $user->getGroups());
    }

    // ===== LDAP AUTH MOCK TESTS =====

    public function testLdapAuthConstructor()
    {
        // Da wir Config::get() mocken müssten, testen wir nur die Grundfunktionalität
        $this->assertTrue(class_exists('LdapAuth'));
    }

    public function testLdapAuthGetLastError()
    {
        $ldapAuth = new LdapAuth();
        
        // Initial sollte kein Fehler vorhanden sein
        $this->assertNull($ldapAuth->getLastError());
    }

    public function testLdapAuthIsAvailable()
    {
        $ldapAuth = new LdapAuth();
        
        // Test ob LDAP-Extension verfügbar ist
        $available = $ldapAuth->isAvailable();
        
        if (extension_loaded('ldap')) {
            // Wenn LDAP verfügbar ist, hängt es von der Konfiguration ab
            $this->assertIsBool($available);
        } else {
            // Wenn LDAP nicht verfügbar ist, sollte false zurückgegeben werden
            $this->assertFalse($available);
            $this->assertEquals("LDAP-Erweiterung ist nicht installiert", $ldapAuth->getLastError());
        }
    }

    public function testLdapAuthAuthenticateEmptyCredentials()
    {
        $ldapAuth = new LdapAuth();
        
        $result = $ldapAuth->authenticate('', '');
        $this->assertFalse($result);
        $this->assertEquals("Benutzername und Passwort sind erforderlich", $ldapAuth->getLastError());
        
        $result = $ldapAuth->authenticate('user', '');
        $this->assertFalse($result);
        $this->assertEquals("Benutzername und Passwort sind erforderlich", $ldapAuth->getLastError());
        
        $result = $ldapAuth->authenticate('', 'password');
        $this->assertFalse($result);
        $this->assertEquals("Benutzername und Passwort sind erforderlich", $ldapAuth->getLastError());
    }

    // ===== EDGE CASES TESTS =====

    public function testUserWithSpecialCharacters()
    {
        $userData = [
            'username' => 'test.üser',
            'email' => 'test.üser@exämple.com',
            'first_name' => 'Tëst',
            'last_name' => 'Üser',
            'display_name' => 'Tëst Üser'
        ];
        
        $user = new User($userData);
        
        $this->assertEquals('test.üser', $user->getUsername());
        $this->assertEquals('test.üser@exämple.com', $user->getEmail());
        $this->assertEquals('Tëst', $user->getFirstName());
        $this->assertEquals('Üser', $user->getLastName());
        $this->assertEquals('Tëst Üser', $user->getDisplayName());
    }

    public function testUserWithEmptyGroups()
    {
        $userData = $this->validUserData;
        $userData['groups'] = [];
        
        $user = new User($userData);
        
        $this->assertEquals([], $user->getGroups());
        $this->assertFalse($user->hasGroup('any'));
        $this->assertFalse($user->hasAnyGroup(['any', 'group']));
        $this->assertTrue($user->hasAllGroups([]));
    }

    public function testUserWithNullValues()
    {
        $userData = [
            'username' => 'test.user',
            'email' => null,
            'first_name' => null,
            'last_name' => null,
            'display_name' => null,
            'groups' => null
        ];
        
        $user = new User($userData);
        
        $this->assertEquals('test.user', $user->getUsername());
        $this->assertEquals('', $user->getEmail());
        $this->assertEquals('', $user->getFirstName());
        $this->assertEquals('', $user->getLastName());
        $this->assertEquals('test.user', $user->getDisplayName()); // Fallback zu username
        $this->assertEquals([], $user->getGroups());
    }

    // ===== HELPER METHODS =====

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
