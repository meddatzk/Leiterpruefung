<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

/**
 * ExcelExporter - Exportiert Berichte als Excel-Dateien
 * 
 * @author System
 * @version 1.0
 */
class ExcelExporter
{
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'company_name' => 'Leiterverwaltung',
            'company_address' => 'Musterstraße 1, 12345 Musterstadt',
            'logo_path' => null,
            'header_color' => 'FF4472C4',
            'accent_color' => 'FFE7E6E6',
            'success_color' => 'FF70AD47',
            'warning_color' => 'FFFFC000',
            'danger_color' => 'FFC5504B'
        ], $config);
    }

    /**
     * Generiert Excel-Datei aus Berichtsdaten
     */
    public function generateExcel(array $reportData): string
    {
        $spreadsheet = new Spreadsheet();
        
        // Dokument-Eigenschaften setzen
        $properties = $spreadsheet->getProperties();
        $properties->setCreator($this->config['company_name'])
                   ->setLastModifiedBy($this->config['company_name'])
                   ->setTitle($reportData['title'] ?? 'Bericht')
                   ->setSubject('Automatisch generierter Bericht')
                   ->setDescription('Bericht aus dem Leiterverwaltungssystem')
                   ->setKeywords('Leiter Prüfung Bericht Excel')
                   ->setCategory('Berichte');

        // Inhalt basierend auf Berichtstyp generieren
        switch ($reportData['type']) {
            case 'inspection_report':
                $this->generateInspectionReportExcel($spreadsheet, $reportData);
                break;
            case 'overview_report':
                $this->generateOverviewReportExcel($spreadsheet, $reportData);
                break;
            case 'statistics_report':
                $this->generateStatisticsReportExcel($spreadsheet, $reportData);
                break;
            case 'calendar_report':
                $this->generateCalendarReportExcel($spreadsheet, $reportData);
                break;
            case 'failure_report':
                $this->generateFailureReportExcel($spreadsheet, $reportData);
                break;
            default:
                $this->generateGenericReportExcel($spreadsheet, $reportData);
        }

        // Excel-Datei als String zurückgeben
        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        return ob_get_clean();
    }

    /**
     * Speichert Excel-Datei
     */
    public function saveExcel(array $reportData, string $filename): string
    {
        $excelContent = $this->generateExcel($reportData);
        
        // Verzeichnis erstellen falls nicht vorhanden
        $directory = dirname($filename);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        file_put_contents($filename, $excelContent);
        return $filename;
    }

    /**
     * Generiert Excel für Prüfungsprotokoll
     */
    private function generateInspectionReportExcel(Spreadsheet $spreadsheet, array $reportData): void
    {
        $data = $reportData['data'];
        $ladder = $data['ladder'];
        $inspection = $data['inspection'];
        
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Prüfungsprotokoll');
        
        $row = 1;
        
        // Header
        $this->addReportHeader($sheet, $reportData['title'], $row);
        $row += 3;
        
        // Leiter-Informationen
        $sheet->setCellValue("A{$row}", 'Leiter-Informationen');
        $this->styleHeader($sheet, "A{$row}:F{$row}");
        $row++;
        
        $ladderInfo = [
            ['Leiternummer:', $ladder->getLadderNumber()],
            ['Hersteller:', $ladder->getManufacturer()],
            ['Modell:', $ladder->getModel() ?: '-'],
            ['Typ:', $ladder->getLadderType()],
            ['Material:', $ladder->getMaterial()],
            ['Höhe (cm):', $ladder->getHeightCm()],
            ['Max. Belastung (kg):', $ladder->getMaxLoadKg()],
            ['Standort:', $ladder->getLocation()],
            ['Abteilung:', $ladder->getDepartment() ?: '-'],
            ['Verantwortlich:', $ladder->getResponsiblePerson() ?: '-']
        ];
        
        foreach ($ladderInfo as $info) {
            $sheet->setCellValue("A{$row}", $info[0]);
            $sheet->setCellValue("B{$row}", $info[1]);
            $row++;
        }
        
        $row += 2;
        
        // Prüfungs-Informationen
        $sheet->setCellValue("A{$row}", 'Prüfungs-Informationen');
        $this->styleHeader($sheet, "A{$row}:F{$row}");
        $row++;
        
        $inspectionInfo = [
            ['Prüfdatum:', date('d.m.Y', strtotime($inspection->getInspectionDate()))],
            ['Prüftyp:', ucfirst($inspection->getInspectionType())],
            ['Ergebnis:', strtoupper($inspection->getOverallResult())],
            ['Nächste Prüfung:', date('d.m.Y', strtotime($inspection->getNextInspectionDate()))],
            ['Prüfdauer (Min):', $inspection->getInspectionDurationMinutes() ?: '-'],
            ['Prüfer:', $data['inspector']['display_name'] ?? '-'],
            ['Vorgesetzter:', $data['supervisor']['display_name'] ?? '-']
        ];
        
        foreach ($inspectionInfo as $info) {
            $sheet->setCellValue("A{$row}", $info[0]);
            $sheet->setCellValue("B{$row}", $info[1]);
            
            // Ergebnis farblich hervorheben
            if ($info[0] === 'Ergebnis:') {
                $this->styleResultCell($sheet, "B{$row}", $inspection->getOverallResult());
            }
            
            $row++;
        }
        
        $row += 2;
        
        // Prüfpunkte
        if (!empty($data['inspection_items'])) {
            $sheet->setCellValue("A{$row}", 'Prüfpunkte');
            $this->styleHeader($sheet, "A{$row}:G{$row}");
            $row++;
            
            // Tabellen-Header
            $headers = ['Kategorie', 'Prüfpunkt', 'Beschreibung', 'Ergebnis', 'Schweregrad', 'Reparatur nötig', 'Bemerkungen'];
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue("{$col}{$row}", $header);
                $col++;
            }
            $this->styleTableHeader($sheet, "A{$row}:G{$row}");
            $row++;
            
            // Prüfpunkte-Daten
            foreach ($data['inspection_items'] as $item) {
                $sheet->setCellValue("A{$row}", $item->getCategory());
                $sheet->setCellValue("B{$row}", $item->getItemName());
                $sheet->setCellValue("C{$row}", $item->getDescription() ?: '-');
                $sheet->setCellValue("D{$row}", strtoupper($item->getResult()));
                $sheet->setCellValue("E{$row}", $item->getSeverity() ?: '-');
                $sheet->setCellValue("F{$row}", $item->isRepairRequired() ? 'JA' : 'NEIN');
                $sheet->setCellValue("G{$row}", $item->getNotes() ?: '-');
                
                // Ergebnis farblich hervorheben
                $this->styleResultCell($sheet, "D{$row}", $item->getResult());
                
                // Schweregrad farblich hervorheben
                if ($item->getSeverity()) {
                    $this->styleSeverityCell($sheet, "E{$row}", $item->getSeverity());
                }
                
                $row++;
            }
            
            // Tabelle formatieren
            $this->styleTable($sheet, "A" . ($row - count($data['inspection_items']) - 1) . ":G" . ($row - 1));
        }
        
        // Spaltenbreiten anpassen
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(40);
        
        // Zusätzliche Arbeitsblätter für Mängel und Historie
        if (!empty($data['defects'])) {
            $this->addDefectsSheet($spreadsheet, $data['defects']);
        }
        
        if (!empty($data['history'])) {
            $this->addHistorySheet($spreadsheet, $data['history']);
        }
    }

    /**
     * Generiert Excel für Übersichtsbericht
     */
    private function generateOverviewReportExcel(Spreadsheet $spreadsheet, array $reportData): void
    {
        $data = $reportData['data'];
        
        // Hauptarbeitsblatt - Leitern-Übersicht
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Leitern-Übersicht');
        
        $row = 1;
        
        // Header
        $this->addReportHeader($sheet, $reportData['title'], $row);
        $row += 3;
        
        // Zusammenfassung
        $sheet->setCellValue("A{$row}", 'Zusammenfassung');
        $this->styleHeader($sheet, "A{$row}:D{$row}");
        $row++;
        
        $summary = $data['summary'];
        $summaryData = [
            ['Gesamt Leitern:', $summary['total_ladders']],
            ['Aktive Leitern:', $summary['active_ladders']],
            ['Prüfung erforderlich:', $summary['needs_inspection']],
            ['Prüfung in 30 Tagen:', $summary['inspection_due_30_days']]
        ];
        
        foreach ($summaryData as $item) {
            $sheet->setCellValue("A{$row}", $item[0]);
            $sheet->setCellValue("B{$row}", $item[1]);
            $row++;
        }
        
        $row += 2;
        
        // Leitern-Liste
        $sheet->setCellValue("A{$row}", 'Leitern-Liste');
        $this->styleHeader($sheet, "A{$row}:L{$row}");
        $row++;
        
        // Tabellen-Header
        $headers = ['Leiternummer', 'Hersteller', 'Modell', 'Typ', 'Material', 'Höhe (cm)', 
                   'Belastung (kg)', 'Standort', 'Abteilung', 'Status', 'Nächste Prüfung', 'Tage bis Prüfung'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue("{$col}{$row}", $header);
            $col++;
        }
        $this->styleTableHeader($sheet, "A{$row}:L{$row}");
        $row++;
        
        // Leitern-Daten
        foreach ($data['ladders'] as $ladder) {
            $daysUntilInspection = $ladder->getDaysUntilInspection();
            
            $sheet->setCellValue("A{$row}", $ladder->getLadderNumber());
            $sheet->setCellValue("B{$row}", $ladder->getManufacturer());
            $sheet->setCellValue("C{$row}", $ladder->getModel() ?: '-');
            $sheet->setCellValue("D{$row}", $ladder->getLadderType());
            $sheet->setCellValue("E{$row}", $ladder->getMaterial());
            $sheet->setCellValue("F{$row}", $ladder->getHeightCm());
            $sheet->setCellValue("G{$row}", $ladder->getMaxLoadKg());
            $sheet->setCellValue("H{$row}", $ladder->getLocation());
            $sheet->setCellValue("I{$row}", $ladder->getDepartment() ?: '-');
            $sheet->setCellValue("J{$row}", strtoupper($ladder->getStatus()));
            $sheet->setCellValue("K{$row}", date('d.m.Y', strtotime($ladder->getNextInspectionDate())));
            $sheet->setCellValue("L{$row}", $daysUntilInspection);
            
            // Status farblich hervorheben
            $this->styleStatusCell($sheet, "J{$row}", $ladder->getStatus());
            
            // Prüfdatum farblich hervorheben
            if ($daysUntilInspection <= 0) {
                $this->styleCell($sheet, "K{$row}", $this->config['danger_color']);
                $this->styleCell($sheet, "L{$row}", $this->config['danger_color']);
            } elseif ($daysUntilInspection <= 30) {
                $this->styleCell($sheet, "K{$row}", $this->config['warning_color']);
                $this->styleCell($sheet, "L{$row}", $this->config['warning_color']);
            }
            
            $row++;
        }
        
        // Tabelle formatieren
        $this->styleTable($sheet, "A" . ($row - count($data['ladders']) - 1) . ":L" . ($row - 1));
        
        // Spaltenbreiten anpassen
        $this->autoSizeColumns($sheet, 'A', 'L');
        
        // Zusätzliche Arbeitsblätter
        $this->addStatisticsSheet($spreadsheet, $data['statistics']);
        $this->addLocationStatsSheet($spreadsheet, $data['location_statistics']);
    }

    /**
     * Generiert Excel für Statistikbericht
     */
    private function generateStatisticsReportExcel(Spreadsheet $spreadsheet, array $reportData): void
    {
        $data = $reportData['data'];
        
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Statistiken');
        
        $row = 1;
        
        // Header
        $this->addReportHeader($sheet, $reportData['title'], $row);
        $row += 3;
        
        // Zeitraum
        if (!empty($reportData['period'])) {
            $sheet->setCellValue("A{$row}", 'Zeitraum:');
            $sheet->setCellValue("B{$row}", date('d.m.Y', strtotime($reportData['period']['from'])) . ' - ' . 
                                           date('d.m.Y', strtotime($reportData['period']['to'])));
            $row += 2;
        }
        
        // Zusammenfassung
        $sheet->setCellValue("A{$row}", 'Zusammenfassung');
        $this->styleHeader($sheet, "A{$row}:D{$row}");
        $row++;
        
        $summary = $data['summary'];
        $summaryData = [
            ['Gesamte Prüfungen:', $summary['total_inspections']],
            ['Erfolgsquote (%):', $summary['pass_rate']],
            ['Durchschnittsdauer (Min):', $summary['avg_duration']],
            ['Anzahl Prüfer:', $summary['unique_inspectors']]
        ];
        
        foreach ($summaryData as $item) {
            $sheet->setCellValue("A{$row}", $item[0]);
            $sheet->setCellValue("B{$row}", $item[1]);
            $row++;
        }
        
        // Weitere Arbeitsblätter für detaillierte Statistiken
        if (!empty($data['monthly_trends'])) {
            $this->addMonthlyTrendsSheet($spreadsheet, $data['monthly_trends']);
        }
        
        if (!empty($data['inspector_statistics'])) {
            $this->addInspectorStatsSheet($spreadsheet, $data['inspector_statistics']);
        }
        
        if (!empty($data['defect_statistics'])) {
            $this->addDefectStatsSheet($spreadsheet, $data['defect_statistics']);
        }
    }

    /**
     * Generiert generisches Excel
     */
    private function generateGenericReportExcel(Spreadsheet $spreadsheet, array $reportData): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Bericht');
        
        $row = 1;
        
        // Header
        $this->addReportHeader($sheet, $reportData['title'] ?? 'Bericht', $row);
        $row += 3;
        
        // Zusammenfassung falls vorhanden
        if (!empty($reportData['data']['summary'])) {
            $sheet->setCellValue("A{$row}", 'Zusammenfassung');
            $this->styleHeader($sheet, "A{$row}:D{$row}");
            $row++;
            
            foreach ($reportData['data']['summary'] as $key => $value) {
                $sheet->setCellValue("A{$row}", ucfirst(str_replace('_', ' ', $key)) . ':');
                $sheet->setCellValue("B{$row}", $value);
                $row++;
            }
        }
        
        // Spaltenbreiten anpassen
        $this->autoSizeColumns($sheet, 'A', 'D');
    }

    /**
     * Fügt Berichts-Header hinzu
     */
    private function addReportHeader(object $sheet, string $title, int &$row): void
    {
        // Logo falls vorhanden
        if (!empty($this->config['logo_path']) && file_exists($this->config['logo_path'])) {
            $drawing = new Drawing();
            $drawing->setName('Logo');
            $drawing->setDescription('Firmenlogo');
            $drawing->setPath($this->config['logo_path']);
            $drawing->setHeight(50);
            $drawing->setCoordinates("A{$row}");
            $drawing->setWorksheet($sheet);
            $row += 3;
        }
        
        // Titel
        $sheet->setCellValue("A{$row}", $title);
        $sheet->mergeCells("A{$row}:F{$row}");
        $this->styleTitle($sheet, "A{$row}");
        $row++;
        
        // Generierungsdatum
        $sheet->setCellValue("A{$row}", 'Generiert am: ' . date('d.m.Y H:i'));
        $row++;
    }

    /**
     * Styling-Methoden
     */
    private function styleTitle(object $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => '000000']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);
    }

    private function styleHeader(object $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => substr($this->config['header_color'], 2)]
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);
    }

    private function styleTableHeader(object $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => substr($this->config['header_color'], 2)]
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);
    }

    private function styleTable(object $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC']
                ]
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);
    }

    private function styleCell(object $sheet, string $cell, string $color): void
    {
        $sheet->getStyle($cell)->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => substr($color, 2)]
            ]
        ]);
    }

    private function styleResultCell(object $sheet, string $cell, string $result): void
    {
        $color = match($result) {
            'passed', 'ok' => $this->config['success_color'],
            'failed', 'defect' => $this->config['danger_color'],
            'conditional', 'wear' => $this->config['warning_color'],
            default => 'FFFFFFFF'
        };
        
        $this->styleCell($sheet, $cell, $color);
    }

    private function styleSeverityCell(object $sheet, string $cell, string $severity): void
    {
        $color = match($severity) {
            'critical' => $this->config['danger_color'],
            'high' => 'FFFF6B35',
            'medium' => $this->config['warning_color'],
            'low' => 'FFFFFF99',
            default => 'FFFFFFFF'
        };
        
        $this->styleCell($sheet, $cell, $color);
    }

    private function styleStatusCell(object $sheet, string $cell, string $status): void
    {
        $color = match($status) {
            'active' => $this->config['success_color'],
            'defective' => $this->config['danger_color'],
            'inactive' => $this->config['warning_color'],
            'disposed' => 'FFCCCCCC',
            default => 'FFFFFFFF'
        };
        
        $this->styleCell($sheet, $cell, $color);
    }

    private function autoSizeColumns(object $sheet, string $startCol, string $endCol): void
    {
        $currentCol = $startCol;
        while ($currentCol <= $endCol) {
            $sheet->getColumnDimension($currentCol)->setAutoSize(true);
            $currentCol++;
        }
    }

    /**
     * Zusätzliche Arbeitsblätter
     */
    private function addDefectsSheet(Spreadsheet $spreadsheet, array $defects): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Mängel');
        
        // Implementation für Mängel-Arbeitsblatt
        // Vereinfacht für Platzgründe
    }

    private function addHistorySheet(Spreadsheet $spreadsheet, array $history): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Historie');
        
        // Implementation für Historie-Arbeitsblatt
        // Vereinfacht für Platzgründe
    }

    private function addStatisticsSheet(Spreadsheet $spreadsheet, array $statistics): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Statistiken');
        
        // Implementation für Statistiken-Arbeitsblatt
        // Vereinfacht für Platzgründe
    }

    private function addLocationStatsSheet(Spreadsheet $spreadsheet, array $locationStats): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Standort-Statistiken');
        
        // Implementation für Standort-Statistiken
        // Vereinfacht für Platzgründe
    }

    private function addMonthlyTrendsSheet(Spreadsheet $spreadsheet, array $trends): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Monatliche Trends');
        
        // Implementation für monatliche Trends
        // Vereinfacht für Platzgründe
    }

    private function addInspectorStatsSheet(Spreadsheet $spreadsheet, array $inspectorStats): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Prüfer-Statistiken');
        
        // Implementation für Prüfer-Statistiken
        // Vereinfacht für Platzgründe
    }

    private function addDefectStatsSheet(Spreadsheet $spreadsheet, array $defectStats): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Mängel-Statistiken');
        
        // Implementation für Mängel-Statistiken
        // Vereinfacht für Platzgründe
    }

    private function generateCalendarReportExcel(Spreadsheet $spreadsheet, array $reportData): void
    {
        // Implementation für Kalender-Bericht
        $this->generateGenericReportExcel($spreadsheet, $reportData);
    }

    private function generateFailureReportExcel(Spreadsheet $spreadsheet, array $reportData): void
    {
        // Implementation für Ausfall-Bericht
        $this->generateGenericReportExcel($spreadsheet, $reportData);
    }
}
