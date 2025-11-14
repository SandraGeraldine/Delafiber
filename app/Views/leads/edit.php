<?= $this->extend('layouts/base') ?>

<?= $this->section('content') ?>

<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title mb-0">Editar Lead</h4>
                    <a href="<?= base_url('leads/view/' . $lead['idlead']) ?>" class="btn btn-light">
                        <i class="icon-arrow-left"></i> Volver
                    </a>
                </div>

                <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= session()->getFlashdata('error') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('errors')): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <ul class="mb-0">
                        <?php foreach (session()->getFlashdata('errors') as $error): ?>
                            <li><?= esc($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <form action="<?= base_url('leads/update/' . $lead['idlead']) ?>" method="POST" id="formEditLead">
                    <?= csrf_field() ?>

                    <!-- Información Personal -->
                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Información Personal</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>DNI <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="dni" 
                                               value="<?= esc($lead['dni']) ?>" 
                                               maxlength="8" readonly>
                                        <small class="text-muted">El DNI no se puede modificar</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Nombres <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="nombres" 
                                               value="<?= esc($lead['nombres']) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label>Apellidos <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="apellidos" 
                                               value="<?= esc($lead['apellidos']) ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Teléfono <span class="text-danger">*</span></label>
                                        <input type="tel" class="form-control" name="telefono" 
                                               value="<?= esc($lead['telefono']) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Teléfono Alternativo</label>
                                        <input type="tel" class="form-control" name="telefono_alternativo" 
                                               value="<?= esc($lead['telefono_alternativo']) ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Correo Electrónico</label>
                                        <input type="email" class="form-control" name="correo" 
                                               value="<?= esc($lead['correo']) ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Dirección</label>
                                        <textarea class="form-control" name="direccion" rows="2"><?= esc($lead['direccion']) ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información del Lead -->
                    <div class="card mb-3">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Información del Lead</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Etapa <span class="text-danger">*</span></label>
                                        <select class="form-control" name="idetapa" required>
                                            <option value="">Seleccionar...</option>
                                            <?php foreach ($etapas as $etapa): ?>
                                            <option value="<?= $etapa['idetapa'] ?>" 
                                                    <?= ($lead['idetapa'] == $etapa['idetapa']) ? 'selected' : '' ?>>
                                                <?= esc($etapa['nombre']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Origen <span class="text-danger">*</span></label>
                                        <select class="form-control" name="idorigen" required>
                                            <option value="">Seleccionar...</option>
                                            <?php foreach ($origenes as $origen): ?>
                                            <option value="<?= $origen['idorigen'] ?>"
                                                    <?= ($lead['idorigen'] == $origen['idorigen']) ? 'selected' : '' ?>>
                                                <?= esc($origen['nombre']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Modalidad</label>
                                        <select class="form-control" name="idmodalidad">
                                            <option value="">Seleccionar...</option>
                                            <?php foreach ($modalidades as $modalidad): ?>
                                            <option value="<?= $modalidad['idmodalidad'] ?>"
                                                    <?= ($lead['idmodalidad'] == $modalidad['idmodalidad']) ? 'selected' : '' ?>>
                                                <?= esc($modalidad['nombre']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Campaña</label>
                                        <select class="form-control" name="idcampania">
                                            <option value="">Sin campaña</option>
                                            <?php foreach ($campanias as $campania): ?>
                                            <option value="<?= $campania['idcampania'] ?>"
                                                    <?= ($lead['idcampania'] == $campania['idcampania']) ? 'selected' : '' ?>>
                                                <?= esc($campania['nombre']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Presupuesto Estimado</label>
                                        <input type="number" class="form-control" name="presupuesto_estimado" 
                                               value="<?= esc($lead['presupuesto_estimado']) ?>" 
                                               step="0.01" min="0">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Probabilidad de Cierre (%)</label>
                                        <input type="number" class="form-control" name="probabilidad_cierre" 
                                               value="<?= esc($lead['probabilidad_cierre']) ?>" 
                                               min="0" max="100">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Fecha Estimada Cierre</label>
                                        <input type="date" class="form-control" name="fecha_estimada_cierre" 
                                               value="<?= esc($lead['fecha_estimada_cierre']) ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Asignar a</label>
                                        <select class="form-control" name="idusuario">
                                            <?php foreach ($vendedores as $vendedor): ?>
                                            <option value="<?= $vendedor['idusuario'] ?>"
                                                    <?= ($lead['idusuario'] == $vendedor['idusuario']) ? 'selected' : '' ?>>
                                                <?= esc($vendedor['nombres'] . ' ' . $vendedor['apellidos']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Notas / Observaciones</label>
                                        <textarea class="form-control" name="notas" rows="3"><?= esc($lead['notas']) ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="text-right">
                        <a href="<?= base_url('leads/view/' . $lead['idlead']) ?>" class="btn btn-light">
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="icon-check"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
