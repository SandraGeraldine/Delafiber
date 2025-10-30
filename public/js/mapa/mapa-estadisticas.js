/**
 * ============================================
 * MAPA CRM - CÁLCULO DE ESTADÍSTICAS
 * ============================================
 * Procesa y muestra estadísticas de zonas
 */

/**
 * Calcular estadísticas de zonas
 */
export function calcularEstadisticas(zonas) {
    if (!zonas || !Array.isArray(zonas) || zonas.length === 0) {
        console.warn('No hay zonas para calcular estadísticas');
        return;
    }
    
    let totalProspectos = 0;
    let areaTotal = 0;
    
    zonas.forEach(zona => {
        totalProspectos += parseInt(zona.total_prospectos || 0);
        areaTotal += parseFloat(zona.area_km2 || 0);
    });
    
    const densidadPromedio = areaTotal > 0 ? (totalProspectos / areaTotal).toFixed(2) : 0;
    
    actualizarTarjetasEstadisticas(totalProspectos, areaTotal, densidadPromedio);
}

/**
 * Actualizar tarjetas de estadísticas en el DOM
 */
function actualizarTarjetasEstadisticas(totalProspectos, areaTotal, densidadPromedio) {
    const elemTotalProspectos = document.getElementById('totalProspectos');
    const elemAreaTotal = document.getElementById('areaTotal');
    const elemDensidadPromedio = document.getElementById('densidadPromedio');
    
    if (elemTotalProspectos) {
        elemTotalProspectos.textContent = totalProspectos;
    }
    
    if (elemAreaTotal) {
        elemAreaTotal.textContent = areaTotal.toFixed(2) + ' km²';
    }
    
    if (elemDensidadPromedio) {
        elemDensidadPromedio.textContent = densidadPromedio + '/km²';
    }
}

/**
 * Obtener resumen de estadísticas
 */
export function obtenerResumenEstadisticas(zonas) {
    if (!zonas || !Array.isArray(zonas)) {
        return {
            totalZonas: 0,
            totalProspectos: 0,
            areaTotal: 0,
            densidadPromedio: 0
        };
    }
    
    let totalProspectos = 0;
    let areaTotal = 0;
    
    zonas.forEach(zona => {
        totalProspectos += parseInt(zona.total_prospectos || 0);
        areaTotal += parseFloat(zona.area_km2 || 0);
    });
    
    return {
        totalZonas: zonas.length,
        totalProspectos: totalProspectos,
        areaTotal: areaTotal,
        densidadPromedio: areaTotal > 0 ? (totalProspectos / areaTotal) : 0
    };
}
