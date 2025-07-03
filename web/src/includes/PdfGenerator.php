<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

use TCPDF;

/**
 * PdfGenerator - Generiert PDF-Dokumente für Berichte
 * 
 * @author System
 * @version 1.0
 */
class PdfGenerator
{
    private TemplateEngine $templateEngine;
    private array $config;

    public function __construct(TemplateEngine $templateEngine, array $config = [])
    {
        $this->templateEngine = $templateEngine;
        $this->config = array_merge([
            'company_name' => 'Leiterverwaltung',
            'company_address' => 'Musterstraße 1, 12345 Musterstadt',
            'company_phone' => '+49 123 456789',
            'company_email' => 'info@example.com',
            'logo_path' => null,
            'watermark' => 'VERTRAULICH',
            'font_family' => 'helvetica',
            'font_size' => 10,
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 27,
            'margin_bottom' => 25,
            'header_height' => 15,
            'footer_height' => 10
        ], $config);
    }

    /**
     * Generiert ein PDF aus Berichtsdaten
     */
    public function generatePdf(array $reportData, string $templateName = null): string
    {
        // PDF-Klasse initialisieren
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Dokument-Informationen setzen
        $pdf->SetCreator('Leiterverwaltungssystem');
        $pdf->SetAuthor($this->config['company_name']);
        $pdf->SetTitle($reportData['title'] ?? 'Bericht');
        $pdf->SetSubject('Automatisch generierter Bericht');
        $pdf->SetKeywords('Leiter, Prüfung, Bericht, PDF');

        // Seitenränder setzen
        $pdf->SetMargins($this->config['margin_left'], $this->config['margin_top'], $this->config['margin_right']);
        $pdf->SetHeaderMargin($this->config['header_height']);
        $pdf->SetFooterMargin($this->config['footer_height']);

        // Auto-Seitenumbruch
        $pdf->SetAutoPageBreak(TRUE, $this->config['margin_bottom']);

        // Schriftart setzen
        $pdf->SetFont($this->config['font_family'], '', $this->config['font_size']);

        // Header und Footer konfigurieren
        $this->setupHeaderFooter($pdf, $reportData);

        // Wasserzeichen hinzufügen
        if (!empty($this->config['watermark'])) {
            $this->addWatermark($pdf, $this->config['watermark']);
        }

        // Erste Seite hinzufügen
        $pdf->AddPage();

        // Inhalt basierend auf Berichtstyp generieren
        switch ($reportData['type']) {
            case 'inspection_report':
                $this->generateInspectionReportContent($pdf, $reportData);
                break;
            case 'overview_report':
                $this->generateOverviewReportContent($pdf, $reportData);
                break;
            case 'statistics_report':
                $this->generateStatisticsReportContent($pdf, $reportData);
                break;
            case 'calendar_report':
                $this->generateCalendarReportContent($pdf, $reportData);
                break;
            case 'failure_report':
                $this->generateFailureReportContent($pdf, $reportData);
                break;
            default:
                $this->generateGenericReportContent($pdf, $reportData);
        }

        // PDF als String zurückgeben
        return $pdf->Output('', 'S');
    }

    /**
     * Speichert PDF in Datei
     */
    public function savePdf(array $reportData, string $filename, string $templateName = null): string
    {
        $pdfContent = $this->generatePdf($reportData, $templateName);
        
        // Verzeichnis erstellen falls nicht vorhanden
        $directory = dirname($filename);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        file_put_contents($filename, $pdfContent);
        return $filename;
    }

