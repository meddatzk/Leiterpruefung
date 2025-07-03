/**
 * JavaScript für Prüfungsformular
 * Dynamische Prüfpunkte, Status-Berechnung und Formular-Validierung
 */

// Globale Variablen
let itemIndex = 0;
let inspectionItems = [];

// Initialisierung
document.addEventListener('DOMContentLoaded', function() {
    initializeForm();
    updateItemsCount();
    calculateOverallResult();
    
    // Bestehende Prüfpunkte indexieren
    const existingItems = document.querySelectorAll('.inspection-item');
    existingItems.forEach((item, index) => {
        item.dataset.index = index;
        itemIndex = Math.max(itemIndex, index + 1);
    });
});

/**
 * Formular initialisieren
 */
function initializeForm() {
    // Bootstrap Validierung aktivieren
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            } else {
                // Bestätigungsdialog vor Speicherung
                if (!confirm('Sind Sie sicher, dass Sie die Prüfung speichern möchten? Nach dem Speichern kann sie nicht mehr bearbeitet werden.')) {
                    event.preventDefault();
                    return false;
                }
            }
            form.classList.add('was-validated');
        });
    });

    // Leiter-Auswahl Event
    const ladderSelect = document.getElementById('ladder_id');
    if (ladderSelect) {
        ladderSelect.addEventListener('change', function() {
            loadLadderInfo(this.value);
        });
    }

    // Prüfdatum-Änderung für automatische Berechnung des nächsten Prüfdatums
    const inspectionDateInput = document.getElementById('inspection_date');
    const nextInspectionDateInput = document.getElementById('next_inspection_date');
    
    if (inspectionDateInput && nextInspectionDateInput) {
        inspectionDateInput.addEventListener('change', function() {
            if (this.value && !nextInspectionDateInput.value) {
                // Automatisch 12 Monate hinzufügen
                const inspectionDate = new Date(this.value);
                inspectionDate.setFullYear(inspectionDate.getFullYear() + 1);
                nextInspectionDateInput.value = inspectionDate.toISOString().split('T')[0];
            }
        });
    }
}

/**
 * Leiter-Informationen laden und anzeigen
 */
function loadLadderInfo(ladderId) {
    if (!ladderId) {
        const ladderInfo = document.getElementById('ladder-info');
        if (ladderInfo) {
            ladderInfo.style.display = 'none';
        }
        return;
    }

    const selectedOption = document.querySelector(`#ladder_id option[value="${ladderId}"]`);
    if (!selectedOption) return;

    const ladderType = selectedOption.dataset.type;
    const ladderMaterial = selectedOption.dataset.material;

    // Leiter-Info anzeigen
    let ladderInfo = document.getElementById('ladder-info');
    if (!ladderInfo) {
        ladderInfo = document.createElement('div');
        ladderInfo.id = 'ladder-info';
        ladderInfo.className = 'alert alert-info';
        document.getElementById('ladder_id').parentNode.appendChild(ladderInfo);
    }

    ladderInfo.innerHTML = `
        <strong>${selectedOption.textContent}</strong><br>
        <small>
            Typ: ${ladderType}<br>
            Material: ${ladderMaterial}
        </small>
    `;
    ladderInfo.style.display = 'block';

    // Prüfpunkte-Vorlage laden wenn noch keine vorhanden
    const container = document.getElementById('inspection-items-container');
    if (container && container.children.length === 0) {
        loadTemplateForLadderType(ladderType, ladderMaterial);
    }
}

/**
 * Vorlage für Leitertyp laden
 */
function loadTemplateForLadderType(ladderType, material) {
    // Hier könnte ein AJAX-Call gemacht werden, um spezifische Vorlagen zu laden
    // Für jetzt laden wir eine Standard-Vorlage
    loadStandardTemplate();
}

/**
 * Standard-Vorlage laden
 */
