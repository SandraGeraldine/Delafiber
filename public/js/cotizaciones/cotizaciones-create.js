/**
 * JavaScript para Crear Cotizaciones
 */

document.addEventListener('DOMContentLoaded', function() {
    const servicioSelect = document.getElementById('idservicio');
    const precioCotizadoInput = document.getElementById('precio_cotizado');
    const precioInstalacionInput = document.getElementById('precio_instalacion');
    const descuentoInput = document.getElementById('descuento_aplicado');

    // Auto-completar precios al seleccionar servicio
    servicioSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            const precioReferencial = parseFloat(selectedOption.dataset.precio) || 0;
            const precioInstalacion = parseFloat(selectedOption.dataset.instalacion) || 0;
            
            precioCotizadoInput.value = precioReferencial.toFixed(2);
            precioInstalacionInput.value = precioInstalacion.toFixed(2);
            
            calcularTotal();
        } else {
            precioCotizadoInput.value = '';
            precioInstalacionInput.value = '';
            calcularTotal();
        }
    });

    // Calcular total cuando cambien los valores
    [precioCotizadoInput, precioInstalacionInput, descuentoInput].forEach(input => {
        input.addEventListener('input', calcularTotal);
    });

    function calcularTotal() {
        const precioServicio = parseFloat(precioCotizadoInput.value) || 0;
        const precioInstalacion = parseFloat(precioInstalacionInput.value) || 0;
        const descuentoPorcentaje = parseFloat(descuentoInput.value) || 0;
        
        const descuentoMonto = precioServicio * (descuentoPorcentaje / 100);
        const precioServicioConDescuento = precioServicio - descuentoMonto;
        const total = precioServicioConDescuento + precioInstalacion;
        
        // Actualizar display
        document.getElementById('precio-servicio').textContent = `S/ ${precioServicioConDescuento.toFixed(2)}`;
        document.getElementById('precio-instalacion-display').textContent = `S/ ${precioInstalacion.toFixed(2)}`;
        document.getElementById('precio-total').textContent = `S/ ${total.toFixed(2)}`;
        
        // Mostrar/ocultar descuento
        const descuentoDisplay = document.getElementById('descuento-display');
        if (descuentoPorcentaje > 0) {
            descuentoDisplay.style.display = 'flex';
            document.getElementById('descuento-monto').textContent = `-S/ ${descuentoMonto.toFixed(2)}`;
        } else {
            descuentoDisplay.style.display = 'none';
        }
    }

    // Calcular total inicial si hay valores
    calcularTotal();
});

// Función para cambiar lead preseleccionado
window.cambiarLead = function() {
    const cardLead = document.querySelector('.card.bg-light');
    const hiddenInput = document.getElementById('idlead');
    const selectLead = document.getElementById('idlead-select');
    
    // Ocultar card y mostrar select
    cardLead.style.display = 'none';
    selectLead.classList.remove('d-none');
    selectLead.setAttribute('name', 'idlead');
    selectLead.setAttribute('required', 'required');
    
    // Remover input hidden
    hiddenInput.remove();
    
    // Cambiar ID del select
    selectLead.id = 'idlead';
};

// Validación del formulario
document.getElementById('form-cotizacion')?.addEventListener('submit', function(e) {
    const idlead = document.getElementById('idlead').value;
    const idservicio = document.getElementById('idservicio').value;
    const precio = document.getElementById('precio_cotizado').value;
    
    if (!idlead || !idservicio || !precio) {
        e.preventDefault();
        alert('Por favor complete todos los campos obligatorios');
        return false;
    }
    
    if (parseFloat(precio) <= 0) {
        e.preventDefault();
        alert('El precio cotizado debe ser mayor a 0');
        return false;
    }
});

// Inicializar Select2 para búsqueda de leads
$(document).ready(function() {
    // Verificar si Select2 está disponible
    if (typeof $.fn.select2 === 'undefined') {
        console.error('Select2 no está cargado');
        alert('Error: Select2 no está cargado. Por favor recarga la página.');
        return;
    }
    
    // Solo inicializar si el select existe y no tiene lead preseleccionado
    if ($('#idlead').length && !$('#idlead').is('[type="hidden"]')) {
        // Obtener base URL
        const baseUrl = $('meta[name="base-url"]').attr('content') || window.location.origin;
        const ajaxUrl = baseUrl + '/cotizaciones/buscarLeads';
        
        $('#idlead').select2({
            theme: 'bootstrap-5',
            placeholder: 'Buscar cliente por nombre, teléfono o DNI...',
            allowClear: true,
            width: '100%',
            ajax: {
                url: ajaxUrl,
                dataType: 'json',
                delay: 250,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                data: function (params) {
                    return {
                        q: params.term,
                        page: params.page || 1
                    };
                },
                processResults: function (data, params) {
                    if (data.error) {
                        console.error('Error del servidor:', data.error);
                        return { results: [] };
                    }
                    
                    params.page = params.page || 1;
                    return {
                        results: data.results || [],
                        pagination: {
                            more: data.pagination ? data.pagination.more : false
                        }
                    };
                },
                error: function(xhr, status, error) {
                    console.error('Error AJAX:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                },
                cache: true
            },
            minimumInputLength: 2,
            language: {
                inputTooShort: function() {
                    return 'Escribe al menos 2 caracteres para buscar';
                },
                searching: function() {
                    return 'Buscando clientes...';
                },
                noResults: function() {
                    return 'No se encontraron clientes';
                },
                errorLoading: function() {
                    return 'Error al cargar resultados. Revisa la consola.';
                }
            },
            templateResult: function(lead) {
                if (lead.loading) return lead.text;
                
                var dniInfo = lead.dni ? '<small class="text-info">DNI: ' + lead.dni + '</small><br>' : '';
                var etapaInfo = lead.etapa ? '<small class="text-muted">Etapa: ' + lead.etapa + '</small>' : '';
                var usuarioInfo = lead.usuario_asignado ? '<small class="text-warning"> | Asignado a: ' + lead.usuario_asignado + '</small>' : '';
                
                var $container = $(
                    '<div class="select2-result-lead">' +
                        '<div><strong>' + lead.text + '</strong></div>' +
                        dniInfo +
                        etapaInfo +
                        usuarioInfo +
                    '</div>'
                );
                return $container;
            },
            templateSelection: function(lead) {
                return lead.text || lead.id;
            }
        });
    }
});