    /**
     * Konfiguriert Header und Footer
     */
    private function setupHeaderFooter(TCPDF $pdf, array $reportData): void
    {
        // Header-Callback
        $pdf->setHeaderCallback(function($pdf) use ($reportData) {
            $pdf->SetFont($this->config['font_family'], 'B', 12);
            
            // Logo falls vorhanden
            if (!empty($this->config['logo_path']) && file_exists($this->config['logo_path'])) {
                $pdf->Image($this->config['logo_path'], 15, 10, 30, 0, '', '', 'T', false, 300, '', false, false, 0, false, false, false);
                $pdf->SetXY(50, 15);
            } else {
                $pdf->SetXY(15, 15);
            }
            
            // Titel
            $pdf->Cell(0, 10, $reportData['title'] ?? 'Bericht', 0, 1, 'L');
            
            // Linie unter Header
            $pdf->SetLineStyle(['width' => 0.5, 'color' => [200, 200, 200]]);
            $pdf->Line(15, 25, 195, 25);
        });

        // Footer-Callback
        $pdf->setFooterCallback(function($pdf) use ($reportData) {
            $pdf->SetY(-15);
            $pdf->SetFont($this->config['font_family'], '', 8);
            
            // Linie über Footer
            $pdf->SetLineStyle(['width' => 0.5, 'color' => [200, 200, 200]]);
            $pdf->Line(15, $pdf->GetY() - 5, 195, $pdf->GetY() - 5);
            
            // Footer-Inhalt
            $pdf->Cell(0, 10, $this->config['company_name'] . ' - ' . $this->config['company_address'], 0, 0, 'L');
            $pdf->Cell(0, 10, 'Seite ' . $pdf->getAliasNumPage() . ' von ' . $pdf->getAliasNbPages(), 0, 0, 'R');
            $pdf->Ln();
            $pdf->Cell(0, 5, 'Generiert am: ' . ($reportData['generated_at'] ?? date('d.m.Y H:i')), 0, 0, 'L');
        });
    }

    /**
     * Fügt Wasserzeichen hinzu
     */
    private function addWatermark(TCPDF $pdf, string $text): void
    {
        $pdf->setWatermarkText($text, 0.1);
        $pdf->showWatermarkText = true;
    }

    /**
     * Generiert Inhalt für Prüfungsprotokoll
     */
    private function generateInspectionReportContent(TCPDF $pdf, array $reportData): void
    {
        $data = $reportData['data'];
        $ladder = $data['ladder'];
        $inspection = $data['inspection'];
        
        // Titel
        $pdf->SetFont($this->config['font_family'], 'B', 16);
        $pdf->Cell(0, 10, 'Prüfungsprotokoll', 0, 1, 'C');
        $pdf->Ln(5);

        // Leiter-Informationen
        $pdf->SetFont($this->config['font_family'], 'B', 12);
        $pdf->Cell(0, 8, 'Leiter-Informationen', 0, 1, 'L');
        $pdf->SetFont($this->config['font_family'], '', 10);
        
        $pdf->Cell(50, 6, 'Leiternummer:', 0, 0, 'L');
        $pdf->Cell(0, 6, $ladder->getLadderNumber(), 0, 1, 'L');
        
        $pdf->Cell(50, 6, 'Hersteller:', 0, 0, 'L');
        $pdf->Cell(0, 6, $ladder->getManufacturer(), 0, 1, 'L');
        
        $pdf->Cell(50, 6, 'Typ:', 0, 0, 'L');
        $pdf->Cell(0, 6, $ladder->getLadderType(), 0, 1, 'L');
        
        $pdf->Cell(50, 6, 'Standort:', 0, 0, 'L');
        $pdf->Cell(0, 6, $ladder->getLocation(), 0, 1, 'L');
        
        $pdf->Ln(5);

        // Prüfungs-Informationen
        $pdf->SetFont($this->config['font_family'], 'B', 12);
        $pdf->Cell(0, 8, 'Prüfungs-Informationen', 0, 1, 'L');
        $pdf->SetFont($this->config['font_family'], '', 10);
        
        $pdf->Cell(50, 6, 'Prüfdatum:', 0, 0, 'L');
        $pdf->Cell(0, 6, date('d.m.Y', strtotime($inspection->getInspectionDate())), 0, 1, 'L');
        
        $pdf->Cell(50, 6, 'Prüftyp:', 0, 0, 'L');
        $pdf->Cell(0, 6, ucfirst($inspection->getInspectionType()), 0, 1, 'L');
        
        $pdf->Cell(50, 6, 'Ergebnis:', 0, 0, 'L');
        $resultColor = $this->getResultColor($inspection->getOverallResult());
        $pdf->SetTextColor($resultColor[0], $resultColor[1], $resultColor[2]);
        $pdf->Cell(0, 6, strtoupper($inspection->getOverallResult()), 0, 1, 'L');
        $pdf->SetTextColor(0, 0, 0);
        
        if ($data['inspector']) {
            $pdf->Cell(50, 6, 'Prüfer:', 0, 0, 'L');
            $pdf->Cell(0, 6, $data['inspector']['display_name'], 0, 1, 'L');
        }
        
        $pdf->Ln(5);

        // Prüfpunkte
        if (!empty($data['inspection_items'])) {
            $pdf->SetFont($this->config['font_family'], 'B', 12);
            $pdf->Cell(0, 8, 'Prüfpunkte', 0, 1, 'L');
            
            $this->generateInspectionItemsTable($pdf, $data['inspection_items']);
        }

        // Mängel
        if (!empty($data['defects'])) {
            $pdf->AddPage();
            $pdf->SetFont($this->config['font_family'], 'B', 12);
            $pdf->Cell(0, 8, 'Festgestellte Mängel', 0, 1, 'L');
            
            $this->generateDefectsTable($pdf, $data['defects']);
        }

        // Bemerkungen
        if ($inspection->getGeneralNotes()) {
            $pdf->Ln(10);
            $pdf->SetFont($this->config['font_family'], 'B', 12);
            $pdf->Cell(0, 8, 'Bemerkungen', 0, 1, 'L');
            $pdf->SetFont($this->config['font_family'], '', 10);
            $pdf->MultiCell(0, 6, $inspection->getGeneralNotes(), 0, 'L');
        }

        // Empfehlungen
        if ($inspection->getRecommendations()) {
            $pdf->Ln(5);
            $pdf->SetFont($this->config['font_family'], 'B', 12);
            $pdf->Cell(0, 8, 'Empfehlungen', 0, 1, 'L');
            $pdf->SetFont($this->config['font_family'], '', 10);
            $pdf->MultiCell(0, 6, $inspection->getRecommendations(), 0, 'L');
        }

        // Unterschriften
        $pdf->Ln(20);
        $this->generateSignatureSection($pdf, $data);
    }

