/**
 * JavaScript para el Sidebar y Navegación
 * Archivo: public/js/layout/sidebar.js
 */

$(document).ready(function() {
    // Inicializar colapsos de Bootstrap para los menús
    $('.nav-link[data-bs-toggle="collapse"]').on('click', function(e) {
        e.preventDefault();
        const target = $(this).data('bs-target');
        $(target).collapse('toggle');
    });

    // Toggle sidebar en desktop (minimizar/expandir)
    $('#sidebarToggle').on('click', function(e) {
        e.preventDefault();
        $('body').toggleClass('sidebar-icon-only');
    });
    
    // Toggle sidebar en mobile (mostrar/ocultar)
    $('#mobileMenuToggle').on('click', function(e) {
        e.preventDefault();
        $('.sidebar-offcanvas').toggleClass('active');
    });
    
    // Cerrar sidebar mobile al hacer click fuera
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.sidebar-offcanvas, #mobileMenuToggle').length) {
            $('.sidebar-offcanvas').removeClass('active');
        }
    });
    
    // Búsqueda global
    $('#searchInput').on('keypress', function(e) {
        if (e.key === 'Enter') {
            const query = $(this).val().trim();
            if (query.length >= 3) {
                const baseUrl = $('meta[name="base-url"]').attr('content') || '';
                window.location.href = baseUrl + '/buscar?q=' + encodeURIComponent(query);
            } else {
                if (typeof showToast === 'function') {
                    showToast('info', 'Ingresa al menos 3 caracteres para buscar');
                }
            }
        }
    });

    // Atajo de teclado Ctrl+K para búsqueda
    $(document).on('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            $('#searchInput').focus();
        }
    });
});
