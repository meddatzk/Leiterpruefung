/**
 * Dashboard JavaScript
 * Funktionalität für das Dashboard mit Auto-Refresh, Interaktivität und Charts
 */

class Dashboard {
    constructor() {
        this.config = window.dashboardConfig || {
            autoRefresh: true,
            refreshInterval: 300000, // 5 Minuten
            ajaxUrl: '/dashboard.php?ajax=1'
        };
        
        this.refreshTimer = null;
        this.charts = {};
        this.isRefreshing = false;
        
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupAutoRefresh();
        this.setupWidgetInteractions();
        this.setupResponsiveHandling();
        
        console.log('Dashboard initialized');
    }

    setupEventListeners() {
        // Refresh Button
        const refreshBtn = document.getElementById('refreshDashboard');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                this.refreshDashboard();
            });
        }

        // Filter Dropdown
        const filterItems = document.querySelectorAll('[data-period]');
        filterItems.forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const period = e.target.dataset.period;
                this.applyFilter(period);
            });
        });

        // Auto-Refresh Toggle
        const autoRefreshToggle = document.getElementById('autoRefreshToggle');
        if (autoRefreshToggle) {
            autoRefreshToggle.addEventListener('change', (e) => {
                this.config.autoRefresh = e.target.checked;
                if (e.target.checked) {
                    this.setupAutoRefresh();
                } else {
                    this.stopAutoRefresh();
                }
            });
        }

        // Widget Minimize/Maximize
        document.addEventListener('click', (e) => {
            if (e.target.matches('.widget-toggle') || e.target.closest('.widget-toggle')) {
                const card = e.target.closest('.card');
                const body = card.querySelector('.card-body');
                const icon = e.target.querySelector('i') || e.target;
                
                if (body.style.display === 'none') {
                    body.style.display = 'block';
                    icon.className = 'fas fa-minus';
                } else {
                    body.style.display = 'none';
                    icon.className = 'fas fa-plus';
                }
            }
        });

        // Keyboard Shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey || e.metaKey) {
                switch (e.key) {
                    case 'r':
                        e.preventDefault();
                        this.refreshDashboard();
                        break;
                    case 'f':
                        e.preventDefault();
                        document.querySelector('[data-bs-toggle="dropdown"]')?.click();
                        break;
                }
            }
        });
    }

    setupAutoRefresh() {
        if (!this.config.autoRefresh) return;
        
        this.stopAutoRefresh();
        
        this.refreshTimer = setInterval(() => {
            this.refreshDashboard(true);
        }, this.config.refreshInterval);
        
        console.log(`Auto-refresh enabled (${this.config.refreshInterval / 1000}s)`);
    }

    stopAutoRefresh() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
            this.refreshTimer = null;
            console.log('Auto-refresh disabled');
        }
    }

    async refreshDashboard(silent = false) {
        if (this.isRefreshing) return;
        
        this.isRefreshing = true;
        
        if (!silent) {
            this.showRefreshIndicator();
        }

        try {
            const response = await fetch(this.config.ajaxUrl, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            
            if (data.success) {
                this.updateDashboardData(data.data);
                this.updateLastRefreshTime(data.timestamp);
                
                if (!silent) {
                    this.showSuccessMessage('Dashboard aktualisiert');
                }
            } else {
                throw new Error(data.error || 'Unbekannter Fehler');
            }

        } catch (error) {
            console.error('Dashboard refresh error:', error);
            this.showErrorMessage('Fehler beim Aktualisieren: ' + error.message);
        } finally {
            this.isRefreshing = false;
            this.hideRefreshIndicator();
        }
    }

    updateDashboardData(data) {
        // Statistik-Karten aktualisieren
        this.updateStatCards(data.statistics);
        
        // Listen aktualisieren
        this.updateOverdueList(data.overdue_inspections);
        this.updateTodayList(data.today_inspections);
        this.updateUpcomingList(data.upcoming_inspections);
        this.updateRecentActivity(data.recent_activity);
        
        // Charts aktualisieren
        this.updateCharts(data);
    }

    updateStatCards(statistics) {
        const updates = [
            { selector: '[data-stat="total-ladders"]', value: statistics.ladders.total },
            { selector: '[data-stat="needs-inspection"]', value: statistics.ladders.needs_inspection },
            { selector: '[data-stat="total-inspections"]', value: statistics.inspections.total },
            { selector: '[data-stat="defective-ladders"]', value: statistics.ladders.defective }
        ];

        updates.forEach(update => {
            const element = document.querySelector(update.selector);
            if (element) {
                this.animateValue(element, parseInt(element.textContent.replace(/\D/g, '')), update.value);
            }
        });
    }

    updateOverdueList(overdueInspections) {
        const container = document.querySelector('[data-widget="overdue-list"]');
        if (container && overdueInspections) {
            // Hier würde normalerweise die Liste aktualisiert werden
            // Für jetzt nur die Anzahl im Header aktualisieren
            const header = container.closest('.card').querySelector('.card-header h5');
            if (header) {
                header.innerHTML = header.innerHTML.replace(/\(\d+\)/, `(${overdueInspections.length})`);
            }
        }
    }

    updateTodayList(todayInspections) {
        const container = document.querySelector('[data-widget="today-list"]');
        if (container && todayInspections) {
            const header = container.closest('.card').querySelector('.card-header h5');
            if (header) {
                header.innerHTML = header.innerHTML.replace(/\(\d+\)/, `(${todayInspections.length})`);
            }
        }
    }

    updateUpcomingList(upcomingInspections) {
        const container = document.querySelector('[data-widget="upcoming-list"]');
        if (container && upcomingInspections) {
            // Liste aktualisieren
            // Implementierung würde hier erfolgen
        }
    }

    updateRecentActivity(recentActivity) {
        const container = document.querySelector('[data-widget="recent-activity"]');
        if (container && recentActivity) {
            // Aktivitätsliste aktualisieren
            // Implementierung würde hier erfolgen
        }
    }

    updateCharts(data) {
        // Charts werden automatisch durch Chart.js aktualisiert
        // wenn die Daten geändert werden
        Object.keys(this.charts).forEach(chartId => {
            const chart = this.charts[chartId];
            if (chart && chart.update) {
                chart.update('none'); // Ohne Animation
            }
        });
    }

    animateValue(element, start, end, duration = 1000) {
        const range = end - start;
        const increment = range / (duration / 16);
        let current = start;
        
        const timer = setInterval(() => {
            current += increment;
            if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
                current = end;
                clearInterval(timer);
            }
            element.textContent = Math.floor(current).toLocaleString();
        }, 16);
    }

    applyFilter(period) {
        console.log('Applying filter:', period);
        
        // Filter-Logik implementieren
        const filterParams = this.getFilterParams(period);
        
        // URL mit Filtern aktualisieren
        const url = new URL(window.location);
        url.searchParams.set('period', period);
        window.history.pushState({}, '', url);
        
        // Dashboard mit Filtern neu laden
        this.refreshDashboard();
    }

    getFilterParams(period) {
        const now = new Date();
        let dateFrom, dateTo;
        
        switch (period) {
            case 'today':
                dateFrom = dateTo = now.toISOString().split('T')[0];
                break;
            case 'week':
                dateFrom = new Date(now.setDate(now.getDate() - 7)).toISOString().split('T')[0];
                dateTo = new Date().toISOString().split('T')[0];
                break;
            case 'month':
                dateFrom = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
                dateTo = new Date().toISOString().split('T')[0];
                break;
            case 'year':
                dateFrom = new Date(now.getFullYear(), 0, 1).toISOString().split('T')[0];
                dateTo = new Date().toISOString().split('T')[0];
                break;
            default:
                return {};
        }
        
        return { date_from: dateFrom, date_to: dateTo };
    }

    setupWidgetInteractions() {
        // Widget-spezifische Interaktionen
        this.setupTableSorting();
        this.setupTooltips();
        this.setupModalTriggers();
    }

    setupTableSorting() {
        const tables = document.querySelectorAll('.dashboard-table');
        tables.forEach(table => {
            const headers = table.querySelectorAll('th[data-sort]');
            headers.forEach(header => {
                header.style.cursor = 'pointer';
                header.addEventListener('click', () => {
                    this.sortTable(table, header.dataset.sort);
                });
            });
        });
    }

    sortTable(table, column) {
        // Einfache Tabellensortierung
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        rows.sort((a, b) => {
            const aVal = a.querySelector(`[data-value="${column}"]`)?.textContent || '';
            const bVal = b.querySelector(`[data-value="${column}"]`)?.textContent || '';
            return aVal.localeCompare(bVal);
        });
        
        rows.forEach(row => tbody.appendChild(row));
    }

    setupTooltips() {
        // Bootstrap Tooltips initialisieren
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    setupModalTriggers() {
        // Modal-Trigger für Details
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-modal-trigger]')) {
                const modalId = e.target.dataset.modalTrigger;
                const modal = document.getElementById(modalId);
                if (modal) {
                    new bootstrap.Modal(modal).show();
                }
            }
        });
    }

    setupResponsiveHandling() {
        // Responsive Anpassungen
        const handleResize = () => {
            this.adjustChartsForScreenSize();
            this.adjustTableResponsiveness();
        };
        
        window.addEventListener('resize', this.debounce(handleResize, 250));
        handleResize(); // Initial call
    }

    adjustChartsForScreenSize() {
        Object.keys(this.charts).forEach(chartId => {
            const chart = this.charts[chartId];
            if (chart && chart.resize) {
                chart.resize();
            }
        });
    }

    adjustTableResponsiveness() {
        const tables = document.querySelectorAll('.table-responsive');
        tables.forEach(table => {
            if (window.innerWidth < 768) {
                table.classList.add('table-sm');
            } else {
                table.classList.remove('table-sm');
            }
        });
    }

    showRefreshIndicator() {
        const refreshBtn = document.getElementById('refreshDashboard');
        if (refreshBtn) {
            const icon = refreshBtn.querySelector('i');
            if (icon) {
                icon.classList.add('fa-spin');
            }
            refreshBtn.disabled = true;
        }
    }

    hideRefreshIndicator() {
        const refreshBtn = document.getElementById('refreshDashboard');
        if (refreshBtn) {
            const icon = refreshBtn.querySelector('i');
            if (icon) {
                icon.classList.remove('fa-spin');
            }
            refreshBtn.disabled = false;
        }
    }

    updateLastRefreshTime(timestamp) {
        const lastUpdateElement = document.getElementById('lastUpdate');
        if (lastUpdateElement) {
            const time = new Date(timestamp).toLocaleTimeString('de-DE');
            lastUpdateElement.textContent = `Zuletzt aktualisiert: ${time}`;
        }
    }

    showSuccessMessage(message) {
        this.showToast(message, 'success');
    }

    showErrorMessage(message) {
        this.showToast(message, 'error');
    }

    showToast(message, type = 'info') {
        // Einfache Toast-Implementierung
        const toast = document.createElement('div');
        toast.className = `alert alert-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info'} toast-message`;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            opacity: 0;
            transition: opacity 0.3s ease;
        `;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        // Fade in
        setTimeout(() => {
            toast.style.opacity = '1';
        }, 10);
        
        // Auto remove
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, 3000);
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Öffentliche API
    refresh() {
        this.refreshDashboard();
    }

    toggleAutoRefresh() {
        this.config.autoRefresh = !this.config.autoRefresh;
        if (this.config.autoRefresh) {
            this.setupAutoRefresh();
        } else {
            this.stopAutoRefresh();
        }
    }

    setRefreshInterval(interval) {
        this.config.refreshInterval = interval;
        if (this.config.autoRefresh) {
            this.setupAutoRefresh();
        }
    }

    destroy() {
        this.stopAutoRefresh();
        // Weitere Cleanup-Operationen
        console.log('Dashboard destroyed');
    }
}

// Dashboard initialisieren wenn DOM geladen ist
document.addEventListener('DOMContentLoaded', function() {
    window.dashboard = new Dashboard();
});

// Cleanup beim Verlassen der Seite
window.addEventListener('beforeunload', function() {
    if (window.dashboard) {
        window.dashboard.destroy();
    }
});
