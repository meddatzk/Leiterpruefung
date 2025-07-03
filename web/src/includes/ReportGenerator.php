<?php

require_once __DIR__ . '/Ladder.php';
require_once __DIR__ . '/LadderRepository.php';
require_once __DIR__ . '/Inspection.php';
require_once __DIR__ . '/InspectionRepository.php';
require_once __DIR__ . '/User.php';

/**
 * ReportGenerator - Generiert verschiedene Berichte für das Leiterverwaltungssystem
 * 
 * @author System
 * @version 1.0
 */
class ReportGenerator
{
    private PDO $pdo;
    private LadderRepository $ladderRepository;
    private InspectionRepository $inspectionRepository;
    private TemplateEngine $templateEngine;

    public function __construct(PDO $pdo, LadderRepository $ladderRepository, InspectionRepository $inspectionRepository, TemplateEngine $templateEngine)
    {
        $this->pdo = $pdo;
        $this->ladderRepository = $ladderRepository;
        $this->inspectionRepository = $inspectionRepository;
        $this->templateEngine = $templateEngine;
    }

    /**
     * Generiert ein Prüfungsprotokoll für eine spezifische Leiter
     */
    public function generateInspectionReport(int $ladderId, ?int $inspectionId = null): array
    {
        // Leiter laden
        $ladder = $this->ladderRepository->findById($ladderId);
        if (!$ladder) {
            throw new InvalidArgumentException("Leiter nicht gefunden: {$ladderId}");
        }

        // Prüfung laden (neueste wenn nicht spezifiziert)
        if ($inspectionId) {
            $inspection = $this->inspectionRepository->findById($inspectionId);
            if (!$inspection || $inspection->getLadderId() !== $ladderId) {
                throw new InvalidArgumentException("Prüfung nicht gefunden oder gehört nicht zur Leiter: {$inspectionId}");
            }
        } else {
            $inspections = $this->inspectionRepository->findByLadder($ladderId, 1, 0);
            if (empty($inspections)) {
                throw new InvalidArgumentException("Keine Prüfungen für Leiter gefunden: {$ladderId}");
            }
            $inspection = $inspections[0];
        }

        // Prüfer-Daten laden
        $inspector = $this->getUserById($inspection->getInspectorId());
        $supervisor = $inspection->getSupervisorApprovalId() ? 
            $this->getUserById($inspection->getSupervisorApprovalId()) : null;

        // Prüfungshistorie laden (letzte 5 Prüfungen)
        $history = $this->inspectionRepository->findByLadder($ladderId, 5, 0);

        // Mängel kategorisieren
        $defects = $inspection->getAllDefects();
        $criticalDefects = $inspection->getCriticalDefects();

        return [
            'type' => 'inspection_report',
            'title' => 'Prüfungsprotokoll - ' . $ladder->getLadderNumber(),
            'generated_at' => date('Y-m-d H:i:s'),
            'data' => [
                'ladder' => $ladder,
                'inspection' => $inspection,
                'inspector' => $inspector,
                'supervisor' => $supervisor,
                'inspection_items' => $inspection->getInspectionItems(),
                'defects' => $defects,
                'critical_defects' => $criticalDefects,
                'history' => $history,
                'summary' => [
                    'total_items' => count($inspection->getInspectionItems()),
                    'defects_count' => count($defects),
                    'critical_defects_count' => count($criticalDefects),
                    'overall_result' => $inspection->getOverallResult(),
                    'next_inspection_date' => $inspection->getNextInspectionDate()
                ]
            ]
        ];
    }

