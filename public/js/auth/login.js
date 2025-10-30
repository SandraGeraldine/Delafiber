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
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const usuario = document.getElementById('usuario').value.trim();
            const password = document.getElementById('password').value.trim();
            
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
        });
    }
});
