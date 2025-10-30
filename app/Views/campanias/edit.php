<?= $this->extend('Layouts/base') ?>

<?= $this->section('content') ?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-1">Editar Campaña</h3>
                <p class="text-muted mb-0">ID: <?= $campania['idcampania'] ?> | Creada: <?= date('d/m/Y', strtotime($campania['fecha_creacion'] ?? 'now')) ?></p>
            </div>
            <a href="<?= base_url('campanias') ?>" class="btn btn-outline-secondary">
                <i class="icon-arrow-left"></i> Volver
            </a>
        </div>

        <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="icon-alert-circle me-2"></i>
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="icon-check-circle me-2"></i>
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form action="<?= base_url('campanias/update/' . $campania['idcampania']) ?>" method="POST" id="formEditarCampania">
                    <?= csrf_field() ?>
                    
                    <!-- Información Básica -->
                    <h5 class="mb-3 text-primary">Información Básica</h5>
                    
                    <div class="form-group mb-3">
                        <label for="nombre" class="form-label">Nombre de la Campaña *</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" 
                               value="<?= old('nombre', esc($campania['nombre'])) ?>" required
                               placeholder="Ej: Campaña Black Friday 2025">
                        <div class="invalid-feedback">Por favor ingrese el nombre de la campaña</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="tipo" class="form-label">Tipo de Campaña *</label>
                                <select class="form-control" id="tipo" name="tipo" required>
                                    <option value="">Seleccione...</option>
                                    <option value="Marketing Digital" <?= old('tipo', $campania['tipo'] ?? '') == 'Marketing Digital' ? 'selected' : '' ?>>Marketing Digital</option>
                                    <option value="Email Marketing" <?= old('tipo', $campania['tipo'] ?? '') == 'Email Marketing' ? 'selected' : '' ?>>Email Marketing</option>
                                    <option value="Publicidad" <?= old('tipo', $campania['tipo'] ?? '') == 'Publicidad' ? 'selected' : '' ?>>Publicidad</option>
                                    <option value="Redes Sociales" <?= old('tipo', $campania['tipo'] ?? '') == 'Redes Sociales' ? 'selected' : '' ?>>Redes Sociales</option>
                                    <option value="Eventos" <?= old('tipo', $campania['tipo'] ?? '') == 'Eventos' ? 'selected' : '' ?>>Eventos</option>
                                    <option value="Telemarketing" <?= old('tipo', $campania['tipo'] ?? '') == 'Telemarketing' ? 'selected' : '' ?>>Telemarketing</option>
                                    <option value="Otro" <?= old('tipo', $campania['tipo'] ?? '') == 'Otro' ? 'selected' : '' ?>>Otro</option>
                                </select>
                                <div class="invalid-feedback">Por favor seleccione el tipo de campaña</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="presupuesto" class="form-label">Presupuesto *</label>
                                <div class="input-group">
                                    <span class="input-group-text">S/</span>
                                    <input type="number" class="form-control" id="presupuesto" name="presupuesto" 
                                           value="<?= old('presupuesto', $campania['presupuesto']) ?>" 
                                           step="0.01" min="0" required placeholder="0.00">
                                    <div class="invalid-feedback">El presupuesto debe ser mayor o igual a 0</div>
                                </div>
                                <?php if (!empty($campania['total_invertido'])): ?>
                                <small class="form-text text-info">
                                    <i class="icon-info"></i> 
                                    Ya invertido: S/ <?= number_format($campania['total_invertido'], 2) ?>
                                </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" 
                                  rows="3" maxlength="500"
                                  placeholder="Describe los objetivos y alcance de la campaña..."><?= old('descripcion', esc($campania['descripcion'])) ?></textarea>
                        <div class="d-flex justify-content-between">
                            <small class="form-text text-muted">Máximo 500 caracteres</small>
                            <small class="form-text text-muted" id="contadorCaracteres">
                                <span id="caracteresActuales"><?= strlen($campania['descripcion'] ?? '') ?></span>/500
                            </small>
                        </div>
                    </div>

                    <!-- Estado y Fechas -->
                    <h5 class="mb-3 mt-4 text-primary">Estado y Período</h5>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label d-block">Estado de la Campaña</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="estado" id="estadoActiva" 
                                           value="Activa" <?= old('estado', $campania['estado'] ?? '') == 'Activa' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-success" for="estadoActiva">
                                        <i class="icon-check-circle"></i> Activa
                                    </label>
                                    
                                    <input type="radio" class="btn-check" name="estado" id="estadoInactiva" 
                                           value="Inactiva" <?= old('estado', $campania['estado'] ?? '') == 'Inactiva' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-warning" for="estadoInactiva">
                                        <i class="icon-pause-circle"></i> Inactiva
                                    </label>
                                    
                                    <input type="radio" class="btn-check" name="estado" id="estadoFinalizada" 
                                           value="Finalizada" <?= old('estado', $campania['estado'] ?? '') == 'Finalizada' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-secondary" for="estadoFinalizada">
                                        <i class="icon-x-circle"></i> Finalizada
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="alert alert-info mb-3">
                                <small>
                                    <i class="icon-info"></i>
                                    <strong>Leads asociados:</strong> <?= $campania['total_leads'] ?? 0 ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="fecha_inicio" class="form-label">Fecha de Inicio *</label>
                                <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" 
                                       value="<?= old('fecha_inicio', $campania['fecha_inicio']) ?>" required>
                                <div class="invalid-feedback">Por favor ingrese la fecha de inicio</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="fecha_fin" class="form-label">Fecha de Fin *</label>
                                <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" 
                                       value="<?= old('fecha_fin', $campania['fecha_fin']) ?>" required>
                                <div class="invalid-feedback" id="errorFechaFin">Por favor ingrese la fecha de fin</div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mt-2" id="duracionCampania" style="display: none;">
                        <i class="icon-calendar"></i> <strong>Duración:</strong> <span id="textoDuracion"></span>
                    </div>

                    <!-- Información adicional -->
                    <?php if (isset($campania['updated_at'])): ?>
                    <div class="alert alert-light mt-4">
                        <small class="text-muted">
                            <i class="icon-clock"></i>
                            <strong>Última modificación:</strong> 
                            <?= date('d/m/Y H:i', strtotime($campania['updated_at'])) ?>
                        </small>
                    </div>
                    <?php endif; ?>

                    <hr class="my-4">

                    <!-- Botones de acción -->
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="<?= base_url('campanias') ?>" class="btn btn-secondary">
                            <i class="icon-x"></i> Cancelar
                        </a>
                        <div>
                            <button type="button" class="btn btn-outline-danger me-2" id="btnEliminar">
                                <i class="icon-trash-2"></i> Eliminar
                            </button>
                            <button type="submit" class="btn btn-primary" id="btnGuardar">
                                <i class="icon-check"></i> Guardar Cambios
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Card de estadísticas -->
        <?php if (($campania['total_leads'] ?? 0) > 0): ?>
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0"><i class="icon-bar-chart-2"></i> Estadísticas de la Campaña</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-4">
                        <div class="p-3">
                            <h3 class="text-primary mb-0"><?= $campania['total_leads'] ?? 0 ?></h3>
                            <small class="text-muted">Total Leads</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3">
                            <h3 class="text-success mb-0">
                                S/ <?= number_format(($campania['presupuesto'] ?? 0) / max(($campania['total_leads'] ?? 1), 1), 2) ?>
                            </h3>
                            <small class="text-muted">Costo por Lead</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3">
                            <h3 class="text-info mb-0">
                                <?= $campania['total_invertido'] ?? 0 > 0 ? number_format((($campania['presupuesto'] ?? 0) / ($campania['total_invertido'] ?? 1)) * 100, 0) : 0 ?>%
                            </h3>
                            <small class="text-muted">Presupuesto Usado</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de confirmación de eliminación -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="icon-alert-triangle me-2"></i>
                    Confirmar Eliminación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">¿Estás seguro de que deseas eliminar esta campaña?</p>
                <div class="alert alert-warning">
                    <i class="icon-alert-circle me-2"></i>
                    <strong>Advertencia:</strong> Esta acción no se puede deshacer. 
                    <?php if (($campania['total_leads'] ?? 0) > 0): ?>
                        Se eliminarán también los <strong><?= $campania['total_leads'] ?> leads</strong> asociados.
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="icon-x"></i> Cancelar
                </button>
                <a href="<?= base_url('campanias/delete/' . $campania['idcampania']) ?>" 
                   class="btn btn-danger" id="btnConfirmarEliminar">
                    <i class="icon-trash-2"></i> Sí, eliminar
                </a>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="<?= base_url('css/campaniasEdit.css') ?>">

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/campaniasJS/campaniasEdit.js') ?>"></script>
<?= $this->endSection() ?>