    /**
     * Generiert einen Übersichtsbericht aller Leitern
     */
    public function generateOverviewReport(array $filters = []): array
    {
        // Filter validieren und standardisieren
        $validatedFilters = $this->validateFilters($filters);
        
        // Leitern laden
        $ladders = $this->ladderRepository->search($validatedFilters, 1000, 0);
        
        // Statistiken berechnen
        $statistics = $this->ladderRepository->getStatistics();
        
        // Prüfungsstatistiken laden
        $inspectionStats = $this->inspectionRepository->getStatistics($validatedFilters);
        
        // Anstehende Prüfungen
        $upcomingInspections = $this->inspectionRepository->getUpcoming(30);
        
        // Überfällige Prüfungen
        $overdueInspections = $this->ladderRepository->getLaddersNeedingInspection(0);
        
        // Standort-Statistiken
        $locationStats = $this->getLocationStatistics($validatedFilters);
        
        // Typ-Statistiken
        $typeStats = $this->getTypeStatistics($validatedFilters);

        return [
            'type' => 'overview_report',
            'title' => 'Leitern-Übersichtsbericht',
            'generated_at' => date('Y-m-d H:i:s'),
            'filters' => $validatedFilters,
            'data' => [
                'ladders' => $ladders,
                'statistics' => $statistics,
                'inspection_statistics' => $inspectionStats,
                'upcoming_inspections' => $upcomingInspections,
                'overdue_inspections' => $overdueInspections,
                'location_statistics' => $locationStats,
                'type_statistics' => $typeStats,
                'summary' => [
                    'total_ladders' => count($ladders),
                    'active_ladders' => $statistics['active'],
                    'needs_inspection' => $statistics['needs_inspection'],
                    'inspection_due_30_days' => $statistics['inspection_due_30_days']
                ]
            ]
        ];
    }

    /**
     * Generiert einen Statistikbericht
     */
    public function generateStatisticsReport(array $filters = []): array
    {
        $validatedFilters = $this->validateFilters($filters);
        
        // Zeitraum bestimmen
        $dateFrom = $validatedFilters['date_from'] ?? date('Y-01-01');
        $dateTo = $validatedFilters['date_to'] ?? date('Y-m-d');
        
        // Grundstatistiken
        $ladderStats = $this->ladderRepository->getStatistics();
        $inspectionStats = $this->inspectionRepository->getStatistics($validatedFilters);
        $defectStats = $this->inspectionRepository->getDefectStatistics($validatedFilters);
        
        // Monatliche Trends
        $monthlyTrends = $this->getMonthlyTrends($dateFrom, $dateTo);
        
        // Prüfer-Statistiken
        $inspectorStats = $this->getInspectorStatistics($validatedFilters);
        
        // Standort-Performance
        $locationPerformance = $this->getLocationPerformance($validatedFilters);
        
        // Mängel-Trends
        $defectTrends = $this->getDefectTrends($dateFrom, $dateTo);

        return [
            'type' => 'statistics_report',
            'title' => 'Prüfungsstatistiken',
            'generated_at' => date('Y-m-d H:i:s'),
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'filters' => $validatedFilters,
            'data' => [
                'ladder_statistics' => $ladderStats,
                'inspection_statistics' => $inspectionStats,
                'defect_statistics' => $defectStats,
                'monthly_trends' => $monthlyTrends,
                'inspector_statistics' => $inspectorStats,
                'location_performance' => $locationPerformance,
                'defect_trends' => $defectTrends,
                'summary' => [
                    'total_inspections' => $inspectionStats['total_inspections'],
                    'pass_rate' => $inspectionStats['total_inspections'] > 0 ? 
                        round(($inspectionStats['passed'] / $inspectionStats['total_inspections']) * 100, 2) : 0,
                    'avg_duration' => round($inspectionStats['avg_duration'] ?? 0, 1),
                    'unique_inspectors' => $inspectionStats['unique_inspectors']
                ]
            ]
        ];
    }

    /**
     * Generiert einen Prüfkalender
     */
    public function generateCalendarReport(array $filters = []): array
    {
        $validatedFilters = $this->validateFilters($filters);
        
        // Zeitraum bestimmen (Standard: nächste 12 Monate)
        $dateFrom = $validatedFilters['date_from'] ?? date('Y-m-d');
        $dateTo = $validatedFilters['date_to'] ?? date('Y-m-d', strtotime('+12 months'));
        
        // Anstehende Prüfungen laden
        $upcomingInspections = $this->getUpcomingInspectionsForPeriod($dateFrom, $dateTo, $validatedFilters);
        
        // Nach Monaten gruppieren
        $monthlySchedule = $this->groupInspectionsByMonth($upcomingInspections);
        
        // Wöchentliche Auslastung berechnen
        $weeklyLoad = $this->calculateWeeklyLoad($upcomingInspections);
        
        // Kritische Termine (überfällig oder sehr dringend)
        $criticalDates = $this->getCriticalInspectionDates($upcomingInspections);

        return [
            'type' => 'calendar_report',
            'title' => 'Prüfkalender',
            'generated_at' => date('Y-m-d H:i:s'),
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'filters' => $validatedFilters,
            'data' => [
                'upcoming_inspections' => $upcomingInspections,
                'monthly_schedule' => $monthlySchedule,
                'weekly_load' => $weeklyLoad,
                'critical_dates' => $criticalDates,
                'summary' => [
                    'total_inspections' => count($upcomingInspections),
                    'overdue_count' => count(array_filter($upcomingInspections, function($item) {
                        return strtotime($item['next_inspection_date']) < time();
                    })),
                    'this_month_count' => count($monthlySchedule[date('Y-m')] ?? []),
                    'avg_weekly_load' => array_sum($weeklyLoad) / max(count($weeklyLoad), 1)
                ]
            ]
        ];
    }

