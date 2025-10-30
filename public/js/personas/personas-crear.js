/**
 * JavaScript para Crear Persona
 */

const BASE_URL = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '';

// Verificar DNI duplicado en tiempo real
let dniTimeout;
const dniInput = document.getElementById('dni');

if (dniInput) {
    dniInput.addEventListener('input', function() {
        const dni = this.value;
        
        if (dni.length === 8) {
            clearTimeout(dniTimeout);
            dniTimeout = setTimeout(() => {
                fetch(`${BASE_URL}/personas/verificarDni?dni=${dni}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.existe) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'DNI Ya Registrado',
                                html: `
                                    <div class="text-start">
                                        <p><strong>Este DNI ya pertenece a:</strong></p>
                                        <ul class="list-unstyled">
                                            <li><strong>Nombre:</strong> ${data.persona.nombres} ${data.persona.apellidos}</li>
                                            <li><strong>Teléfono:</strong> ${data.persona.telefono || 'No registrado'}</li>
                                            <li><strong>Correo:</strong> ${data.persona.correo || 'No registrado'}</li>
                                        </ul>
                                        <p class="text-muted small mt-3">No puedes registrar el mismo DNI dos veces.</p>
                                    </div>
                                `,
                                confirmButtonText: 'Entendido',
                                confirmButtonColor: '#3085d6'
                            });
                            
                            // Limpiar el campo DNI
                            document.getElementById('dni').value = '';
                            document.getElementById('dni').focus();
                        }
                    });
            }, 500); // Esperar 500ms después de que el usuario deje de escribir
        }
    });
}

// Función para buscar persona por DNI (RENIEC)
const buscarDniBtn = document.getElementById('buscar-dni');
if (buscarDniBtn) {
    buscarDniBtn.addEventListener('click', function() {
        const dni = document.getElementById('dni').value;
        const searchingElement = document.getElementById('searching');

        if (dni.length === 8) {
            searchingElement.classList.remove('d-none');

            // Primero verificar si ya existe en la BD
            fetch(`${BASE_URL}/personas/verificarDni?dni=${dni}`)
                .then(response => response.json())
                .then(data => {
                    if (data.existe) {
                        searchingElement.classList.add('d-none');
                        Swal.fire({
                            icon: 'error',
                            title: 'Persona Ya Registrada',
                            html: `
                                <div class="text-start">
                                    <p><strong>Esta persona ya está en el sistema:</strong></p>
                                    <ul class="list-unstyled">
                                        <li><strong>Nombre:</strong> ${data.persona.nombres} ${data.persona.apellidos}</li>
                                        <li><strong>Teléfono:</strong> ${data.persona.telefono || 'No registrado'}</li>
                                        <li><strong>Correo:</strong> ${data.persona.correo || 'No registrado'}</li>
                                    </ul>
                                </div>
                            `,
                            showCancelButton: true,
                            confirmButtonText: 'Ver Contacto',
                            cancelButtonText: 'Cancelar',
                            confirmButtonColor: '#3085d6'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = `${BASE_URL}/personas`;
                            }
                        });
                        return;
                    }

                    // Si no existe, buscar en RENIEC
                    fetch(`${BASE_URL}/api/personas/buscar?dni=${dni}`)
                        .then(response => response.json())
                        .then(data => {
                            searchingElement.classList.add('d-none');

                            if (data.success && data.persona) {
                                // Autocompletar campos
                                document.getElementById('apellidos').value = data.persona.apellidos || '';
                                document.getElementById('nombres').value = data.persona.nombres || '';
                                document.getElementById('telefono').value = data.persona.telefono || '';
                                document.getElementById('correo').value = data.persona.correo || '';
                                document.getElementById('direccion').value = data.persona.direccion || '';
                                
                                if (data.persona.iddistrito) {
                                    document.getElementById('iddistrito').value = data.persona.iddistrito;
                                }
                                
                                document.getElementById('apellidos').readOnly = true;
                                document.getElementById('nombres').readOnly = true;
                                
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Datos Encontrados',
                                    text: data.message || 'Datos obtenidos correctamente',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            } else {
                                Swal.fire({
                                    icon: 'info',
                                    title: 'DNI no encontrado',
                                    text: 'Puedes registrar manualmente los datos',
                                    confirmButtonText: 'Entendido'
                                });

                                // Habilitar campos para nuevo registro
                                document.getElementById('apellidos').readOnly = false;
                                document.getElementById('nombres').readOnly = false;
                                document.getElementById('apellidos').focus();
                            }
                        })
                        .catch(error => {
                            searchingElement.classList.add('d-none');
                            console.error('Error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'No se pudo conectar al servidor'
                            });
                        });
                });
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'DNI inválido',
                text: 'El DNI debe tener 8 dígitos'
            });
        }
    });
}

// Validación del formulario antes de enviar
const formPersona = document.getElementById('form-persona');
if (formPersona) {
    formPersona.addEventListener('submit', function(e) {
        const telefono = document.getElementById('telefono').value;
        const dni = document.getElementById('dni').value;
        
        if (dni.length !== 8) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'DNI inválido',
                text: 'El DNI debe tener 8 dígitos'
            });
            return;
        }
        
        if (telefono.length !== 9) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Teléfono inválido',
                text: 'El teléfono debe tener 9 dígitos'
            });
        }
    });
}
