<?php

require_once __DIR__ . '/InspectionItem.php';

/**
 * InspectionTemplate - Standard-Prüfpunkte und Vorlagen für Prüfungen
 * 
 * @author System
 * @version 1.0
 */
class InspectionTemplate
{
    /**
     * Gibt Standard-Prüfpunkte für alle Leitertypen zurück
     */
    public static function getStandardItems(): array
    {
        $items = [];
        $sortOrder = 0;

        // Struktur-Prüfpunkte
        $structureItems = [
            'Holme/Rahmen auf Risse und Brüche prüfen',
            'Sprossen/Stufen auf Beschädigungen prüfen',
            'Verbindungen und Verschraubungen kontrollieren',
            'Schweißnähte auf Risse untersuchen',
            'Korrosion und Materialermüdung bewerten',
            'Verformungen und Biegungen feststellen',
            'Oberflächenbeschädigungen dokumentieren'
        ];

        foreach ($structureItems as $itemName) {
            $item = new InspectionItem();
            $item->setCategory('structure');
            $item->setItemName($itemName);
            $item->setDescription('Sichtprüfung und Funktionstest');
            $item->setResult('ok');
            $item->setSortOrder($sortOrder++);
            $items[] = $item;
        }

        // Sicherheits-Prüfpunkte
        $safetyItems = [
            'Rutschsichere Füße/Endkappen prüfen',
            'Spreizschutz/Kette kontrollieren',
            'Arretierungen und Verriegelungen testen',
            'Sicherheitsbügel und Handläufe prüfen',
            'Standsicherheit und Stabilität bewerten',
            'Belastbarkeit entsprechend Kennzeichnung',
            'Sicherheitsabstände einhalten'
        ];

        foreach ($safetyItems as $itemName) {
            $item = new InspectionItem();
            $item->setCategory('safety');
            $item->setItemName($itemName);
            $item->setDescription('Sicherheitsprüfung gemäß Norm');
            $item->setResult('ok');
            $item->setSortOrder($sortOrder++);
            $items[] = $item;
        }

        // Funktions-Prüfpunkte
        $functionItems = [
            'Auszieh-/Teleskopmechanismus testen',
            'Gelenke und Scharniere prüfen',
            'Verriegelungsmechanismen kontrollieren',
            'Bewegliche Teile auf Leichtgängigkeit',
            'Federelemente und Dämpfer prüfen',
            'Höhenverstellung funktionsfähig',
            'Klapp- und Faltmechanismus testen'
        ];

        foreach ($functionItems as $itemName) {
            $item = new InspectionItem();
            $item->setCategory('function');
            $item->setItemName($itemName);
            $item->setDescription('Funktionsprüfung aller beweglichen Teile');
            $item->setResult('ok');
            $item->setSortOrder($sortOrder++);
            $items[] = $item;
        }

        // Kennzeichnungs-Prüfpunkte
        $markingItems = [
            'CE-Kennzeichnung vorhanden und lesbar',
            'Herstellerangaben vollständig',
            'Baujahr und Seriennummer erkennbar',
            'Belastungsangaben lesbar',
            'Warnhinweise und Piktogramme',
            'Prüfplakette aktuell',
            'Inventarnummer vorhanden'
        ];

        foreach ($markingItems as $itemName) {
            $item = new InspectionItem();
            $item->setCategory('marking');
            $item->setItemName($itemName);
            $item->setDescription('Kennzeichnung gemäß Vorschriften');
            $item->setResult('ok');
            $item->setSortOrder($sortOrder++);
            $items[] = $item;
        }

        // Zubehör-Prüfpunkte
        $accessoryItems = [
            'Werkzeugablage/Eimerhaken prüfen',
            'Traverse/Auflage kontrollieren',
            'Rollen und Transporträder',
            'Zusätzliche Sicherheitsausrüstung',
            'Aufbewahrung und Lagerung',
            'Bedienungsanleitung vorhanden'
        ];

        foreach ($accessoryItems as $itemName) {
            $item = new InspectionItem();
            $item->setCategory('accessories');
            $item->setItemName($itemName);
            $item->setDescription('Zubehör und Zusatzausstattung');
            $item->setResult('ok');
            $item->setSortOrder($sortOrder++);
            $items[] = $item;
        }

        return $items;
    }

    /**
     * Gibt Prüfpunkte nach Kategorie zurück
     */
    public static function getItemsByCategory(string $category): array
    {
        $allItems = self::getStandardItems();
        
        return array_filter($allItems, function($item) use ($category) {
            return $item->getCategory() === $category;
        });
    }