    /**
     * Generiert einen Ausfallbericht
     */
    public function generateFailureReport(array $filters = []): array
    {
        $validatedFilters = $this->validateFilters($filters);
        
        // Zeitraum bestimmen
        $dateFrom = $validatedFilters['date_from'] ?? date('Y-01-01');
        $dateTo = $validatedFilters['date_to'] ?? date('Y-m-d');
        
        // Fehlgeschlagene Prüfungen laden
        $failedInspections = $this->getFailedInspections($dateFrom, $dateTo, $validatedFilters);
        
        // Defekte Leitern
        $defectiveLadders = $this->ladderRepository->search(['status' => 'defective'], 1000, 0);
        
        // Kritische Mängel
        $criticalDefects = $this->getCriticalDefectsInPeriod($dateFrom, $dateTo);
        
        // Ausfallursachen analysieren
        $failureCauses = $this->analyzeFailureCauses($failedInspections);
        
        // Kosten-Schätzung
        $costEstimation = $this->estimateFailureCosts($defectiveLadders, $criticalDefects);

        return [
            'type' => 'failure_report',
            'title' => 'Ausfallbericht',
            'generated_at' => date('Y-m-d H:i:s'),
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'filters' => $validatedFilters,
            'data' => [
                'failed_inspections' => $failedInspections,
                'defective_ladders' => $defectiveLadders,
                'critical_defects' => $criticalDefects,
                'failure_causes' => $failureCauses,
                'cost_estimation' => $costEstimation,
                'summary' => [
                    'total_failures' => count($failedInspections),
                    'defective_ladders_count' => count($defectiveLadders),
                    'critical_defects_count' => count($criticalDefects),
                    'estimated_total_cost' => $costEstimation['total_cost']
                ]
            ]
        ];
    }

    /**
     * Validiert und standardisiert Filter
     */
    private function validateFilters(array $filters): array
    {
        $validated = [];
        
        // Datumsfilter
        if (!empty($filters['date_from']) && $this->isValidDate($filters['date_from'])) {
            $validated['date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to']) && $this->isValidDate($filters['date_to'])) {
            $validated['date_to'] = $filters['date_to'];
        }
        
        // Standortfilter
        if (!empty($filters['location'])) {
            $validated['location'] = trim($filters['location']);
        }
        
        // Abteilungsfilter
        if (!empty($filters['department'])) {
            $validated['department'] = trim($filters['department']);
        }
        
        // Statusfilter
        if (!empty($filters['status']) && in_array($filters['status'], Ladder::STATUSES)) {
            $validated['status'] = $filters['status'];
        }
        
        // Leitertypfilter
        if (!empty($filters['ladder_type']) && in_array($filters['ladder_type'], Ladder::LADDER_TYPES)) {
            $validated['ladder_type'] = $filters['ladder_type'];
        }
        
        // Prüfungstyp-Filter
        if (!empty($filters['inspection_type']) && in_array($filters['inspection_type'], Inspection::INSPECTION_TYPES)) {
            $validated['inspection_type'] = $filters['inspection_type'];
        }
        
