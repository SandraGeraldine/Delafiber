/**
 * ============================================
 * MAPA CRM - INICIALIZACIÓN DEL SISTEMA
 * ============================================
 */

import { inicializarMapaCampanas } from './MapaCampanas.js';
import { inicializarEventos } from './mapa-ui.js';
import { calcularEstadisticas } from './mapa-estadisticas.js';

/**
 * Esperar a que Google Maps esté completamente cargado
 */
function esperarGoogleMaps() {
    return new Promise((resolve) => {
        if (typeof google !== 'undefined' && google.maps) {
            resolve();
        } else {
            const interval = setInterval(() => {
                if (typeof google !== 'undefined' && google.maps) {
                    clearInterval(interval);
                    resolve();
                }
            }, 100);
        }
    });
}

/**
 * Inicializar todo el sistema del mapa CRM
 */
export async function inicializarSistema(idCampana = null, zonas = null) {
    try {
        // Esperar a que Google Maps esté listo
        await esperarGoogleMaps();
        console.log('Google Maps API cargada');
        
        // Inicializar el mapa
        const mapaInstancia = await inicializarMapaCampanas('mapCampanas', idCampana);
        console.log('Mapa inicializado');
        
        // Inicializar event listeners
        inicializarEventos();
        console.log('Event listeners configurados');
        
        // Calcular estadísticas si hay zonas
        if (zonas && Array.isArray(zonas) && zonas.length > 0) {
            calcularEstadisticas(zonas);
            console.log(' Estadísticas calculadas');
        }
        
        return mapaInstancia;
        
    } catch (error) {
        console.error(' Error al inicializar sistema:', error);
        throw error;
    }
}