    /**
     * Generiert Tabelle für Prüfpunkte
     */
    private function generateInspectionItemsTable(TCPDF $pdf, array $items): void
    {
        $pdf->SetFont($this->config['font_family'], 'B', 9);
        
        // Tabellen-Header
        $pdf->Cell(60, 8, 'Prüfpunkt', 1, 0, 'C');
        $pdf->Cell(30, 8, 'Kategorie', 1, 0, 'C');
        $pdf->Cell(25, 8, 'Ergebnis', 1, 0, 'C');
        $pdf->Cell(20, 8, 'Schwere', 1, 0, 'C');
        $pdf->Cell(45, 8, 'Bemerkungen', 1, 1, 'C');
        
        $pdf->SetFont($this->config['font_family'], '', 8);
        
        foreach ($items as $item) {
            $pdf->Cell(60, 6, $item->getItemName(), 1, 0, 'L');
            $pdf->Cell(30, 6, $item->getCategory(), 1, 0, 'L');
            
            // Ergebnis mit Farbe
            $resultColor = $this->getResultColor($item->getResult());
            $pdf->SetTextColor($resultColor[0], $resultColor[1], $resultColor[2]);
            $pdf->Cell(25, 6, strtoupper($item->getResult()), 1, 0, 'C');
            $pdf->SetTextColor(0, 0, 0);
            
            $pdf->Cell(20, 6, $item->getSeverity() ?: '-', 1, 0, 'C');
            $pdf->Cell(45, 6, substr($item->getNotes() ?: '', 0, 30), 1, 1, 'L');
        }
    }

    /**
     * Generiert Tabelle für Mängel
     */
    private function generateDefectsTable(TCPDF $pdf, array $defects): void
    {
        $pdf->SetFont($this->config['font_family'], 'B', 9);
        
        // Tabellen-Header
        $pdf->Cell(50, 8, 'Mangel', 1, 0, 'C');
        $pdf->Cell(25, 8, 'Schweregrad', 1, 0, 'C');
        $pdf->Cell(25, 8, 'Reparatur nötig', 1, 0, 'C');
        $pdf->Cell(80, 8, 'Beschreibung', 1, 1, 'C');
        
        $pdf->SetFont($this->config['font_family'], '', 8);
        
        foreach ($defects as $defect) {
            $pdf->Cell(50, 6, $defect->getItemName(), 1, 0, 'L');
            
            // Schweregrad mit Farbe
            $severityColor = $this->getSeverityColor($defect->getSeverity());
            $pdf->SetTextColor($severityColor[0], $severityColor[1], $severityColor[2]);
            $pdf->Cell(25, 6, strtoupper($defect->getSeverity()), 1, 0, 'C');
            $pdf->SetTextColor(0, 0, 0);
            
            $pdf->Cell(25, 6, $defect->isRepairRequired() ? 'JA' : 'NEIN', 1, 0, 'C');
            $pdf->Cell(80, 6, substr($defect->getNotes() ?: '', 0, 50), 1, 1, 'L');
        }
    }

