/**
 * Búsqueda Interactiva de Clientes con Select2
 * Permite buscar clientes existentes en tiempo real
 */

document.addEventListener('DOMContentLoaded', function() {
    inicializarBusquedaCliente();
});

/**
 * Inicializar Select2 para búsqueda de clientes
 */
function inicializarBusquedaCliente() {
    const selectElement = $('#buscar_cliente_select');
    
    if (!selectElement.length) {
        console.error('Elemento buscar_cliente_select no encontrado');
        return;
    }

    // Inicializar Select2 con búsqueda AJAX
    selectElement.select2({
        theme: 'bootstrap-5',
        placeholder: 'Escribe para buscar por nombre, teléfono o DNI...',
        allowClear: true,
        minimumInputLength: 3,
        language: {
            inputTooShort: function() {
                return 'Escribe al menos 3 caracteres para buscar';
            },
            searching: function() {
                return 'Buscando clientes...';
            },
            noResults: function() {
                return 'No se encontraron clientes. Puedes crear uno nuevo abajo.';
            },
            errorLoading: function() {
                return 'Error al buscar clientes';
            }
        },
        ajax: {
            url: `${BASE_URL}/leads/buscarClienteAjax`,
            dataType: 'json',
            delay: 300, // Esperar 300ms después de que el usuario deje de escribir
            data: function(params) {
                return {
                    q: params.term, // término de búsqueda
                    page: params.page || 1
                };
            },
            processResults: function(data, params) {
                params.page = params.page || 1;

                if (!data.success) {
                    return {
                        results: []
                    };
                }

                // Formatear resultados para Select2
                const results = data.clientes.map(cliente => {
                    return {
                        id: cliente.idpersona,
                        text: formatearTextoCliente(cliente),
                        cliente: cliente // Guardar datos completos
                    };
                });

                return {
                    results: results,
                    pagination: {
                        more: (params.page * 10) < data.total
                    }
                };
            },
            cache: true
        },
        templateResult: formatearResultado,
        templateSelection: formatearSeleccion
    });

    // Evento cuando se selecciona un cliente
    selectElement.on('select2:select', function(e) {
        const clienteData = e.params.data.cliente;
        autocompletarFormulario(clienteData);
        mostrarMensajeClienteExistente(clienteData);
    });

    // Evento cuando se limpia la selección
    selectElement.on('select2:clear', function() {
        limpiarFormulario();
    });
}

/**
 * Formatear texto del cliente para el select
 */
function formatearTextoCliente(cliente) {
    let texto = `${cliente.nombres} ${cliente.apellidos}`;
    
    if (cliente.telefono) {
        texto += ` - ${cliente.telefono}`;
    }
    
    if (cliente.dni) {
        texto += ` (DNI: ${cliente.dni})`;
    }
    
    return texto;
}

/**
 * Formatear resultado en el dropdown (con más detalles)
 */
function formatearResultado(cliente) {
    if (cliente.loading) {
        return cliente.text;
    }

    if (!cliente.cliente) {
        return cliente.text;
    }

    const data = cliente.cliente;
    
    const $resultado = $(`
        <div class="select2-result-cliente">
            <div class="d-flex align-items-center">
                <div class="cliente-avatar">
                    <i class="icon-user"></i>
                </div>
                <div class="cliente-info ms-3">
                    <div class="cliente-nombre">
                        <strong>${data.nombres} ${data.apellidos}</strong>
                        ${data.es_lead ? '<span class="badge badge-warning ms-2">Ya es Lead</span>' : ''}
                    </div>
                    <div class="cliente-detalles">
                        ${data.telefono ? `<span><i class="icon-phone"></i> ${data.telefono}</span>` : ''}
                        ${data.dni ? `<span class="ms-2"><i class="icon-id-card"></i> DNI: ${data.dni}</span>` : ''}
                    </div>
                    ${data.direccion ? `<div class="cliente-direccion text-muted small"><i class="icon-location-pin"></i> ${data.direccion}</div>` : ''}
                </div>
            </div>
        </div>
    `);

    return $resultado;
}