    /**
     * Gibt leitertyp-spezifische Prüfpunkte zurück
     */
    public static function getItemsByLadderType(string $ladderType): array
    {
        $standardItems = self::getStandardItems();
        $specificItems = [];

        switch ($ladderType) {
            case 'Anlegeleiter':
                $specificItems = self::getAnlelegeleiterItems();
                break;
            case 'Stehleiter':
                $specificItems = self::getStehleiterItems();
                break;
            case 'Mehrzweckleiter':
                $specificItems = self::getMehrzweckleiterItems();
                break;
            case 'Podestleiter':
                $specificItems = self::getPodestleiterItems();
                break;
            case 'Schiebeleiter':
                $specificItems = self::getSchiebeleiterlItems();
                break;
        }

        return array_merge($standardItems, $specificItems);
    }

    /**
     * Spezifische Prüfpunkte für Anlegeleitern
     */
    private static function getAnlelegeleiterItems(): array
    {
        $items = [];
        $sortOrder = 1000; // Hohe Sortierung für spezifische Items

        $specificItems = [
            'Anlegewinkel 65-75° einhalten',
            'Leiterkopf und Anlegepunkte prüfen',
            'Rutschsicherung am Leiterfuß',
            'Mindestüberstand am oberen Ende',
            'Seitliche Stabilität bewerten'
        ];

        foreach ($specificItems as $itemName) {
            $item = new InspectionItem();
            $item->setCategory('safety');
            $item->setItemName($itemName);
            $item->setDescription('Spezifisch für Anlegeleitern');
            $item->setResult('ok');
            $item->setSortOrder($sortOrder++);
            $items[] = $item;
        }

        return $items;
    }

    /**
     * Spezifische Prüfpunkte für Stehleitern
     */
    private static function getStehleiterItems(): array
    {
        $items = [];
        $sortOrder = 1000;

        $specificItems = [
            'Spreizschutz/Kette funktionsfähig',
            'Plattform und Geländer sicher',
            'Standsicherheit auf ebenem Untergrund',
            'Maximale Spreizung einhalten',
            'Beidseitige Begehbarkeit prüfen'
        ];

        foreach ($specificItems as $itemName) {
            $item = new InspectionItem();
            $item->setCategory('safety');
            $item->setItemName($itemName);
            $item->setDescription('Spezifisch für Stehleitern');
            $item->setResult('ok');
            $item->setSortOrder($sortOrder++);
            $items[] = $item;
        }

        return $items;
    }

    /**
     * Spezifische Prüfpunkte für Mehrzweckleitern
     */
    private static function getMehrzweckleiterItems(): array
    {
        $items = [];
        $sortOrder = 1000;

        $specificItems = [
            'Alle Konfigurationen testen',
            'Umbauvorgänge prüfen',
            'Verriegelungen in allen Positionen',
            'Gelenkverbindungen kontrollieren',
            'Stabilität in jeder Konfiguration'
        ];

        foreach ($specificItems as $itemName) {
            $item = new InspectionItem();
            $item->setCategory('function');
            $item->setItemName($itemName);
            $item->setDescription('Spezifisch für Mehrzweckleitern');
            $item->setResult('ok');
            $item->setSortOrder($sortOrder++);
            $items[] = $item;
        }

        return $items;
    }

    /**
     * Spezifische Prüfpunkte für Podestleitern
     */
    private static function getPodestleiterItems(): array
    {
        $items = [];
        $sortOrder = 1000;

        $specificItems = [
            'Podest und Arbeitsfläche sicher',
            'Geländer und Absturzsicherung',
            'Aufstiegshilfen beidseitig',
            'Belastung der Arbeitsfläche',
            'Höhenverstellung funktionsfähig'
        ];

        foreach ($specificItems as $itemName) {
            $item = new InspectionItem();
            $item->setCategory('safety');
            $item->setItemName($itemName);
            $item->setDescription('Spezifisch für Podestleitern');
            $item->setResult('ok');
            $item->setSortOrder($sortOrder++);
            $items[] = $item;
        }

        return $items;
    }

    /**
     * Spezifische Prüfpunkte für Schiebeleitern
     */
    private static function getSchiebeleiterlItems(): array
    {
        $items = [];
        $sortOrder = 1000;

        $specificItems = [
            'Auszieh-/Schiebemechanismus',
            'Seilzug und Umlenkrollen',
            'Hakeneinhängung und Rastung',
            'Überlappung der Leiterteile',
            'Führungsschienen und -bolzen'
        ];

        foreach ($specificItems as $itemName) {
            $item = new InspectionItem();
            $item->setCategory('function');
            $item->setItemName($itemName);
            $item->setDescription('Spezifisch für Schiebeleitern');
            $item->setResult('ok');
            $item->setSortOrder($sortOrder++);
            $items[] = $item;
        }

        return $items;
    }

