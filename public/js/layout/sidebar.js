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

    // Función para aplicar comportamiento responsive
    function aplicarColapsoResponsive() {
        const ancho = window.innerWidth || document.documentElement.clientWidth;
        
        if (ancho <= 991) {
            // En mobile/tablet: cerrar sidebar y remover clase de colapso
            $('.sidebar-offcanvas').removeClass('active');
            $('body').removeClass('sidebar-icon-only');
        }
        // En desktop: no forzar ningún estado, permitir toggle manual
    }

    // Aplicar al cargar
    aplicarColapsoResponsive();

    // Aplicar al redimensionar
    $(window).on('resize', function() {
        aplicarColapsoResponsive();
    });

    // Restaurar preferencia del usuario en desktop
    const sidebarCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
    if (window.innerWidth > 991 && sidebarCollapsed) {
        $('body').addClass('sidebar-icon-only');
    }

    // Toggle sidebar en desktop (minimizar/expandir)
    $('#sidebarToggle').on('click', function(e) {
        e.preventDefault();
        $('body').toggleClass('sidebar-icon-only');
        
        // Guardar preferencia del usuario
        localStorage.setItem('sidebar-collapsed', $('body').hasClass('sidebar-icon-only'));
    });
    
    // Toggle sidebar en mobile: mostrar/ocultar offcanvas
    // Solo el botón #mobileMenuToggle debe controlar el offcanvas móvil,
    // no el botón de colapsar sidebar de escritorio (#sidebarToggle).
    $('#mobileMenuToggle').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('Toggle clicked'); // Debug
        $('.sidebar-offcanvas').toggleClass('active');
        
        // Prevenir scroll del body cuando sidebar está abierto
        if ($('.sidebar-offcanvas').hasClass('active')) {
            $('body').addClass('sidebar-open');
        } else {
            $('body').removeClass('sidebar-open');
        }
    });
    
    // Cerrar sidebar mobile al hacer click fuera
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.sidebar-offcanvas, #mobileMenuToggle').length) {

            if ($('.sidebar-offcanvas').hasClass('active')) {
                $('.sidebar-offcanvas').removeClass('active');
                $('body').removeClass('sidebar-open');
            }
        }
    });
    
    // NOTA: ya no cerramos automáticamente el sidebar al hacer click en los links.
    // El usuario lo abrirá/cerrará manualmente con el botón hamburguesa.
    
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