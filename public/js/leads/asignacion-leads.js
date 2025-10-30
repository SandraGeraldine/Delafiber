/**
 * Sistema de Asignación y Reasignación de Leads
 * Comunicación entre usuarios
 */

// Variables globales
var usuariosDisponibles = [];
var baseUrl = window.location.origin;

// Obtener baseUrl del meta tag cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Obtener baseUrl del meta tag
    var metaBase = document.querySelector('meta[name="base-url"]');
    if (metaBase) {
        baseUrl = metaBase.content;
    }
    
    // Inicializar
    cargarUsuariosDisponibles();
    inicializarEventos();
});

/**
 * Cargar lista de usuarios disponibles
 */
async function cargarUsuariosDisponibles() {
    try {
        const response = await fetch(`${baseUrl}/lead-asignacion/getUsuariosDisponibles`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            usuariosDisponibles = data.usuarios;
        }
    } catch (error) {
        console.error('Error al cargar usuarios:', error);
    }
}

/**
 * Inicializar eventos
 */
var eventosInicializados = false;

function inicializarEventos() {
    // Evitar inicializar eventos múltiples veces
    if (eventosInicializados) return;
    eventosInicializados = true;
    
    // Usar delegación de eventos en el body para evitar duplicados
    document.body.addEventListener('click', function(e) {
        // Solo procesar si el clic es directamente en un botón, no en inputs/textareas
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
            return; // No hacer nada si es un campo de formulario
        }
        
        // Botón de reasignar
        const btnReasignar = e.target.closest('.btn-reasignar-lead');
        if (btnReasignar) {
            e.preventDefault();
            e.stopPropagation();
            const idlead = btnReasignar.dataset.idlead;
            mostrarModalReasignar(idlead);
            return;
        }

        // Botón de solicitar apoyo
        const btnApoyo = e.target.closest('.btn-solicitar-apoyo');
        if (btnApoyo) {
            e.preventDefault();
            e.stopPropagation();
            const idlead = btnApoyo.dataset.idlead;
            mostrarModalSolicitarApoyo(idlead);
            return;
        }

        // Botón de programar seguimiento
        const btnProgramar = e.target.closest('.btn-programar-seguimiento');
        if (btnProgramar) {
            e.preventDefault();
            e.stopPropagation();
            const idlead = btnProgramar.dataset.idlead;
            mostrarModalProgramarSeguimiento(idlead);
            return;
        }
    });
}

/**
 * Mostrar modal de reasignación
 */
