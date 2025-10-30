/**
 * Auto-Completar Servicio en Cotizaciones
 * 
 * Selecciona automáticamente el servicio basándose en el plan de interés
 * que el cliente indicó al registrarse como lead.
 * 
 * @author Delafiber CRM
 * @version 1.0
 */

(function() {
    'use strict';

    /**
     * Inicializar auto-completado de servicio
     */
    function inicializarAutoCompletado() {
        // Obtener el plan de interés desde el atributo data
        const contenedor = document.getElementById('cotizacion-form-container');
        if (!contenedor) {
            console.log('Contenedor de formulario no encontrado');
            return;
        }

        const planInteres = contenedor.dataset.planInteres;
        const servicioSelect = document.getElementById('idservicio');

        if (!servicioSelect) {
            console.log('Select de servicios no encontrado');
            return;
        }

        if (!planInteres || planInteres === '') {
            console.log('ℹNo hay plan de interés definido');
            return;
        }

        console.log('Plan de interés del cliente:', planInteres);
        
        // Buscar y seleccionar el servicio
        buscarYSeleccionarServicio(servicioSelect, planInteres);
    }

    /**
     * Buscar el servicio que coincida con el plan de interés
     * 
     * @param {HTMLSelectElement} selectElement - Select de servicios
     * @param {string} planInteres - Plan de interés del cliente
     */
    function buscarYSeleccionarServicio(selectElement, planInteres) {
        const opciones = selectElement.options;
        let servicioEncontrado = false;
        
        for (let i = 0; i < opciones.length; i++) {
            const nombreServicio = opciones[i].text.toLowerCase();
            const planInteresLower = planInteres.toLowerCase();
            
            // Buscar coincidencias inteligentes
            if (coincideServicio(nombreServicio, planInteresLower)) {
                // Seleccionar el servicio
                selectElement.selectedIndex = i;
                servicioEncontrado = true;
                
                // Disparar evento change para actualizar el precio
                const event = new Event('change', { bubbles: true });
                selectElement.dispatchEvent(event);
                
                console.log('Servicio auto-seleccionado:', opciones[i].text);
                
                // Mostrar notificación visual
                mostrarNotificacion(selectElement, opciones[i].text);
                
                break;
            }
        }
        
        if (!servicioEncontrado) {
            console.log('No se encontró coincidencia exacta para:', planInteres);
        }
    }

    /**
     * Verificar si el servicio coincide con el plan de interés
     * 
     * @param {string} nombreServicio - Nombre del servicio
     * @param {string} planInteres - Plan de interés
     * @returns {boolean} - True si coincide
     */
    function coincideServicio(nombreServicio, planInteres) {
        // Coincidencia directa
        if (nombreServicio.includes(planInteres)) {
            return true;
        }
        
        // Coincidencia inversa
        if (planInteres.includes(nombreServicio.split('-')[0].trim())) {
            return true;
        }
        
        // Coincidencia por palabras clave (Mbps, MB, etc.)
        const palabrasClave = extraerPalabrasClave(planInteres);
        for (const palabra of palabrasClave) {
            if (nombreServicio.includes(palabra)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Extraer palabras clave del plan de interés
     * 
     * @param {string} texto - Texto del plan
     * @returns {Array} - Array de palabras clave
     */
    function extraerPalabrasClave(texto) {
        const palabras = [];
        
        // Buscar velocidades (50 Mbps, 100 Mbps, etc.)
        const regexVelocidad = /(\d+)\s*(mbps|mb|megas?)/gi;
        const matchVelocidad = texto.match(regexVelocidad);
        if (matchVelocidad) {
            palabras.push(...matchVelocidad.map(m => m.toLowerCase()));
        }
        
        // Buscar palabras importantes
        const palabrasImportantes = ['internet', 'fibra', 'cable', 'tv', 'instalacion', 'router'];
        for (const palabra of palabrasImportantes) {
            if (texto.toLowerCase().includes(palabra)) {
                palabras.push(palabra);
            }
        }
        
        return palabras;
    }

    /**
     * Mostrar notificación visual de auto-selección
     * 
     * @param {HTMLElement} selectElement - Select de servicios
     * @param {string} nombreServicio - Nombre del servicio seleccionado
     */
    function mostrarNotificacion(selectElement, nombreServicio) {
        // Crear elemento de notificación
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-info alert-dismissible fade show mt-3';
        alertDiv.setAttribute('role', 'alert');
        alertDiv.innerHTML = `
            <i class="ti-info-alt me-2"></i>
            <strong>Servicio pre-seleccionado:</strong> Se ha seleccionado automáticamente el servicio 
            "<strong>${nombreServicio.split('(')[0].trim()}</strong>" basado en el interés del cliente.
            <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                <span aria-hidden="true">&times;</span>
            </button>
        `;
        
        // Insertar antes del select
        const contenedorSelect = selectElement.closest('.col-md-6');
        if (contenedorSelect) {
            contenedorSelect.insertBefore(alertDiv, contenedorSelect.firstChild);
        } else {
            selectElement.parentElement.insertBefore(alertDiv, selectElement);
        }
        
        // Auto-cerrar después de 8 segundos
        setTimeout(() => {
            alertDiv.classList.remove('show');
            setTimeout(() => {
                alertDiv.remove();
            }, 150);
        }, 8000);
    }

    /**
     * Inicializar cuando el DOM esté listo
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inicializarAutoCompletado);
    } else {
        inicializarAutoCompletado();
    }

})();
