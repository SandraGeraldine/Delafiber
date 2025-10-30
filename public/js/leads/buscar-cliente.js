/**
 * Búsqueda de Cliente Existente por Teléfono
 * Permite crear múltiples solicitudes para el mismo cliente
 */

document.addEventListener('DOMContentLoaded', function() {
    const btnBuscarTelefono = document.getElementById('btnBuscarTelefono');
    const buscarTelefono = document.getElementById('buscar_telefono');
    const resultadoBusqueda = document.getElementById('resultado-busqueda');
    
    if (btnBuscarTelefono) {
        btnBuscarTelefono.addEventListener('click', buscarClientePorTelefono);
    }
    
    // Buscar al presionar Enter
    if (buscarTelefono) {
        buscarTelefono.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarClientePorTelefono();
            }
        });
    }
});

function buscarClientePorTelefono() {
    const telefono = document.getElementById('buscar_telefono').value.trim();
    const resultadoDiv = document.getElementById('resultado-busqueda');
    
    // Validar teléfono
    if (!telefono || telefono.length !== 9) {
        Swal.fire({
            icon: 'warning',
            title: 'Teléfono inválido',
            text: 'Ingrese un teléfono de 9 dígitos',
            confirmButtonColor: '#3085d6'
        });
        return;
    }
    
    // Mostrar loading
    resultadoDiv.innerHTML = `
        <div class="alert alert-info">
            <i class="icon-refresh rotating"></i> Buscando cliente...
        </div>
    `;
    resultadoDiv.style.display = 'block';
    
    // Hacer petición AJAX
    fetch(`${BASE_URL}/leads/buscarPorTelefono`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `telefono=${telefono}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.existe) {
            // Cliente encontrado
            mostrarClienteEncontrado(data.lead);
        } else {
            // Cliente no encontrado
            mostrarClienteNoEncontrado(telefono);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        resultadoDiv.innerHTML = `
            <div class="alert alert-danger">
                <i class="icon-close"></i> Error al buscar cliente. Intente nuevamente.
            </div>
        `;
    });
}

function mostrarClienteEncontrado(lead) {
    const resultadoDiv = document.getElementById('resultado-busqueda');
    
    // Mostrar información del cliente
    resultadoDiv.innerHTML = `
        <div class="alert alert-success">
            <h5><i class="icon-check"></i> Cliente Encontrado</h5>
            <hr>
            <p><strong>Nombre:</strong> ${lead.nombres} ${lead.apellidos}</p>
            <p><strong>Teléfono:</strong> ${lead.telefono}</p>
            <p><strong>DNI:</strong> ${lead.dni || 'No registrado'}</p>
            <p><strong>Correo:</strong> ${lead.correo || 'No registrado'}</p>
            <hr>
            <p class="mb-0">
                <i class="icon-info"></i> <strong>Solicitudes activas:</strong> ${lead.total_leads || 0}
            </p>
            <small class="text-muted">
                Puedes crear una nueva solicitud de servicio para este cliente en una ubicación diferente.
            </small>
        </div>
    `;
    
    // Autocompletar formulario
    document.getElementById('idpersona').value = lead.idpersona;
    document.getElementById('nombres').value = lead.nombres;
    document.getElementById('apellidos').value = lead.apellidos;
    document.getElementById('telefono').value = lead.telefono;
    document.getElementById('correo').value = lead.correo || '';
    document.getElementById('dni').value = lead.dni || '';
    
    // Deshabilitar campos personales (ya existen)
    document.getElementById('nombres').readOnly = true;
    document.getElementById('apellidos').readOnly = true;
    document.getElementById('telefono').readOnly = true;
    document.getElementById('dni').readOnly = true;
    
    // Enfocar en tipo de solicitud
    document.getElementById('tipo_solicitud').focus();
    
    // Mostrar notificación
    Swal.fire({
        icon: 'success',
        title: '¡Cliente encontrado!',
        text: 'Ahora puedes crear una nueva solicitud de servicio',
        confirmButtonColor: '#28a745'
    });
}

function mostrarClienteNoEncontrado(telefono) {
    const resultadoDiv = document.getElementById('resultado-busqueda');
    
    resultadoDiv.innerHTML = `
        <div class="alert alert-warning">
            <h5><i class="icon-info"></i> Cliente No Encontrado</h5>
            <p>No se encontró ningún cliente con el teléfono <strong>${telefono}</strong></p>
            <p class="mb-0">
                <i class="icon-arrow-down"></i> Completa los datos del nuevo cliente a continuación.
            </p>
        </div>
    `;
    
    // Limpiar campo oculto
    document.getElementById('idpersona').value = '';
    
    // Autocompletar teléfono
    document.getElementById('telefono').value = telefono;
    
    // Habilitar campos para nuevo cliente
    document.getElementById('nombres').readOnly = false;
    document.getElementById('apellidos').readOnly = false;
    document.getElementById('telefono').readOnly = false;
    document.getElementById('dni').readOnly = false;
    
    // Enfocar en nombres
    document.getElementById('nombres').focus();
}

// Función para limpiar búsqueda
function limpiarBusqueda() {
    document.getElementById('buscar_telefono').value = '';
    document.getElementById('resultado-busqueda').style.display = 'none';
    document.getElementById('idpersona').value = '';
    
    // Limpiar formulario
    document.getElementById('nombres').value = '';
    document.getElementById('apellidos').value = '';
    document.getElementById('telefono').value = '';
    document.getElementById('correo').value = '';
    document.getElementById('dni').value = '';
    
    // Habilitar campos
    document.getElementById('nombres').readOnly = false;
    document.getElementById('apellidos').readOnly = false;
    document.getElementById('telefono').readOnly = false;
    document.getElementById('dni').readOnly = false;
}
