/**
 * JavaScript para la página de Login
 * Archivo: public/js/auth/login.js
 */

document.addEventListener('DOMContentLoaded', function() {
    // Auto-focus en el campo usuario
    const usuarioInput = document.getElementById('usuario');
    if (usuarioInput) {
        usuarioInput.focus();
    }
    
    // Limpiar mensajes después de unos segundos
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            alert.style.opacity = '0';
            setTimeout(function() {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 500);
        });
    }, 5000);
    
    // Validación simple del formulario
    const loginForm = document.querySelector('form');
    const passwordInput = document.getElementById('password');
    const togglePasswordBtn = document.querySelector('.btn-toggle-password');
    const btnLogin = document.getElementById('btnLogin');
    const btnLoginText = btnLogin ? btnLogin.querySelector('.btn-login-text') : null;
    const btnLoginSpinner = btnLogin ? btnLogin.querySelector('.btn-login-spinner') : null;

    // Mostrar / ocultar contraseña
    if (togglePasswordBtn && passwordInput) {
        const icon = togglePasswordBtn.querySelector('i');
        togglePasswordBtn.addEventListener('click', function () {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');

            if (icon) {
                icon.classList.toggle('ti-eye');
                icon.classList.toggle('ti-eye-off');
            }
        });
    }

    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const usuario = document.getElementById('usuario').value.trim();
            const password = passwordInput ? passwordInput.value.trim() : '';
            
            if (!usuario || !password) {
                e.preventDefault();
                alert('Por favor completa todos los campos');
                return false;
            }
            
            if (usuario.length < 3 || password.length < 3) {
                e.preventDefault();
                alert('Usuario y contraseña deben tener al menos 3 caracteres');
                return false;
            }

            // Mostrar loader y deshabilitar botón mientras se envía
            if (btnLogin && btnLoginText && btnLoginSpinner) {
                btnLogin.disabled = true;
                btnLoginText.classList.add('opacity-75');
                btnLoginSpinner.classList.remove('d-none');
            }
        });
    }
});