function loadStandardTemplate() {
    const standardItems = [
        { category: 'structure', name: 'Holme/Rahmen auf Risse und Brüche prüfen', description: 'Sichtprüfung und Funktionstest' },
        { category: 'structure', name: 'Sprossen/Stufen auf Beschädigungen prüfen', description: 'Sichtprüfung und Funktionstest' },
        { category: 'safety', name: 'Rutschsichere Füße/Endkappen prüfen', description: 'Sicherheitsprüfung gemäß Norm' },
        { category: 'safety', name: 'Spreizschutz/Kette kontrollieren', description: 'Sicherheitsprüfung gemäß Norm' },
        { category: 'function', name: 'Auszieh-/Teleskopmechanismus testen', description: 'Funktionsprüfung aller beweglichen Teile' },
        { category: 'marking', name: 'CE-Kennzeichnung vorhanden und lesbar', description: 'Kennzeichnung gemäß Vorschriften' }
    ];

    standardItems.forEach(item => {
        addInspectionItem(item.category, item.name, item.description);
    });
}

/**
 * Neuen Prüfpunkt hinzufügen
 */
function addInspectionItem(category = 'structure', itemName = '', description = '') {
    const template = document.getElementById('inspection-item-template');
    if (!template) {
        console.error('Template nicht gefunden');
        return;
    }

    const container = document.getElementById('inspection-items-container');
    const emptyState = document.getElementById('empty-state');
    
    // Template klonen und Index ersetzen
    let html = template.innerHTML.replace(/__INDEX__/g, itemIndex);
    
    // Neues Element erstellen
    const wrapper = document.createElement('div');
    wrapper.innerHTML = html;
    const newItem = wrapper.firstElementChild;
    
    // Werte setzen wenn übergeben
    if (category) {
        const categorySelect = newItem.querySelector('select[name*="[category]"]');
        if (categorySelect) categorySelect.value = category;
    }
    
    if (itemName) {
        const nameInput = newItem.querySelector('input[name*="[item_name]"]');
        if (nameInput) nameInput.value = itemName;
    }
    
    if (description) {
        const descInput = newItem.querySelector('input[name*="[description]"]');
        if (descInput) descInput.value = description;
    }

    // Event-Listener hinzufügen
    addItemEventListeners(newItem, itemIndex);
    
    // Element hinzufügen
    container.appendChild(newItem);
    
    // Display aktualisieren
    updateItemDisplay(itemIndex);
    
    // Empty State verstecken
    if (emptyState) {
        emptyState.style.display = 'none';
    }
    
    itemIndex++;
    updateItemsCount();
    calculateOverallResult();
}

/**
 * Event-Listener für Prüfpunkt hinzufügen
 */
function addItemEventListeners(item, index) {
    // Kategorie-Änderung
    const categorySelect = item.querySelector('select[name*="[category]"]');
    if (categorySelect) {
        categorySelect.addEventListener('change', () => updateItemDisplay(index));
    }
    
    // Name-Änderung
    const nameInput = item.querySelector('input[name*="[item_name]"]');
    if (nameInput) {
        nameInput.addEventListener('input', () => updateItemDisplay(index));
    }
    
    // Ergebnis-Änderung
    const resultSelect = item.querySelector('select[name*="[result]"]');
    if (resultSelect) {
        resultSelect.addEventListener('change', () => handleResultChange(index));
    }
    
    // Schweregrad-Änderung
    const severitySelect = item.querySelector('select[name*="[severity]"]');
    if (severitySelect) {
        severitySelect.addEventListener('change', () => calculateOverallResult());
    }
}

/**
 * Prüfpunkt entfernen
 */
function removeInspectionItem(index) {
    if (!confirm('Möchten Sie diesen Prüfpunkt wirklich entfernen?')) {
        return;
    }
    
    const item = document.querySelector(`.inspection-item[data-index="${index}"]`);
    if (item) {
        item.remove();
        updateItemsCount();
        calculateOverallResult();
        
        // Empty State anzeigen wenn keine Items mehr vorhanden
        const container = document.getElementById('inspection-items-container');
        const emptyState = document.getElementById('empty-state');
        if (container && container.children.length === 0 && emptyState) {
            emptyState.style.display = 'block';
        }
    }
}

