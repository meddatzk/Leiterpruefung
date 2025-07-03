# Leiterprüfung - Datenbankschema Dokumentation

## Übersicht

Das MySQL-Datenbankschema für die Leiterprüfungs-Applikation wurde entsprechend den Anforderungen für eine professionelle Leiter-Verwaltung und -Prüfung entwickelt. Es erfüllt alle Compliance-Anforderungen und bietet eine solide Grundlage für die Anwendungsentwicklung.

## Tabellen-Struktur

### Kern-Tabellen

#### 1. `users` - Benutzer-Cache (LDAP)
- **Zweck**: Zwischenspeicherung von LDAP-Benutzern mit lokalen Attributen
- **Besonderheiten**: 
  - Unterstützt sowohl lokale als auch LDAP-Benutzer
  - `last_login` Tracking für Aktivitätsüberwachung
  - Hierarchische Benutzer-Erstellung (`created_by`)

#### 2. `ladders` - Leiter-Stammdaten
- **Zweck**: Zentrale Verwaltung aller Leitern mit eindeutigen Identifikatoren
- **Eindeutigkeit**: 
  - `ladder_number`: Interne Leiternummer
  - `serial_number`: Herstellerseriennummer
  - Kombinierte Eindeutigkeit: Hersteller + Modell + Seriennummer
- **Status-Management**: Aktiv, Gesperrt, Reparatur, Ausgemustert

#### 3. `inspections` - Prüfungshistorie
- **Zweck**: Unveränderliche Historie aller Prüfungen
- **Besonderheiten**:
  - `is_final` Flag verhindert nachträgliche Änderungen
  - Automatische Berechnung des nächsten Prüfungstermins
  - Verschiedene Prüfungstypen mit spezifischen Intervallen

#### 4. `inspection_items` - Detaillierte Prüfpunkte
- **Zweck**: Strukturierte Erfassung einzelner Prüfaspekte
- **Kategorien**: Mechanisch, Korrosion, Verschleiß, Sicherheit, Funktionalität, Kennzeichnung, Zubehör
- **Status-Abstufungen**: OK, Mangel gering/erheblich/gefährlich, Nicht prüfbar

### Erweiterte Tabellen

#### 5. `defects` - Mängelverfolgung
- **Zweck**: Detaillierte Dokumentation und Verfolgung von Mängeln
- **Reparatur-Workflow**: Deadline-Management, Kostenverfolgung, Abschluss-Dokumentation
- **Verknüpfung**: Optional zu spezifischen Prüfpunkten

#### 6. `inspection_files` - Dokumentenanhänge
- **Zweck**: Verwaltung von Prüfprotokollen, Fotos, etc.
- **Sicherheit**: SHA-256 Hashes für Datei-Integrität
- **Größenbeschränkung**: Maximum 50MB pro Datei

#### 7. `audit_log` - Änderungsprotokoll
- **Zweck**: Compliance-konforme Nachverfolgung aller Änderungen
- **JSON-Speicherung**: Alte und neue Werte für detaillierte Nachverfolgung
- **Metadaten**: IP-Adresse, User-Agent für forensische Analyse

## Constraints und Datenintegrität

### Geschäftslogik-Constraints
- **Datumslogik**: Nächstes Prüfungsdatum muss nach aktuellem Prüfungsdatum liegen
- **Reparatur-Logik**: Reparatur-Deadline erforderlich wenn Reparatur nötig
- **Wertebereiche**: Maximale Traglasten, Höhen, Dateigrößen
- **Textvalidierung**: Keine leeren Pflichtfelder

### Referentielle Integrität
- **CASCADE**: Löschen von Leitern löscht alle zugehörigen Prüfungen
- **RESTRICT**: Benutzer können nicht gelöscht werden wenn sie Prüfungen durchgeführt haben
- **SET NULL**: Flexible Handhabung bei optionalen Verknüpfungen

## Performance-Optimierung

### Indizes
- **Primary Keys**: Automatische Clustered Indizes
- **Foreign Keys**: Indizes für Join-Performance
- **Suchfelder**: Indizes auf häufig gesuchte Spalten
- **Composite Indizes**: Für komplexe Abfragen

### Views für häufige Abfragen
- `v_ladders_current`: Aktuelle Leiter-Übersicht mit letzter Prüfung
- `v_inspections_due`: Fällige Prüfungen mit Prioritätseinstufung
- `v_defects_overview`: Mängel-Übersicht mit Reparatur-Status

## Stored Procedures und Functions

### Procedures
1. **GetUpcomingInspections(days_ahead)**: Fällige Prüfungen abrufen
2. **GetLadderHistory(ladder_id)**: Vollständige Prüfungshistorie
3. **GetDefectsDashboard()**: Mängel-Dashboard Statistiken
4. **GetInspectionStats(start_date, end_date)**: Prüfungsstatistiken
5. **GetLadderDashboardStats()**: Allgemeine Dashboard-Kennzahlen
6. **CreateInspectionWithItems()**: Neue Prüfung mit Standard-Prüfpunkten