    /**
     * Erstellt eine anpassbare Vorlage basierend auf Leitertyp und Material
     */
    public static function customizeTemplate(string $ladderType, string $material = 'Aluminium', array $options = []): array
    {
        // Basis-Prüfpunkte für Leitertyp
        $items = self::getItemsByLadderType($ladderType);

        // Material-spezifische Anpassungen
        $materialItems = self::getMaterialSpecificItems($material);
        $items = array_merge($items, $materialItems);

        // Optionale Prüfpunkte hinzufügen
        if (!empty($options['include_electrical']) && $options['include_electrical']) {
            $electricalItems = self::getElectricalSafetyItems();
            $items = array_merge($items, $electricalItems);
        }

        if (!empty($options['include_environmental']) && $options['include_environmental']) {
            $environmentalItems = self::getEnvironmentalItems();
            $items = array_merge($items, $environmentalItems);
        }

        if (!empty($options['include_special']) && $options['include_special']) {
            $specialItems = self::getSpecialApplicationItems();
            $items = array_merge($items, $specialItems);
        }

        // Sortierung neu ordnen
        $items = self::reorderItems($items);

        return $items;
    }

    /**
     * Material-spezifische Prüfpunkte
     */
    private static function getMaterialSpecificItems(string $material): array
    {
        $items = [];
        $sortOrder = 2000;

        switch ($material) {
            case 'Aluminium':
                $specificItems = [
                    'Korrosion an Aluminiumteilen',
                    'Oxidation und Verfärbungen',
                    'Materialermüdung bei Biegung'
                ];
                break;
            case 'Holz':
                $specificItems = [
                    'Holzfeuchtigkeit und Risse',
                    'Insektenbefall und Fäulnis',
                    'Oberflächenbehandlung intakt'
                ];
                break;
            case 'Fiberglas':
                $specificItems = [
                    'Faserbrüche und Delaminierung',
                    'UV-Schäden an der Oberfläche',
                    'Elektrische Isolationseigenschaften'
                ];
                break;
            case 'Stahl':
                $specificItems = [
                    'Rostbildung und Korrosion',
                    'Beschichtung und Lackierung',
                    'Schweißnahtqualität'
                ];
                break;
            default:
                $specificItems = [];
        }

        foreach ($specificItems as $itemName) {
            $item = new InspectionItem();
            $item->setCategory('structure');
            $item->setItemName($itemName);
            $item->setDescription("Material-spezifisch für {$material}");
            $item->setResult('ok');
            $item->setSortOrder($sortOrder++);
            $items[] = $item;
        }

        return $items;
    }

    /**
     * Elektrische Sicherheitsprüfpunkte
     */
    private static function getElectricalSafetyItems(): array
    {
        $items = [];
        $sortOrder = 3000;

        $electricalItems = [
            'Isolationswiderstand messen',
            'Erdung und Potentialausgleich',
            'Elektrische Leitfähigkeit prüfen',
            'Spannungsfestigkeit testen'
        ];

        foreach ($electricalItems as $itemName) {
            $item = new InspectionItem();
            $item->setCategory('safety');
            $item->setItemName($itemName);
            $item->setDescription('Elektrische Sicherheitsprüfung');
            $item->setResult('ok');
            $item->setSortOrder($sortOrder++);
            $items[] = $item;
        }

        return $items;
    }

    /**
     * Umwelt- und Witterungsbeständigkeit
     */
    private static function getEnvironmentalItems(): array
    {
        $items = [];
        $sortOrder = 4000;

        $environmentalItems = [
            'UV-Beständigkeit bewerten',
            'Witterungseinflüsse dokumentieren',
            'Temperaturbeständigkeit prüfen',
            'Chemikalienresistenz bewerten'
        ];

        foreach ($environmentalItems as $itemName) {
            $item = new InspectionItem();
            $item->setCategory('structure');
            $item->setItemName($itemName);
            $item->setDescription('Umwelt- und Witterungsbeständigkeit');
            $item->setResult('ok');
            $item->setSortOrder($sortOrder++);
            $items[] = $item;
        }

        return $items;
    }

    /**
     * Spezielle Anwendungsprüfpunkte
     */
    private static function getSpecialApplicationItems(): array
    {
        $items = [];
        $sortOrder = 5000;

        $specialItems = [
            'Explosionsschutz-Eigenschaften',
            'Chemikalienbeständigkeit',
            'Hochtemperatur-Einsatz',
            'Reinraumtauglichkeit'
        ];

        foreach ($specialItems as $itemName) {
            $item = new InspectionItem();
            $item->setCategory('safety');
            $item->setItemName($itemName);
            $item->setDescription('Spezielle Anwendungsanforderungen');
            $item->setResult('ok');
            $item->setSortOrder($sortOrder++);
            $items[] = $item;
        }

        return $items;
    }