/**
 * Formatear selección (texto corto)
 */
function formatearSeleccion(cliente) {
    if (!cliente.cliente) {
        return cliente.text;
    }
    
    const data = cliente.cliente;
    return `${data.nombres} ${data.apellidos} - ${data.telefono || data.dni || 'Sin contacto'}`;
}

/**
 * Autocompletar formulario con datos del cliente
 */
function autocompletarFormulario(cliente) {
    // Guardar ID de persona
    document.getElementById('idpersona').value = cliente.idpersona;
    
    // Llenar campos
    document.getElementById('nombres').value = cliente.nombres || '';
    document.getElementById('apellidos').value = cliente.apellidos || '';
    document.getElementById('telefono').value = cliente.telefono || '';
    document.getElementById('correo').value = cliente.correo || '';
    document.getElementById('dni').value = cliente.dni || '';
    
    // Deshabilitar campos para evitar edición
    document.getElementById('nombres').setAttribute('readonly', true);
    document.getElementById('apellidos').setAttribute('readonly', true);
    document.getElementById('telefono').setAttribute('readonly', true);
    
    // Agregar clase visual
    document.getElementById('nombres').classList.add('bg-light');
    document.getElementById('apellidos').classList.add('bg-light');
    document.getElementById('telefono').classList.add('bg-light');
    
    // Scroll suave al formulario
    document.getElementById('nombres').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

/**
 * Mostrar mensaje si el cliente ya es un lead
 */
function mostrarMensajeClienteExistente(cliente) {
    const resultadoDiv = document.getElementById('resultado-busqueda');
    
    if (cliente.es_lead) {
        resultadoDiv.innerHTML = `
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <strong><i class="icon-alert-triangle"></i> Cliente ya registrado como Lead</strong>
                <p class="mb-2">Este cliente ya tiene un lead activo en el sistema.</p>
                <a href="${BASE_URL}/leads/view/${cliente.idlead}" class="btn btn-sm btn-primary" target="_blank">
                    <i class="icon-eye"></i> Ver Lead Existente
                </a>
                <button type="button" class="btn btn-sm btn-secondary ms-2" onclick="limpiarFormulario()">
                    <i class="icon-close"></i> Crear Nuevo Lead de Todas Formas
                </button>
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        `;
        resultadoDiv.style.display = 'block';
    } else {
        resultadoDiv.innerHTML = `
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong><i class="icon-check"></i> Cliente encontrado</strong>
                <p class="mb-0">Los datos se han cargado automáticamente. Continúa al Paso 2 para registrar el lead.</p>
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        `;
        resultadoDiv.style.display = 'block';
    }
}

/**
 * Limpiar formulario
 */
window.limpiarFormulario = function() {
    // Limpiar Select2
    $('#buscar_cliente_select').val(null).trigger('change');
    
    // Limpiar campos
    document.getElementById('idpersona').value = '';
    document.getElementById('nombres').value = '';
    document.getElementById('apellidos').value = '';
    document.getElementById('telefono').value = '';
    document.getElementById('correo').value = '';
    document.getElementById('dni').value = '';
    
    // Habilitar campos
    document.getElementById('nombres').removeAttribute('readonly');
    document.getElementById('apellidos').removeAttribute('readonly');
    document.getElementById('telefono').removeAttribute('readonly');
    
    // Quitar clase visual
    document.getElementById('nombres').classList.remove('bg-light');
    document.getElementById('apellidos').classList.remove('bg-light');
    document.getElementById('telefono').classList.remove('bg-light');
    
    // Ocultar resultado
    document.getElementById('resultado-busqueda').style.display = 'none';
    document.getElementById('resultado-busqueda').innerHTML = '';
}
