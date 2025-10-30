/**
 * Gestión de Dropdowns del Header
 */

document.addEventListener('DOMContentLoaded', function() {
    // Esperar a que Bootstrap se inicialice
    setTimeout(() => {
        if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
            inicializarDropdowns();
            configurarComportamientos();
        } else {
            setTimeout(arguments.callee, 300);
        }
    }, 200);
});

/**
 * Inicializar dropdowns con Bootstrap
 */
function inicializarDropdowns() {
    const toggles = document.querySelectorAll('[data-bs-toggle="dropdown"]');
    
    toggles.forEach(toggle => {
        try {
            // Verificar si getInstance existe (Bootstrap 5.1+)
            const hasGetInstance = typeof bootstrap.Dropdown.getInstance === 'function';
            
            // Crear instancia de Bootstrap SIN Popper.js
            if (hasGetInstance) {
                if (!bootstrap.Dropdown.getInstance(toggle)) {
                    new bootstrap.Dropdown(toggle, {
                        autoClose: true,
                        popperConfig: null  // Deshabilitar Popper completamente
                    });
                }
            } else {
                // Fallback para versiones antiguas
                new bootstrap.Dropdown(toggle, {
                    autoClose: true
                });
            }
            
            // Forzar posicionamiento manual después de abrir
            toggle.addEventListener('shown.bs.dropdown', function() {
                const menu = this.nextElementSibling;
                if (menu && menu.classList.contains('dropdown-menu')) {
                    console.log('Ajustando posición del dropdown...');
                    
                    // Obtener dimensiones
                    const toggleRect = this.getBoundingClientRect();
                    const menuWidth = menu.offsetWidth;
                    const windowWidth = window.innerWidth;
                    
                    // Forzar posición absoluta debajo del toggle
                    menu.style.position = 'absolute';
                    menu.style.top = '100%';
                    menu.style.bottom = 'auto';
                    menu.style.transform = 'none';
                    menu.style.marginTop = '0.5rem';
                    
                    // Alinear a la derecha con margen de seguridad
                    if (menu.classList.contains('dropdown-menu-end')) {
                        // Calcular si se sale del viewport
                        const rightEdge = toggleRect.right;
                        const spaceOnRight = windowWidth - rightEdge;
                        
                        // Si el menú es más ancho que el espacio disponible, moverlo a la izquierda
                        if (menuWidth > spaceOnRight + 20) {
                            // Mover a la izquierda para que quepa
                            console.log(' Aplicando: right = 10px (poco espacio)');
                            menu.style.setProperty('left', 'auto', 'important');
                            menu.style.setProperty('right', '10px', 'important');
                        } else {
                            // Alineación normal con pequeño offset
                            console.log(' Aplicando: right = -20px (posición normal)');
                            menu.style.setProperty('left', 'auto', 'important');
                            menu.style.setProperty('right', '-15px', 'important');
                        }
                    } else {
                        // Centrar respecto al toggle
                        menu.style.setProperty('left', '50%', 'important');
                        menu.style.setProperty('right', 'auto', 'important');
                        menu.style.setProperty('transform', 'translateX(-50%)', 'important');
                    }
                }
            });
            
            // Cerrar otros dropdowns cuando se abre este
            toggle.addEventListener('show.bs.dropdown', function(e) {
                toggles.forEach(otherToggle => {
                    if (otherToggle !== this) {
                        try {
                            if (hasGetInstance) {
                                const instance = bootstrap.Dropdown.getInstance(otherToggle);
                                if (instance) {
                                    instance.hide();
                                }
                            } else {
                                // Fallback: cerrar manualmente
                                const menu = otherToggle.nextElementSibling;
                                if (menu && menu.classList.contains('show')) {
                                    menu.classList.remove('show');
                                    otherToggle.classList.remove('show');
                                    otherToggle.setAttribute('aria-expanded', 'false');
                                }
                            }
                        } catch (err) {
                            console.warn('Error al cerrar dropdown:', err);
                        }
                    }
                });
            });
        } catch (error) {
            console.error('Error al inicializar dropdown:', error);
        }
    });
}

/**
 * Configurar comportamientos personalizados
 */
