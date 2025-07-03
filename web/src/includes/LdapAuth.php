<?php
/**
 * LDAP-Authentifizierungsklasse
 * Verwaltet die Authentifizierung gegen LDAP-Server
 */

class LdapAuth
{
    private $ldapConnection;
    private $config;
    private $lastError;

    public function __construct()
    {
        $this->config = Config::get('ldap');
        $this->lastError = null;
    }

    /**
     * Stellt eine Verbindung zum LDAP-Server her
     * 
     * @return bool
     */
    private function connect()
    {
        if ($this->ldapConnection) {
            return true;
        }

        try {
            $server = $this->config['server'];
            $port = $this->config['port'];
            
            $this->ldapConnection = ldap_connect($server, $port);
            
            if (!$this->ldapConnection) {
                $this->lastError = "Verbindung zum LDAP-Server fehlgeschlagen";
                return false;
            }

            // LDAP-Optionen setzen
            ldap_set_option($this->ldapConnection, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($this->ldapConnection, LDAP_OPT_REFERRALS, 0);
            ldap_set_option($this->ldapConnection, LDAP_OPT_NETWORK_TIMEOUT, 10);

            // TLS verwenden falls konfiguriert
            if ($this->config['use_tls']) {
                if (!ldap_start_tls($this->ldapConnection)) {
                    $this->lastError = "TLS-Verbindung konnte nicht gestartet werden";
                    return false;
                }
            }

            return true;
        } catch (Exception $e) {
            $this->lastError = "LDAP-Verbindungsfehler: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Authentifiziert einen Benutzer gegen LDAP
     * 
     * @param string $username Benutzername
     * @param string $password Passwort
     * @return array|false Benutzerdaten bei Erfolg, false bei Fehler
     */
    public function authenticate($username, $password)
    {
        if (empty($username) || empty($password)) {
            $this->lastError = "Benutzername und Passwort sind erforderlich";
            return false;
        }

        if (!$this->connect()) {
            return false;
        }

        try {
            // Bind mit Service-Account falls konfiguriert
            if (!empty($this->config['bind_dn']) && !empty($this->config['bind_password'])) {
                $bind = ldap_bind($this->ldapConnection, $this->config['bind_dn'], $this->config['bind_password']);
                if (!$bind) {
                    $this->lastError = "Service-Account-Authentifizierung fehlgeschlagen";
                    return false;
                }
            }

            // Benutzer suchen
            $userDn = $this->findUserDn($username);
            if (!$userDn) {
                $this->lastError = "Benutzer nicht gefunden";
                return false;
            }

            // Benutzer authentifizieren
            $bind = ldap_bind($this->ldapConnection, $userDn, $password);
            if (!$bind) {
                $this->lastError = "Ungültige Anmeldedaten";
                return false;
            }

            // Benutzerdaten laden
            $userData = $this->getUserData($userDn);
            if (!$userData) {
                $this->lastError = "Benutzerdaten konnten nicht geladen werden";
                return false;
            }

            return $userData;

        } catch (Exception $e) {
            $this->lastError = "Authentifizierungsfehler: " . $e->getMessage();
            return false;
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Sucht die DN eines Benutzers
     * 
     * @param string $username Benutzername
     * @return string|false DN bei Erfolg, false bei Fehler
     */
    private function findUserDn($username)
    {
        $filter = sprintf($this->config['user_filter'], ldap_escape($username, '', LDAP_ESCAPE_FILTER));
        $search = ldap_search($this->ldapConnection, $this->config['base_dn'], $filter);
        
        if (!$search) {
            return false;
        }

        $entries = ldap_get_entries($this->ldapConnection, $search);
        
        if ($entries['count'] === 0) {
            return false;
        }

        return $entries[0]['dn'];
    }

    /**
     * Lädt Benutzerdaten aus LDAP
     * 
     * @param string $userDn Benutzer-DN
     * @return array|false Benutzerdaten bei Erfolg, false bei Fehler
     */
    private function getUserData($userDn)
    {
        $search = ldap_read($this->ldapConnection, $userDn, '(objectClass=*)');
        
        if (!$search) {
            return false;
        }

        $entries = ldap_get_entries($this->ldapConnection, $search);
        
        if ($entries['count'] === 0) {
            return false;
        }

        $entry = $entries[0];
        
        return [
            'dn' => $userDn,
            'username' => $this->getAttribute($entry, 'uid') ?: $this->getAttribute($entry, 'sAMAccountName'),
            'email' => $this->getAttribute($entry, 'mail'),
            'first_name' => $this->getAttribute($entry, 'givenName'),
            'last_name' => $this->getAttribute($entry, 'sn'),
            'display_name' => $this->getAttribute($entry, 'displayName') ?: $this->getAttribute($entry, 'cn'),
            'groups' => $this->getUserGroups($userDn)
        ];
    }

    /**
     * Holt ein Attribut aus einem LDAP-Eintrag
     * 
     * @param array $entry LDAP-Eintrag
     * @param string $attribute Attributname
     * @return string|null Attributwert oder null
     */
    private function getAttribute($entry, $attribute)
    {
        $attribute = strtolower($attribute);
        return isset($entry[$attribute][0]) ? $entry[$attribute][0] : null;
    }

    /**
     * Lädt die Gruppen eines Benutzers
     * 
     * @param string $userDn Benutzer-DN
     * @return array Gruppenliste
     */
    private function getUserGroups($userDn)
    {
        $groups = [];
        
        try {
            $username = $this->extractUsernameFromDn($userDn);
            $filter = sprintf($this->config['group_filter'], ldap_escape($username, '', LDAP_ESCAPE_FILTER));
            
            $search = ldap_search($this->ldapConnection, $this->config['base_dn'], $filter);
            
            if ($search) {
                $entries = ldap_get_entries($this->ldapConnection, $search);
                
                for ($i = 0; $i < $entries['count']; $i++) {
                    $groupName = $this->getAttribute($entries[$i], 'cn');
                    if ($groupName) {
                        $groups[] = $groupName;
                    }
                }
            }
        } catch (Exception $e) {
            // Gruppen sind optional, Fehler ignorieren
        }
        
        return $groups;
    }

    /**
     * Extrahiert den Benutzernamen aus einer DN
     * 
     * @param string $dn Benutzer-DN
     * @return string Benutzername
     */
    private function extractUsernameFromDn($dn)
    {
        if (preg_match('/uid=([^,]+)/i', $dn, $matches)) {
            return $matches[1];
        }
        
        if (preg_match('/cn=([^,]+)/i', $dn, $matches)) {
            return $matches[1];
        }
        
        return '';
    }

    /**
     * Schließt die LDAP-Verbindung
     */
    private function disconnect()
    {
        if ($this->ldapConnection) {
            ldap_close($this->ldapConnection);
            $this->ldapConnection = null;
        }
    }

    /**
     * Gibt den letzten Fehler zurück
     * 
     * @return string|null Fehlermeldung oder null
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Prüft ob LDAP verfügbar ist
     * 
     * @return bool
     */
    public function isAvailable()
    {
        if (!extension_loaded('ldap')) {
            $this->lastError = "LDAP-Erweiterung ist nicht installiert";
            return false;
        }

        return $this->connect();
    }

    /**
     * Destruktor - schließt Verbindung
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
