/**
 * ============================================
 * MAPA CRM - MANEJO DE INTERFAZ DE USUARIO
 * ============================================
 * Event listeners y manejo de botones
 */

import { asignarProspectosAZonas, cargarProspectosEnMapa } from './MapaCampanas.js';

let prospectosVisibles = false;

/**
 * Inicializar todos los event listeners
 */
export function inicializarEventos() {
    inicializarSelectorCampana();
    inicializarBotonCargarProspectos();
    inicializarBotonToggleProspectos();
    inicializarBotonGeocodificar();
    inicializarBotonAsignarAutomatico();
    inicializarBotonAnalisisZonas();
}

/**
 * Selector de campaña
 */
function inicializarSelectorCampana() {
    const selector = document.getElementById('id_campana_select');
    if (!selector) return;
    
    selector.addEventListener('change', function() {
        const idCampana = this.value;
        const baseUrl = selector.dataset.baseUrl || '/crm-campanas/mapa-campanas';
        
        if (idCampana) {
            window.location.href = `${baseUrl}/${idCampana}`;
        } else {
            window.location.href = baseUrl;
        }
    });
}

/**
 * Botón cargar prospectos
 */
function inicializarBotonCargarProspectos() {
    const btn = document.getElementById('btnCargarProspectos');
    if (!btn) return;
    
    btn.addEventListener('click', async function() {
        const idCampana = document.getElementById('id_campana_select')?.value;
        
        if (!idCampana) {
            alert('Selecciona una campaña primero');
            return;
        }
        
        btn.disabled = true;
        btn.innerHTML = '<i class="icon-loader"></i> Cargando...';
        
        try {
            await cargarProspectosEnMapa();
            prospectosVisibles = true;
            alert('✅ Prospectos cargados en el mapa');
        } catch (error) {
            alert('❌ Error al cargar prospectos: ' + error.message);
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="icon-users"></i> Cargar Prospectos';
        }
    });
}

/**
 * Botón toggle prospectos
 */
function inicializarBotonToggleProspectos() {
    const btn = document.getElementById('btnToggleProspectos');
    if (!btn) return;
    
    btn.addEventListener('click', function() {
        if (prospectosVisibles) {
            // Ocultar prospectos (implementar lógica)
            prospectosVisibles = false;
        } else {
            document.getElementById('btnCargarProspectos')?.click();
        }
    });
}

/**
 * Botón geocodificar prospectos
 */
function inicializarBotonGeocodificar() {
    const btn = document.getElementById('btnGeocodificar');
    if (!btn) return;
    
    btn.addEventListener('click', async function() {
        if (!confirm('¿Deseas geocodificar los prospectos sin coordenadas? (máximo 50 por ejecución)')) {
            return;
        }
        
        btn.disabled = true;
        btn.innerHTML = '<i class="icon-loader"></i> Geocodificando...';
        
        try {
            const response = await fetch('/crm-campanas/geocodificar-prospectos', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert(`Geocodificación completada:\n\n` +
                      `Total procesados: ${result.total}\n` +
                      `Geocodificados: ${result.geocodificados}\n` +
                      `Errores: ${result.errores}`);
                
                // Recargar prospectos en el mapa si están visibles
                if (prospectosVisibles) {
                    await cargarProspectosEnMapa();
                }
            } else {
                alert(' Error: ' + result.message);
            }
            
        } catch (error) {
            alert('Error al geocodificar: ' + error.message);
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="icon-map-pin"></i> Geocodificar';
        }
    });
}

/**
 * Botón asignar automático
 */
function inicializarBotonAsignarAutomatico() {
    const btn = document.getElementById('btnAsignarAutomatico');
    if (!btn) return;
    
    btn.addEventListener('click', async function() {
        const idCampana = document.getElementById('id_campana_select')?.value;
        
        if (!idCampana) {
            alert('Selecciona una campaña primero');
            return;
        }
        
        if (!confirm('¿Deseas asignar automáticamente los prospectos a las zonas según su ubicación?')) {
            return;
        }
        
        btn.disabled = true;
        btn.innerHTML = '<i class="icon-loader"></i> Procesando...';
        
        try {
            const resultado = await asignarProspectosAZonas(idCampana);
            
            alert(`✅ Asignación completada:\n\n` +
                  `Total prospectos: ${resultado.total}\n` +
                  `Asignados: ${resultado.asignados}\n` +
                  `Sin asignar: ${resultado.total - resultado.asignados}`);
            
            // Recargar página
            window.location.reload();
            
        } catch (error) {
            alert('❌ Error al asignar prospectos: ' + error.message);
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="icon-zap"></i> Asignar Automático';
        }
    });
}

/**
 * Botón análisis de zonas
 */
function inicializarBotonAnalisisZonas() {
    const btn = document.getElementById('btnAnalisisZonas');
    if (!btn) return;
    
    btn.addEventListener('click', function() {
        const idCampana = document.getElementById('id_campana_select')?.value;
        const baseUrl = btn.dataset.baseUrl || '/crm-campanas/reporte-zonas';
        
        if (!idCampana) {
            alert('Selecciona una campaña primero');
            return;
        }
        
        window.location.href = `${baseUrl}/${idCampana}`;
    });
}
