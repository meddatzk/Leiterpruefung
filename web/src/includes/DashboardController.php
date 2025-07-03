<?php

require_once __DIR__ . '/LadderRepository.php';
require_once __DIR__ . '/InspectionRepository.php';

/**
 * DashboardController - Dashboard-Logik und Datenbereitstellung
 * 
 * @author System
 * @version 1.0
 */
class DashboardController
{
    private PDO $pdo;
    private LadderRepository $ladderRepository;
    private InspectionRepository $inspectionRepository;
    private AuditLogger $auditLogger;

    public function __construct(PDO $pdo, AuditLogger $auditLogger)
    {
        $this->pdo = $pdo;
        $this->auditLogger = $auditLogger;
        $this->ladderRepository = new LadderRepository($pdo, $auditLogger);
        $this->inspectionRepository = new InspectionRepository($pdo, $auditLogger);
    }

    /**
     * Gibt alle Dashboard-Übersichtsdaten zurück
     */
    public function getOverviewData(): array
    {
        try {
            return [
                'statistics' => $this->getStatistics(),
                'upcoming_inspections' => $this->getUpcomingInspections(30),
                'overdue_inspections' => $this->getOverdueInspections(),
                'today_inspections' => $this->getTodayInspections(),
                'recent_activity' => $this->getRecentActivity(10),
                'defect_statistics' => $this->getDefectStatistics(),
                'monthly_stats' => $this->getMonthlyStatistics(),
                'department_stats' => $this->getDepartmentStatistics()
            ];
        } catch (Exception $e) {
            error_log("Dashboard Overview Error: " . $e->getMessage());
            throw new RuntimeException('Fehler beim Laden der Dashboard-Daten: ' . $e->getMessage());
        }
    }

    /**
     * Gibt allgemeine Statistiken zurück
     */
    public function getStatistics(): array
    {
        try {
            $ladderStats = $this->ladderRepository->getStatistics();
            $inspectionStats = $this->inspectionRepository->getStatistics();

            return [
                'ladders' => [
                    'total' => (int) $ladderStats['total'],
                    'active' => (int) $ladderStats['active'],
                    'inactive' => (int) $ladderStats['inactive'],
                    'defective' => (int) $ladderStats['defective'],
                    'disposed' => (int) $ladderStats['disposed'],
                    'needs_inspection' => (int) $ladderStats['needs_inspection'],
                    'inspection_due_30_days' => (int) $ladderStats['inspection_due_30_days']
                ],
                'inspections' => [
                    'total' => (int) $inspectionStats['total_inspections'],
                    'passed' => (int) $inspectionStats['passed'],
                    'failed' => (int) $inspectionStats['failed'],
                    'conditional' => (int) $inspectionStats['conditional'],
                    'avg_duration' => round((float) $inspectionStats['avg_duration'], 1),
                    'unique_ladders' => (int) $inspectionStats['unique_ladders'],
                    'unique_inspectors' => (int) $inspectionStats['unique_inspectors']
                ]
            ];
        } catch (Exception $e) {
            error_log("Dashboard Statistics Error: " . $e->getMessage());
            return [
                'ladders' => ['total' => 0, 'active' => 0, 'inactive' => 0, 'defective' => 0, 'disposed' => 0, 'needs_inspection' => 0, 'inspection_due_30_days' => 0],
                'inspections' => ['total' => 0, 'passed' => 0, 'failed' => 0, 'conditional' => 0, 'avg_duration' => 0, 'unique_ladders' => 0, 'unique_inspectors' => 0]
            ];
        }
    }

