<?php
/**
 * Berichtsgenerierung - Generiert und liefert Berichte aus
 */

require_once __DIR__ . '/../../src/includes/bootstrap.php';
require_once __DIR__ . '/../../src/includes/auth_middleware.php';
require_once __DIR__ . '/../../src/includes/ReportGenerator.php';
require_once __DIR__ . '/../../src/includes/PdfGenerator.php';
require_once __DIR__ . '/../../src/includes/ExcelExporter.php';

// Authentifizierung prüfen
requireAuth();

// Nur POST-Requests erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method Not Allowed');
}

// CSRF-Token prüfen
if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    die('CSRF-Token ungültig');
}

try {
    // Parameter validieren
    $reportType = $_POST['report_type'] ?? '';
    $format = $_POST['format'] ?? 'pdf';
    $filters = $_POST['filters'] ?? [];
    
    // Erlaubte Berichtstypen
    $allowedTypes = ['inspection_report', 'overview_report', 'statistics_report', 'calendar_report', 'failure_report'];
    if (!in_array($reportType, $allowedTypes)) {
        throw new InvalidArgumentException('Ungültiger Berichtstyp');
    }
    
    // Erlaubte Formate
    $allowedFormats = ['pdf', 'excel'];
    if (!in_array($format, $allowedFormats)) {
        throw new InvalidArgumentException('Ungültiges Format');
    }
    
    // Datenbankverbindung und Repositories initialisieren
    $pdo = getDbConnection();
    $auditLogger = new AuditLogger($pdo);
    $ladderRepo = new LadderRepository($pdo, $auditLogger);
    $inspectionRepo = new InspectionRepository($pdo, $auditLogger);
    $templateEngine = new TemplateEngine(__DIR__ . '/../../templates');
    
    // Report Generator initialisieren
    $reportGenerator = new ReportGenerator($pdo, $ladderRepo, $inspectionRepo, $templateEngine);
    
    // Filter verarbeiten und validieren
    $processedFilters = processFilters($filters);
    
    // Spezielle Parameter für Prüfungsprotokoll
    $ladderId = null;
    $inspectionId = null;
    
    if ($reportType === 'inspection_report') {
        $ladderId = (int)($_POST['ladder_id'] ?? 0);
        $inspectionId = !empty($_POST['inspection_id']) ? (int)$_POST['inspection_id'] : null;
        
        if (!$ladderId) {
            throw new InvalidArgumentException('Leiter-ID ist für Prüfungsprotokoll erforderlich');
        }
    }
    
    // Bericht generieren
    $reportData = generateReport($reportGenerator, $reportType, $processedFilters, $ladderId, $inspectionId);
    
    // Export-Konfiguration laden
    $config = getExportConfig();
    
    // Datei generieren und ausliefern
    if ($format === 'pdf') {
        generateAndDeliverPdf($reportData, $config);
    } else {
        generateAndDeliverExcel($reportData, $config);
    }
    
    // Bericht in Recent Reports speichern
    saveToRecentReports($reportType, $format, $reportData['title']);
    
    // Audit-Log
    $auditLogger->logAction('report_generated', [
        'report_type' => $reportType,
        'format' => $format,
        'user_id' => $_SESSION['user']['id'],
        'filters' => $processedFilters
    ]);
    
} catch (Exception $e) {
    error_log("Fehler bei Berichtsgenerierung: " . $e->getMessage());
    
    // Fehler-Response
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => true,
        'message' => 'Fehler bei der Berichtsgenerierung: ' . $e->getMessage()
    ]);
}

/**
 * Verarbeitet und validiert Filter
 */
function processFilters(array $filters): array
{
    $processed = [];
    
    // Datumsfilter
    if (!empty($filters['date_from'])) {
        $date = DateTime::createFromFormat('Y-m-d', $filters['date_from']);
        if ($date) {
            $processed['date_from'] = $date->format('Y-m-d');
        }
    }
    
    if (!empty($filters['date_to'])) {
        $date = DateTime::createFromFormat('Y-m-d', $filters['date_to']);
        if ($date) {
            $processed['date_to'] = $date->format('Y-m-d');
        }
    }
    
    // Textfilter
    $textFilters = ['location', 'department', 'ladder_number', 'manufacturer'];
    foreach ($textFilters as $filter) {
        if (!empty($filters[$filter])) {
            $processed[$filter] = trim($filters[$filter]);
        }
    }
    
    // Enum-Filter
    if (!empty($filters['status']) && in_array($filters['status'], Ladder::STATUSES)) {
        $processed['status'] = $filters['status'];
    }
    
    if (!empty($filters['ladder_type']) && in_array($filters['ladder_type'], Ladder::LADDER_TYPES)) {
        $processed['ladder_type'] = $filters['ladder_type'];
    }
    
    if (!empty($filters['inspection_type']) && in_array($filters['inspection_type'], Inspection::INSPECTION_TYPES)) {
        $processed['inspection_type'] = $filters['inspection_type'];
    }
    
    if (!empty($filters['overall_result']) && in_array($filters['overall_result'], Inspection::OVERALL_RESULTS)) {
        $processed['overall_result'] = $filters['overall_result'];
    }
    
    return $processed;
}

/**
 * Generiert Bericht basierend auf Typ
 */
function generateReport(ReportGenerator $generator, string $type, array $filters, ?int $ladderId = null, ?int $inspectionId = null): array
{
    switch ($type) {
        case 'inspection_report':
            return $generator->generateInspectionReport($ladderId, $inspectionId);
            
        case 'overview_report':
            return $generator->generateOverviewReport($filters);
            
        case 'statistics_report':
            return $generator->generateStatisticsReport($filters);
            
        case 'calendar_report':
            return $generator->generateCalendarReport($filters);
            
        case 'failure_report':
            return $generator->generateFailureReport($filters);
            
        default:
            throw new InvalidArgumentException("Unbekannter Berichtstyp: {$type}");
    }
}

