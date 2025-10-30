/**
 * JavaScript para Vista de Campaña
 * Archivo: public/js/campanias/campanias-view.js
 */

window.toggleEstado = function(id) {
    if (confirm('¿Cambiar el estado de la campaña?')) {
        const baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '';
        window.location.href = `${baseUrl}/campanias/toggleEstado/${id}`;
    }
};