    /**
     * Gibt anstehende Prüfungen zurück
     */
    public function getUpcomingInspections(int $daysAhead = 30): array
    {
        try {
            return $this->inspectionRepository->getUpcoming($daysAhead);
        } catch (Exception $e) {
            error_log("Dashboard Upcoming Inspections Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Gibt überfällige Prüfungen zurück
     */
    public function getOverdueInspections(): array
    {
        try {
            $sql = "SELECT l.*, 
                           DATEDIFF(CURDATE(), l.next_inspection_date) as days_overdue,
                           last_inspection.inspection_date as last_inspection_date,
                           last_inspection.overall_result as last_result
                    FROM ladders l
                    LEFT JOIN (
                        SELECT ladder_id, 
                               MAX(inspection_date) as inspection_date,
                               overall_result
                        FROM inspections 
                        GROUP BY ladder_id
                    ) last_inspection ON l.id = last_inspection.ladder_id
                    WHERE l.status = 'active' 
                    AND l.next_inspection_date < CURDATE()
                    ORDER BY days_overdue DESC";

            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Dashboard Overdue Inspections Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Gibt heute anstehende Prüfungen zurück
     */
    public function getTodayInspections(): array
    {
        try {
            $sql = "SELECT l.*, 
                           last_inspection.inspection_date as last_inspection_date,
                           last_inspection.overall_result as last_result
                    FROM ladders l
                    LEFT JOIN (
                        SELECT ladder_id, 
                               MAX(inspection_date) as inspection_date,
                               overall_result
                        FROM inspections 
                        GROUP BY ladder_id
                    ) last_inspection ON l.id = last_inspection.ladder_id
                    WHERE l.status = 'active' 
                    AND l.next_inspection_date = CURDATE()
                    ORDER BY l.ladder_number ASC";

            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Dashboard Today Inspections Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Gibt letzte Aktivitäten zurück
     */
    public function getRecentActivity(int $limit = 10): array
    {
        try {
            $sql = "SELECT i.*, 
                           l.ladder_number, l.manufacturer, l.model, l.location,
                           u.display_name as inspector_name
                    FROM inspections i
                    LEFT JOIN ladders l ON i.ladder_id = l.id
                    LEFT JOIN users u ON i.inspector_id = u.id
                    ORDER BY i.created_at DESC
                    LIMIT :limit";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Dashboard Recent Activity Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Gibt Mängel-Statistiken zurück
     */
    public function getDefectStatistics(): array
    {
        try {
            return $this->inspectionRepository->getDefectStatistics();
        } catch (Exception $e) {
            error_log("Dashboard Defect Statistics Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Gibt monatliche Statistiken zurück
     */
    public function getMonthlyStatistics(int $months = 12): array
    {
        try {
            $sql = "SELECT 
                        DATE_FORMAT(inspection_date, '%Y-%m') as month,
                        COUNT(*) as total_inspections,
                        SUM(CASE WHEN overall_result = 'passed' THEN 1 ELSE 0 END) as passed,
                        SUM(CASE WHEN overall_result = 'failed' THEN 1 ELSE 0 END) as failed,
                        SUM(CASE WHEN overall_result = 'conditional' THEN 1 ELSE 0 END) as conditional
                    FROM inspections 
                    WHERE inspection_date >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
                    GROUP BY DATE_FORMAT(inspection_date, '%Y-%m')
                    ORDER BY month ASC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':months', $months, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Dashboard Monthly Statistics Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Gibt Abteilungs-Statistiken zurück
     */
    public function getDepartmentStatistics(): array
    {
        try {
            $sql = "SELECT 
                        COALESCE(l.department, 'Unbekannt') as department,
                        COUNT(l.id) as total_ladders,
                        SUM(CASE WHEN l.status = 'active' THEN 1 ELSE 0 END) as active_ladders,
                        SUM(CASE WHEN l.status = 'active' AND l.next_inspection_date <= CURDATE() THEN 1 ELSE 0 END) as needs_inspection,
                        SUM(CASE WHEN l.status = 'defective' THEN 1 ELSE 0 END) as defective_ladders
                    FROM ladders l
                    GROUP BY COALESCE(l.department, 'Unbekannt')
                    ORDER BY total_ladders DESC";

            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Dashboard Department Statistics Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Gibt Benutzer-spezifische Übersicht zurück
     */
    public function getUserOverview(int $userId): array
    {
        try {
            // Prüfungen des Benutzers
            $sql = "SELECT 
                        COUNT(*) as total_inspections,
                        SUM(CASE WHEN inspection_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as inspections_last_30_days,
                        SUM(CASE WHEN overall_result = 'passed' THEN 1 ELSE 0 END) as passed,
                        SUM(CASE WHEN overall_result = 'failed' THEN 1 ELSE 0 END) as failed,
                        AVG(inspection_duration_minutes) as avg_duration
                    FROM inspections 
                    WHERE inspector_id = :user_id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            $userStats = $stmt->fetch(PDO::FETCH_ASSOC);

            // Letzte Prüfungen des Benutzers
            $sql = "SELECT i.*, l.ladder_number, l.manufacturer, l.model, l.location
                    FROM inspections i
                    LEFT JOIN ladders l ON i.ladder_id = l.id
                    WHERE i.inspector_id = :user_id
                    ORDER BY i.inspection_date DESC
                    LIMIT 5";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            $recentInspections = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'statistics' => $userStats,
                'recent_inspections' => $recentInspections
            ];
        } catch (Exception $e) {
            error_log("Dashboard User Overview Error: " . $e->getMessage());
            return [
                'statistics' => ['total_inspections' => 0, 'inspections_last_30_days' => 0, 'passed' => 0, 'failed' => 0, 'avg_duration' => 0],
                'recent_inspections' => []
            ];
        }
    }

    /**
     * Gibt Prüfungstyp-Statistiken zurück
     */
    public function getInspectionTypeStatistics(): array
    {
        try {
            $sql = "SELECT 
                        inspection_type,
                        COUNT(*) as count,
                        SUM(CASE WHEN overall_result = 'passed' THEN 1 ELSE 0 END) as passed,
                        SUM(CASE WHEN overall_result = 'failed' THEN 1 ELSE 0 END) as failed,
                        SUM(CASE WHEN overall_result = 'conditional' THEN 1 ELSE 0 END) as conditional,
                        AVG(inspection_duration_minutes) as avg_duration
                    FROM inspections 
                    WHERE inspection_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                    GROUP BY inspection_type
                    ORDER BY count DESC";

            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Dashboard Inspection Type Statistics Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Gibt Leiter-Typ-Statistiken zurück
     */
    public function getLadderTypeStatistics(): array
    {
        try {
            $sql = "SELECT 
                        ladder_type,
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                        SUM(CASE WHEN status = 'defective' THEN 1 ELSE 0 END) as defective,
                        SUM(CASE WHEN status = 'active' AND next_inspection_date <= CURDATE() THEN 1 ELSE 0 END) as needs_inspection
                    FROM ladders 
                    GROUP BY ladder_type
                    ORDER BY total DESC";

            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Dashboard Ladder Type Statistics Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Gibt Standort-Statistiken zurück
     */
    public function getLocationStatistics(): array
    {
        try {
            $sql = "SELECT 
                        location,
                        COUNT(*) as total_ladders,
                        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_ladders,
                        SUM(CASE WHEN status = 'active' AND next_inspection_date <= CURDATE() THEN 1 ELSE 0 END) as needs_inspection,
                        SUM(CASE WHEN status = 'defective' THEN 1 ELSE 0 END) as defective_ladders
                    FROM ladders 
                    GROUP BY location
                    ORDER BY total_ladders DESC
                    LIMIT 10";

            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Dashboard Location Statistics Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Gibt Wartungsintervall-Analyse zurück
     */
    public function getMaintenanceIntervalAnalysis(): array
    {
        try {
            $sql = "SELECT 
                        inspection_interval_months,
                        COUNT(*) as ladder_count,
                        AVG(DATEDIFF(CURDATE(), purchase_date) / 365.25) as avg_age_years,
                        SUM(CASE WHEN status = 'active' AND next_inspection_date <= CURDATE() THEN 1 ELSE 0 END) as overdue_count
                    FROM ladders 
                    WHERE status = 'active'
                    GROUP BY inspection_interval_months
                    ORDER BY inspection_interval_months ASC";

            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Dashboard Maintenance Interval Analysis Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Exportiert Dashboard-Daten als JSON
     */
    public function exportData(array $filters = []): array
    {
        try {
            $data = [
                'export_date' => date('Y-m-d H:i:s'),
                'filters' => $filters,
                'overview' => $this->getOverviewData(),
                'inspection_types' => $this->getInspectionTypeStatistics(),
                'ladder_types' => $this->getLadderTypeStatistics(),
                'locations' => $this->getLocationStatistics(),
                'maintenance_intervals' => $this->getMaintenanceIntervalAnalysis()
            ];

            return $data;
        } catch (Exception $e) {
            error_log("Dashboard Export Error: " . $e->getMessage());
            throw new RuntimeException('Fehler beim Exportieren der Dashboard-Daten: ' . $e->getMessage());
        }
    }
}