function configurarComportamientos() {
    const dropdownMenus = document.querySelectorAll('.dropdown-menu');
    
    dropdownMenus.forEach(menu => {
        // Limpiar estilos inline cuando el dropdown se cierra completamente
        const parentDropdown = menu.closest('.dropdown');
        const toggle = parentDropdown?.querySelector('[data-bs-toggle="dropdown"]');
        
        if (toggle) {
            toggle.addEventListener('hidden.bs.dropdown', function() {
                menu.style.left = '';
                menu.style.right = '';
                menu.style.transition = '';
            });
        }
        
        menu.addEventListener('click', function(event) {
            const target = event.target;
            
            // Detectar si es una notificación
            const notificacion = target.closest('.notificacion-item');
            if (notificacion) {
                // Ejecutar lógica personalizada (ej: marcar como leída)
                // y cerrar después
                setTimeout(() => cerrarDropdownActual(this), 50);
                return;
            }
            
            // Detectar si es un item del menú de usuario
            const item = target.closest('.dropdown-item');
            const parentDropdown = this.closest('.dropdown');
            const toggle = parentDropdown?.querySelector('[data-bs-toggle="dropdown"]');
            
            if (item && toggle?.id === 'profileDropdown') {
                // Si es un link válido o botón de cerrar sesión
                if (item.tagName === 'A' || item.classList.contains('text-danger')) {
                    setTimeout(() => cerrarDropdownActual(this), 50);
                    return;
                }
            }
            
            // Botón especial: no cerrar dropdown
            if (target.id === 'btn-marcar-todas-leidas') {
                event.stopPropagation();
            }
        });
    });
}

/**
 * Cerrar el dropdown actual
 */
function cerrarDropdownActual(menu) {
    const parentDropdown = menu.closest('.dropdown');
    if (parentDropdown) {
        const toggle = parentDropdown.querySelector('[data-bs-toggle="dropdown"]');
        if (toggle) {
            // Deshabilitar transiciones temporalmente para evitar movimientos
            const originalTransition = menu.style.transition;
            menu.style.transition = 'none';
            
            // Fijar la posición actual antes de cerrar
            const rect = menu.getBoundingClientRect();
            menu.style.left = rect.left + 'px';
            menu.style.right = 'auto';
            
            // Restaurar transición después de un frame
            requestAnimationFrame(() => {
                menu.style.transition = originalTransition;
                
                try {
                    // Cerrar usando Bootstrap
                    if (typeof bootstrap !== 'undefined' && 
                        bootstrap.Dropdown && 
                        typeof bootstrap.Dropdown.getInstance === 'function') {
                        
                        const bsDropdown = bootstrap.Dropdown.getInstance(toggle);
                        if (bsDropdown) {
                            bsDropdown.hide();
                        } else {
                            forzarCierreDropdown(menu, toggle);
                        }
                    } else {
                        forzarCierreDropdown(menu, toggle);
                    }
                } catch (error) {
                    forzarCierreDropdown(menu, toggle);
                }
            });
        }
    }
}

/**
 * Forzar cierre de dropdown (fallback)
 */
function forzarCierreDropdown(menu, toggle) {
    menu.classList.remove('show');
    toggle.classList.remove('show');
    toggle.setAttribute('aria-expanded', 'false');
}

/**
 * Cerrar todos los dropdowns
 */
function cerrarTodosLosDropdowns() {
    const toggles = document.querySelectorAll('[data-bs-toggle="dropdown"]');
    toggles.forEach(toggle => {
        try {
            if (typeof bootstrap !== 'undefined' && 
                bootstrap.Dropdown && 
                typeof bootstrap.Dropdown.getInstance === 'function') {
                const instance = bootstrap.Dropdown.getInstance(toggle);
                if (instance) {
                    instance.hide();
                }
            } else {
                // Fallback manual
                const menu = toggle.nextElementSibling;
                if (menu && menu.classList.contains('show')) {
                    forzarCierreDropdown(menu, toggle);
                }
            }
        } catch (error) {
            console.warn('Error al cerrar dropdown:', error);
        }
    });
}

// Exportar funciones globales
window.cerrarDropdownActual = cerrarDropdownActual;
window.cerrarTodosLosDropdowns = cerrarTodosLosDropdowns;