window.mostrarModalReasignar = function(idlead) {
    const html = `
        <div class="modal fade" id="modalReasignar" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="mdi mdi-account-switch"></i> Reasignar Lead
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="formReasignar">
                            <input type="hidden" name="idlead" value="${idlead}">
                            
                            <div class="mb-3">
                                <label class="form-label">Buscar y asignar usuario:</label>
                                <input type="text" 
                                       id="inputBuscarUsuario" 
                                       class="form-control" 
                                       placeholder="Escribe para buscar por nombre, teléfono o DNI"
                                       autocomplete="off">
                                <input type="hidden" name="nuevo_usuario" id="hiddenUsuarioSeleccionado" required>
                                
                                <div id="resultadosBusqueda" class="list-group mt-2" style="max-height: 250px; overflow-y: auto; display: none;">
                                    <!-- Resultados de búsqueda aparecerán aquí -->
                                </div>
                                
                                <div id="infoUsuarioSeleccionado" class="mt-2" style="display:none;">
                                    <div class="alert alert-info mb-0">
                                        <strong><i class="mdi mdi-account"></i> <span id="nombreUsuario"></span></strong><br>
                                        <small>
                                            <i class="mdi mdi-clock"></i> Turno: <span id="turnoUsuario"></span> | 
                                            <i class="mdi mdi-account-group"></i> Leads activos: <span id="leadsUsuario"></span> | 
                                            <i class="mdi mdi-checkbox-marked-circle"></i> Tareas pendientes: <span id="tareasUsuario"></span>
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Motivo de reasignación:</label>
                                <textarea name="motivo" class="form-control" rows="3" 
                                    placeholder="Ej: Cliente prefiere horario de tarde, zona no corresponde, etc."></textarea>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="crearTarea" name="crear_tarea">
                                    <label class="form-check-label" for="crearTarea">
                                        Crear tarea de seguimiento programada
                                    </label>
                                </div>
                            </div>

                            <div id="camposTarea" style="display: none;">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Fecha:</label>
                                        <input type="date" name="fecha_tarea" class="form-control" 
                                            min="${new Date().toISOString().split('T')[0]}">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Hora:</label>
                                        <input type="time" name="hora_tarea" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <i class="mdi mdi-information"></i>
                                El usuario recibirá una notificación inmediata sobre esta asignación.
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" onclick="ejecutarReasignacion()">
                            <i class="mdi mdi-check"></i> Reasignar Lead
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Remover modal anterior si existe
    const modalExistente = document.getElementById('modalReasignar');
    if (modalExistente) {
        $('#modalReasignar').modal('hide');
        modalExistente.remove();
    }
    
    // Limpiar todos los backdrops residuales
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open').css('padding-right', '');
    
    // Agregar nuevo modal
    document.body.insertAdjacentHTML('beforeend', html);
    
    // Esperar un momento para que la limpieza se complete
    setTimeout(function() {
        const $modal = $('#modalReasignar');
        $modal.modal('show');
        
        // Configurar búsqueda de usuarios
        const inputBuscar = document.getElementById('inputBuscarUsuario');
        const resultadosDiv = document.getElementById('resultadosBusqueda');
        const hiddenInput = document.getElementById('hiddenUsuarioSeleccionado');
        
        inputBuscar.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            
            if (query.length === 0) {
                resultadosDiv.style.display = 'none';
                resultadosDiv.innerHTML = '';
                return;
            }
            
            // Filtrar usuarios
            const usuariosFiltrados = usuariosDisponibles.filter(u => 
                u.nombre.toLowerCase().includes(query) ||
                u.turno.toLowerCase().includes(query)
            );
            
            if (usuariosFiltrados.length === 0) {
                resultadosDiv.innerHTML = '<div class="list-group-item text-muted">No se encontraron usuarios</div>';
                resultadosDiv.style.display = 'block';
                return;
            }
            
            // Mostrar resultados
            resultadosDiv.innerHTML = usuariosFiltrados.map(u => {
                const colorTurno = u.turno.toLowerCase().includes('mañana') ? 'warning' : 
                                   u.turno.toLowerCase().includes('tarde') ? 'info' : 'secondary';
                return `
                    <a href="#" class="list-group-item list-group-item-action usuario-item" 
                       data-id="${u.idusuario}"
                       data-nombre="${u.nombre}"
                       data-turno="${u.turno}"
                       data-leads="${u.leads_activos}"
                       data-tareas="${u.tareas_pendientes}">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-2">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                                     style="width: 32px; height: 32px; font-size: 14px; font-weight: bold;">
                                    ${u.nombre.charAt(0).toUpperCase()}
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">${u.nombre}</div>
                                <small class="text-muted">
                                    <span class="badge bg-${colorTurno}">${u.turno}</span>
                                    <span class="ms-2">📊 ${u.leads_activos} leads</span>
                                    <span class="ms-2">✅ ${u.tareas_pendientes} tareas</span>
                                </small>
                            </div>
                        </div>
                    </a>
                `;
            }).join('');
            
            resultadosDiv.style.display = 'block';
            
            // Event listeners para seleccionar usuario
            resultadosDiv.querySelectorAll('.usuario-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const id = this.dataset.id;
                    const nombre = this.dataset.nombre;
                    const turno = this.dataset.turno;
                    const leads = this.dataset.leads;
                    const tareas = this.dataset.tareas;
                    
                    // Actualizar input y hidden
                    inputBuscar.value = nombre;
                    hiddenInput.value = id;
                    
                    // Mostrar info del usuario
                    document.getElementById('nombreUsuario').textContent = nombre;
                    document.getElementById('turnoUsuario').textContent = turno;
                    document.getElementById('leadsUsuario').textContent = leads;
                    document.getElementById('tareasUsuario').textContent = tareas;
                    document.getElementById('infoUsuarioSeleccionado').style.display = 'block';
                    
                    // Ocultar resultados
                    resultadosDiv.style.display = 'none';
                });
            });
        });
        
        // Ocultar resultados al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (!inputBuscar.contains(e.target) && !resultadosDiv.contains(e.target)) {
                resultadosDiv.style.display = 'none';
            }
        });
    }, 100);

    // Toggle campos de tarea
    setTimeout(() => {
        const crearTareaCheckbox = document.getElementById('crearTarea');
        if (crearTareaCheckbox) {
            crearTareaCheckbox.addEventListener('change', function() {
                document.getElementById('camposTarea').style.display = this.checked ? 'block' : 'none';
            });
        }
    }, 200);
}

/**
 * Ejecutar reasignación
 */
window.ejecutarReasignacion = async function() {
    const form = document.getElementById('formReasignar');
    const formData = new FormData(form);

    // Validar
    if (!formData.get('nuevo_usuario')) {
        Swal.fire('Error', 'Debes seleccionar un usuario', 'error');
        return;
    }

    // Mostrar loading
    Swal.fire({
        title: 'Reasignando...',
        text: 'Por favor espera',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const response = await fetch(`${baseUrl}/lead-asignacion/reasignar`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: data.message,
                timer: 2000
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire('Error', data.message, 'error');
        }

    } catch (error) {
        console.error('Error:', error);
        Swal.fire('Error', 'No se pudo completar la reasignación', 'error');
    }
}

/**
 * Mostrar modal de solicitar apoyo
 */
window.mostrarModalSolicitarApoyo = function(idlead) {
    const html = `
        <div class="modal fade" id="modalSolicitarApoyo" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title">
                            <i class="mdi mdi-account-multiple"></i> Solicitar Apoyo
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="formSolicitarApoyo">
                            <input type="hidden" name="idlead" value="${idlead}">
                            
                            <div class="mb-3">
                                <label class="form-label">Buscar usuario para solicitar apoyo:</label>
                                <input type="text" 
                                       id="inputBuscarUsuarioApoyo" 
                                       class="form-control" 
                                       placeholder="Escribe para buscar usuario..."
                                       autocomplete="off">
                                <input type="hidden" name="usuario_apoyo" id="hiddenUsuarioApoyo" required>
                                
                                <div id="resultadosBusquedaApoyo" class="list-group mt-2" style="max-height: 250px; overflow-y: auto; display: none;">
                                    <!-- Resultados de búsqueda aparecerán aquí -->
                                </div>
                                
                                <div id="infoUsuarioApoyo" class="mt-2" style="display:none;">
                                    <div class="alert alert-info mb-0">
                                        <strong><i class="mdi mdi-account"></i> <span id="nombreUsuarioApoyo"></span></strong><br>
                                        <small>
                                            <i class="mdi mdi-clock"></i> Turno: <span id="turnoUsuarioApoyo"></span> | 
                                            <i class="mdi mdi-account-group"></i> Leads activos: <span id="leadsUsuarioApoyo"></span> | 
                                            <i class="mdi mdi-checkbox-marked-circle"></i> Tareas pendientes: <span id="tareasUsuarioApoyo"></span>
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Mensaje:</label>
                                <textarea name="mensaje" class="form-control" rows="4" required
                                    placeholder="Describe en qué necesitas apoyo..."></textarea>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="urgente" name="urgente">
                                    <label class="form-check-label" for="urgente">
                                        <span class="badge bg-danger">URGENTE</span> Marcar como urgente
                                    </label>
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <i class="mdi mdi-information"></i>
                                El lead seguirá asignado a ti. Solo estás solicitando ayuda.
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-warning" onclick="ejecutarSolicitarApoyo()">
                            <i class="mdi mdi-send"></i> Enviar Solicitud
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Remover modal anterior si existe
    const modalExistente = document.getElementById('modalSolicitarApoyo');
    if (modalExistente) {
        $('#modalSolicitarApoyo').modal('hide');
        modalExistente.remove();
    }
    
    // Limpiar todos los backdrops residuales
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open').css('padding-right', '');
    
    document.body.insertAdjacentHTML('beforeend', html);
    
    // Esperar un momento para que la limpieza se complete
    setTimeout(function() {
        const $modal = $('#modalSolicitarApoyo');
        $modal.modal('show');
        
        // Configurar búsqueda de usuarios
        const inputBuscar = document.getElementById('inputBuscarUsuarioApoyo');
        const resultadosDiv = document.getElementById('resultadosBusquedaApoyo');
        const hiddenInput = document.getElementById('hiddenUsuarioApoyo');
        
        inputBuscar.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            
            if (query.length === 0) {
                resultadosDiv.style.display = 'none';
                resultadosDiv.innerHTML = '';
                return;
            }
            
            // Filtrar usuarios
            const usuariosFiltrados = usuariosDisponibles.filter(u => 
                u.nombre.toLowerCase().includes(query) ||
                u.turno.toLowerCase().includes(query)
            );
            
            if (usuariosFiltrados.length === 0) {
                resultadosDiv.innerHTML = '<div class="list-group-item text-muted">No se encontraron usuarios</div>';
                resultadosDiv.style.display = 'block';
                return;
            }
            
            // Mostrar resultados
            resultadosDiv.innerHTML = usuariosFiltrados.map(u => {
                const colorTurno = u.turno.toLowerCase().includes('mañana') ? 'warning' : 
                                   u.turno.toLowerCase().includes('tarde') ? 'info' : 'secondary';
                return `
                    <a href="#" class="list-group-item list-group-item-action usuario-item-apoyo" 
                       data-id="${u.idusuario}"
                       data-nombre="${u.nombre}"
                       data-turno="${u.turno}"
                       data-leads="${u.leads_activos}"
                       data-tareas="${u.tareas_pendientes}">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-2">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                                     style="width: 32px; height: 32px; font-size: 14px; font-weight: bold;">
                                    ${u.nombre.charAt(0).toUpperCase()}
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">${u.nombre}</div>
                                <small class="text-muted">
                                    <span class="badge bg-${colorTurno}">${u.turno}</span>
                                    <span class="ms-2">📊 ${u.leads_activos} leads</span>
                                    <span class="ms-2">✅ ${u.tareas_pendientes} tareas</span>
                                </small>
                            </div>
                        </div>
                    </a>
                `;
            }).join('');
            
            resultadosDiv.style.display = 'block';
            
            // Event listeners para seleccionar usuario
            resultadosDiv.querySelectorAll('.usuario-item-apoyo').forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const id = this.dataset.id;
                    const nombre = this.dataset.nombre;
                    const turno = this.dataset.turno;
                    const leads = this.dataset.leads;
                    const tareas = this.dataset.tareas;
                    
                    // Actualizar input y hidden
                    inputBuscar.value = nombre;
                    hiddenInput.value = id;
                    
                    // Mostrar info del usuario
                    document.getElementById('nombreUsuarioApoyo').textContent = nombre;
                    document.getElementById('turnoUsuarioApoyo').textContent = turno;
                    document.getElementById('leadsUsuarioApoyo').textContent = leads;
                    document.getElementById('tareasUsuarioApoyo').textContent = tareas;
                    document.getElementById('infoUsuarioApoyo').style.display = 'block';
                    
                    // Ocultar resultados
                    resultadosDiv.style.display = 'none';
                });
            });
        });
        
        // Ocultar resultados al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (!inputBuscar.contains(e.target) && !resultadosDiv.contains(e.target)) {
                resultadosDiv.style.display = 'none';
            }
        });
    }, 100);
}

