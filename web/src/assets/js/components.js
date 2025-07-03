/**
 * UI Components JavaScript
 * Stellt interaktive Funktionalität für UI-Komponenten bereit
 */

// Globale Komponenten-Objekte
window.Components = {
    // Initialisierung aller Komponenten
    init: function() {
        this.initNavigation();
        this.initSidebar();
        this.initTables();
        this.initForms();
        this.initModals();
        this.initTooltips();
        this.initAlerts();
        this.initSearch();
        this.initFilters();
        console.log('UI Components initialized');
    },

    // ===== NAVIGATION =====
    initNavigation: function() {
        // Mobile Navigation Toggle
        const navToggler = document.querySelector('.navbar-toggler');
        const navCollapse = document.querySelector('.navbar-collapse');
        const overlay = document.querySelector('.mobile-nav-overlay');

        if (navToggler && navCollapse) {
            navToggler.addEventListener('click', function() {
                navCollapse.classList.toggle('show');
                overlay?.classList.toggle('show');
            });
        }

        // Close mobile nav when clicking overlay
        if (overlay) {
            overlay.addEventListener('click', function() {
                navCollapse?.classList.remove('show');
                overlay.classList.remove('show');
            });
        }

        // Active navigation highlighting
        this.highlightActiveNavigation();
    },

    highlightActiveNavigation: function() {
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('.main-navigation .nav-link');
        
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href && currentPath.includes(href.replace(/^.*\//, ''))) {
                link.classList.add('active');
            }
        });
    },

    // ===== SIDEBAR =====
    initSidebar: function() {
        const sidebarToggle = document.querySelector('.sidebar-toggle');
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.mobile-nav-overlay');

        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('show');
                overlay?.classList.toggle('show');
            });
        }

        // Close sidebar when clicking overlay
        if (overlay) {
            overlay.addEventListener('click', function() {
                sidebar?.classList.remove('show');
                overlay.classList.remove('show');
            });
        }

        // Initialize sidebar filters
        this.initSidebarFilters();
    },

    initSidebarFilters: function() {
        const applyBtn = document.querySelector('#applyFilters');
        const clearBtn = document.querySelector('#clearFilters');
        
        if (applyBtn) {
            applyBtn.addEventListener('click', this.applyFilters.bind(this));
        }
        
        if (clearBtn) {
            clearBtn.addEventListener('click', this.clearFilters.bind(this));
        }
    },

    applyFilters: function() {
        const statusFilter = document.querySelector('#statusFilter')?.value;
        const standortFilter = document.querySelector('#standortFilter')?.value;
        const kategorieFilter = document.querySelector('#kategorieFilter')?.value;
        
        const filters = {
            status: statusFilter,
            standort: standortFilter,
            kategorie: kategorieFilter
        };
        
        // Trigger custom event for filter application
        document.dispatchEvent(new CustomEvent('filtersApplied', { detail: filters }));
        
        // Show loading state
        applyBtn.innerHTML = '<span class="spinner spinner-sm"></span> Wird angewendet...';
        applyBtn.disabled = true;
        
        // Reset button after delay (simulate processing)
        setTimeout(() => {
            applyBtn.innerHTML = '<i class="bi bi-funnel"></i> Anwenden';
            applyBtn.disabled = false;
        }, 1000);
    },

    clearFilters: function() {
        document.querySelector('#statusFilter').value = '';
        document.querySelector('#standortFilter').value = '';
        document.querySelector('#kategorieFilter').value = '';
        
        // Trigger custom event for filter clearing
        document.dispatchEvent(new CustomEvent('filtersCleared'));
    },

    // ===== TABLES =====
    initTables: function() {
        // Initialize sortable tables
        const sortableTables = document.querySelectorAll('.table-sortable');
        sortableTables.forEach(table => {
            this.makeSortable(table);
        });

        // Initialize table row selection
        this.initTableSelection();
    },

    makeSortable: function(table) {
        const headers = table.querySelectorAll('thead th');
        
        headers.forEach((header, index) => {
            if (!header.classList.contains('no-sort')) {
                header.classList.add('sortable');
                header.addEventListener('click', () => {
                    this.sortTable(table, index, header);
                });
            }
        });
    },

    sortTable: function(table, columnIndex, header) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const isAscending = !header.classList.contains('sort-asc');
        
        // Remove sort classes from all headers
        table.querySelectorAll('th').forEach(th => {
            th.classList.remove('sort-asc', 'sort-desc');
        });
        
        // Add appropriate sort class
        header.classList.add(isAscending ? 'sort-asc' : 'sort-desc');
        
        // Sort rows
        rows.sort((a, b) => {
            const aText = a.cells[columnIndex].textContent.trim();
            const bText = b.cells[columnIndex].textContent.trim();
            
            // Try to parse as numbers
            const aNum = parseFloat(aText);
            const bNum = parseFloat(bText);
            
            if (!isNaN(aNum) && !isNaN(bNum)) {
                return isAscending ? aNum - bNum : bNum - aNum;
            }
            
            // Sort as strings
            return isAscending ? 
                aText.localeCompare(bText) : 
                bText.localeCompare(aText);
        });
        
        // Reorder rows in DOM
        rows.forEach(row => tbody.appendChild(row));
    },

    initTableSelection: function() {
        const selectAllCheckbox = document.querySelector('#selectAll');
        const rowCheckboxes = document.querySelectorAll('.row-select');
        
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                rowCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                Components.updateBulkActions();
            });
        }
        
        rowCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                Components.updateBulkActions();
                
                // Update select all checkbox
                if (selectAllCheckbox) {
                    const checkedCount = document.querySelectorAll('.row-select:checked').length;
                    selectAllCheckbox.checked = checkedCount === rowCheckboxes.length;
                    selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < rowCheckboxes.length;
                }
            });
        });
    },

    updateBulkActions: function() {
        const checkedCount = document.querySelectorAll('.row-select:checked').length;
        const bulkActions = document.querySelector('.bulk-actions');
        
        if (bulkActions) {
            bulkActions.style.display = checkedCount > 0 ? 'block' : 'none';
            
            const countSpan = bulkActions.querySelector('.selected-count');
            if (countSpan) {
                countSpan.textContent = checkedCount;
            }
        }
    },

    // ===== FORMS =====
    initForms: function() {
        // Initialize form validation
        const forms = document.querySelectorAll('.needs-validation');
        forms.forEach(form => {
            form.addEventListener('submit', this.validateForm.bind(this));
        });

        // Initialize auto-save
        this.initAutoSave();
        
        // Initialize form dependencies
        this.initFormDependencies();
    },

    validateForm: function(event) {
        const form = event.target;
        
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        form.classList.add('was-validated');
    },

    initAutoSave: function() {
        const autoSaveForms = document.querySelectorAll('[data-auto-save]');
        
        autoSaveForms.forEach(form => {
            const inputs = form.querySelectorAll('input, textarea, select');
            let saveTimeout;
            
            inputs.forEach(input => {
                input.addEventListener('input', () => {
                    clearTimeout(saveTimeout);
                    saveTimeout = setTimeout(() => {
                        this.autoSaveForm(form);
                    }, 2000);
                });
            });
        });
    },

    autoSaveForm: function(form) {
        const formData = new FormData(form);
        const saveIndicator = form.querySelector('.auto-save-indicator');
        
        if (saveIndicator) {
            saveIndicator.textContent = 'Wird gespeichert...';
            saveIndicator.classList.add('saving');
        }
        
        // Simulate auto-save (replace with actual implementation)
        setTimeout(() => {
            if (saveIndicator) {
                saveIndicator.textContent = 'Automatisch gespeichert';
                saveIndicator.classList.remove('saving');
                saveIndicator.classList.add('saved');
                
                setTimeout(() => {
                    saveIndicator.classList.remove('saved');
                }, 2000);
            }
        }, 500);
    },

    initFormDependencies: function() {
        // Show/hide form fields based on other field values
        const dependentFields = document.querySelectorAll('[data-depends-on]');
        
        dependentFields.forEach(field => {
            const dependsOn = field.getAttribute('data-depends-on');
            const dependsValue = field.getAttribute('data-depends-value');
            const triggerField = document.querySelector(`[name="${dependsOn}"]`);
            
            if (triggerField) {
                const checkDependency = () => {
                    const shouldShow = triggerField.value === dependsValue;
                    field.style.display = shouldShow ? 'block' : 'none';
                    
                    // Clear field value if hidden
                    if (!shouldShow) {
                        const input = field.querySelector('input, select, textarea');
                        if (input) input.value = '';
                    }
                };
                
                triggerField.addEventListener('change', checkDependency);
                checkDependency(); // Initial check
            }
        });
    },

    // ===== MODALS =====
    initModals: function() {
        // Initialize modal triggers
        const modalTriggers = document.querySelectorAll('[data-bs-toggle="modal"]');
        modalTriggers.forEach(trigger => {
            trigger.addEventListener('click', this.handleModalTrigger.bind(this));
        });

        // Initialize modal close handlers
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            this.initModalHandlers(modal);
        });
    },

    handleModalTrigger: function(event) {
        const trigger = event.currentTarget;
        const targetSelector = trigger.getAttribute('data-bs-target');
        const modal = document.querySelector(targetSelector);
        
        if (modal) {
            // Load dynamic content if specified
            const url = trigger.getAttribute('data-url');
            if (url) {
                this.loadModalContent(modal, url);
            }
            
            this.showModal(modal);
        }
    },

    loadModalContent: function(modal, url) {
        const modalBody = modal.querySelector('.modal-body');
        if (modalBody) {
            modalBody.innerHTML = '<div class="text-center"><span class="spinner"></span> Wird geladen...</div>';
            
            // Simulate content loading (replace with actual fetch)
            setTimeout(() => {
                modalBody.innerHTML = '<p>Dynamischer Inhalt wurde geladen.</p>';
            }, 1000);
        }
    },

    showModal: function(modal) {
        modal.style.display = 'block';
        modal.classList.add('show');
        document.body.classList.add('modal-open');
        
        // Create backdrop
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        document.body.appendChild(backdrop);
        
        // Focus management
        modal.setAttribute('tabindex', '-1');
        modal.focus();
    },

    hideModal: function(modal) {
        modal.style.display = 'none';
        modal.classList.remove('show');
        document.body.classList.remove('modal-open');
        
        // Remove backdrop
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
    },

    initModalHandlers: function(modal) {
        // Close button
        const closeButtons = modal.querySelectorAll('[data-bs-dismiss="modal"]');
        closeButtons.forEach(button => {
            button.addEventListener('click', () => this.hideModal(modal));
        });
        
        // Click outside to close
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                this.hideModal(modal);
            }
        });
        
        // Escape key to close
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && modal.classList.contains('show')) {
                this.hideModal(modal);
            }
        });
    },

    // ===== TOOLTIPS =====
    initTooltips: function() {
        const tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        
        tooltipElements.forEach(element => {
            element.addEventListener('mouseenter', this.showTooltip.bind(this));
            element.addEventListener('mouseleave', this.hideTooltip.bind(this));
        });
    },

    showTooltip: function(event) {
        const element = event.currentTarget;
        const text = element.getAttribute('data-bs-title') || element.getAttribute('title');
        
        if (!text) return;
        
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip fade show';
        tooltip.innerHTML = `<div class="tooltip-inner">${text}</div>`;
        
        document.body.appendChild(tooltip);
        
        // Position tooltip
        const rect = element.getBoundingClientRect();
        tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
        tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
        
        element._tooltip = tooltip;
    },

    hideTooltip: function(event) {
        const element = event.currentTarget;
        if (element._tooltip) {
            element._tooltip.remove();
            delete element._tooltip;
        }
    },

    // ===== ALERTS =====
    initAlerts: function() {
        // Auto-dismiss alerts
        const autoDismissAlerts = document.querySelectorAll('.alert[data-auto-dismiss]');
        autoDismissAlerts.forEach(alert => {
            const delay = parseInt(alert.getAttribute('data-auto-dismiss')) || 5000;
            setTimeout(() => {
                this.dismissAlert(alert);
            }, delay);
        });

        // Manual dismiss buttons
        const dismissButtons = document.querySelectorAll('.alert .btn-close');
        dismissButtons.forEach(button => {
            button.addEventListener('click', (event) => {
                const alert = event.target.closest('.alert');
                this.dismissAlert(alert);
            });
        });
    },

    dismissAlert: function(alert) {
        alert.classList.add('fade');
        setTimeout(() => {
            alert.remove();
        }, 150);
    },

    // ===== SEARCH =====
    initSearch: function() {
        const globalSearch = document.querySelector('#globalSearch');
        if (globalSearch) {
            let searchTimeout;
            
            globalSearch.addEventListener('input', (event) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.performSearch(event.target.value);
                }, 300);
            });
        }
    },

    performSearch: function(query) {
        if (query.length < 2) return;
        
        // Trigger custom search event
        document.dispatchEvent(new CustomEvent('globalSearch', { 
            detail: { query: query }
        }));
        
        console.log('Searching for:', query);
    },

    // ===== FILTERS =====
    initFilters: function() {
        // Listen for filter events
        document.addEventListener('filtersApplied', this.handleFiltersApplied.bind(this));
        document.addEventListener('filtersCleared', this.handleFiltersCleared.bind(this));
    },

    handleFiltersApplied: function(event) {
        const filters = event.detail;
        console.log('Filters applied:', filters);
        
        // Apply filters to current view
        this.applyTableFilters(filters);
    },

    handleFiltersCleared: function() {
        console.log('Filters cleared');
        
        // Clear all table filters
        this.applyTableFilters({});
    },

    applyTableFilters: function(filters) {
        const tables = document.querySelectorAll('.table tbody');
        
        tables.forEach(tbody => {
            const rows = tbody.querySelectorAll('tr');
            
            rows.forEach(row => {
                let shouldShow = true;
                
                // Check each filter
                Object.entries(filters).forEach(([key, value]) => {
                    if (value && shouldShow) {
                        const cell = row.querySelector(`[data-${key}]`);
                        if (cell && cell.getAttribute(`data-${key}`) !== value) {
                            shouldShow = false;
                        }
                    }
                });
                
                row.style.display = shouldShow ? '' : 'none';
            });
        });
    },

    // ===== UTILITY METHODS =====
    showNotification: function(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show`;
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Add to notifications container or body
        const container = document.querySelector('.notifications') || document.body;
        container.appendChild(notification);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            this.dismissAlert(notification);
        }, 5000);
    },

    confirmAction: function(message, callback) {
        if (confirm(message)) {
            callback();
        }
    },

    loadingState: function(element, loading = true) {
        if (loading) {
            element.classList.add('loading');
            element.disabled = true;
        } else {
            element.classList.remove('loading');
            element.disabled = false;
        }
    }
};

// Initialize components when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    Components.init();
});

// Export for use in other scripts
window.Components = Components;