### Functions
1. **CalculateNextInspectionDate()**: Automatische Terminberechnung basierend auf Leiter-Typ und Prüfungsart

## Trigger-System

### Automatisierung
- **tr_inspections_calculate_next_date**: Automatische Berechnung des nächsten Prüfungstermins
- **tr_inspections_prevent_final_changes**: Schutz vor Änderungen an finalisierten Prüfungen
- **tr_ladders_audit_update**: Audit-Log für Leiter-Änderungen

## Prüfungsintervalle

### Standardintervalle nach Leiter-Typ
- **Sichtprüfung**: 3 Monate (alle Typen)
- **Hauptprüfung**:
  - Anlegeleiter, Schiebeleiter: 12 Monate
  - Andere Typen: 6 Monate
- **Außerordentliche Prüfung**: 1 Monat

## Datenmodell-Diagramm

```
users (LDAP-Cache)
├── created_by → users.id
├── ladders.created_by
├── inspections.inspector_id
└── inspection_files.uploaded_by

ladders (Leiter-Stammdaten)
├── created_by → users.id
└── inspections.ladder_id

inspections (Prüfungshistorie)
├── ladder_id → ladders.id
├── inspector_id → users.id
├── inspection_items.inspection_id
├── defects.inspection_id
└── inspection_files.inspection_id

inspection_items (Prüfpunkte)
├── inspection_id → inspections.id
└── defects.inspection_item_id

defects (Mängelverfolgung)
├── inspection_id → inspections.id
└── inspection_item_id → inspection_items.id

inspection_files (Dokumentenanhänge)
├── inspection_id → inspections.id
└── uploaded_by → users.id

audit_log (Änderungsprotokoll)
└── changed_by → users.id
```

## Sicherheitsfeatures

### Datenintegrität
- Umfassende CHECK Constraints
- Referentielle Integrität mit CASCADE/RESTRICT
- Trigger für Geschäftslogik-Validierung

### Audit-Trail
- Vollständige Änderungshistorie in `audit_log`
- Unveränderliche Prüfungshistorie durch `is_final`
- Datei-Integrität durch SHA-256 Hashes

### Benutzer-Management
- LDAP-Integration mit lokalem Cache
- Hierarchische Benutzer-Verwaltung
- Aktivitäts-Tracking (`last_login`)

## Beispieldaten

Das Schema enthält realistische Testdaten für:
- 10 Benutzer (Admin + LDAP-Benutzer)
- 10 Leitern verschiedener Typen
- 14 Prüfungen mit unterschiedlichen Ergebnissen
- 18 detaillierte Prüfpunkte
- 7 Mängel verschiedener Schweregrade
- 4 Beispiel-Dateien

## Wartung und Monitoring

### Datenqualitäts-Checks
- Leitern ohne Prüfungen identifizieren
- Prüfungen ohne Prüfpunkte finden
- Inkonsistente Datumsangaben aufspüren

### Wartungs-Scripts
- Alte Audit-Logs bereinigen
- Inaktive Benutzer identifizieren
- Überfällige Reparaturen eskalieren

## Deployment

### Initialisierung
1. `01_create_tables.sql` - Hauptschema mit Beispieldaten
2. `02_test_data_and_queries.sql` - Erweiterte Testdaten und Beispielabfragen

### Docker-Integration
Das Schema ist vollständig in die Docker-Umgebung integriert und wird automatisch beim Container-Start initialisiert.

## Erweiterungsmöglichkeiten

### Geplante Features
- Benachrichtigungssystem für fällige Prüfungen
- QR-Code Integration für Leiter-Identifikation
- Mobile App Unterstützung
- Reporting-Engine Integration
- Workflow-Management für Reparaturen

### Skalierung
- Partitionierung für große Datenmengen
- Read-Replicas für Reporting
- Archivierung alter Prüfungsdaten
- Caching-Layer für häufige Abfragen

## Best Practices

### Entwicklung
- Verwenden Sie die bereitgestellten Views für Standard-Abfragen
- Nutzen Sie Stored Procedures für komplexe Geschäftslogik
- Beachten Sie die Trigger-Logik bei direkten Datenbankzugriffen
- Verwenden Sie Transaktionen für zusammenhängende Operationen

### Performance
- Nutzen Sie die vorhandenen Indizes optimal
- Vermeiden Sie SELECT * in Produktionsabfragen
- Verwenden Sie LIMIT bei großen Ergebnismengen
- Monitoren Sie langsame Abfragen mit EXPLAIN

### Sicherheit
- Verwenden Sie Prepared Statements
- Validieren Sie Eingaben auf Anwendungsebene
- Implementieren Sie Rollen-basierte Zugriffskontrolle
- Regelmäßige Backups und Disaster Recovery Tests

## Support und Dokumentation

Für weitere Informationen und Support:
- Schema-Dokumentation: `database/README_SCHEMA.md`
- Beispielabfragen: `database/init/02_test_data_and_queries.sql`
- Docker-Setup: `README_DOCKER.md`

---

*Erstellt für die Leiterprüfungs-Applikation - Version 1.0*
*Letzte Aktualisierung: Januar 2025*
