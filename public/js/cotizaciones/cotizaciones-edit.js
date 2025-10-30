/**
 * JavaScript para Editar Cotizaciones
 */

document.addEventListener('DOMContentLoaded', function() {
    const precioCotizadoInput = document.getElementById('precio_cotizado');
    const precioInstalacionInput = document.getElementById('precio_instalacion');
    const descuentoInput = document.getElementById('descuento_aplicado');

    // Calcular total cuando cambien los valores
    [precioCotizadoInput, precioInstalacionInput, descuentoInput].forEach(input => {
        if (input) {
            input.addEventListener('input', calcularTotal);
        }
    });

    function calcularTotal() {
        const precioServicio = parseFloat(precioCotizadoInput.value) || 0;
        const precioInstalacion = parseFloat(precioInstalacionInput.value) || 0;
        const descuentoPorcentaje = parseFloat(descuentoInput.value) || 0;
        
        const descuentoMonto = precioServicio * (descuentoPorcentaje / 100);
        const precioServicioConDescuento = precioServicio - descuentoMonto;
        const total = precioServicioConDescuento + precioInstalacion;
        
        // Actualizar display
        const precioServicioEl = document.getElementById('precio-servicio');
        const precioInstalacionEl = document.getElementById('precio-instalacion-display');
        const precioTotalEl = document.getElementById('precio-total');
        const precioMensualEl = document.getElementById('precio-mensual');
        
        if (precioServicioEl) precioServicioEl.textContent = `S/ ${precioServicioConDescuento.toFixed(2)}`;
        if (precioInstalacionEl) precioInstalacionEl.textContent = `S/ ${precioInstalacion.toFixed(2)}`;
        if (precioTotalEl) precioTotalEl.textContent = `S/ ${total.toFixed(2)}`;
        if (precioMensualEl) precioMensualEl.textContent = `S/ ${precioServicioConDescuento.toFixed(2)}`;
        
        // Mostrar/ocultar descuento
        const descuentoDisplay = document.getElementById('descuento-display');
        if (descuentoDisplay) {
            if (descuentoPorcentaje > 0) {
                descuentoDisplay.style.display = 'flex';
                const descuentoMontoEl = document.getElementById('descuento-monto');
                if (descuentoMontoEl) {
                    descuentoMontoEl.textContent = `-S/ ${descuentoMonto.toFixed(2)}`;
                }
            } else {
                descuentoDisplay.style.display = 'none';
            }
        }
    }

    // Calcular total inicial
    calcularTotal();
});

// Validación del formulario
const formCotizacion = document.getElementById('form-cotizacion');
if (formCotizacion) {
    formCotizacion.addEventListener('submit', function(e) {
        const precio = parseFloat(document.getElementById('precio_cotizado').value);
        
        if (precio <= 0) {
            e.preventDefault();
            alert('El precio cotizado debe ser mayor a 0');
            return false;
        }
        
        const descuento = parseFloat(document.getElementById('descuento_aplicado').value) || 0;
        if (descuento < 0 || descuento > 100) {
            e.preventDefault();
            alert('El descuento debe estar entre 0% y 100%');
            return false;
        }
    });
}