    /**
     * Ordnet Prüfpunkte neu nach Kategorien und Priorität
     */
    private static function reorderItems(array $items): array
    {
        // Nach Kategorie und dann nach ursprünglicher Sortierung ordnen
        usort($items, function($a, $b) {
            $categoryOrder = [
                'structure' => 1,
                'safety' => 2,
                'function' => 3,
                'marking' => 4,
                'accessories' => 5
            ];

            $catA = $categoryOrder[$a->getCategory()] ?? 999;
            $catB = $categoryOrder[$b->getCategory()] ?? 999;

            if ($catA === $catB) {
                return $a->getSortOrder() <=> $b->getSortOrder();
            }

            return $catA <=> $catB;
        });

        // Sortierung neu vergeben
        foreach ($items as $index => $item) {
            $item->setSortOrder($index + 1);
        }

        return $items;
    }

    /**
     * Erstellt eine Vorlage basierend auf einer vorherigen Prüfung
     */
    public static function createFromPreviousInspection(array $previousItems): array
    {
        $templateItems = [];

        foreach ($previousItems as $previousItem) {
            if ($previousItem instanceof InspectionItem) {
                $templateItem = $previousItem->createTemplate();
                $templateItems[] = $templateItem;
            }
        }

        return $templateItems;
    }

    /**
     * Gibt eine minimale Prüfvorlage zurück
     */
    public static function getMinimalTemplate(): array
    {
        $items = [];
        $sortOrder = 0;

        $minimalItems = [
            ['structure', 'Holme/Rahmen auf Risse prüfen'],
            ['structure', 'Sprossen/Stufen kontrollieren'],
            ['safety', 'Rutschsichere Füße prüfen'],
            ['safety', 'Standsicherheit bewerten'],
            ['function', 'Bewegliche Teile testen'],
            ['marking', 'Kennzeichnung lesbar'],
        ];

        foreach ($minimalItems as [$category, $itemName]) {
            $item = new InspectionItem();
            $item->setCategory($category);
            $item->setItemName($itemName);
            $item->setDescription('Basis-Prüfpunkt');
            $item->setResult('ok');
            $item->setSortOrder($sortOrder++);
            $items[] = $item;
        }

        return $items;
    }

    /**
     * Gibt eine erweiterte Prüfvorlage zurück
     */
    public static function getExtendedTemplate(): array
    {
        $standardItems = self::getStandardItems();
        $electricalItems = self::getElectricalSafetyItems();
        $environmentalItems = self::getEnvironmentalItems();

        $allItems = array_merge($standardItems, $electricalItems, $environmentalItems);
        
        return self::reorderItems($allItems);
    }

    /**
     * Validiert eine Vorlage
     */
    public static function validateTemplate(array $templateItems): array
    {
        $errors = [];

        if (empty($templateItems)) {
            $errors[] = 'Vorlage darf nicht leer sein';
            return $errors;
        }

        $categories = [];
        $itemNames = [];

        foreach ($templateItems as $index => $item) {
            if (!$item instanceof InspectionItem) {
                $errors[] = "Element {$index} ist kein gültiges InspectionItem";
                continue;
            }

            $categories[] = $item->getCategory();
            
            // Doppelte Prüfpunkte vermeiden
            $itemName = $item->getItemName();
            if (in_array($itemName, $itemNames)) {
                $errors[] = "Doppelter Prüfpunkt: {$itemName}";
            }
            $itemNames[] = $itemName;
        }

        // Mindestens eine Kategorie aus jeder Hauptgruppe
        $requiredCategories = ['structure', 'safety', 'function'];
        $missingCategories = array_diff($requiredCategories, $categories);
        
        if (!empty($missingCategories)) {
            $errors[] = 'Folgende Kategorien fehlen: ' . implode(', ', $missingCategories);
        }

        return $errors;
    }

    /**
     * Exportiert eine Vorlage als Array
     */
    public static function exportTemplate(array $templateItems): array
    {
        $export = [];

        foreach ($templateItems as $item) {
            if ($item instanceof InspectionItem) {
                $export[] = [
                    'category' => $item->getCategory(),
                    'item_name' => $item->getItemName(),
                    'description' => $item->getDescription(),
                    'sort_order' => $item->getSortOrder()
                ];
            }
        }

        return $export;
    }

    /**
     * Importiert eine Vorlage aus Array
     */
    public static function importTemplate(array $templateData): array
    {
        $items = [];

        foreach ($templateData as $data) {
            $item = new InspectionItem();
            $item->setCategory($data['category'] ?? 'structure');
            $item->setItemName($data['item_name'] ?? '');
            $item->setDescription($data['description'] ?? '');
            $item->setSortOrder($data['sort_order'] ?? 0);
            $item->setResult('ok');
            
            $items[] = $item;
        }

        return $items;
    }
}
