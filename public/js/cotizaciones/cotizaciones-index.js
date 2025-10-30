/**
 * JavaScript para Listado de Cotizaciones
 * Archivo: public/js/cotizaciones/cotizaciones-index.js
 */

// Filtros en tiempo real
document.getElementById('filtro-estado')?.addEventListener('change', function() {
    filtrarTabla();
});

document.getElementById('buscar-cliente')?.addEventListener('keyup', function() {
    filtrarTabla();
});

function filtrarTabla() {
    const estadoFiltro = document.getElementById('filtro-estado')?.value.toLowerCase() || '';
    const clienteFiltro = document.getElementById('buscar-cliente')?.value.toLowerCase() || '';
    const filas = document.querySelectorAll('tbody tr');

    filas.forEach(function(fila) {
        if (fila.cells.length === 1) return; // Skip empty state row
        
        const estado = fila.cells[4]?.textContent.toLowerCase() || '';
        const cliente = fila.cells[1]?.textContent.toLowerCase() || '';
        
        const mostrarEstado = !estadoFiltro || estado.includes(estadoFiltro);
        const mostrarCliente = !clienteFiltro || cliente.includes(clienteFiltro);
        
        fila.style.display = (mostrarEstado && mostrarCliente) ? '' : 'none';
    });
}

window.limpiarFiltros = function() {
    const filtroEstado = document.getElementById('filtro-estado');
    const buscarCliente = document.getElementById('buscar-cliente');
    
    if (filtroEstado) filtroEstado.value = '';
    if (buscarCliente) buscarCliente.value = '';
    
    filtrarTabla();
};

// Cambiar estado de cotización
window.cambiarEstado = function(idcotizacion, nuevoEstado) {
    const mensajes = {
        'Enviada': 'enviar',
        'Aceptada': 'aceptar',
        'Rechazada': 'rechazar'
    };
    
    if (!confirm(`¿Está seguro de ${mensajes[nuevoEstado] || 'cambiar'} esta cotización?`)) {
        return;
    }

    const baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '';

    fetch(`${baseUrl}/cotizaciones/cambiarEstado/${idcotizacion}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `estado=${nuevoEstado}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al cambiar el estado');
    });
};
