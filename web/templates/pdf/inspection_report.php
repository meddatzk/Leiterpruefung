<?php
/**
 * PDF-Template für Prüfungsprotokoll
 * Dieses Template wird vom PdfGenerator verwendet
 */

// Template-Variablen extrahieren
$ladder = $data['ladder'];
$inspection = $data['inspection'];
$inspector = $data['inspector'];
$supervisor = $data['supervisor'];
$inspectionItems = $data['inspection_items'];
$defects = $data['defects'];
$criticalDefects = $data['critical_defects'];
$history = $data['history'];
$summary = $data['summary'];
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prüfungsprotokoll - <?= htmlspecialchars($ladder->getLadderNumber()) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #4472C4;
            padding-bottom: 15px;
        }
        
        .header h1 {
            color: #4472C4;
            font-size: 18pt;
            margin: 0 0 10px 0;
        }
        
        .header .subtitle {
            color: #666;
            font-size: 12pt;
        }
        
        .section {
            margin-bottom: 25px;
        }
        
        .section-title {
            background-color: #4472C4;
            color: white;
            padding: 8px 12px;
            font-weight: bold;
            font-size: 12pt;
            margin-bottom: 10px;
        }
        
        .info-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-label {
            display: table-cell;
            font-weight: bold;
            padding: 4px 8px 4px 0;
            width: 30%;
            vertical-align: top;
        }
        
        .info-value {
            display: table-cell;
            padding: 4px 0;
            vertical-align: top;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .data-table th {
            background-color: #4472C4;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #ddd;
        }
        
        .data-table td {
            padding: 6px 8px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        
        .data-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .result-passed {
            background-color: #d4edda;
            color: #155724;
            font-weight: bold;
            text-align: center;
        }
        
        .result-failed {
            background-color: #f8d7da;
            color: #721c24;
            font-weight: bold;
            text-align: center;
        }
        
        .result-conditional {
            background-color: #fff3cd;
            color: #856404;
            font-weight: bold;
            text-align: center;
        }
        
        .severity-critical {
            background-color: #f8d7da;
            color: #721c24;
            font-weight: bold;
            text-align: center;
        }
        
        .severity-high {
            background-color: #ffeaa7;
            color: #8b4513;
            font-weight: bold;
            text-align: center;
        }
        
        .severity-medium {
            background-color: #fff3cd;
            color: #856404;
            font-weight: bold;
            text-align: center;
        }
        
        .severity-low {
            background-color: #f0f8ff;
            color: #0066cc;
            text-align: center;
        }
        
        .signature-section {
            margin-top: 40px;
            page-break-inside: avoid;
        }
        
        .signature-box {
            display: inline-block;
            width: 45%;
            margin-right: 5%;
            vertical-align: top;
        }
        
        .signature-line {
            border-bottom: 1px solid #333;
            height: 40px;
            margin-bottom: 5px;
        }
        
        .notes-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #4472C4;
            margin: 15px 0;
        }
        
        .summary-box {
            background-color: #e7f3ff;
            border: 1px solid #4472C4;
            padding: 15px;
            margin: 20px 0;
        }
        
        .summary-box h3 {
            margin-top: 0;
            color: #4472C4;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .font-small {
            font-size: 8pt;
        }
        
        .font-large {
            font-size: 12pt;
        }
        
        .mt-20 {
            margin-top: 20px;
        }
        
        .mb-10 {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>Prüfungsprotokoll</h1>
        <div class="subtitle">
            Leiter <?= htmlspecialchars($ladder->getLadderNumber()) ?> • 
            <?= date('d.m.Y', strtotime($inspection->getInspectionDate())) ?>
        </div>
    </div>

    <!-- Zusammenfassung -->
    <div class="summary-box">
        <h3>Prüfungsergebnis</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Gesamtergebnis:</div>
                <div class="info-value">
                    <strong class="result-<?= $inspection->getOverallResult() ?>">
                        <?= strtoupper($inspection->getOverallResult()) ?>
                    </strong>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Geprüfte Punkte:</div>
                <div class="info-value"><?= $summary['total_items'] ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Mängel gefunden:</div>
                <div class="info-value"><?= $summary['defects_count'] ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Kritische Mängel:</div>
                <div class="info-value"><?= $summary['critical_defects_count'] ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Nächste Prüfung:</div>
                <div class="info-value"><?= date('d.m.Y', strtotime($summary['next_inspection_date'])) ?></div>
            </div>
        </div>
    </div>

    <!-- Leiter-Informationen -->
    <div class="section">
        <div class="section-title">Leiter-Informationen</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Leiternummer:</div>
                <div class="info-value"><?= htmlspecialchars($ladder->getLadderNumber()) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Hersteller:</div>
                <div class="info-value"><?= htmlspecialchars($ladder->getManufacturer()) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Modell:</div>
                <div class="info-value"><?= htmlspecialchars($ladder->getModel() ?: '-') ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Typ:</div>
                <div class="info-value"><?= htmlspecialchars($ladder->getLadderType()) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Material:</div>
                <div class="info-value"><?= htmlspecialchars($ladder->getMaterial()) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Höhe:</div>
                <div class="info-value"><?= $ladder->getHeightCm() ?> cm</div>
            </div>
            <div class="info-row">
                <div class="info-label">Max. Belastung:</div>
                <div class="info-value"><?= $ladder->getMaxLoadKg() ?> kg</div>
            </div>
            <div class="info-row">
                <div class="info-label">Standort:</div>
                <div class="info-value"><?= htmlspecialchars($ladder->getLocation()) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Abteilung:</div>
                <div class="info-value"><?= htmlspecialchars($ladder->getDepartment() ?: '-') ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Verantwortlich:</div>
                <div class="info-value"><?= htmlspecialchars($ladder->getResponsiblePerson() ?: '-') ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Seriennummer:</div>
                <div class="info-value"><?= htmlspecialchars($ladder->getSerialNumber() ?: '-') ?></div>
            </div>
        </div>
    </div>

    <!-- Prüfungs-Informationen -->
    <div class="section">
        <div class="section-title">Prüfungs-Informationen</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Prüfdatum:</div>
                <div class="info-value"><?= date('d.m.Y', strtotime($inspection->getInspectionDate())) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Prüftyp:</div>
                <div class="info-value"><?= ucfirst($inspection->getInspectionType()) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Prüfdauer:</div>
                <div class="info-value"><?= $inspection->getInspectionDurationMinutes() ?: '-' ?> Minuten</div>
            </div>
            <div class="info-row">
                <div class="info-label">Wetterbedingungen:</div>
                <div class="info-value"><?= htmlspecialchars($inspection->getWeatherConditions() ?: '-') ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Temperatur:</div>
                <div class="info-value"><?= $inspection->getTemperatureCelsius() ? $inspection->getTemperatureCelsius() . '°C' : '-' ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Prüfer:</div>
                <div class="info-value"><?= htmlspecialchars($inspector['display_name'] ?? '-') ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Vorgesetzter:</div>
                <div class="info-value"><?= htmlspecialchars($supervisor['display_name'] ?? '-') ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Genehmigungsdatum:</div>
                <div class="info-value">
                    <?= $inspection->getApprovalDate() ? date('d.m.Y H:i', strtotime($inspection->getApprovalDate())) : 'Noch nicht genehmigt' ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Prüfpunkte -->
    <?php if (!empty($inspectionItems)): ?>
    <div class="section page-break">
        <div class="section-title">Prüfpunkte</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 15%;">Kategorie</th>
                    <th style="width: 20%;">Prüfpunkt</th>
                    <th style="width: 25%;">Beschreibung</th>
                    <th style="width: 10%;">Ergebnis</th>
                    <th style="width: 10%;">Schwere</th>
                    <th style="width: 8%;">Reparatur</th>
                    <th style="width: 12%;">Bemerkungen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($inspectionItems as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item->getCategory()) ?></td>
                    <td><?= htmlspecialchars($item->getItemName()) ?></td>
                    <td><?= htmlspecialchars($item->getDescription() ?: '-') ?></td>
                    <td class="result-<?= $item->getResult() ?>">
                        <?= strtoupper($item->getResult()) ?>
                    </td>
                    <td class="<?= $item->getSeverity() ? 'severity-' . $item->getSeverity() : '' ?>">
                        <?= $item->getSeverity() ? strtoupper($item->getSeverity()) : '-' ?>
                    </td>
                    <td class="text-center">
                        <?= $item->isRepairRequired() ? 'JA' : 'NEIN' ?>
                    </td>
                    <td class="font-small">
                        <?= htmlspecialchars(substr($item->getNotes() ?: '', 0, 50)) ?>
                        <?= strlen($item->getNotes() ?: '') > 50 ? '...' : '' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Mängel -->
    <?php if (!empty($defects)): ?>
    <div class="section page-break">
        <div class="section-title">Festgestellte Mängel</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 20%;">Mangel</th>
                    <th style="width: 15%;">Schweregrad</th>
                    <th style="width: 15%;">Reparatur nötig</th>
                    <th style="width: 15%;">Reparatur bis</th>
                    <th style="width: 35%;">Beschreibung</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($defects as $defect): ?>
                <tr>
                    <td><?= htmlspecialchars($defect->getItemName()) ?></td>
                    <td class="severity-<?= $defect->getSeverity() ?>">
                        <?= strtoupper($defect->getSeverity()) ?>
                    </td>
                    <td class="text-center">
                        <?= $defect->isRepairRequired() ? 'JA' : 'NEIN' ?>
                    </td>
                    <td class="text-center">
                        <?= $defect->getRepairDeadline() ? date('d.m.Y', strtotime($defect->getRepairDeadline())) : '-' ?>
                    </td>
                    <td><?= htmlspecialchars($defect->getNotes() ?: '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Bemerkungen und Empfehlungen -->
    <?php if ($inspection->getGeneralNotes() || $inspection->getRecommendations()): ?>
    <div class="section">
        <?php if ($inspection->getGeneralNotes()): ?>
        <div class="notes-section">
            <h4 style="margin-top: 0;">Allgemeine Bemerkungen</h4>
            <p><?= nl2br(htmlspecialchars($inspection->getGeneralNotes())) ?></p>
        </div>
        <?php endif; ?>

        <?php if ($inspection->getRecommendations()): ?>
        <div class="notes-section">
            <h4 style="margin-top: 0;">Empfehlungen</h4>
            <p><?= nl2br(htmlspecialchars($inspection->getRecommendations())) ?></p>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Unterschriften -->
    <div class="signature-section">
        <div class="section-title">Unterschriften</div>
        <div style="margin-top: 30px;">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div><strong>Unterschrift Prüfer</strong></div>
                <div class="font-small">
                    <?= htmlspecialchars($inspector['display_name'] ?? '') ?><br>
                    Datum: <?= date('d.m.Y', strtotime($inspection->getInspectionDate())) ?>
                </div>
            </div>
            
            <div class="signature-box">
                <div class="signature-line"></div>
                <div><strong>Unterschrift Vorgesetzter</strong></div>
                <div class="font-small">
                    <?= htmlspecialchars($supervisor['display_name'] ?? '') ?><br>
                    Datum: <?= $inspection->getApprovalDate() ? date('d.m.Y', strtotime($inspection->getApprovalDate())) : '___________' ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div style="position: fixed; bottom: 20px; left: 20px; right: 20px; font-size: 8pt; color: #666; border-top: 1px solid #ddd; padding-top: 10px;">
        <div style="float: left;">
            Generiert am: <?= date('d.m.Y H:i') ?> • 
            Leiterverwaltungssystem
        </div>
        <div style="float: right;">
            Seite {PAGENO} von {nb}
        </div>
        <div style="clear: both;"></div>
    </div>
</body>
</html>
