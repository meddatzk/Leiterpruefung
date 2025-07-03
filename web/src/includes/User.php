<?php
/**
 * Benutzer-Model-Klasse
 * Verwaltet Benutzerdaten und lokalen Cache
 */

class User
{
    private $id;
    private $username;
    private $email;
    private $firstName;
    private $lastName;
    private $displayName;
    private $groups;
    private $ldapDn;
    private $lastLogin;
    private $createdAt;
    private $updatedAt;
    private $isActive;

    private $db;

    public function __construct($data = [])
    {
        $this->db = Database::getInstance();
        
        if (!empty($data)) {
            $this->populate($data);
        }
    }

    /**
     * Füllt das Objekt mit Daten
     * 
     * @param array $data Benutzerdaten
     */
    private function populate($data)
    {
        $this->id = $data['id'] ?? null;
        $this->username = $data['username'] ?? '';
        $this->email = $data['email'] ?? '';
        $this->firstName = $data['first_name'] ?? '';
        $this->lastName = $data['last_name'] ?? '';
        $this->displayName = $data['display_name'] ?? '';
        $this->groups = is_array($data['groups']) ? $data['groups'] : json_decode($data['groups'] ?? '[]', true);
        $this->ldapDn = $data['ldap_dn'] ?? $data['dn'] ?? '';
        $this->lastLogin = $data['last_login'] ?? null;
        $this->createdAt = $data['created_at'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? null;
        $this->isActive = $data['is_active'] ?? true;
    }

    /**
     * Lädt einen Benutzer anhand der ID
     * 
     * @param int $id Benutzer-ID
     * @return User|null
     */
    public static function findById($id)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1");
        $stmt->execute([$id]);
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        return new self($data);
    }

