/**
 * Login Corporativo - Delafiber CRM
 * JavaScript mejorado con validaciones y UX profesional
 * 
 * @author Sandra De la Cruz
 * @version 2.0
 * @date 2025-10-29
 */

(function() {
    'use strict';

    // ============================================
    // CONFIGURACIÓN
    // ============================================
    const CONFIG = {
        AUTO_HIDE_ALERTS: true,
        ALERT_DURATION: 5000,
        MIN_PASSWORD_LENGTH: 3,
        REMEMBER_ME_DAYS: 30,
        ENABLE_PASSWORD_TOGGLE: true,
        ENABLE_EMAIL_VALIDATION: true
    };

    // ============================================
    // ELEMENTOS DOM
    // ============================================
    let elements = {};

    /**
     * Inicializar referencias a elementos DOM
     */
    function initElements() {
        elements = {
            form: document.getElementById('loginForm'),
            usuarioInput: document.getElementById('usuario'),
            passwordInput: document.getElementById('password'),
            recordarCheckbox: document.getElementById('recordar'),
            btnLogin: document.getElementById('btnLogin'),
            alerts: document.querySelectorAll('.alert')
        };
    }

    // ============================================
    // VALIDACIONES
    // ============================================

    /**
     * Valida formato de email
     * @param {string} email - Email a validar
     * @returns {boolean}
     */
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Valida el campo de usuario/email
     * @returns {object} {valid: boolean, message: string}
     */
    function validateUsuario() {
        const valor = elements.usuarioInput.value.trim();
        
        if (!valor) {
            return {
                valid: false,
                message: 'El usuario o email es obligatorio'
            };
        }

        if (valor.length < 3) {
            return {
                valid: false,
                message: 'El usuario debe tener al menos 3 caracteres'
            };
        }

        // Si parece un email, validar formato
        if (CONFIG.ENABLE_EMAIL_VALIDATION && valor.includes('@')) {
            if (!isValidEmail(valor)) {
                return {
                    valid: false,
                    message: 'El formato del email no es válido'
                };
            }
        }

        return { valid: true };
    }

    /**
     * Valida el campo de contraseña
     * @returns {object} {valid: boolean, message: string}
     */
    function validatePassword() {
        const valor = elements.passwordInput.value;
        
        if (!valor) {
            return {
                valid: false,
                message: 'La contraseña es obligatoria'
            };
        }

        if (valor.length < CONFIG.MIN_PASSWORD_LENGTH) {
            return {
                valid: false,
                message: `La contraseña debe tener al menos ${CONFIG.MIN_PASSWORD_LENGTH} caracteres`
            };
        }

        return { valid: true };
    }

    /**
     * Valida todo el formulario
     * @returns {boolean}
     */
    function validateForm() {
        // Limpiar errores previos
        clearFieldErrors();

        const usuarioValidation = validateUsuario();
        const passwordValidation = validatePassword();

        if (!usuarioValidation.valid) {
            showFieldError(elements.usuarioInput, usuarioValidation.message);
            return false;
        }

        if (!passwordValidation.valid) {
            showFieldError(elements.passwordInput, passwordValidation.message);
            return false;
        }

        return true;
    }

    // ============================================
    // MANEJO DE ERRORES EN UI
    // ============================================

    /**
     * Muestra error en un campo específico
     * @param {HTMLElement} field - Campo con error
     * @param {string} message - Mensaje de error
     */
    function showFieldError(field, message) {
        // Agregar clase de error al campo
        field.classList.add('is-invalid');
        
        // Crear o actualizar mensaje de error
        let errorDiv = field.parentElement.querySelector('.invalid-feedback');
        
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            field.parentElement.appendChild(errorDiv);
        }
        
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
        
        // Focus en el campo con error
        field.focus();
    }

    /**
     * Limpia todos los errores de campos
     */
    function clearFieldErrors() {
        document.querySelectorAll('.is-invalid').forEach(field => {
            field.classList.remove('is-invalid');
        });
        
        document.querySelectorAll('.invalid-feedback').forEach(error => {
            error.style.display = 'none';
        });
    }

    /**
     * Muestra mensaje de error general
     * @param {string} message - Mensaje de error
     */
    function showError(message) {
        showAlert(message, 'danger');
    }

    /**
     * Muestra mensaje de éxito
     * @param {string} message - Mensaje de éxito
     */
    function showSuccess(message) {
        showAlert(message, 'success');
    }

    /**
     * Muestra alerta en la UI
     * @param {string} message - Mensaje
     * @param {string} type - Tipo (success, danger, info)
     */
    function showAlert(message, type = 'info') {
        // Buscar contenedor de alertas o crear uno
        let alertContainer = document.querySelector('.login-body');
        
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} mb-3`;
        alertDiv.innerHTML = `
            <i class="ti-${type === 'danger' ? 'alert' : 'check'}"></i>
            <span>${message}</span>
        `;
        
        // Insertar al inicio del login-body
        alertContainer.insertBefore(alertDiv, alertContainer.firstChild);
        
        // Auto-ocultar después de 5 segundos
        if (CONFIG.AUTO_HIDE_ALERTS) {
            setTimeout(() => {
                alertDiv.style.transition = 'opacity 0.3s ease';
                alertDiv.style.opacity = '0';
                setTimeout(() => alertDiv.remove(), 300);
            }, CONFIG.ALERT_DURATION);
        }
    }

    // ============================================
    // FUNCIONALIDAD "RECORDARME"
    // ============================================

    /**
     * Guarda el usuario en localStorage
     * @param {string} usuario - Usuario a recordar
     */
    function rememberUser(usuario) {
        if (elements.recordarCheckbox.checked) {
            const expiryDate = new Date();
            expiryDate.setDate(expiryDate.getDate() + CONFIG.REMEMBER_ME_DAYS);
            
            localStorage.setItem('delafiber_remembered_user', usuario);
            localStorage.setItem('delafiber_remember_expiry', expiryDate.toISOString());
        } else {
            localStorage.removeItem('delafiber_remembered_user');
            localStorage.removeItem('delafiber_remember_expiry');
        }
    }

    /**
     * Carga el usuario recordado si existe
     */
    function loadRememberedUser() {
        const rememberedUser = localStorage.getItem('delafiber_remembered_user');
        const expiryDate = localStorage.getItem('delafiber_remember_expiry');
        
        if (rememberedUser && expiryDate) {
            const expiry = new Date(expiryDate);
            const now = new Date();
            
            if (now < expiry) {
                elements.usuarioInput.value = rememberedUser;
                elements.recordarCheckbox.checked = true;
                
                // Focus en contraseña si hay usuario recordado
                if (elements.passwordInput) {
                    elements.passwordInput.focus();
                }
            } else {
                // Expiró, limpiar
                localStorage.removeItem('delafiber_remembered_user');
                localStorage.removeItem('delafiber_remember_expiry');
            }
        }
    }

    /**
     * Inicializa el toggle de contraseña
     */
    function initPasswordToggle() {
        if (!CONFIG.ENABLE_PASSWORD_TOGGLE) return;
        
        const toggleBtn = document.getElementById('togglePassword');
        const toggleIcon = document.getElementById('toggleIcon');
        
        if (!toggleBtn || !elements.passwordInput) {
            console.log('Botón toggle o input de contraseña no encontrado');
            return;
        }
        
        console.log('✅ Toggle de contraseña inicializado');
        
        // Event listener para el botón
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Alternar tipo de input
            const isPassword = elements.passwordInput.type === 'password';
            elements.passwordInput.type = isPassword ? 'text' : 'password';
            
            // Cambiar icono
            if (toggleIcon) {
                toggleIcon.className = isPassword ? 'ti-eye-off' : 'ti-eye';
            }
            
            // Cambiar título
            toggleBtn.title = isPassword ? 'Ocultar contraseña' : 'Mostrar contraseña';
            
            console.log('Contraseña:', isPassword ? 'visible' : 'oculta');
        });
    }

    // ============================================
    // MANEJO DEL FORMULARIO
    // ============================================

    /**
     * Maneja el envío del formulario
     * @param {Event} e - Evento de submit
     */
    function handleFormSubmit(e) {
        // Prevenir doble submit
        if (elements.btnLogin.classList.contains('loading')) {
            e.preventDefault();
            return;
        }

        // Validar formulario
        if (!validateForm()) {
            e.preventDefault();
            return;
        }

        // Guardar usuario si "Recordarme" está marcado
        rememberUser(elements.usuarioInput.value.trim());

        // Mostrar estado de carga
        showLoadingState();
    }

    /**
     * Muestra estado de carga en el botón
     */
    function showLoadingState() {
        elements.btnLogin.classList.add('loading');
        elements.btnLogin.disabled = true;
        
        const originalText = elements.btnLogin.innerHTML;
        elements.btnLogin.setAttribute('data-original-text', originalText);
        elements.btnLogin.innerHTML = '<i class="ti-reload"></i> Iniciando sesión...';
    }

    /**
     * Restaura el estado normal del botón
     */
    function resetLoadingState() {
        elements.btnLogin.classList.remove('loading');
        elements.btnLogin.disabled = false;
        
        const originalText = elements.btnLogin.getAttribute('data-original-text');
        if (originalText) {
            elements.btnLogin.innerHTML = originalText;
        }
    }

    // ============================================
    // AUTO-HIDE ALERTS
    // ============================================

    /**
     * Auto-oculta las alertas después de un tiempo
     */
    function autoHideAlerts() {
        if (!CONFIG.AUTO_HIDE_ALERTS) return;
        
        elements.alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.3s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }, CONFIG.ALERT_DURATION);
        });
    }

    // ============================================
    // VALIDACIÓN EN TIEMPO REAL
    // ============================================

    /**
     * Agrega validación en tiempo real a los campos
     */
    function initRealtimeValidation() {
        // Validar usuario al perder foco
        elements.usuarioInput.addEventListener('blur', function() {
            const validation = validateUsuario();
            if (!validation.valid && this.value.trim()) {
                showFieldError(this, validation.message);
            } else {
                this.classList.remove('is-invalid');
                const errorDiv = this.parentElement.querySelector('.invalid-feedback');
                if (errorDiv) errorDiv.style.display = 'none';
            }
        });

        // Validar contraseña al perder foco
        elements.passwordInput.addEventListener('blur', function() {
            const validation = validatePassword();
            if (!validation.valid && this.value) {
                showFieldError(this, validation.message);
            } else {
                this.classList.remove('is-invalid');
                const errorDiv = this.parentElement.querySelector('.invalid-feedback');
                if (errorDiv) errorDiv.style.display = 'none';
            }
        });

        // Limpiar error al escribir
        elements.usuarioInput.addEventListener('input', function() {
            this.classList.remove('is-invalid');
        });

        elements.passwordInput.addEventListener('input', function() {
            this.classList.remove('is-invalid');
        });
    }

    // ============================================
    // ANIMACIONES
    // ============================================

    /**
     * Anima la entrada de la tarjeta de login
     */
    function animateLoginCard() {
        const loginCard = document.querySelector('.login-card');
        if (!loginCard) return;
        
        loginCard.style.opacity = '0';
        loginCard.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            loginCard.style.transition = 'all 0.6s ease-out';
            loginCard.style.opacity = '1';
            loginCard.style.transform = 'translateY(0)';
        }, 100);
    }

    // ============================================
    // INICIALIZACIÓN
    // ============================================

    /**
     * Inicializa todas las funcionalidades
     */
    function init() {
        // Inicializar elementos DOM
        initElements();

        // Verificar que existan los elementos necesarios
        if (!elements.form || !elements.usuarioInput || !elements.passwordInput) {
            console.error('Elementos del formulario no encontrados');
            return;
        }

        // Cargar usuario recordado
        loadRememberedUser();

        // Inicializar toggle de contraseña
        initPasswordToggle();

        // Auto-ocultar alertas existentes
        autoHideAlerts();

        // Validación en tiempo real
        initRealtimeValidation();

        // Animar entrada
        animateLoginCard();

        // Event listener para submit
        elements.form.addEventListener('submit', handleFormSubmit);

        // Auto-focus en campo vacío
        if (!elements.usuarioInput.value) {
            elements.usuarioInput.focus();
        } else if (!elements.passwordInput.value) {
            elements.passwordInput.focus();
        }

        // Enter en usuario pasa a contraseña
        elements.usuarioInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                elements.passwordInput.focus();
            }
        });

        console.log('✅ Login corporativo inicializado correctamente');
    }

    // ============================================
    // EJECUTAR AL CARGAR EL DOM
    // ============================================
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
