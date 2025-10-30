<?= $this->extend('Layouts/base') ?>

<?= $this->section('content') ?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0">Nueva Campaña</h3>
            <a href="<?= base_url('campanias') ?>" class="btn btn-outline-secondary">
                <i class="icon-arrow-left"></i> Volver
            </a>
        </div>

        <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form action="<?= base_url('campanias/store') ?>" method="POST" id="formCampania">
                    <?= csrf_field() ?>
                    
                    <!-- Información Básica -->
                    <h5 class="mb-3 text-primary">Información Básica</h5>
                    
                    <div class="form-group mb-3">
                        <label for="nombre" class="form-label">Nombre de la Campaña *</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" 
                               value="<?= old('nombre') ?>" required
                               placeholder="Ej: Campaña Black Friday 2025">
                        <div class="invalid-feedback">Por favor ingrese el nombre de la campaña</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="tipo" class="form-label">Tipo de Campaña *</label>
                                <select class="form-control" id="tipo" name="tipo" required>
                                    <option value="">Seleccione...</option>
                                    <option value="Marketing Digital" <?= old('tipo') == 'Marketing Digital' ? 'selected' : '' ?>>Marketing Digital</option>
                                    <option value="Email Marketing" <?= old('tipo') == 'Email Marketing' ? 'selected' : '' ?>>Email Marketing</option>
                                    <option value="Publicidad" <?= old('tipo') == 'Publicidad' ? 'selected' : '' ?>>Publicidad</option>
                                    <option value="Redes Sociales" <?= old('tipo') == 'Redes Sociales' ? 'selected' : '' ?>>Redes Sociales</option>
                                    <option value="Eventos" <?= old('tipo') == 'Eventos' ? 'selected' : '' ?>>Eventos</option>
                                    <option value="Telemarketing" <?= old('tipo') == 'Telemarketing' ? 'selected' : '' ?>>Telemarketing</option>
                                    <option value="Otro" <?= old('tipo') == 'Otro' ? 'selected' : '' ?>>Otro</option>
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
                                           value="<?= old('presupuesto', '0.00') ?>" step="0.01" min="0" required
                                           placeholder="0.00">
                                    <div class="invalid-feedback">El presupuesto debe ser mayor o igual a 0</div>
                                </div>
                                <small class="form-text text-muted">Ingrese el presupuesto total asignado</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" 
                                  rows="3" placeholder="Describe los objetivos y alcance de la campaña..."><?= old('descripcion') ?></textarea>
                        <small class="form-text text-muted">Máximo 500 caracteres</small>
                    </div>

                    <!-- Fechas -->
                    <h5 class="mb-3 mt-4 text-primary">Período de la Campaña</h5>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="fecha_inicio" class="form-label">Fecha de Inicio *</label>
                                <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" 
                                       value="<?= old('fecha_inicio', date('Y-m-d')) ?>" required>
                                <div class="invalid-feedback">Por favor ingrese la fecha de inicio</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="fecha_fin" class="form-label">Fecha de Fin *</label>
                                <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" 
                                       value="<?= old('fecha_fin') ?>" required>
                                <div class="invalid-feedback" id="errorFechaFin">Por favor ingrese la fecha de fin</div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mt-3" id="duracionCampania" style="display: none;">
                        <i class="icon-info-circle"></i> <strong>Duración:</strong> <span id="textoDuracion"></span>
                    </div>

                    <hr class="my-4">

                    <!-- Botones -->
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="<?= base_url('campanias') ?>" class="btn btn-secondary">
                            <i class="icon-x"></i> Cancelar
                        </a>
                        <div>
                            <button type="reset" class="btn btn-outline-secondary me-2">
                                <i class="icon-refresh-cw"></i> Limpiar
                            </button>
                            <button type="submit" class="btn btn-primary" id="btnGuardar">
                                <i class="icon-check"></i> Crear Campaña
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/campaniasJS/campaniasCreate.js') ?>"></script>
<?= $this->endSection() ?>