/**
 * Anzeige eines Prüfpunkts aktualisieren
 */
function updateItemDisplay(index) {
    const item = document.querySelector(`.inspection-item[data-index="${index}"]`);
    if (!item) return;
    
    const categorySelect = item.querySelector('select[name*="[category]"]');
    const nameInput = item.querySelector('input[name*="[item_name]"]');
    const categoryLabel = item.querySelector('.item-category-label');
    const nameDisplay = item.querySelector('.item-name-display');
    
    if (categorySelect && categoryLabel) {
        const categoryText = categorySelect.options[categorySelect.selectedIndex].text;
        categoryLabel.textContent = categoryText;
    }
    
    if (nameInput && nameDisplay) {
        nameDisplay.textContent = nameInput.value || 'Neuer Prüfpunkt';
    }
}

/**
 * Ergebnis-Änderung behandeln
 */
function handleResultChange(index) {
    const item = document.querySelector(`.inspection-item[data-index="${index}"]`);
    if (!item) return;
    
    const resultSelect = item.querySelector('select[name*="[result]"]');
    const severitySelect = item.querySelector('select[name*="[severity]"]');
    const repairSection = item.querySelector('.repair-section');
    const repairCheckbox = item.querySelector('input[name*="[repair_required]"]');
    
    if (!resultSelect) return;
    
    const result = resultSelect.value;
    
    // Schweregrad nur bei Defekten anzeigen
    if (severitySelect) {
        if (result === 'defect') {
            severitySelect.style.display = 'block';
            severitySelect.required = true;
            if (!severitySelect.value) {
                severitySelect.value = 'medium'; // Standard-Schweregrad
            }
        } else {
            severitySelect.style.display = 'none';
            severitySelect.required = false;
            severitySelect.value = '';
        }
    }
    
    // Reparatur-Sektion nur bei Defekten anzeigen
    if (repairSection) {
        if (result === 'defect') {
            repairSection.style.display = 'flex';
            if (repairCheckbox) {
                repairCheckbox.checked = true;
            }
        } else {
            repairSection.style.display = 'none';
            if (repairCheckbox) {
                repairCheckbox.checked = false;
            }
        }
    }
    
    // Farbkodierung des Prüfpunkts
    updateItemStyling(item, result);
    
    // Gesamtergebnis neu berechnen
    calculateOverallResult();
}

/**
 * Styling eines Prüfpunkts basierend auf Ergebnis aktualisieren
 */
function updateItemStyling(item, result) {
    // Alle Klassen entfernen
    item.classList.remove('border-success', 'border-danger', 'border-warning', 'border-secondary');
    
    // Neue Klasse basierend auf Ergebnis hinzufügen
    switch (result) {
        case 'ok':
            item.classList.add('border-success');
            break;
        case 'defect':
            item.classList.add('border-danger');
            break;
        case 'wear':
            item.classList.add('border-warning');
            break;
        case 'not_applicable':
            item.classList.add('border-secondary');
            break;
    }
}

/**
 * Gesamtergebnis berechnen und anzeigen
 */
