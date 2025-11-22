<?= $this->extend('Layouts/base') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/leads/leads-view.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
// Inicializa variables para evitar error 500
$lead = $lead ?? [];
$error = $error ?? null;
$seguimientos = $seguimientos ?? [];
$tareas = $tareas ?? [];
$campania = $campania ?? [];
$persona = $persona ?? [];
$etapas = $etapas ?? [];
$modalidades = $modalidades ?? [];
$historial = $historial ?? [];
?>

<?php
// Coordenadas unificadas: usar primero las del lead, si no existen usar las de servicio
$coordenadasLead = $lead['coordenadas'] ?? '';
if (empty($coordenadasLead) && !empty($lead['coordenadas_servicio'] ?? '')) {
    $coordenadasLead = $lead['coordenadas_servicio'];
}
?>

<div class="row lead-view-page" 
     data-lead-id="<?= $lead['idlead'] ?? '' ?>"
     data-lead-nombre="<?= esc(($lead['nombres'] ?? '') . ' ' . ($lead['apellidos'] ?? '')) ?>"
     data-lead-telefono="<?= esc($lead['telefono'] ?? '') ?>"
     data-lead-direccion="<?= esc($lead['direccion'] ?? 'Sin dirección') ?>"
     data-coordenadas="<?= esc($coordenadasLead) ?>"
     <?php if (!empty($zona)): ?>
     data-zona-poligono='<?= $zona['poligono'] ?? '' ?>'
     data-zona-color="<?= $zona['color'] ?? '' ?>"
     data-zona-nombre="<?= esc($zona['nombre_zona'] ?? '') ?>"
     <?php endif; ?>>
    <div class="col-12">
        <!-- Encabezado con acciones -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="<?= base_url('leads') ?>" class="btn btn-outline-secondary">
                    <i class="icon-arrow-left"></i> Volver a Leads
                </a>
            </div>
            <div>
                <a href="<?= base_url('leads/edit/' . $lead['idlead']) ?>" class="btn btn-warning">
                    <i class="icon-pencil"></i> Editar
                </a>
                <?php if ($lead['estado'] == 'activo'): ?>
                <a href="<?= base_url('leads/convertirACliente/' . $lead['idlead']) ?>" class="btn btn-success">
                    <i class="icon-check"></i> Convertir a Cliente
                </a>
                <?php endif; ?>
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalDescartar">
                    <i class="icon-close"></i> Descartar
                </button>
                
                <!-- Separador -->
                <span class="mx-2">|</span>
                
                <!-- Botones de Asignación y Comunicación -->
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-primary btn-sm btn-reasignar-lead" data-idlead="<?= $lead['idlead'] ?>">
                        <i class="ti-reload"></i> Reasignar
                    </button>
                    <button type="button" class="btn btn-warning btn-sm btn-solicitar-apoyo" data-idlead="<?= $lead['idlead'] ?>">
                        <i class="ti-help-alt"></i> Solicitar Apoyo
                    </button>
                    <button type="button" class="btn btn-success btn-sm btn-programar-seguimiento" data-idlead="<?= $lead['idlead'] ?>">
                        <i class="ti-alarm-clock"></i> Programar
                    </button>
                </div>
            </div>
        </div>

        <!-- Información del Lead -->
        <div class="row">
            <!-- Columna Izquierda: Información Principal -->
            <div class="col-md-8">
                <!-- Datos del Cliente -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <?php 
                            $nombreCompleto = ($lead['nombres'] ?? '') . ' ' . ($lead['apellidos'] ?? '');
                            $iniciales = strtoupper(substr($lead['nombres'] ?? 'L', 0, 1) . substr($lead['apellidos'] ?? 'L', 0, 1));
                            ?>
                            <div class="avatar-lg bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mr-3" style="width:80px;height:80px;">
                                <h2 class="mb-0"><?= $iniciales ?></h2>
                            </div>
                            <div>
                                <h3 class="mb-1"><?= esc($nombreCompleto) ?></h3>
                                <p class="text-muted mb-0">DNI: <?= esc($lead['dni'] ?? 'Sin DNI') ?></p>
                                <span class="badge badge-<?= ($lead['estado'] ?? '') == 'Convertido' ? 'success' : (($lead['estado'] ?? '') == 'Descartado' ? 'danger' : 'info') ?>">
                                    <?= $lead['estado'] ?? 'Activo' ?>
                                </span>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-6">
                                <p><strong><i class="icon-phone mr-2"></i>Teléfono:</strong><br>
                                <?= esc($lead['telefono']) ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong><i class="icon-envelope mr-2"></i>Correo:</strong><br>
                                <?= esc($lead['correo'] ?? 'No registrado') ?></p>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <p><strong><i class="icon-location-pin mr-2"></i>Dirección:</strong><br>
                                <?= esc($lead['direccion'] ?? 'No registrado') ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong><i class="icon-map mr-2"></i>Distrito:</strong><br>
                                <?= esc($lead['distrito_nombre'] ?? 'No registrado') ?></p>
                            </div>
                        </div>

                        <?php if (!empty($lead['referencias'])): ?>
                        <p><strong><i class="icon-info mr-2"></i>Referencias:</strong><br>
                        <?= esc($lead['referencias']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Información del Lead -->
                <?php
                // Texto de ayuda para las etapas, pensado para usuarios de oficina
                $etapaActualNombre = $lead['etapa_nombre'] ?? '';
                $etapaClave = strtoupper($etapaActualNombre);
                $ayudasEtapas = [
                    'CAPTACION'   => 'Cliente recién contactado. Aún se está tomando datos y validando interés.',
                    'INTERES'     => 'Cliente interesado que pidió más información o está evaluando el servicio.',
                    'COTIZACION'  => 'Cliente con propuesta enviada. Pendiente de respuesta o ajustes.',
                    'NEGOCIACION' => 'Se está conversando condiciones finales (precio, fecha, instalación).',
                    'INSTALADO'   => 'Servicio ya instalado. Lead convertido en cliente.',
                ];
                $textoAyudaEtapa = $ayudasEtapas[$etapaClave] ?? '';
                ?>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Información del Lead</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="text-muted">Etapa Actual</label>
                                <h6><?= esc($etapaActualNombre ?: 'Sin etapa') ?></h6>
                                <?php if ($textoAyudaEtapa): ?>
                                    <small class="text-muted d-block"><?= esc($textoAyudaEtapa) ?></small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted">Origen</label>
                                <h6><?= esc($lead['origen_nombre'] ?? 'Sin origen') ?></h6>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted">Campaña</label>
                                <h6><?= esc($lead['campania_nombre'] ?? 'Sin campaña') ?></h6>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted">Vendedor Asignado</label>
                                <h6><?= esc($lead['vendedor_asignado'] ?? 'Sin asignar') ?></h6>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted">Plan de Interés </label>
                                <h6>
                                    <?php if (!empty($lead['plan_interes'])): ?>
                                        <span class="badge badge-primary" style="font-size: 14px;">
                                            <?= esc($lead['plan_interes']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">No especificado</span>
                                    <?php endif; ?>
                                </h6>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted">Fecha de Registro</label>
                                <?php
                                // Mostrar fecha en formato d/m/Y H:i. La zona horaria global debe estar
                                // configurada en America/Lima en app/Config/App.php para reflejar hora peruana.
                                $fechaRegistro = null;
                                if (!empty($lead['created_at'])) {
                                    try {
                                        $dt = new \DateTime($lead['created_at']);
                                        $fechaRegistro = $dt->format('d/m/Y H:i');
                                    } catch (\Exception $e) {
                                        $fechaRegistro = $lead['created_at'];
                                    }
                                }
                                ?>
                                <h6><?= $fechaRegistro ? esc($fechaRegistro) : 'No disponible' ?></h6>
                            </div>
                            <?php if (!empty($lead['nota_inicial'])): ?>
                            <div class="col-12 mb-3">
                                <label class="text-muted">Detalles</label>
                                <p class="mb-0"><?= nl2br(esc($lead['nota_inicial'])) ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Ubicación en Mapa -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"> Ubicación en Mapa</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($coordenadasLead)): ?>
                            <p class="text-muted mb-2">
                                <small>
                                    <strong>Coordenadas:</strong>
                                    <?= esc($coordenadasLead) ?>
                                </small>
                            </p>
                            <div id="miniMapLead" style="height: 350px; width: 100%; border-radius: 8px;"></div>
                            
                            <?php if (!empty($zona)): ?>
                            <div class="alert alert-info mt-3 mb-0">
                                <div class="d-flex align-items-center">
                                    <i class="icon-map-pin mr-2" style="font-size: 1.5rem;"></i>
                                    <div>
                                        <strong>Zona asignada:</strong> <?= esc($zona['nombre_zona']) ?>
                                        <br>
                                        <small class="text-muted">Este lead está dentro de una zona de campaña activa</small>
                                        <br>
                                        <a href="<?= base_url('crm-campanas/zona-detalle/' . $zona['id_zona']) ?>" class="btn btn-sm btn-primary mt-2">
                                            <i class="icon-eye"></i> Ver Zona Completa
                                        </a>
                                        <a href="<?= base_url('crm-campanas/mapa-campanas') ?>?lead=<?= $lead['idlead'] ?>" class="btn btn-sm btn-outline-primary mt-2">
                                            <i class="icon-map"></i> Ver en Mapa General
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-warning mt-3 mb-0">
                                <i class="icon-alert-triangle"></i> 
                                <strong>Sin zona asignada</strong>
                                <br>
                                <small>Este lead no está asignado a ninguna zona de campaña.</small>
                            </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-warning mb-0">
                                <div class="d-flex align-items-start">
                                    <i class="icon-alert-triangle mr-2" style="font-size: 1.5rem;"></i>
                                    <div>
                                        <strong>Este lead no tiene coordenadas</strong>
                                        <br>
                                        <small class="text-muted">
                                            Para ver la ubicación en el mapa, necesitas agregar una dirección y geocodificarla.
                                        </small>
                                        <br>
                                        <button class="btn btn-sm btn-primary mt-2" onclick="geocodificarLeadAhora()">
                                            <i class="icon-map-pin"></i> Geocodificar Ahora
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Documentos del Lead -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"> Documentos</h5>
                        <?php if (!empty($resumen_documentos)): ?>
                        <div>
                            <span class="badge badge-secondary mr-1">Total: <?= (int)($resumen_documentos['total'] ?? 0) ?></span>
                            <span class="badge badge-success mr-1">Verificados: <?= (int)($resumen_documentos['verificados'] ?? 0) ?></span>
                            <span class="badge badge-warning">Pendientes: <?= (int)($resumen_documentos['pendientes'] ?? 0) ?></span>
                            <?php if (!empty($resumen_documentos['completo'])): ?>
                                <span class="badge badge-primary ml-2">Completo</span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <!-- Formulario subir documento -->
                        <form action="<?= base_url('leads/subirDocumento/' . ($lead['idlead'] ?? 0)) ?>" method="post" enctype="multipart/form-data" class="mb-3">
                            <?= csrf_field() ?>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label>Tipo de documento</label>
                                    <select name="tipo_documento" class="form-control" required>
                                        <option value="dni_frontal">DNI - Frontal</option>
                                        <option value="dni_reverso">DNI - Reverso</option>
                                        <option value="recibo_luz">Recibo de Luz</option>
                                        <option value="recibo_agua">Recibo de Agua</option>
                                        <option value="foto_domicilio">Foto de Domicilio</option>
                                        <option value="otro">Otro</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Archivo</label>
                                    <input type="file" name="archivo" class="form-control" accept="image/*,.pdf" required>
                                    <small class="text-muted">Formatos: JPG, PNG o PDF. Máx 3MB.</small>
                                </div>
                                <div class="form-group col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary btn-block">
                                        <i class="icon-cloud-upload"></i> Subir
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- Lista de documentos -->
                        <?php if (!empty($documentos)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Tipo</th>
                                            <th>Nombre</th>
                                            <th>Tamaño</th>
                                            <th>Estado</th>
                                            <th>Fecha</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($documentos as $doc): ?>
                                        <tr>
                                            <td><?= esc($doc['tipo_documento']) ?></td>
                                            <td><?= esc($doc['nombre_archivo']) ?></td>
                                            <td><?= isset($doc['tamano_kb']) ? (int)$doc['tamano_kb'] . ' KB' : '-' ?></td>
                                            <td>
                                                <?php if (!empty($doc['verificado'])): ?>
                                                    <span class="badge badge-success">Verificado</span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">Pendiente</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= isset($doc['created_at']) ? date('d/m/Y H:i', strtotime($doc['created_at'])) : '-' ?></td>
                                            <td>
                                                <?php if (!empty($doc['ruta_archivo'])): ?>
                                                    <a class="btn btn-sm btn-outline-primary" target="_blank" href="<?= base_url($doc['ruta_archivo']) ?>">
                                                        <i class="icon-eye"></i> Ver
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-0">Aún no se han subido documentos.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Seguimientos -->
                <?php
                // Indicador simple de "Último seguimiento" para que se vea urgencia
                $textoUltimoSeg = 'Sin seguimientos';
                if (!empty($seguimientos) && isset($seguimientos[0]['fecha'])) {
                    try {
                        $fechaUltimo = new \DateTime($seguimientos[0]['fecha']);
                        $hoy = new \DateTime();
                        $diff = $hoy->diff($fechaUltimo);
                        $dias = (int) $diff->days;
                        if ($dias === 0) {
                            $textoUltimoSeg = 'Hoy';
                        } elseif ($dias === 1) {
                            $textoUltimoSeg = 'Hace 1 día';
                        } else {
                            $textoUltimoSeg = 'Hace ' . $dias . ' días';
                        }
                    } catch (\Exception $e) {
                        $textoUltimoSeg = 'Fecha no disponible';
                    }
                }
                ?>

                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Seguimientos</h5>
                            <small class="text-muted">Último seguimiento: <?= esc($textoUltimoSeg) ?></small>
                        </div>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalSeguimiento">
                            <i class="icon-plus"></i> Agregar
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($seguimientos)): ?>
                            <div class="timeline">
                                <?php foreach ($seguimientos as $seg): ?>
                                <div class="timeline-item mb-4">
                                    <div class="timeline-marker bg-primary"></div>
                                    <div class="timeline-content">
                                        <div class="d-flex justify-content-between">
                                            <strong><?= esc($seg['usuario_nombre']) ?></strong>
                                            <small class="text-muted"><?= date('d/m/Y H:i', strtotime($seg['fecha'])) ?></small>
                                        </div>
                                        <span class="badge badge-info"><?= esc($seg['modalidad'] ?? $seg['modalidad_nombre'] ?? 'Sin modalidad') ?></span>
                                        <p class="mt-2 mb-0"><?= nl2br(esc($seg['nota'])) ?></p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-muted">No hay seguimientos registrados</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Comentarios Internos -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center bg-light">
                        <h5 class="mb-0">💬 Comentarios Internos</h5>
                        <span class="badge badge-secondary" id="contadorComentarios">0</span>
                    </div>
                    <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                        <!-- Lista de comentarios -->
                        <div id="listaComentarios">
                            <div class="text-center text-muted py-3">
                                <i class="icon-speech" style="font-size: 2rem;"></i>
                                <p>Cargando comentarios...</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <!-- Formulario para nuevo comentario -->
                        <form id="formComentario">
                            <input type="hidden" name="idlead" value="<?= $lead['idlead'] ?>">
                            <div class="form-group mb-2">
                                <textarea class="form-control" name="comentario" id="nuevoComentario" rows="2" placeholder="Escribe un comentario..." required></textarea>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <input type="radio" class="btn-check" name="tipo" id="tipoNota" value="nota_interna" checked>
                                    <label class="btn btn-outline-secondary" for="tipoNota">
                                        <i class="icon-note"></i> Nota
                                    </label>
                                    
                                    <input type="radio" class="btn-check" name="tipo" id="tipoApoyo" value="solicitud_apoyo">
                                    <label class="btn btn-outline-warning" for="tipoApoyo">
                                        <i class="icon-bell"></i> Solicitar Apoyo
                                    </label>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="icon-paper-plane"></i> Enviar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Historial de Cambios de Etapa -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">📊 Historial de Cambios de Etapa</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($historial)): ?>
                            <div class="timeline">
                                <?php foreach ($historial as $h): ?>
                                <div class="timeline-item mb-3">
                                    <div class="timeline-marker bg-info"></div>
                                    <div class="timeline-content">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <strong><?= esc($h['usuario_nombre'] ?? 'Sistema') ?></strong>
                                                <br>
                                                <?php if (!empty($h['etapa_anterior_nombre'])): ?>
                                                    <span class="badge" style="background-color: <?= $h['etapa_anterior_color'] ?? '#6c757d' ?>">
                                                        <?= esc($h['etapa_anterior_nombre']) ?>
                                                    </span>
                                                    <i class="icon-arrow-right mx-1"></i>
                                                <?php endif; ?>
                                                <span class="badge" style="background-color: <?= $h['etapa_nueva_color'] ?? '#28a745' ?>">
                                                    <?= esc($h['etapa_nueva_nombre']) ?>
                                                </span>
                                                <?php if (!empty($h['motivo'])): ?>
                                                    <br>
                                                    <small class="text-muted"><?= esc($h['motivo']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted"><?= date('d/m/Y H:i', strtotime($h['fecha'])) ?></small>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-muted">No hay cambios de etapa registrados</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Acciones Rápidas -->
            <div class="col-md-4">
                <!-- Cambiar Etapa -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Cambiar Etapa</h5>
                    </div>
                    <div class="card-body">
                        <form id="formCambiarEtapa">
                            <div class="form-group">
                                <select class="form-control" id="nueva_etapa" name="idetapa">
                                    <?php foreach ($etapas as $etapa): ?>
                                    <option value="<?= $etapa['idetapa'] ?>" <?= $etapa['idetapa'] == $lead['idetapa'] ? 'selected' : '' ?>>
                                        <?= esc($etapa['nombre']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <textarea class="form-control" name="nota" placeholder="Nota (opcional)" rows="2"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Mover Etapa</button>
                        </form>
                    </div>
                </div>

                <!-- Tareas -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Tareas</h5>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalTarea">
                            <i class="icon-plus"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($tareas)): ?>
                            <div class="list-group">
                                <?php foreach ($tareas as $tarea): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <strong><?= esc($tarea['titulo']) ?></strong>
                                        <span class="badge badge-<?= $tarea['prioridad'] == 'alta' ? 'danger' : 'warning' ?>">
                                            <?= esc($tarea['prioridad']) ?>
                                        </span>
                                    </div>
                                    <small class="text-muted">Vence: <?= date('d/m/Y', strtotime($tarea['fecha_vencimiento'])) ?></small>
                                    <?php if ($tarea['estado'] != 'Completada'): ?>
                                    <button class="btn btn-sm btn-success mt-2" onclick="completarTarea(<?= $tarea['idtarea'] ?>)">
                                        Completar
                                    </button>
                                    <?php else: ?>
                                    <span class="badge badge-success">Completada</span>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center">Sin tareas</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Acciones Rápidas -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Acciones Rápidas</h5>
                    </div>
                    <div class="card-body">
                        <a href="tel:<?= $lead['telefono'] ?>" class="btn btn-outline-primary btn-block mb-2">
                            <i class="icon-phone"></i> Llamar
                        </a>
                        <a href="https://wa.me/51<?= $lead['telefono'] ?>" target="_blank" class="btn btn-outline-success btn-block mb-2">
                            <i class="icon-social-whatsapp"></i> WhatsApp
                        </a>
                        <a href="mailto:<?= $lead['correo'] ?>" class="btn btn-outline-info btn-block">
                            <i class="icon-envelope"></i> Enviar Email
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Agregar Seguimiento -->
<div class="modal fade" id="modalSeguimiento" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Agregar Seguimiento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formSeguimiento">
                <div class="modal-body">
                    <input type="hidden" name="idlead" value="<?= $lead['idlead'] ?>">
                    <div class="form-group">
                        <label>Tipo de Comunicación</label>
                        <select class="form-control" name="idmodalidad" required>
                            <?php foreach ($modalidades as $mod): ?>
                            <option value="<?= $mod['idmodalidad'] ?>"><?= esc($mod['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nota</label>
                        <textarea class="form-control" name="nota" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Crear Tarea -->
<div class="modal fade" id="modalTarea" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nueva Tarea</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formTarea">
                <div class="modal-body">
                    <input type="hidden" name="idlead" value="<?= $lead['idlead'] ?>">
                    <div class="form-group">
                        <label>Título</label>
                        <input type="text" class="form-control" name="titulo" required>
                    </div>
                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea class="form-control" name="descripcion" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Prioridad</label>
                                <select class="form-control" name="prioridad">
                                    <option value="baja">Baja</option>
                                    <option value="media" selected>Media</option>
                                    <option value="alta">Alta</option>
                                    <option value="urgente">Urgente</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Fecha Vencimiento</label>
                                <input type="datetime-local" class="form-control" name="fecha_vencimiento" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Tarea</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Convertir -->
<div class="modal fade" id="modalConvertir" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Convertir a Cliente</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('leads/convertir/' . $lead['idlead']) ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <p>¿Estás seguro de convertir este lead en cliente?</p>
                    <div class="form-group">
                        <label>Número de Contrato (opcional)</label>
                        <input type="text" class="form-control" name="numero_contrato">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Convertir</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Descartar -->
<div class="modal fade" id="modalDescartar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Descartar Lead</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('leads/descartar/' . $lead['idlead']) ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Motivo del Descarte *</label>
                        <textarea class="form-control" name="motivo" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Descartar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

<!-- Google Maps API mediante mapakey.js (misma configuración que otros mapas) -->
<script src="<?= base_url('js/leads/mapakey.js') ?>"></script>

<!-- Scripts de Leads -->
<script src="<?= base_url('js/leads/leads-view.js') ?>"></script>
<script src="<?= base_url('js/leads/asignacion-leads.js') ?>"></script>
<script src="<?= base_url('js/leads/comentarios-lead.js') ?>"></script>

<?= $this->endSection() ?>