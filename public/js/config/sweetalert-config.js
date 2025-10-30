/**
 * Configuración global de SweetAlert2
 * Archivo: public/js/config/sweetalert-config.js
 */

// Configuración global de SweetAlert2 para centrado vertical
const SwalDefaults = Swal.mixin({
    customClass: {
        popup: 'swal2-center',
        container: 'swal2-container-center'
    }
});

// Sobrescribir Swal.fire con la configuración por defecto
window.Swal = SwalDefaults;

$(document).ready(function() {
    // Función para cerrar sesión con SweetAlert2
    window.cerrarSesion = function() {
        const baseUrl = $('meta[name="base-url"]').attr('content') || '';
        
        Swal.fire({
            title: '¿Cerrar sesión?',
            text: '¿Estás seguro que deseas salir del sistema?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: '<i class="ti-power-off"></i> Sí, salir',
            cancelButtonText: '<i class="ti-close"></i> Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Cerrando sesión...',
                    text: 'Por favor espera',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                setTimeout(() => {
                    window.location.href = baseUrl + '/auth/logout';
                }, 500);
            }
        });
    };

    // Configurar Toast global
    window.Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    // Función helper para mostrar toast
    window.showToast = function(icon, title) {
        Toast.fire({ icon: icon, title: title });
    };

    // Función global para confirmar eliminación
    window.confirmarEliminacion = function(titulo, texto, url) {
        Swal.fire({
            title: titulo || '¿Estás seguro?',
            html: texto || 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: '<i class="ti-trash"></i> Sí, eliminar',
            cancelButtonText: '<i class="ti-close"></i> Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Eliminando...',
                    text: 'Por favor espera',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                window.location.href = url;
            }
        });
    };
});