function calculateOverallResult() {
    const items = document.querySelectorAll('.inspection-item');
    let hasDefects = false;
    let hasCriticalDefects = false;
    let totalDefects = 0;
    let criticalDefects = 0;
    
    items.forEach(item => {
        const resultSelect = item.querySelector('select[name*="[result]"]');
        const severitySelect = item.querySelector('select[name*="[severity]"]');
        
        if (resultSelect && resultSelect.value === 'defect') {
            hasDefects = true;
            totalDefects++;
            
            if (severitySelect && severitySelect.value === 'critical') {
                hasCriticalDefects = true;
                criticalDefects++;
            }
        }
    });
    
    // Gesamtergebnis bestimmen
    let overallResult, resultText, badgeClass;
    
    if (hasCriticalDefects) {
        overallResult = 'failed';
        resultText = 'Nicht bestanden';
        badgeClass = 'badge-danger';
    } else if (hasDefects) {
        overallResult = 'conditional';
        resultText = 'Bedingt bestanden';
        badgeClass = 'badge-warning';
    } else {
        overallResult = 'passed';
        resultText = 'Bestanden';
        badgeClass = 'badge-success';
    }
    
    // Anzeige aktualisieren
    const resultTextElement = document.getElementById('overall-result-text');
    const resultBadge = document.getElementById('overall-result-badge');
    const defectSummary = document.getElementById('defect-summary');
    
    if (resultTextElement) {
        resultTextElement.textContent = resultText;
    }
    
    if (resultBadge) {
        resultBadge.className = `badge badge-lg ${badgeClass}`;
        resultBadge.textContent = resultText;
    }
    
    if (defectSummary) {
        if (totalDefects === 0) {
            defectSummary.textContent = 'Keine Mängel gefunden';
        } else {
            let summary = `${totalDefects} Mängel gefunden`;
            if (criticalDefects > 0) {
                summary += ` (${criticalDefects} kritisch)`;
            }
            defectSummary.textContent = summary;
        }
    }
    
    // Gesamtstatus-Alert-Klasse aktualisieren
    const overallStatus = document.getElementById('overall-status');
    if (overallStatus) {
        overallStatus.className = `alert alert-${badgeClass.replace('badge-', '')}`;
    }
}

/**
 * Anzahl der Prüfpunkte aktualisieren
 */
function updateItemsCount() {
    const count = document.querySelectorAll('.inspection-item').length;
    const countElement = document.getElementById('items-count');
    if (countElement) {
        countElement.textContent = count;
    }
}

/**
 * Vorlage laden (Button-Handler)
 */
function loadTemplate() {
    if (document.querySelectorAll('.inspection-item').length > 0) {
        if (!confirm('Möchten Sie die vorhandenen Prüfpunkte durch eine Vorlage ersetzen?')) {
            return;
        }
        
        // Alle vorhandenen Prüfpunkte entfernen
        const items = document.querySelectorAll('.inspection-item');
        items.forEach(item => item.remove());
    }
    
    // Standard-Vorlage laden
    loadStandardTemplate();
}

/**
 * Formular-Validierung vor Submit
 */
function validateForm() {
    const items = document.querySelectorAll('.inspection-item');
    
    if (items.length === 0) {
        alert('Bitte fügen Sie mindestens einen Prüfpunkt hinzu.');
        return false;
    }
    
    // Prüfen ob alle Defekte einen Schweregrad haben
    let hasInvalidDefects = false;
    items.forEach(item => {
        const resultSelect = item.querySelector('select[name*="[result]"]');
        const severitySelect = item.querySelector('select[name*="[severity]"]');
        
        if (resultSelect && resultSelect.value === 'defect') {
            if (!severitySelect || !severitySelect.value) {
                hasInvalidDefects = true;
            }
        }
    });
    
    if (hasInvalidDefects) {
        alert('Bitte geben Sie für alle Defekte einen Schweregrad an.');
        return false;
    }
    
    return true;
}

/**
 * Tastatur-Shortcuts
 */
document.addEventListener('keydown', function(e) {
    // Ctrl+Enter: Formular speichern
    if (e.ctrlKey && e.key === 'Enter') {
        const saveButton = document.getElementById('save-button');
        if (saveButton && validateForm()) {
            saveButton.click();
        }
    }
    
    // Ctrl+N: Neuen Prüfpunkt hinzufügen
    if (e.ctrlKey && e.key === 'n') {
        e.preventDefault();
        addInspectionItem();
    }
});

/**
 * Auto-Save Funktionalität (optional)
 */
function enableAutoSave() {
    let autoSaveTimeout;
    
    document.addEventListener('input', function() {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = setTimeout(() => {
            // Hier könnte eine Auto-Save Funktionalität implementiert werden
            console.log('Auto-Save würde hier ausgeführt...');
        }, 5000); // 5 Sekunden nach letzter Eingabe
    });
}

// Auto-Save aktivieren (optional)
// enableAutoSave();
