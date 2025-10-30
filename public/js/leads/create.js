/**
 * JavaScript para el formulario de creación de Leads
 * Maneja búsqueda por DNI, validaciones y verificación de cobertura
 */

// Función auxiliar para escapar HTML
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text || '').replace(/[&<>"']/g, m => map[m]);
}

class PersonaManager {
    constructor(baseUrl) {
        this.baseUrl = baseUrl;
        this.coberturaInicializada = false; // Flag para evitar doble inicialización
        this.initEvents();
    }

    initEvents() {
        const btnBuscarDni = document.getElementById('btnBuscarDni');
        const dniInput = document.getElementById('dni');
        const dniLoading = document.getElementById('dni-loading');
        
        if (!btnBuscarDni || !dniInput) {
            // Botones de búsqueda no encontrados
            return;
        }
        
        // NO inicializar verificación de cobertura aquí
        // Se inicializará cuando el usuario llegue al Paso 2

        // =========================================
        // BÚSQUEDA POR DNI
        // =========================================
        btnBuscarDni.addEventListener('click', () => {
            const dni = dniInput.value.trim();
            
            if (dni.length !== 8) {
                Swal.fire({
                    icon: 'error',
                    title: 'DNI Inválido',
                    text: 'El DNI debe tener exactamente 8 dígitos',
                    confirmButtonColor: '#3085d6'
                });
                dniInput.focus();
                return;
            }

            dniLoading.style.display = 'block';
            btnBuscarDni.disabled = true;

            // Primero verificar si ya existe en la BD
            fetch(`${this.baseUrl}/personas/verificarDni?dni=${dni}`)
                .then(response => response.json())
                .then(data => {
                    if (data.existe) {
                        dniLoading.style.display = 'none';
                        btnBuscarDni.disabled = false;
                        
                        const personaNombreSafe = escapeHtml(data.persona.nombres || '');
                        const personaApellidosSafe = escapeHtml(data.persona.apellidos || '');
                        const personaTelefonoSafe = escapeHtml(data.persona.telefono || 'No registrado');
                        const personaCorreoSafe = escapeHtml(data.persona.correo || 'No registrado');

                        Swal.fire({
                            icon: 'warning',
                            title: '⚠️ Persona Ya Registrada',
                            html: `
                                <div class="text-start">
                                    <p><strong>Esta persona ya está en el sistema:</strong></p>
                                    <ul class="list-unstyled">
                                        <li>👤 <strong>Nombre:</strong> ${personaNombreSafe} ${personaApellidosSafe}</li>
                                        <li>📞 <strong>Teléfono:</strong> ${personaTelefonoSafe}</li>
                                        <li>📧 <strong>Correo:</strong> ${personaCorreoSafe}</li>
                                    </ul>
                                    <hr>
                                    <p class="text-muted small">
                                        <i class="icon-info"></i> Puedes crear una nueva solicitud de servicio para este cliente
                                    </p>
                                </div>
                            `,
                            showCancelButton: true,
                            confirmButtonText: 'Usar estos datos',
                            cancelButtonText: 'Cancelar',
                            confirmButtonColor: '#28a745',
                            cancelButtonColor: '#6c757d'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                this.autocompletarDatos(data.persona);
                            }
                        });
                        return;
                    }

                    // Si no existe, buscar en RENIEC
                    this.buscarEnReniec(dni, dniLoading, btnBuscarDni);
                })
                .catch(error => {
                    dniLoading.style.display = 'none';
                    btnBuscarDni.disabled = false;
                    console.error('❌ Error al verificar DNI:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de Conexión',
                        text: 'No se pudo conectar al servidor. Intenta de nuevo.',
                        confirmButtonColor: '#d33'
                    });
                });
        });

        // =========================================
        // ENTER SOLO EN DNI (no en otros campos)
        // =========================================
        dniInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                btnBuscarDni.click();
            }
        });

        // =========================================
        // VALIDACIÓN EN TIEMPO REAL - TELÉFONO
        // =========================================
        const telefonoInput = document.getElementById('telefono');
        if (telefonoInput) {
            telefonoInput.addEventListener('input', (e) => {
                // Solo permitir números
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
                
                // Validar formato mientras escribe
                const valor = e.target.value;
                if (valor.length === 9) {
                    if (valor.startsWith('9')) {
                        e.target.classList.remove('is-invalid');
                        e.target.classList.add('is-valid');
                    } else {
                        e.target.classList.remove('is-valid');
                        e.target.classList.add('is-invalid');
                    }
                } else {
                    e.target.classList.remove('is-valid', 'is-invalid');
                }
            });
        }

        // =========================================
        // VALIDACIÓN EN TIEMPO REAL - DNI
        // =========================================
        dniInput.addEventListener('input', (e) => {
            // Solo permitir números
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
        });
    }

    // =========================================
    // BUSCAR EN RENIEC
    // =========================================
    buscarEnReniec(dni, dniLoading, btnBuscarDni) {
        fetch(`${this.baseUrl}/api/personas/buscar?dni=${dni}`)
            .then(response => response.json())
            .then(data => {
                dniLoading.style.display = 'none';
                btnBuscarDni.disabled = false;
                
                if (data.success && data.persona) {
                    document.getElementById('nombres').value = data.persona.nombres || '';
                    document.getElementById('apellidos').value = data.persona.apellidos || '';
                    
                    Swal.fire({
                        icon: 'success',
                        title: '✅ Datos encontrados en RENIEC',
                        text: 'Ahora completa teléfono y demás información',
                        timer: 2500,
                        showConfirmButton: false,
                        timerProgressBar: true
                    });
                    
                    // Focus en teléfono después del toast
                    setTimeout(() => {
                        document.getElementById('telefono')?.focus();
                    }, 2600);
                } else {
                    Swal.fire({
                        icon: 'info',
                        title: 'DNI no encontrado en RENIEC',
                        text: 'Puedes registrar los datos manualmente',
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#3085d6'
                    });
                    document.getElementById('nombres')?.focus();
                }
            })
            .catch(error => {
                dniLoading.style.display = 'none';
                btnBuscarDni.disabled = false;
                console.error('❌ Error al consultar RENIEC:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error al consultar RENIEC',
                    text: 'Puedes registrar los datos manualmente',
                    confirmButtonColor: '#d33'
                });
            });
    }

    // =========================================
    // AUTOCOMPLETAR DATOS DE PERSONA EXISTENTE
    // =========================================
    autocompletarDatos(persona) {
        const nombresEl = document.getElementById('nombres');
        const apellidosEl = document.getElementById('apellidos');
        const telefonoEl = document.getElementById('telefono');
        const correoEl = document.getElementById('correo');
        const idpersonaEl = document.getElementById('idpersona');

        if (nombresEl) nombresEl.value = escapeHtml(persona.nombres || '');
        if (apellidosEl) apellidosEl.value = escapeHtml(persona.apellidos || '');
        if (telefonoEl) telefonoEl.value = escapeHtml(persona.telefono || '');
        if (correoEl) correoEl.value = escapeHtml(persona.correo || '');
        
        // IMPORTANTE: Guardar ID de persona para no duplicar
        if (idpersonaEl) {
            idpersonaEl.value = persona.idpersona;
        }

        // Agregar indicador visual
        const indicador = document.createElement('div');
        indicador.className = 'alert alert-success mt-3 alert-cliente-existente';
        indicador.innerHTML = `
            <i class="icon-check"></i> <strong>Cliente existente cargado</strong><br>
            <small>Se creará una nueva solicitud de servicio para este cliente</small>
        `;
        
        const cardBody = nombresEl.closest('.card-body');
        if (cardBody) {
            // Remover indicador anterior si existe
            const indicadorAnterior = cardBody.querySelector('.alert-cliente-existente');
            if (indicadorAnterior) {
                indicadorAnterior.remove();
            }
            
            cardBody.insertBefore(indicador, cardBody.firstChild);
        }

        Swal.fire({
            icon: 'success',
            title: '✅ Cliente Cargado',
            text: 'Ahora completa la información de la solicitud de servicio',
            timer: 2000,
            showConfirmButton: false,
            timerProgressBar: true
        });
    }
    
    // =========================================
    // VERIFICAR COBERTURA DE ZONAS
    // =========================================
    initVerificarCobertura() {
        // Evitar doble inicialización
        if (this.coberturaInicializada) {
            return;
        }
        
        const distritoSelect = document.getElementById('iddistrito');
        
        if (!distritoSelect) {
            return;
        }
        
        this.coberturaInicializada = true;
        
        distritoSelect.addEventListener('change', async () => {
            const distrito = distritoSelect.value;
            
            if (!distrito) {
                return;
            }
            
            try {
                const url = `${this.baseUrl}/leads/verificar-cobertura?distrito=${distrito}`;
                
                const response = await fetch(url);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                
                // Verificar si SweetAlert está disponible
                if (typeof Swal === 'undefined') {
                    console.error('❌ SweetAlert2 no está cargado!');
                    alert(`Cobertura: ${result.mensaje || 'Verificación completada'}`);
                    return;
                }
                
                if (result.success) {
                    this.mostrarAlertaCobertura(result);
                } else {
                    this.mostrarAlertaCobertura(result);
                }
            } catch (error) {
                console.error('❌ Error al verificar cobertura:', error);
                console.error('❌ Stack:', error.stack);
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'error',
                        title: 'Error al verificar cobertura',
                        text: error.message,
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });
                } else {
                    alert('Error al verificar cobertura: ' + error.message);
                }
            }
        });
    }

    // =========================================
    // MOSTRAR ALERTA DE COBERTURA
    // =========================================
    mostrarAlertaCobertura(result) {
        // Mostrar alerta de cobertura en UI
        
        const alertaContainer = document.getElementById('alerta-cobertura-zona');
        
        if (!alertaContainer) {
            console.error('❌ Contenedor #alerta-cobertura-zona no encontrado');
            return;
        }
        
        if (result.tiene_cobertura) {
            const totalZonas = result.zonas_activas || 0;
            
            // Construir lista de zonas con sus campañas
            let zonasListaHtml = '';
            if (result.zonas && result.zonas.length > 0) {
                zonasListaHtml = result.zonas.map(z => {
                    return `<li><strong>${escapeHtml(z.nombre_zona)}</strong> (${escapeHtml(z.campania_nombre)})</li>`;
                }).join('');
            }
            
            // Mostrar mensaje de cobertura positiva
            alertaContainer.innerHTML = `
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <h6 class="alert-heading mb-2">
                        <i class="icon-check"></i> ¡Excelente! Tenemos ${totalZonas} zona(s) activa(s) en campañas
                    </h6>
                    <p class="mb-2">El lead será asignado automáticamente a una zona al guardar.</p>
                    <hr>
                    <p class="mb-1"><strong>Zonas activas:</strong></p>
                    <ul class="mb-0">
                        ${zonasListaHtml}
                    </ul>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            `;
            alertaContainer.style.display = 'block';
        } else {
            const distrito = result.distrito_nombre || 'esta zona';
            
            // Mostrar mensaje de sin cobertura
            alertaContainer.innerHTML = `
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <h6 class="alert-heading mb-2">
                        <i class="icon-info"></i> Sin zonas activas
                    </h6>
                    <p class="mb-0">
                        <strong>${distrito}</strong> no tiene zonas de campaña activas en este momento.
                        El lead se registrará normalmente.
                    </p>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            `;
            alertaContainer.style.display = 'block';
        }
    }
}

// =========================================
// INICIALIZAR
// =========================================
document.addEventListener('DOMContentLoaded', () => {
    if (typeof BASE_URL !== 'undefined') {
        window.personaManager = new PersonaManager(BASE_URL);
    } else {
        console.error('BASE_URL no está definida');
    }
});