    /**
     * Generiert Unterschriften-Bereich
     */
    private function generateSignatureSection(TCPDF $pdf, array $data): void
    {
        $pdf->SetFont($this->config['font_family'], '', 10);
        
        // Prüfer-Unterschrift
        $pdf->Cell(90, 20, '', 'B', 0, 'L');
        $pdf->Cell(10, 20, '', 0, 0, 'L');
        $pdf->Cell(90, 20, '', 'B', 1, 'L');
        
        $pdf->Cell(90, 6, 'Unterschrift Prüfer', 0, 0, 'L');
        $pdf->Cell(10, 6, '', 0, 0, 'L');
        $pdf->Cell(90, 6, 'Unterschrift Vorgesetzter', 0, 1, 'L');
        
        if ($data['inspector']) {
            $pdf->Cell(90, 6, $data['inspector']['display_name'], 0, 0, 'L');
        }
        
        $pdf->Cell(10, 6, '', 0, 0, 'L');
        
        if ($data['supervisor']) {
            $pdf->Cell(90, 6, $data['supervisor']['display_name'], 0, 1, 'L');
        }
    }

    /**
     * Generiert Inhalt für andere Berichtstypen (vereinfacht)
     */
    private function generateOverviewReportContent(TCPDF $pdf, array $reportData): void
    {
        $this->generateGenericReportContent($pdf, $reportData);
    }

    private function generateStatisticsReportContent(TCPDF $pdf, array $reportData): void
    {
        $this->generateGenericReportContent($pdf, $reportData);
    }

    private function generateCalendarReportContent(TCPDF $pdf, array $reportData): void
    {
        $this->generateGenericReportContent($pdf, $reportData);
    }

    private function generateFailureReportContent(TCPDF $pdf, array $reportData): void
    {
        $this->generateGenericReportContent($pdf, $reportData);
    }

    /**
     * Generiert generischen Berichtsinhalt
     */
    private function generateGenericReportContent(TCPDF $pdf, array $reportData): void
    {
        // Titel
        $pdf->SetFont($this->config['font_family'], 'B', 16);
        $pdf->Cell(0, 10, $reportData['title'] ?? 'Bericht', 0, 1, 'C');
        $pdf->Ln(10);

        // Zusammenfassung falls vorhanden
        if (!empty($reportData['data']['summary'])) {
            $pdf->SetFont($this->config['font_family'], 'B', 12);
            $pdf->Cell(0, 8, 'Zusammenfassung', 0, 1, 'L');
            $pdf->SetFont($this->config['font_family'], '', 10);
            
            foreach ($reportData['data']['summary'] as $key => $value) {
                $pdf->Cell(60, 6, ucfirst(str_replace('_', ' ', $key)) . ':', 0, 0, 'L');
                $pdf->Cell(0, 6, $value, 0, 1, 'L');
            }
            $pdf->Ln(5);
        }

        // Hinweis für detaillierte Berichte
        $pdf->SetFont($this->config['font_family'], 'I', 10);
        $pdf->MultiCell(0, 6, 'Für detaillierte Informationen nutzen Sie bitte die Excel-Export-Funktion oder die Web-Ansicht.', 0, 'L');
    }

    /**
     * Gibt Farbe für Ergebnis zurück
     */
    private function getResultColor(string $result): array
    {
        switch ($result) {
            case 'passed':
            case 'ok':
                return [0, 150, 0]; // Grün
            case 'failed':
            case 'defect':
                return [200, 0, 0]; // Rot
            case 'conditional':
            case 'wear':
                return [255, 140, 0]; // Orange
            default:
                return [0, 0, 0]; // Schwarz
        }
    }

    /**
     * Gibt Farbe für Schweregrad zurück
     */
    private function getSeverityColor(string $severity): array
    {
        switch ($severity) {
            case 'critical':
                return [200, 0, 0]; // Rot
            case 'high':
                return [255, 100, 0]; // Orange-Rot
            case 'medium':
                return [255, 140, 0]; // Orange
            case 'low':
                return [255, 200, 0]; // Gelb
            default:
                return [0, 0, 0]; // Schwarz
        }
    }
}
