/**
 * Componente de Select con Búsqueda Interactiva
 * Utiliza Select2 para búsqueda avanzada de usuarios y leads
 */

/**
 * Inicializar Select2 para búsqueda de usuarios
 * @param {string} selector - Selector del elemento select
 * @param {object} options - Opciones adicionales
 */
function inicializarBuscadorUsuarios(selector, options = {}) {
    const defaults = {
        placeholder: 'Escribe para buscar usuario...',
        allowClear: true,
        width: '100%',
        dropdownAutoWidth: true,
        language: {
            noResults: function() {
                return "No se encontraron usuarios";
            },
            searching: function() {
                return "Buscando...";
            },
            inputTooShort: function() {
                return "Escribe al menos 2 caracteres";
            }
        },
        minimumInputLength: 0,
        templateResult: formatearUsuario,
        templateSelection: formatearSeleccionUsuario,
        // Configuración para que el campo de búsqueda esté DENTRO del dropdown
        closeOnSelect: true,
        selectOnClose: false
    };

    const config = { ...defaults, ...options };
    
    // Destruir instancia previa si existe
    if ($(selector).data('select2')) {
        $(selector).select2('destroy');
    }
    
    $(selector).select2(config);
}

/**
 * Inicializar Select2 para búsqueda de leads
 * @param {string} selector - Selector del elemento select
 * @param {object} options - Opciones adicionales
 */
function inicializarBuscadorLeads(selector, options = {}) {
    const baseUrl = document.querySelector('meta[name="base-url"]')?.content || window.location.origin;
    
    const defaults = {
        placeholder: 'Buscar lead por nombre, teléfono o DNI...',
        allowClear: true,
        width: '100%',
        minimumInputLength: 2,
        language: {
            noResults: function() {
                return "No se encontraron leads";
            },
            searching: function() {
                return "Buscando...";
            },
            inputTooShort: function() {
                return "Escribe al menos 2 caracteres para buscar";
            },
            errorLoading: function() {
                return "No se pudieron cargar los resultados";
            }
        },
        ajax: {
            url: `${baseUrl}/leads/buscar`,
            dataType: 'json',
            delay: 300,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            data: function(params) {
                return {
                    q: params.term,
                    page: params.page || 1
                };
            },
            processResults: function(data, params) {
                params.page = params.page || 1;
                
                return {
                    results: data.leads.map(lead => ({
                        id: lead.idlead,
                        text: `${lead.nombre_completo} - ${lead.telefono}`,
                        lead: lead
                    })),
                    pagination: {
                        more: (params.page * 20) < data.total
                    }
                };
            },
            cache: true
        },
        templateResult: formatearLead,
        templateSelection: formatearSeleccionLead
    };

    const config = { ...defaults, ...options };
    
    $(selector).select2(config);
}

/**
 * Formatear resultado de usuario en el dropdown
 */
function formatearUsuario(usuario) {
    if (usuario.loading) {
        return usuario.text;
    }

    // Si es un option normal del HTML
    if (!usuario.element) {
        return usuario.text;
    }

    const $usuario = $(usuario.element);
    const textoCompleto = $usuario.text();
    const nombre = textoCompleto.split(' - ')[0].trim();
    const turno = $usuario.data('turno') || '';
    const leadsActivos = $usuario.data('leads') || 0;
    const tareasPendientes = $usuario.data('tareas') || 0;
    
    // Colores según turno
    const colorTurno = turno.toLowerCase().includes('mañana') ? 'warning' : 
                       turno.toLowerCase().includes('tarde') ? 'info' : 
                       turno.toLowerCase().includes('noche') ? 'dark' : 'secondary';

    const $container = $(`
        <div class="select2-usuario-item py-1">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 me-2">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                         style="width: 32px; height: 32px; font-size: 14px; font-weight: bold;">
                        ${nombre.charAt(0).toUpperCase()}
                    </div>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-semibold mb-0" style="font-size: 14px;">${nombre}</div>
                    <div class="text-muted" style="font-size: 11px;">
                        <span class="badge bg-${colorTurno}" style="font-size: 10px; padding: 2px 6px;">${turno}</span>
                        <span class="ms-2"><i class="mdi mdi-account-group"></i> ${leadsActivos}</span>
                        <span class="ms-2"><i class="mdi mdi-checkbox-marked-circle-outline"></i> ${tareasPendientes}</span>
                    </div>
                </div>
            </div>
        </div>
    `);

    return $container;
}

/**
 * Formatear selección de usuario
 */
function formatearSeleccionUsuario(usuario) {
    if (!usuario.id) {
        return usuario.text;
    }
    
    const $usuario = $(usuario.element);
    const textoCompleto = $usuario.text();
    
    // Extraer solo el nombre (antes del primer " - ")
    const nombre = textoCompleto.split(' - ')[0].trim();
    return nombre;
}

/**
 * Formatear resultado de lead en el dropdown
 */
function formatearLead(lead) {
    if (lead.loading) {
        return lead.text;
    }

    if (!lead.lead) {
        return lead.text;
    }

    const data = lead.lead;
    const estadoBadge = getEstadoBadge(data.estado);

    const $container = $(`
        <div class="select2-lead-item">
            <div class="d-flex align-items-center">
                <div class="avatar-sm me-2">
                    <div class="avatar-title rounded-circle bg-gradient-primary text-white">
                        ${data.nombre_completo.charAt(0).toUpperCase()}
                    </div>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-bold">${data.nombre_completo}</div>
                    <div class="text-muted small">
                        <span class="me-2">📞 ${data.telefono}</span>
                        ${data.dni ? `<span class="me-2">🆔 ${data.dni}</span>` : ''}
                        <span class="badge ${estadoBadge.class}">${estadoBadge.text}</span>
                    </div>
                    ${data.etapa ? `<div class="text-muted small">📍 ${data.etapa}</div>` : ''}
                </div>
            </div>
        </div>
    `);

    return $container;
}

/**
 * Formatear selección de lead
 */
function formatearSeleccionLead(lead) {
    if (!lead.id) {
        return lead.text;
    }
    
    if (lead.lead) {
        return `${lead.lead.nombre_completo} - ${lead.lead.telefono}`;
    }
    
    return lead.text;
}

/**
 * Obtener badge de estado
 */
function getEstadoBadge(estado) {
    const estados = {
        'activo': { class: 'bg-success', text: 'Activo' },
        'Activo': { class: 'bg-success', text: 'Activo' },
        'convertido': { class: 'bg-primary', text: 'Convertido' },
        'Convertido': { class: 'bg-primary', text: 'Convertido' },
        'descartado': { class: 'bg-danger', text: 'Descartado' },
        'Descartado': { class: 'bg-danger', text: 'Descartado' }
    };

    return estados[estado] || { class: 'bg-secondary', text: estado };
}

/**
 * Destruir instancia de Select2
 */
function destruirBuscador(selector) {
    if ($(selector).data('select2')) {
        $(selector).select2('destroy');
    }
}

/**
 * Limpiar selección
 */
function limpiarBuscador(selector) {
    $(selector).val(null).trigger('change');
}

// Exportar funciones globalmente
window.inicializarBuscadorUsuarios = inicializarBuscadorUsuarios;
window.inicializarBuscadorLeads = inicializarBuscadorLeads;
window.destruirBuscador = destruirBuscador;
window.limpiarBuscador = limpiarBuscador;
