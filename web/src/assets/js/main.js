/**
 * Basis-JavaScript für Leiterprüfung-System
 * Enthält allgemeine Funktionen und Utilities
 */

// Namespace für die Anwendung
window.LadderApp = window.LadderApp || {};

(function(app) {
    'use strict';

    // ===== KONFIGURATION =====
    app.config = {
        debug: false,
        csrfToken: window.csrfToken || '',
        apiEndpoint: '/api',
        timeout: 30000, // 30 Sekunden
        retryAttempts: 3
    };

    // ===== UTILITIES =====
    app.utils = {
        /**
         * Escape HTML-Zeichen
         * @param {string} text - Der zu escapeende Text
         * @returns {string}
         */
        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        /**
         * Formatiert Datum
         * @param {Date|string} date - Das zu formatierende Datum
         * @param {string} format - Format (de, en, iso)
         * @returns {string}
         */
        formatDate: function(date, format = 'de') {
            const d = new Date(date);
            if (isNaN(d.getTime())) return '';

            const options = {
                de: { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' },
                en: { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' },
                iso: { year: 'numeric', month: '2-digit', day: '2-digit' }
            };

            const locale = format === 'de' ? 'de-DE' : 'en-US';
            return d.toLocaleString(locale, options[format]);
        },

        /**
         * Formatiert Dateigröße
         * @param {number} bytes - Anzahl Bytes
         * @param {number} decimals - Anzahl Dezimalstellen
         * @returns {string}
         */
        formatFileSize: function(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(decimals)) + ' ' + sizes[i];
        },

        /**
         * Debounce-Funktion
         * @param {Function} func - Die zu debouncende Funktion
         * @param {number} wait - Wartezeit in ms
         * @returns {Function}
         */
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        /**
         * Throttle-Funktion
         * @param {Function} func - Die zu throttelnde Funktion
         * @param {number} limit - Limit in ms
         * @returns {Function}
         */
        throttle: function(func, limit) {
            let inThrottle;
            return function() {
                const args = arguments;
                const context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        },

        /**
         * Generiert eine zufällige ID
         * @param {number} length - Länge der ID
         * @returns {string}
         */
        generateId: function(length = 8) {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            let result = '';
            for (let i = 0; i < length; i++) {
                result += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return result;
        }
    };

    // ===== HTTP CLIENT =====
    app.http = {
        /**
         * Führt eine HTTP-Anfrage aus
         * @param {string} url - Die URL
         * @param {Object} options - Optionen für fetch
         * @returns {Promise}
         */
        request: async function(url, options = {}) {
            const defaultOptions = {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            };

            // CSRF-Token hinzufügen falls vorhanden
            if (app.config.csrfToken && ['POST', 'PUT', 'DELETE', 'PATCH'].includes(options.method?.toUpperCase())) {
                defaultOptions.headers['X-CSRF-Token'] = app.config.csrfToken;
                
                // Auch im Body hinzufügen falls FormData
                if (options.body instanceof FormData) {
                    options.body.append('csrf_token', app.config.csrfToken);
                } else if (options.body && typeof options.body === 'object') {
                    options.body.csrf_token = app.config.csrfToken;
                    options.body = JSON.stringify(options.body);
                }
            }

            const config = { ...defaultOptions, ...options };
            config.headers = { ...defaultOptions.headers, ...options.headers };

            try {
                const response = await fetch(url, config);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return await response.json();
                }
                
                return await response.text();
            } catch (error) {
                app.log('HTTP Request Error:', error);
                throw error;
            }
        },

        /**
         * GET-Request
         * @param {string} url - Die URL
         * @param {Object} options - Zusätzliche Optionen
         * @returns {Promise}
         */
        get: function(url, options = {}) {
            return this.request(url, { ...options, method: 'GET' });
        },

        /**
         * POST-Request
         * @param {string} url - Die URL
         * @param {Object} data - Die zu sendenden Daten
         * @param {Object} options - Zusätzliche Optionen
         * @returns {Promise}
         */
        post: function(url, data = {}, options = {}) {
            return this.request(url, {
                ...options,
                method: 'POST',
                body: data instanceof FormData ? data : JSON.stringify(data)
            });
        },

        /**
         * PUT-Request
         * @param {string} url - Die URL
         * @param {Object} data - Die zu sendenden Daten
         * @param {Object} options - Zusätzliche Optionen
         * @returns {Promise}
         */
        put: function(url, data = {}, options = {}) {
            return this.request(url, {
                ...options,
                method: 'PUT',
                body: JSON.stringify(data)
            });
        },

        /**
         * DELETE-Request
         * @param {string} url - Die URL
         * @param {Object} options - Zusätzliche Optionen
         * @returns {Promise}
         */
        delete: function(url, options = {}) {
            return this.request(url, { ...options, method: 'DELETE' });
        }
    };

    // ===== NOTIFICATIONS =====
    app.notify = {
        /**
         * Zeigt eine Benachrichtigung an
         * @param {string} message - Die Nachricht
         * @param {string} type - Der Typ (success, error, warning, info)
         * @param {number} duration - Anzeigedauer in ms
         */
        show: function(message, type = 'info', duration = 5000) {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            
            notification.innerHTML = `
                ${app.utils.escapeHtml(message)}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            document.body.appendChild(notification);

            // Auto-Remove nach duration
            if (duration > 0) {
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, duration);
            }

            return notification;
        },

        success: function(message, duration = 5000) {
            return this.show(message, 'success', duration);
        },

        error: function(message, duration = 8000) {
            return this.show(message, 'error', duration);
        },

        warning: function(message, duration = 6000) {
            return this.show(message, 'warning', duration);
        },

        info: function(message, duration = 5000) {
            return this.show(message, 'info', duration);
        }
    };

    // ===== LOADING SPINNER =====
    app.loading = {
        /**
         * Zeigt einen Loading-Spinner an
         * @param {string|Element} target - Ziel-Element oder Selektor
         * @param {string} message - Optional: Nachricht
         */
        show: function(target = 'body', message = 'Laden...') {
            const element = typeof target === 'string' ? document.querySelector(target) : target;
            if (!element) return;

            const overlay = document.createElement('div');
            overlay.className = 'loading-overlay';
            overlay.style.cssText = `
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(255, 255, 255, 0.8);
                display: flex;
                align-items: center;
                justify-content: center;
                flex-direction: column;
                z-index: 1000;
            `;

            overlay.innerHTML = `
                <div class="spinner-custom"></div>
                <div class="mt-2">${app.utils.escapeHtml(message)}</div>
            `;

            // Position relative setzen falls nötig
            const position = window.getComputedStyle(element).position;
            if (position === 'static') {
                element.style.position = 'relative';
            }

            element.appendChild(overlay);
            return overlay;
        },

        /**
         * Versteckt den Loading-Spinner
         * @param {string|Element} target - Ziel-Element oder Selektor
         */
        hide: function(target = 'body') {
            const element = typeof target === 'string' ? document.querySelector(target) : target;
            if (!element) return;

            const overlay = element.querySelector('.loading-overlay');
            if (overlay) {
                overlay.remove();
            }
        }
    };

    // ===== FORM UTILITIES =====
    app.forms = {
        /**
         * Serialisiert ein Formular zu einem Objekt
         * @param {HTMLFormElement} form - Das Formular
         * @returns {Object}
         */
        serialize: function(form) {
            const formData = new FormData(form);
            const data = {};
            
            for (let [key, value] of formData.entries()) {
                if (data[key]) {
                    if (Array.isArray(data[key])) {
                        data[key].push(value);
                    } else {
                        data[key] = [data[key], value];
                    }
                } else {
                    data[key] = value;
                }
            }
            
            return data;
        },

        /**
         * Validiert ein Formular
         * @param {HTMLFormElement} form - Das Formular
         * @returns {boolean}
         */
        validate: function(form) {
            let isValid = true;
            const inputs = form.querySelectorAll('input, select, textarea');
            
            inputs.forEach(input => {
                if (!input.checkValidity()) {
                    input.classList.add('is-invalid');
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });
            
            return isValid;
        },

        /**
         * Setzt Formular-Fehler
         * @param {HTMLFormElement} form - Das Formular
         * @param {Object} errors - Fehler-Objekt
         */
        setErrors: function(form, errors) {
            // Alle vorherigen Fehler entfernen
            form.querySelectorAll('.is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });
            form.querySelectorAll('.invalid-feedback').forEach(el => {
                el.remove();
            });

            // Neue Fehler setzen
            Object.keys(errors).forEach(fieldName => {
                const field = form.querySelector(`[name="${fieldName}"]`);
                if (field) {
                    field.classList.add('is-invalid');
                    
                    const feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    feedback.textContent = errors[fieldName];
                    
                    field.parentNode.appendChild(feedback);
                }
            });
        }
    };

    // ===== STORAGE =====
    app.storage = {
        /**
         * Speichert Daten im localStorage
         * @param {string} key - Der Schlüssel
         * @param {*} value - Der Wert
         */
        set: function(key, value) {
            try {
                localStorage.setItem(key, JSON.stringify(value));
            } catch (e) {
                app.log('Storage Error:', e);
            }
        },

        /**
         * Lädt Daten aus dem localStorage
         * @param {string} key - Der Schlüssel
         * @param {*} defaultValue - Standardwert
         * @returns {*}
         */
        get: function(key, defaultValue = null) {
            try {
                const item = localStorage.getItem(key);
                return item ? JSON.parse(item) : defaultValue;
            } catch (e) {
                app.log('Storage Error:', e);
                return defaultValue;
            }
        },

        /**
         * Entfernt Daten aus dem localStorage
         * @param {string} key - Der Schlüssel
         */
        remove: function(key) {
            try {
                localStorage.removeItem(key);
            } catch (e) {
                app.log('Storage Error:', e);
            }
        },

        /**
         * Leert den localStorage
         */
        clear: function() {
            try {
                localStorage.clear();
            } catch (e) {
                app.log('Storage Error:', e);
            }
        }
    };

    // ===== LOGGING =====
    app.log = function(...args) {
        if (app.config.debug || window.location.hostname === 'localhost') {
            console.log('[LadderApp]', ...args);
        }
    };

    app.error = function(...args) {
        console.error('[LadderApp]', ...args);
    };

    // ===== INITIALIZATION =====
    app.init = function() {
        app.log('Initializing LadderApp...');

        // Debug-Modus aus localStorage laden
        app.config.debug = app.storage.get('debug', false);

        // Event-Listener für globale Ereignisse
        document.addEventListener('DOMContentLoaded', function() {
            app.log('DOM Content Loaded');
            
            // Bootstrap-Tooltips initialisieren
            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }

            // Form-Validierung für alle Formulare
            const forms = document.querySelectorAll('form[data-validate]');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    if (!app.forms.validate(form)) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                });
            });
        });

        // Globale Fehlerbehandlung
        window.addEventListener('error', function(e) {
            app.error('Global Error:', e.error);
        });

        window.addEventListener('unhandledrejection', function(e) {
            app.error('Unhandled Promise Rejection:', e.reason);
        });

        app.log('LadderApp initialized successfully');
    };

    // Auto-Initialisierung
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', app.init);
    } else {
        app.init();
    }

})(window.LadderApp);

// ===== GLOBALE HILFSFUNKTIONEN =====

/**
 * Kurze Referenz für häufig verwendete Funktionen
 */
window.$ = window.$ || function(selector) {
    return document.querySelector(selector);
};

window.$$ = window.$$ || function(selector) {
    return document.querySelectorAll(selector);
};

// Debug-Funktionen global verfügbar machen
window.debug = function(enabled = true) {
    LadderApp.config.debug = enabled;
    LadderApp.storage.set('debug', enabled);
    console.log('Debug mode:', enabled ? 'enabled' : 'disabled');
};
