<?= $this->extend('layouts/base') ?>

<?= $this->section('styles') ?>
<!-- FullCalendar CSS -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css' rel='stylesheet' />
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<!-- Calendario CSS (incluye estilos corporativos) -->
<link rel="stylesheet" href="<?= base_url('css/tareas/calendario.css?v=' . time()) ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="container-fluid">
    <!-- Header mejorado -->
    <div class="calendar-header">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h2 class="mb-2">Calendario de Tareas y Reuniones</h2>
                <p class="mb-0 opacity-75">Gestiona tus tareas, seguimientos y reuniones de equipo</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('tareas') ?>" class="btn btn-light btn-sm">
                    <i class="ti-list"></i> Vista Lista
                </a>
                <button class="btn btn-primary btn-sm" id="btnNuevaTarea">
                    <i class="ti-plus"></i> Nueva Tarea
                </button>
                <button class="btn btn-primary btn-sm" id="btnNuevaReunion">
                    <i class="ti-user"></i> Nueva Reunión
                </button>
            </div>
        </div>
        
        <!-- Estadísticas rápidas -->
        <div class="calendar-stats">
            <div class="stat-card">
                <h4 id="stat-hoy">0</h4>
                <p>Tareas Hoy</p>
            </div>
            <div class="stat-card">
                <h4 id="stat-semana">0</h4>
                <p>Esta Semana</p>
            </div>
            <div class="stat-card">
                <h4 id="stat-pendientes">0</h4>
                <p>Pendientes</p>
            </div>
            <div class="stat-card">
                <h4 id="stat-importantes">0</h4>
                <p>Importantes</p>
            </div>
        </div>
    </div>

    <!-- Leyenda de colores mejorada -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-2">
                    <div class="d-flex flex-wrap gap-3 align-items-center">
                        <strong class="me-2">Prioridad:</strong>
                        <span class="badge" style="background-color: #dc3545;">⭐ Urgente</span>
                        <span class="badge" style="background-color: #fd7e14;">Alta</span>
                        <span class="badge" style="background-color: #007bff;">Media</span>
                        <span class="badge" style="background-color: #6c757d;">Baja</span>
                        <span class="badge" style="background-color: #28a745;">✓ Completada</span>
                        <span class="ms-3">Reunión</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendario -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para crear/editar tarea -->