    /**
     * Lädt einen Benutzer anhand des Benutzernamens
     * 
     * @param string $username Benutzername
     * @return User|null
     */
    public static function findByUsername($username)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        return new self($data);
    }

    /**
     * Lädt einen Benutzer anhand der E-Mail
     * 
     * @param string $email E-Mail-Adresse
     * @return User|null
     */
    public static function findByEmail($email)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        return new self($data);
    }

    /**
     * Erstellt oder aktualisiert einen Benutzer aus LDAP-Daten
     * 
     * @param array $ldapData LDAP-Benutzerdaten
     * @return User|null
     */
    public static function createOrUpdateFromLdap($ldapData)
    {
        if (empty($ldapData['username'])) {
            return null;
        }

        $db = Database::getInstance();
        
        // Prüfen ob Benutzer bereits existiert
        $existingUser = self::findByUsername($ldapData['username']);
        
        if ($existingUser) {
            // Benutzer aktualisieren
            return $existingUser->updateFromLdap($ldapData);
        } else {
            // Neuen Benutzer erstellen
            return self::createFromLdap($ldapData);
        }
    }

    /**
     * Erstellt einen neuen Benutzer aus LDAP-Daten
     * 
     * @param array $ldapData LDAP-Benutzerdaten
     * @return User|null
     */
    private static function createFromLdap($ldapData)
    {
        $db = Database::getInstance();
        
        try {
            $stmt = $db->prepare("
                INSERT INTO users (
                    username, email, first_name, last_name, display_name, 
                    groups, ldap_dn, created_at, updated_at, is_active
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), 1)
            ");
            
            $stmt->execute([
                $ldapData['username'],
                $ldapData['email'] ?? '',
                $ldapData['first_name'] ?? '',
                $ldapData['last_name'] ?? '',
                $ldapData['display_name'] ?? $ldapData['username'],
                json_encode($ldapData['groups'] ?? []),
                $ldapData['dn'] ?? ''
            ]);
            
            $userId = $db->lastInsertId();
            return self::findById($userId);
            
        } catch (PDOException $e) {
            error_log("Fehler beim Erstellen des Benutzers: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Aktualisiert den Benutzer mit LDAP-Daten
     * 
     * @param array $ldapData LDAP-Benutzerdaten
     * @return User|null
     */
    private function updateFromLdap($ldapData)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE users SET 
                    email = ?, first_name = ?, last_name = ?, display_name = ?, 
                    groups = ?, ldap_dn = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $ldapData['email'] ?? $this->email,
                $ldapData['first_name'] ?? $this->firstName,
                $ldapData['last_name'] ?? $this->lastName,
                $ldapData['display_name'] ?? $this->displayName,
                json_encode($ldapData['groups'] ?? $this->groups),
                $ldapData['dn'] ?? $this->ldapDn,
                $this->id
            ]);
            
            // Objekt mit neuen Daten aktualisieren
            $this->email = $ldapData['email'] ?? $this->email;
            $this->firstName = $ldapData['first_name'] ?? $this->firstName;
            $this->lastName = $ldapData['last_name'] ?? $this->lastName;
            $this->displayName = $ldapData['display_name'] ?? $this->displayName;
            $this->groups = $ldapData['groups'] ?? $this->groups;
            $this->ldapDn = $ldapData['dn'] ?? $this->ldapDn;
            $this->updatedAt = date('Y-m-d H:i:s');
            
            return $this;
            
        } catch (PDOException $e) {
            error_log("Fehler beim Aktualisieren des Benutzers: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Aktualisiert die letzte Login-Zeit
     */
    public function updateLastLogin()
    {
        try {
            $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$this->id]);
            $this->lastLogin = date('Y-m-d H:i:s');
        } catch (PDOException $e) {
            error_log("Fehler beim Aktualisieren der Login-Zeit: " . $e->getMessage());
        }
    }

    /**
     * Deaktiviert den Benutzer
     */
    public function deactivate()
    {
        try {
            $stmt = $this->db->prepare("UPDATE users SET is_active = 0, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$this->id]);
            $this->isActive = false;
            $this->updatedAt = date('Y-m-d H:i:s');
        } catch (PDOException $e) {
            error_log("Fehler beim Deaktivieren des Benutzers: " . $e->getMessage());
        }
    }

    /**
     * Aktiviert den Benutzer
     */
    public function activate()
    {
        try {
            $stmt = $this->db->prepare("UPDATE users SET is_active = 1, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$this->id]);
            $this->isActive = true;
            $this->updatedAt = date('Y-m-d H:i:s');
        } catch (PDOException $e) {
            error_log("Fehler beim Aktivieren des Benutzers: " . $e->getMessage());
        }
    }

    /**
     * Prüft ob der Benutzer in einer bestimmten Gruppe ist
     * 
     * @param string $group Gruppenname
     * @return bool
     */
    public function hasGroup($group)
    {
        return in_array($group, $this->groups);
    }

    /**
     * Prüft ob der Benutzer eine der angegebenen Gruppen hat
     * 
     * @param array $groups Gruppennamen
     * @return bool
     */
    public function hasAnyGroup($groups)
    {
        return !empty(array_intersect($this->groups, $groups));
    }

    /**
     * Prüft ob der Benutzer alle angegebenen Gruppen hat
     * 
     * @param array $groups Gruppennamen
     * @return bool
     */
    public function hasAllGroups($groups)
    {
        return empty(array_diff($groups, $this->groups));
    }

    /**
     * Gibt den vollständigen Namen zurück
     * 
     * @return string
     */
    public function getFullName()
    {
        $parts = array_filter([$this->firstName, $this->lastName]);
        return implode(' ', $parts) ?: $this->displayName ?: $this->username;
    }

    /**
     * Gibt die Initialen zurück
     * 
     * @return string
     */
    public function getInitials()
    {
        $initials = '';
        
        if ($this->firstName) {
            $initials .= strtoupper(substr($this->firstName, 0, 1));
        }
        
        if ($this->lastName) {
            $initials .= strtoupper(substr($this->lastName, 0, 1));
        }
        
        if (empty($initials) && $this->username) {
            $initials = strtoupper(substr($this->username, 0, 2));
        }
        
        return $initials;
    }

    /**
     * Konvertiert das Objekt zu einem Array
     * 
     * @param bool $includePrivate Private Felder einschließen
     * @return array
     */
    public function toArray($includePrivate = false)
    {
        $data = [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'display_name' => $this->displayName,
            'full_name' => $this->getFullName(),
            'initials' => $this->getInitials(),
            'groups' => $this->groups,
            'is_active' => $this->isActive
        ];
        
        if ($includePrivate) {
            $data['ldap_dn'] = $this->ldapDn;
            $data['last_login'] = $this->lastLogin;
            $data['created_at'] = $this->createdAt;
            $data['updated_at'] = $this->updatedAt;
        }
        
        return $data;
    }

    // Getter-Methoden
    public function getId() { return $this->id; }
    public function getUsername() { return $this->username; }
    public function getEmail() { return $this->email; }
    public function getFirstName() { return $this->firstName; }
    public function getLastName() { return $this->lastName; }
    public function getDisplayName() { return $this->displayName ?: $this->getFullName(); }
    public function getGroups() { return $this->groups; }
    public function getLdapDn() { return $this->ldapDn; }
    public function getLastLogin() { return $this->lastLogin; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }
    public function isActive() { return $this->isActive; }

    // Setter-Methoden (für manuelle Updates)
    public function setEmail($email) { $this->email = $email; }
    public function setFirstName($firstName) { $this->firstName = $firstName; }
    public function setLastName($lastName) { $this->lastName = $lastName; }
    public function setDisplayName($displayName) { $this->displayName = $displayName; }
    public function setGroups($groups) { $this->groups = is_array($groups) ? $groups : []; }
}