        return $validated;
    }

    /**
     * Lädt Benutzer anhand der ID
     */
    private function getUserById(int $userId): ?array
    {
        $sql = "SELECT id, username, display_name, email FROM users WHERE id = :id";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Berechnet Standort-Statistiken
     */
    private function getLocationStatistics(array $filters): array
    {
        $whereConditions = [];
        $params = [];
        
        if (!empty($filters['date_from'])) {
            $whereConditions[] = "created_at >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $whereConditions[] = "created_at <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        $sql = "SELECT 
                    location,
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = 'defective' THEN 1 ELSE 0 END) as defective,
                    SUM(CASE WHEN next_inspection_date <= CURDATE() THEN 1 ELSE 0 END) as needs_inspection
                FROM ladders {$whereClause}
                GROUP BY location
                ORDER BY total DESC";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Berechnet Typ-Statistiken
     */
    private function getTypeStatistics(array $filters): array
    {
        $whereConditions = [];
        $params = [];
        
        if (!empty($filters['date_from'])) {
            $whereConditions[] = "created_at >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $whereConditions[] = "created_at <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        $sql = "SELECT 
                    ladder_type,
                    material,
                    COUNT(*) as count,
                    AVG(height_cm) as avg_height,
                    AVG(max_load_kg) as avg_load
                FROM ladders {$whereClause}
                GROUP BY ladder_type, material
                ORDER BY count DESC";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Berechnet monatliche Trends
     */
    private function getMonthlyTrends(string $dateFrom, string $dateTo): array
    {
        $sql = "SELECT 
                    DATE_FORMAT(inspection_date, '%Y-%m') as month,
                    COUNT(*) as total_inspections,
                    SUM(CASE WHEN overall_result = 'passed' THEN 1 ELSE 0 END) as passed,
                    SUM(CASE WHEN overall_result = 'failed' THEN 1 ELSE 0 END) as failed,
                    AVG(inspection_duration_minutes) as avg_duration
                FROM inspections
                WHERE inspection_date >= :date_from AND inspection_date <= :date_to
                GROUP BY DATE_FORMAT(inspection_date, '%Y-%m')
                ORDER BY month ASC";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':date_from' => $dateFrom, ':date_to' => $dateTo]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Berechnet Prüfer-Statistiken
     */
    private function getInspectorStatistics(array $filters): array
    {
        $whereConditions = ['1=1'];
        $params = [];
        
        if (!empty($filters['date_from'])) {
            $whereConditions[] = "i.inspection_date >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $whereConditions[] = "i.inspection_date <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $sql = "SELECT 
                    u.display_name as inspector_name,
                    COUNT(*) as total_inspections,
                    SUM(CASE WHEN i.overall_result = 'passed' THEN 1 ELSE 0 END) as passed,
                    SUM(CASE WHEN i.overall_result = 'failed' THEN 1 ELSE 0 END) as failed,
                    AVG(i.inspection_duration_minutes) as avg_duration
                FROM inspections i
                JOIN users u ON i.inspector_id = u.id
                WHERE {$whereClause}
                GROUP BY i.inspector_id, u.display_name
                ORDER BY total_inspections DESC";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Weitere private Hilfsmethoden...
     */
    private function isValidDate(string $date): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    private function getLocationPerformance(array $filters): array
    {
        // Implementation für Standort-Performance
        return [];
    }

    private function getDefectTrends(string $dateFrom, string $dateTo): array
    {
        // Implementation für Mängel-Trends
        return [];
    }

    private function getUpcomingInspectionsForPeriod(string $dateFrom, string $dateTo, array $filters): array
    {
        // Implementation für anstehende Prüfungen in Zeitraum
        return [];
    }

    private function groupInspectionsByMonth(array $inspections): array
    {
        // Implementation für Gruppierung nach Monaten
        return [];
    }

    private function calculateWeeklyLoad(array $inspections): array
    {
        // Implementation für wöchentliche Auslastung
        return [];
    }

    private function getCriticalInspectionDates(array $inspections): array
    {
        // Implementation für kritische Termine
        return [];
    }

    private function getFailedInspections(string $dateFrom, string $dateTo, array $filters): array
    {
        // Implementation für fehlgeschlagene Prüfungen
        return [];
    }

    private function getCriticalDefectsInPeriod(string $dateFrom, string $dateTo): array
    {
        // Implementation für kritische Mängel
        return [];
    }

    private function analyzeFailureCauses(array $failedInspections): array
    {
        // Implementation für Ausfallursachen-Analyse
        return [];
    }

    private function estimateFailureCosts(array $defectiveLadders, array $criticalDefects): array
    {
        // Implementation für Kostenschätzung
        return ['total_cost' => 0];
    }
}
