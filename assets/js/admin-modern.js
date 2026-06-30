/**
 * Admin moderno:
 * - Sidebar móvil.
 * - Dark mode con toggle real.
 * - SweetAlert global con diseño premium.
 * - Estados loading para formularios.
 */
(function () {
    'use strict';

    var THEME_KEY = 'qv_admin_theme';
    var DARK_CLASS = 'qv-admin-theme-dark';

    function getStoredTheme() {
        try {
            return localStorage.getItem(THEME_KEY) || 'light';
        } catch (error) {
            return 'light';
        }
    }

    function storeTheme(theme) {
        try {
            localStorage.setItem(THEME_KEY, theme);
        } catch (error) {
            // No bloqueamos la experiencia si localStorage no está disponible.
        }
    }

    function setTheme(theme) {
        var isDark = theme === 'dark';

        document.documentElement.classList.toggle(DARK_CLASS, isDark);
        document.body.classList.toggle(DARK_CLASS, isDark);

        var toggle = document.querySelector('[data-admin-theme-toggle]');
        var icon = document.querySelector('[data-admin-theme-icon]');
        var text = document.querySelector('[data-admin-theme-text]');

        if (toggle) {
            toggle.classList.toggle('is-active', isDark);
            toggle.setAttribute('aria-pressed', isDark ? 'true' : 'false');
            toggle.setAttribute('aria-label', isDark ? 'Activar modo claro' : 'Activar modo oscuro');
        }

        if (icon) {
            icon.className = isDark ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
        }

        if (text) {
            text.textContent = isDark ? 'Modo claro' : 'Modo oscuro';
        }
    }

    function toggleTheme() {
        var currentTheme = document.documentElement.classList.contains(DARK_CLASS) ? 'dark' : 'light';
        var nextTheme = currentTheme === 'dark' ? 'light' : 'dark';

        storeTheme(nextTheme);
        setTheme(nextTheme);
    }

    function openSidebar() {
        document.body.classList.add('qv-admin-sidebar-open');
    }

    function closeSidebar() {
        document.body.classList.remove('qv-admin-sidebar-open');
    }

    /**
     * Aplica clases modernas por defecto a SweetAlert2 sin modificar cada vista.
     */
    function setupSweetAlertDefaults() {
        if (typeof window.Swal === 'undefined') {
            return;
        }

        if (window.Swal.__qvModernized === true) {
            return;
        }

        var originalFire = window.Swal.fire.bind(window.Swal);

        window.Swal.fire = function () {
            var args = Array.prototype.slice.call(arguments);

            if (args.length > 0 && typeof args[0] === 'object') {
                args[0] = Object.assign({
                    buttonsStyling: false,
                    customClass: {
                        popup: 'qv-swal-popup',
                        title: 'qv-swal-title',
                        htmlContainer: 'qv-swal-text',
                        confirmButton: 'btn btn-primary qv-swal-confirm',
                        cancelButton: 'btn btn-outline-secondary qv-swal-cancel',
                        denyButton: 'btn btn-outline-danger qv-swal-deny',
                        actions: 'qv-swal-actions',
                        icon: 'qv-swal-icon'
                    }
                }, args[0]);

                args[0].customClass = Object.assign({
                    popup: 'qv-swal-popup',
                    title: 'qv-swal-title',
                    htmlContainer: 'qv-swal-text',
                    confirmButton: 'btn btn-primary qv-swal-confirm',
                    cancelButton: 'btn btn-outline-secondary qv-swal-cancel',
                    denyButton: 'btn btn-outline-danger qv-swal-deny',
                    actions: 'qv-swal-actions',
                    icon: 'qv-swal-icon'
                }, args[0].customClass || {});
            }

            return originalFire.apply(window.Swal, args);
        };

        window.Swal.__qvModernized = true;
    }

    /**
     * Confirmación reutilizable para eliminar.
     *
     * @param {string} title
     * @param {string} text
     * @param {Function} onConfirm
     */
    window.qvConfirmDelete = function (title, text, onConfirm) {
        if (typeof window.Swal === 'undefined') {
            if (window.confirm(text || title || '¿Confirmar acción?')) {
                onConfirm();
            }

            return;
        }

        window.Swal.fire({
            title: title || '¿Eliminar registro?',
            text: text || 'Esta acción puede ser irreversible.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            focusCancel: true
        }).then(function (result) {
            if (result.isConfirmed) {
                onConfirm();
            }
        });
    };

    /**
     * Crea formulario POST con CSRF para acciones admin.
     */
    window.enviarFormularioAdmin = window.enviarFormularioAdmin || function (action, fields) {
        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

        var form = document.createElement('form');
        form.method = 'POST';
        form.action = action;

        if (csrfToken !== '') {
            var csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_csrf_token';
            csrfInput.value = csrfToken;
            form.appendChild(csrfInput);
        }

        Object.keys(fields || {}).forEach(function (name) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = fields[name];
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
    };

    /**
     * Estado loading para formularios admin.
     */
    function setupFormLoadingStates() {
        document.addEventListener('submit', function (event) {
            var form = event.target;

            if (!form || !form.matches('form')) {
                return;
            }

            if (!document.body.classList.contains('qv-admin-body')) {
                return;
            }

            if (form.dataset.noLoading === 'true') {
                return;
            }

            var submitButton = form.querySelector('button[type="submit"], input[type="submit"]');

            form.classList.add('is-submitting');

            if (submitButton && submitButton.tagName === 'BUTTON') {
                submitButton.dataset.originalHtml = submitButton.innerHTML;
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Guardando...';
            }
        }, true);
    }

    function setupThemeAndSidebarEvents() {
        document.addEventListener('click', function (event) {
            var themeButton = event.target.closest('[data-admin-theme-toggle]');
            var openButton = event.target.closest('[data-admin-sidebar-open]');
            var closeButton = event.target.closest('[data-admin-sidebar-close]');
            var menuLink = event.target.closest('.qv-admin-sidebar-link');

            if (themeButton) {
                event.preventDefault();
                toggleTheme();
                return;
            }

            if (openButton) {
                event.preventDefault();
                openSidebar();
                return;
            }

            if (closeButton) {
                event.preventDefault();
                closeSidebar();
                return;
            }

            if (menuLink && window.innerWidth < 992) {
                closeSidebar();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeSidebar();
            }
        });
    }

    setTheme(getStoredTheme());

    document.addEventListener('DOMContentLoaded', function () {
        setTheme(getStoredTheme());
        setupSweetAlertDefaults();
        setupFormLoadingStates();
        setupThemeAndSidebarEvents();
    });
})();