/**
 * Lädt Export-Konfiguration
 */
function getExportConfig(): array
{
    return [
        'company_name' => 'Leiterverwaltung GmbH',
        'company_address' => 'Musterstraße 1, 12345 Musterstadt',
        'company_phone' => '+49 123 456789',
        'company_email' => 'info@leiterverwaltung.de',
        'logo_path' => __DIR__ . '/../../src/assets/images/logo.png',
        'watermark' => 'VERTRAULICH'
    ];
}

/**
 * Generiert und liefert PDF aus
 */
function generateAndDeliverPdf(array $reportData, array $config): void
{
    $pdfGenerator = new PdfGenerator(new TemplateEngine(__DIR__ . '/../../templates'), $config);
    $pdfContent = $pdfGenerator->generatePdf($reportData);
    
    // Dateiname generieren
    $filename = generateFilename($reportData['type'], 'pdf', $reportData['title']);
    
    // Headers setzen
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdfContent));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    // PDF ausgeben
    echo $pdfContent;
}

/**
 * Generiert und liefert Excel aus
 */
function generateAndDeliverExcel(array $reportData, array $config): void
{
    $excelExporter = new ExcelExporter($config);
    $excelContent = $excelExporter->generateExcel($reportData);
    
    // Dateiname generieren
    $filename = generateFilename($reportData['type'], 'xlsx', $reportData['title']);
    
    // Headers setzen
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($excelContent));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    // Excel ausgeben
    echo $excelContent;
}

/**
 * Generiert Dateiname für Export
 */
function generateFilename(string $reportType, string $extension, string $title): string
{
    // Titel bereinigen
    $cleanTitle = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $title);
    $cleanTitle = preg_replace('/_+/', '_', $cleanTitle);
    $cleanTitle = trim($cleanTitle, '_');
    
    // Datum hinzufügen
    $date = date('Y-m-d');
    
    // Dateiname zusammensetzen
    return "{$cleanTitle}_{$date}.{$extension}";
}

/**
 * Speichert Bericht in Recent Reports
 */
function saveToRecentReports(string $type, string $format, string $title): void
{
    if (!isset($_SESSION['recent_reports'])) {
        $_SESSION['recent_reports'] = [];
    }
    
    // Neuen Eintrag hinzufügen
    $entry = [
        'type' => $type,
        'format' => $format,
        'title' => $title,
        'generated_at' => date('Y-m-d H:i:s'),
        'user' => $_SESSION['user']['display_name'] ?? 'Unbekannt'
    ];
    
    array_unshift($_SESSION['recent_reports'], $entry);
    
    // Nur die letzten 10 Berichte behalten
    $_SESSION['recent_reports'] = array_slice($_SESSION['recent_reports'], 0, 10);
}

/**
 * Validiert Leiter-ID für Prüfungsprotokoll
 */
function validateLadderId(int $ladderId, LadderRepository $ladderRepo): void
{
    $ladder = $ladderRepo->findById($ladderId);
    if (!$ladder) {
        throw new InvalidArgumentException("Leiter mit ID {$ladderId} nicht gefunden");
    }
}

/**
 * Validiert Prüfungs-ID falls angegeben
 */
function validateInspectionId(?int $inspectionId, InspectionRepository $inspectionRepo, int $ladderId): void
{
    if ($inspectionId) {
        $inspection = $inspectionRepo->findById($inspectionId);
        if (!$inspection) {
            throw new InvalidArgumentException("Prüfung mit ID {$inspectionId} nicht gefunden");
        }
        
        if ($inspection->getLadderId() !== $ladderId) {
            throw new InvalidArgumentException("Prüfung gehört nicht zur angegebenen Leiter");
        }
    }
}

/**
 * Prüft Berechtigungen für Berichtstyp
 */
function checkReportPermissions(string $reportType, array $user): void
{
    // Basis-Berechtigung: Alle authentifizierten Benutzer können Berichte generieren
    // Hier könnten spezifische Rollen-Checks implementiert werden
    
    $restrictedReports = ['failure_report', 'statistics_report'];
    
    if (in_array($reportType, $restrictedReports)) {
        // Beispiel: Nur Administratoren und Manager dürfen diese Berichte generieren
        $allowedRoles = ['admin', 'manager'];
        if (!in_array($user['role'] ?? '', $allowedRoles)) {
            throw new UnauthorizedAccessException("Keine Berechtigung für diesen Berichtstyp");
        }
    }
}

/**
 * Validiert Zeitraum-Filter
 */
function validateDateRange(array $filters): void
{
    if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
        $dateFrom = new DateTime($filters['date_from']);
        $dateTo = new DateTime($filters['date_to']);
        
        if ($dateFrom > $dateTo) {
            throw new InvalidArgumentException("Start-Datum muss vor End-Datum liegen");
        }
        
        // Maximaler Zeitraum: 2 Jahre
        $maxInterval = new DateInterval('P2Y');
        $maxDate = clone $dateFrom;
        $maxDate->add($maxInterval);
        
        if ($dateTo > $maxDate) {
            throw new InvalidArgumentException("Zeitraum darf maximal 2 Jahre betragen");
        }
    }
}

/**
 * Exception-Klasse für Berechtigungsfehler
 */
class UnauthorizedAccessException extends Exception {}