<div class="modal fade" id="modalTarea" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title" id="modalTareaTitle">
                    <i class="ti-calendar"></i> Nueva Tarea
                </h5>
                <button type="button" class="btn-close" id="btnCerrarModalTarea" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formTarea">
                    <input type="hidden" id="idtarea" name="idtarea">
                    
                    <div class="mb-3">
                        <label for="titulo" class="form-label">Título *</label>
                        <input type="text" class="form-control" id="titulo" name="titulo" required>
                    </div>

                    <!-- Tipo de evento -->
                    <div class="mb-3">
                        <label class="form-label">Tipo *</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="es_reunion" id="tipo_tarea_radio" value="0" checked>
                            <label class="btn btn-outline-primary" for="tipo_tarea_radio">
                                <i class="ti-calendar"></i> Tarea
                            </label>
                            
                            <input type="radio" class="btn-check" name="es_reunion" id="tipo_reunion_radio" value="1">
                            <label class="btn btn-outline-primary" for="tipo_reunion_radio">
                                <i class="ti-users"></i> Reunión
                            </label>
                        </div>
                        <small class="text-muted d-block mt-1">
                            Las reuniones se asignarán como tarea a cada participante y aparecerán también en sus notificaciones.
                        </small>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="tipo_tarea" class="form-label">Categoría *</label>
                            <select class="form-select" id="tipo_tarea" name="tipo_tarea" required>
                                <option value="llamada">Llamada</option>
                                <option value="reunion">Reunión</option>
                                <option value="seguimiento">Seguimiento</option>
                                <option value="instalacion">Instalación</option>
                                <option value="visita">Visita</option>
                                <option value="email">Email</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="prioridad" class="form-label">Prioridad *</label>
                            <select class="form-select" id="prioridad" name="prioridad" required>
                                <option value="baja">Baja</option>
                                <option value="media" selected>Media</option>
                                <option value="alta">Alta</option>
                                <option value="urgente">⭐ Urgente</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="fecha_vencimiento" class="form-label">Fecha y Hora *</label>
                            <input type="datetime-local" class="form-control" id="fecha_vencimiento" name="fecha_vencimiento" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="idlead" class="form-label">Lead Asociado</label>
                            <select class="form-select" id="idlead" name="idlead">
                                <option value="">Sin lead</option>
                                <?php foreach ($leads as $lead): ?>
                                    <option value="<?= $lead['idlead'] ?>">
                                        <?= esc($lead['cliente'] ?? 'Lead #' . $lead['idlead']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Sección de participantes (solo para reuniones) -->
                    <div id="seccion-participantes" class="participantes-section" style="display: none;">
                        <label class="form-label">
                            <i class="ti-users"></i> Participantes de la Reunión
                        </label>
                        <select class="form-select" id="participantes" name="participantes[]" multiple>
                            <?php if (isset($usuarios)): ?>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <option value="<?= $usuario['idusuario'] ?>">
                                        <?= esc($usuario['nombre']) ?> - <?= esc($usuario['email']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <small class="text-muted">Selecciona los usuarios que participarán en la reunión</small>
                    </div>

                    <!-- Enlace de reunión -->
                    <div id="seccion-enlace" class="mb-3" style="display: none;">
                        <label for="enlace_reunion" class="form-label">
                            <i class="ti-link"></i> Enlace de Videollamada
                        </label>
                        <input type="url" class="form-control" id="enlace_reunion" name="enlace_reunion" 
                               placeholder="https://zoom.us/j/... o https://meet.google.com/...">
                        <small class="text-muted">Zoom, Google Meet, Teams, etc.</small>
                    </div>

                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3" 
                                  placeholder="Detalles adicionales..."></textarea>
                    </div>

                    <div class="mb-3" id="estadoContainer" style="display: none;">
                        <label for="estado" class="form-label">Estado</label>
                        <select class="form-select" id="estado" name="estado">
                            <option value="pendiente">Pendiente</option>
                            <option value="en_proceso">En Proceso</option>
                            <option value="completada">Completada</option>
                            <option value="cancelada">Cancelada</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="btnCancelarTarea" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnEliminarTarea" style="display: none;">
                    <i class="ti-trash"></i> Eliminar
                </button>
                <button type="button" class="btn btn-primary" id="btnGuardarTarea">
                    <i class="ti-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- FullCalendar JS -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales/es.global.min.js'></script>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Calendario JS -->
<script src="<?= base_url('js/tareas/calendario.js?v=' . time()) ?>"></script>

<script>
// Mostrar/ocultar sección de participantes según tipo
document.addEventListener('DOMContentLoaded', function() {
    const tipoTarea = document.getElementById('tipo_tarea_radio');
    const tipoReunion = document.getElementById('tipo_reunion_radio');
    const seccionParticipantes = document.getElementById('seccion-participantes');
    const seccionEnlace = document.getElementById('seccion-enlace');
    const modalTitle = document.getElementById('modalTareaTitle');
    
    function toggleReunion() {
        const esReunion = document.querySelector('input[name="es_reunion"]:checked').value === '1';
        
        if (esReunion) {
            seccionParticipantes.style.display = 'block';
            seccionEnlace.style.display = 'block';
            modalTitle.innerHTML = '<i class="ti-users"></i> Nueva Reunión';
            
            // Inicializar Select2 para participantes
            if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
                $('#participantes').select2({
                    placeholder: 'Selecciona participantes...',
                    allowClear: true,
                    width: '100%'
                });
            }
        } else {
            seccionParticipantes.style.display = 'none';
            seccionEnlace.style.display = 'none';
            modalTitle.innerHTML = '<i class="ti-calendar"></i> Nueva Tarea';
            
            // Destruir Select2 si existe
            if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
                if ($('#participantes').hasClass('select2-hidden-accessible')) {
                    $('#participantes').select2('destroy');
                }
            }
        }
    }
    
    if (tipoTarea && tipoReunion) {
        tipoTarea.addEventListener('change', toggleReunion);
        tipoReunion.addEventListener('change', toggleReunion);
    }
    
    // Botón nueva reunión
    const btnNuevaReunion = document.getElementById('btnNuevaReunion');
    if (btnNuevaReunion) {
        btnNuevaReunion.addEventListener('click', function() {
            // Abrir modal
            const modal = new bootstrap.Modal(document.getElementById('modalTarea'));
            modal.show();
            
            // Seleccionar tipo reunión
            document.getElementById('tipo_reunion_radio').checked = true;
            toggleReunion();
        });
    }
});
</script>
<?= $this->endSection() ?>
