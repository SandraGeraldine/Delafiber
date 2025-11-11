/**
 * Filtrado Dinámico de Paquetes por Servicio
 * 
 * Filtra los paquetes de internet disponibles según el tipo de servicio
 * seleccionado (Fibra Óptica, Cable, Wireless, etc.)
 * 
 * @author Delafiber CRM
 * @version 1.0
 */

(function() {
    'use strict';

    // Variables globales del módulo
    let paquetes = [];
    let servicios = [];

    /**
     * Inicializar el filtrado de paquetes
     */
    function inicializarFiltroPaquetes() {
        // Obtener datos desde el contenedor
        const contenedor = document.getElementById('filtro-paquetes-data');
        if (!contenedor) {
            console.warn('Contenedor de datos de paquetes no encontrado');
            return;
        }

        // Parsear datos JSON
        try {
            paquetes = JSON.parse(contenedor.dataset.paquetes || '[]');
            servicios = JSON.parse(contenedor.dataset.servicios || '[]');
        } catch (e) {
            console.error('Error al parsear datos:', e);
            return;
        }

        console.log('Filtro de paquetes iniciado:', paquetes.length, 'paquetes,', servicios.length, 'servicios');

        // Obtener elementos del DOM
        const tipoServicioSelect = document.getElementById('tipo_servicio');
        const planInteresSelect = document.getElementById('plan_interes');
        const planInfo = document.getElementById('plan_info');

        if (!tipoServicioSelect || !planInteresSelect) {
            console.error(' No se encontraron los selects necesarios');
            return;
        }

        // Configurar evento de cambio
        tipoServicioSelect.addEventListener('change', function() {
            filtrarPaquetesPorServicio(this.value, planInteresSelect, planInfo);
        });
    }

    /**
     * Filtrar paquetes según el servicio seleccionado
     * 
     * @param {string} servicioId - ID del servicio seleccionado
     * @param {HTMLSelectElement} planSelect - Select de planes
     * @param {HTMLElement} infoElement - Elemento para mostrar información
     */
    function filtrarPaquetesPorServicio(servicioId, planSelect, infoElement) {
        console.log('Servicio seleccionado ID:', servicioId);

        // Destruir Select2 antes de limpiar opciones
        destruirSelect2(planSelect);

        // Limpiar opciones
        planSelect.innerHTML = '<option value="">Seleccione un plan</option>';

        if (!servicioId) {
            planSelect.disabled = true;
            infoElement.textContent = 'Seleccione un tipo de servicio primero';
            return;
        }

        // Filtrar paquetes
        const paquetesFiltrados = filtrarPaquetes(servicioId);

        console.log('Paquetes filtrados:', paquetesFiltrados.length);

        if (paquetesFiltrados.length > 0) {
            mostrarPaquetesDisponibles(paquetesFiltrados, planSelect, infoElement);
        } else {
            mostrarSinPaquetes(planSelect, infoElement);
        }
    }

    /**
     * Filtrar paquetes por ID de servicio
     * 
     * @param {string} servicioId - ID del servicio
     * @returns {Array} - Array de paquetes filtrados
     */
    function filtrarPaquetes(servicioId) {
        return paquetes.filter(paquete => {
            if (!paquete.id_servicio) {
                return false;
            }

            let servicioData = paquete.id_servicio;

            // Si es un string, intentar parsearlo
            if (typeof servicioData === 'string') {
                try {
                    servicioData = JSON.parse(servicioData);
                } catch (e) {
                    // Si no se puede parsear, comparar directamente
                    return servicioData == servicioId;
                }
            }

            // Si es un objeto con propiedad id_servicio (estructura anidada)
            if (servicioData && typeof servicioData === 'object' && servicioData.id_servicio) {
                servicioData = servicioData.id_servicio;
            }

            // Si es un array
            if (Array.isArray(servicioData)) {
                return servicioData.includes(parseInt(servicioId));
            }

            // Comparación directa
            return servicioData == servicioId;
        });
    }

    /**
     * Mostrar paquetes disponibles en el select
     * 
     * @param {Array} paquetesFiltrados - Paquetes a mostrar
     * @param {HTMLSelectElement} planSelect - Select de planes
     * @param {HTMLElement} infoElement - Elemento de información
     */
    function mostrarPaquetesDisponibles(paquetesFiltrados, planSelect, infoElement) {
        // Agregar opciones
        paquetesFiltrados.forEach(paquete => {
            const option = document.createElement('option');
            option.value = paquete.paquete;
            option.textContent = `${paquete.paquete} - S/ ${parseFloat(paquete.precio).toFixed(2)}/mes`;
            planSelect.appendChild(option);
        });

        // Habilitar select
        planSelect.disabled = false;
        infoElement.innerHTML = `<i class="icon-check text-success"></i> ${paquetesFiltrados.length} planes disponibles`;

        // Inicializar Select2
        inicializarSelect2(planSelect);
    }

    /**
     * Mostrar mensaje cuando no hay paquetes
     * 
     * @param {HTMLSelectElement} planSelect - Select de planes
     * @param {HTMLElement} infoElement - Elemento de información
     */
    function mostrarSinPaquetes(planSelect, infoElement) {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = 'No hay planes disponibles';
        planSelect.appendChild(option);

        planSelect.disabled = true;
        infoElement.innerHTML = '<i class="icon-info text-warning"></i> No hay planes para este servicio';

        // Destruir Select2 si existe
        destruirSelect2(planSelect);
    }

    /**
     * Inicializar Select2 en un elemento
     * 
     * @param {HTMLSelectElement} selectElement - Elemento select
     */
    function inicializarSelect2(selectElement) {
        if (typeof $ === 'undefined' || typeof $.fn.select2 === 'undefined') {
            console.warn('Select2 no está disponible');
            return;
        }

        // Encontrar un contenedor cercano para alojar el dropdown y evitar que se pinte sobre el mapa
        const $container = $(selectElement).closest('.modal, .offcanvas, .card, .form-group, .container, .content-wrapper').first();
        const dropdownHost = $container.length ? $container : $(selectElement).parent();

        $(selectElement).select2({
            placeholder: 'Buscar plan...',
            allowClear: true,
            minimumResultsForSearch: 0,
            dropdownAutoWidth: false,
            width: '100%',
            // Adjuntar el dropdown a un contenedor cercano para que no se superponga al mapa
            dropdownParent: dropdownHost,
            // Usar tema Bootstrap 5 (el CSS ya está incluido en el layout)
            theme: 'bootstrap-5',
            language: {
                noResults: function() {
                    return "No se encontraron resultados";
                },
                searching: function() {
                    return "Buscando...";
                },
                inputTooShort: function() {
                    return "Escribe para buscar...";
                }
            }
        });
    }

    /**
     * Destruir Select2 si está inicializado
     * 
     * @param {HTMLSelectElement} selectElement - Elemento select
     */
    function destruirSelect2(selectElement) {
        if (typeof $ === 'undefined' || typeof $.fn.select2 === 'undefined') {
            return;
        }

        const $select = $(selectElement);
        if ($select.hasClass('select2-hidden-accessible')) {
            $select.select2('destroy');
        }
    }

    /**
     * Inicializar cuando el DOM esté listo
     */
    function onReady() {
        inicializarFiltroPaquetes();

        // Evitar superposición del Select2 sobre el mapa cerrando cualquier dropdown al abrir el modal del mapa
        const mapModalEl = document.getElementById('mapModal');
        if (mapModalEl) {
            mapModalEl.addEventListener('show.bs.modal', function () {
                if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
                    $('.select2-hidden-accessible').each(function () {
                        try { $(this).select2('close'); } catch (e) {}
                    });
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', onReady);
    } else {
        onReady();
    }

})();