/**
 * Ejecutar solicitud de apoyo
 */
window.ejecutarSolicitarApoyo = async function() {
    const form = document.getElementById('formSolicitarApoyo');
    const formData = new FormData(form);

    try {
        const response = await fetch(`${baseUrl}/lead-asignacion/solicitarApoyo`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Solicitud enviada',
                text: 'El usuario recibirá tu solicitud de apoyo',
                timer: 2000
            });
            $('#modalSolicitarApoyo').modal('hide');
        } else {
            Swal.fire('Error', data.message, 'error');
        }

    } catch (error) {
        Swal.fire('Error', 'No se pudo enviar la solicitud', 'error');
    }
}

/**
 * Mostrar modal de programar seguimiento
 */
window.mostrarModalProgramarSeguimiento = function(idlead) {
    const html = `
        <div class="modal fade" id="modalProgramarSeguimiento" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">
                            <i class="mdi mdi-calendar-clock"></i> Programar Seguimiento
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="formProgramarSeguimiento">
                            <input type="hidden" name="idlead" value="${idlead}">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Fecha:</label>
                                    <input type="date" name="fecha" id="fechaSeguimiento" class="form-control" required
                                        min="${new Date().toISOString().split('T')[0]}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Hora:</label>
                                    <div class="row">
                                        <div class="col-5">
                                            <select name="hora" id="horaSeguimiento" class="form-select" required>
                                                <option value="">HH</option>
                                                ${[8,9,10,11,12,1,2,3,4,5,6,7,8].map(h => `<option value="${h}">${h.toString().padStart(2,'0')}</option>`).join('')}
                                            </select>
                                        </div>
                                        <div class="col-4">
                                            <select name="minutos" id="minutosSeguimiento" class="form-select" required>
                                                <option value="00">00</option>
                                                <option value="15">15</option>
                                                <option value="30">30</option>
                                                <option value="45">45</option>
                                            </select>
                                        </div>
                                        <div class="col-3">
                                            <select name="periodo" id="periodoSeguimiento" class="form-select" required>
                                                <option value="AM">AM</option>
                                                <option value="PM" selected>PM</option>
                                            </select>
                                        </div>
                                    </div>
                                    <small class="text-muted">Horario laboral: 8:00 AM - 8:00 PM</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tipo de seguimiento:</label>
                                <select name="tipo" class="form-select" required>
                                    <option value="Llamada">📞 Llamada telefónica</option>
                                    <option value="WhatsApp">💬 WhatsApp</option>
                                    <option value="Visita">🏠 Visita presencial</option>
                                    <option value="Email">📧 Email</option>
                                    <option value="Seguimiento">📋 Seguimiento general</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Notas:</label>
                                <textarea name="nota" class="form-control" rows="3" required
                                    placeholder="Ej: Llamar para confirmar interés en plan 100 Mbps"></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Recordatorio:</label>
                                <select name="recordatorio" class="form-select">
                                    <option value="">Sin recordatorio</option>
                                    <option value="15">15 minutos antes</option>
                                    <option value="30">30 minutos antes</option>
                                    <option value="60">1 hora antes</option>
                                    <option value="1440">1 día antes</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-success" onclick="ejecutarProgramarSeguimiento()">
                            <i class="mdi mdi-check"></i> Programar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Remover modal anterior si existe
    const modalExistente = document.getElementById('modalProgramarSeguimiento');
    if (modalExistente) {
        $('#modalProgramarSeguimiento').modal('hide');
        modalExistente.remove();
    }
    
    // Limpiar todos los backdrops residuales
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open').css('padding-right', '');
    
    document.body.insertAdjacentHTML('beforeend', html);
    
    // Esperar un momento para que la limpieza se complete
    setTimeout(function() {
        $('#modalProgramarSeguimiento').modal('show');
    }, 100);
}

/**
 * Ejecutar programación de seguimiento
 */
window.ejecutarProgramarSeguimiento = async function() {
    const form = document.getElementById('formProgramarSeguimiento');
    
    // Obtener valores
    const hora = parseInt(document.getElementById('horaSeguimiento').value);
    const minutos = document.getElementById('minutosSeguimiento').value;
    const periodo = document.getElementById('periodoSeguimiento').value;
    
    // Validar que se hayan seleccionado todos los campos
    if (!hora || !minutos || !periodo) {
        Swal.fire('Error', 'Por favor selecciona la hora completa (hora, minutos y AM/PM)', 'error');
        return;
    }
    
    // Convertir a formato 24 horas
    let hora24 = hora;
    if (periodo === 'PM' && hora !== 12) {
        hora24 = hora + 12;
    } else if (periodo === 'AM' && hora === 12) {
        hora24 = 0;
    }
    
    // Validar horario laboral (8 AM - 8 PM = 8:00 - 20:00)
    if (hora24 < 8 || hora24 > 20 || (hora24 === 20 && minutos !== '00')) {
        Swal.fire({
            icon: 'warning',
            title: 'Horario no laboral',
            text: 'Por favor selecciona un horario entre 8:00 AM y 8:00 PM',
            confirmButtonText: 'Entendido'
        });
        return;
    }
    
    // Crear FormData con la hora en formato 24 horas
    const formData = new FormData(form);
    const horaCompleta = `${hora24.toString().padStart(2, '0')}:${minutos}`;
    formData.set('hora', horaCompleta);
    formData.delete('minutos');
    formData.delete('periodo');

    try {
        const response = await fetch(`${baseUrl}/lead-asignacion/programarSeguimiento`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Seguimiento programado',
                text: `Programado para ${horaCompleta} (${hora}:${minutos} ${periodo})`,
                timer: 2500
            });
            $('#modalProgramarSeguimiento').modal('hide');
        } else {
            Swal.fire('Error', data.message, 'error');
        }

    } catch (error) {
        Swal.fire('Error', 'No se pudo programar el seguimiento', 'error');
    }
}
