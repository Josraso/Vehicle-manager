/**
 * Vehicle Manager - JavaScript Principal
 */

// =====================================================
// PWA - CAPTURA DEL PROMPT DE INSTALACIÓN
// =====================================================
let deferredInstallPrompt = null;

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredInstallPrompt = e;
    document.getElementById('pwaInstallItem')?.classList.remove('d-none');
});


document.addEventListener('DOMContentLoaded', function() {

    // =====================================================
    // TOGGLE DE TEMA (CLARO/OSCURO)
    // =====================================================

    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');

    if (themeToggle) {
        // Actualizar icono según tema actual
        updateThemeIcon();

        themeToggle.addEventListener('click', function() {
            // Cambiar tema via AJAX
            fetch('index.php?action=toggle_theme', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.documentElement.setAttribute('data-bs-theme', data.theme);
                    updateThemeIcon();
                }
            })
            .catch(() => {
                // Fallback: cambiar solo visualmente
                const currentTheme = document.documentElement.getAttribute('data-bs-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-bs-theme', newTheme);
                updateThemeIcon();
            });
        });
    }

    function updateThemeIcon() {
        if (!themeIcon) return;
        const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        themeIcon.className = isDark ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
    }


    // =====================================================
    // AUTO-CERRAR ALERTAS
    // =====================================================

    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    });


    // =====================================================
    // CONFIRMACIÓN DE ELIMINACIÓN
    // =====================================================

    document.querySelectorAll('[data-confirm]').forEach(element => {
        element.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm || '¿Estás seguro?')) {
                e.preventDefault();
            }
        });
    });


    // =====================================================
    // TOOLTIPS DE BOOTSTRAP
    // =====================================================

    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));


    // =====================================================
    // VALIDACIÓN DE FORMULARIOS
    // =====================================================

    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });


    // =====================================================
    // FORMATO DE NÚMEROS EN INPUTS
    // =====================================================

    // Formatear km al perder el foco
    document.querySelectorAll('input[name="km"], input[name="current_km"]').forEach(input => {
        input.addEventListener('blur', function() {
            const value = parseInt(this.value.replace(/\D/g, '')) || 0;
            this.value = value;
        });
    });

    // Formatear decimales
    document.querySelectorAll('input[name="liters"], input[name="total_cost"], input[name="cost"]').forEach(input => {
        input.addEventListener('blur', function() {
            const value = parseFloat(this.value) || 0;
            if (value > 0) {
                this.value = value.toFixed(2);
            }
        });
    });


    // =====================================================
    // MAYÚSCULAS EN MATRÍCULA
    // =====================================================

    document.querySelectorAll('input[name="license_plate"]').forEach(input => {
        input.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    });


    // =====================================================
    // TABS - RECORDAR ÚLTIMA TAB ACTIVA
    // =====================================================

    const tabEls = document.querySelectorAll('button[data-bs-toggle="tab"]');
    tabEls.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            const tabId = e.target.getAttribute('data-bs-target');
            if (tabId) {
                sessionStorage.setItem('activeTab', tabId);
            }
        });
    });

    // Restaurar tab activa
    const savedTab = sessionStorage.getItem('activeTab');
    if (savedTab) {
        const tabEl = document.querySelector(`[data-bs-target="${savedTab}"]`);
        if (tabEl) {
            const tab = new bootstrap.Tab(tabEl);
            tab.show();
        }
    }


    // =====================================================
    // PREVIEW DE IMAGEN
    // =====================================================

    document.querySelectorAll('input[type="file"][name="photo"]').forEach(input => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                // Validar tamaño (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('La imagen es demasiado grande. Máximo 5MB.');
                    this.value = '';
                    return;
                }

                // Validar tipo
                const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!validTypes.includes(file.type)) {
                    alert('Formato de imagen no válido. Usa JPG, PNG, GIF o WebP.');
                    this.value = '';
                    return;
                }
            }
        });
    });


    // =====================================================
    // LOADING STATE EN FORMULARIOS
    // =====================================================

    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.disabled = true;
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';

                // Restaurar si hay error (timeout)
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }, 10000);
            }
        });
    });


    // =====================================================
    // PWA - BOTÓN INSTALAR
    // =====================================================

    const pwaInstallBtn = document.getElementById('pwaInstallBtn');
    if (pwaInstallBtn) {
        // Si ya tenemos el prompt guardado, mostrar botón
        if (deferredInstallPrompt) {
            document.getElementById('pwaInstallItem')?.classList.remove('d-none');
        }

        pwaInstallBtn.addEventListener('click', async () => {
            if (!deferredInstallPrompt) return;
            deferredInstallPrompt.prompt();
            await deferredInstallPrompt.userChoice;
            deferredInstallPrompt = null;
            document.getElementById('pwaInstallItem')?.classList.add('d-none');
        });
    }


    // =====================================================
    // ACCESIBILIDAD - SKIP LINK
    // =====================================================

    // Añadir skip link si no existe
    if (!document.querySelector('.skip-link')) {
        const skipLink = document.createElement('a');
        skipLink.href = '#main';
        skipLink.className = 'skip-link visually-hidden-focusable position-absolute top-0 start-0 p-2 bg-primary text-white';
        skipLink.textContent = 'Saltar al contenido';
        document.body.insertBefore(skipLink, document.body.firstChild);
    }

});


// =====================================================
// FUNCIONES GLOBALES
// =====================================================

/**
 * Formatear número como moneda
 */
function formatCurrency(value, decimals = 2) {
    return parseFloat(value).toFixed(decimals).replace('.', ',') + ' €';
}

/**
 * Formatear número con separador de miles
 */
function formatNumber(value) {
    return parseInt(value).toLocaleString('es-ES');
}

/**
 * Confirmar eliminación
 */
function confirmDelete(type, id, name) {
    if (typeof bootstrap !== 'undefined') {
        // Usar modal de Bootstrap
        const modal = document.getElementById('deleteItemModal');
        if (modal) {
            document.getElementById('deleteItemName').textContent = name;
            document.getElementById('deleteItemId').value = id;
            document.getElementById('deleteItemForm').action = 'index.php?action=' + type + '_delete';
            new bootstrap.Modal(modal).show();
            return;
        }
    }

    // Fallback: confirm nativo
    if (confirm('¿Eliminar "' + name + '"?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'index.php?action=' + type + '_delete';

        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = document.querySelector('input[name="csrf_token"]')?.value || '';
        form.appendChild(csrfInput);

        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.value = id;
        form.appendChild(idInput);

        document.body.appendChild(form);
        form.submit();
    }
}
