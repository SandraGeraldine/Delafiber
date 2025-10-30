<?= $this->extend('layouts/base') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">
                        <i class="ti-pencil me-2"></i>Editar Cotización #<?= $cotizacion['idcotizacion'] ?>
                    </h4>
                </div>
                <div class="card-body">
                    <?php if (session()->getFlashdata('errors')): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach (session()->getFlashdata('errors') as $error): ?>
                                    <li><?= esc($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="<?= base_url('cotizaciones/update/' . $cotizacion['idcotizacion']) ?>" method="post" id="form-cotizacion">
                        <div class="row">
                            <!-- Información del Cliente (solo lectura) -->
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-header">
                                        <h6 class="card-title mb-0">Cliente</h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-1"><strong>Nombre:</strong> <?= esc($cotizacion['cliente_nombre'] ?? 'No disponible') ?></p>
                                        <p class="mb-1"><strong>Teléfono:</strong> <?= esc($cotizacion['cliente_telefono'] ?? 'No disponible') ?></p>
                                        <p class="mb-0"><strong>Lead ID:</strong> #<?= $cotizacion['idlead'] ?></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Servicio (solo lectura) -->
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-header">
                                        <h6 class="card-title mb-0">Servicio</h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-1"><strong>Servicio:</strong> <?= esc($cotizacion['servicio_nombre'] ?? 'No disponible') ?></p>
                                        <p class="mb-0"><strong>Velocidad:</strong> <?= esc($cotizacion['velocidad'] ?? 'No disponible') ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <!-- Precios editables -->
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="card-title mb-0">Precios</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <!-- Precio Cotizado -->
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="precio_cotizado" class="form-label">Precio Cotizado (S/) <span class="text-danger">*</span></label>
                                                    <input type="number" class="form-control" id="precio_cotizado" name="precio_cotizado" 
                                                           step="0.01" min="0" value="<?= old('precio_cotizado', $cotizacion['precio_cotizado']) ?>" required>
                                                </div>
                                            </div>

                                            <!-- Descuento -->
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="descuento_aplicado" class="form-label">Descuento (%)</label>
                                                    <input type="number" class="form-control" id="descuento_aplicado" name="descuento_aplicado" 
                                                           step="0.01" min="0" max="100" value="<?= old('descuento_aplicado', $cotizacion['descuento_aplicado']) ?>">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <!-- Precio de Instalación -->
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="precio_instalacion" class="form-label">Precio Instalación (S/)</label>
                                                    <input type="number" class="form-control" id="precio_instalacion" name="precio_instalacion" 
                                                           step="0.01" min="0" value="<?= old('precio_instalacion', $cotizacion['precio_instalacion']) ?>">
                                                </div>
                                            </div>

                                            <!-- Vigencia -->
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="vigencia_dias" class="form-label">Vigencia (días)</label>
                                                    <input type="number" class="form-control" id="vigencia_dias" name="vigencia_dias" 
                                                           min="1" value="<?= old('vigencia_dias', $cotizacion['vigencia_dias']) ?>">
                                                    <small class="form-text text-muted">
                                                        Días de vigencia desde la fecha de creación
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Preview de precios -->
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="card-title mb-0">Resumen</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="card bg-light">
                                            <div class="card-body py-2">
                                                <div class="d-flex justify-content-between">
                                                    <span>Servicio mensual:</span>
                                                    <span id="precio-servicio">S/ 0.00</span>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <span>Instalación:</span>
                                                    <span id="precio-instalacion-display">S/ 0.00</span>
                                                </div>
                                                <div class="d-flex justify-content-between text-success" id="descuento-display" style="display: none !important;">
                                                    <span>Descuento:</span>
                                                    <span id="descuento-monto">-S/ 0.00</span>
                                                </div>
                                                <hr class="my-2">
                                                <div class="d-flex justify-content-between fw-bold">
                                                    <span>Total primer mes:</span>
                                                    <span id="precio-total">S/ 0.00</span>
                                                </div>
                                                <div class="d-flex justify-content-between text-muted">
                                                    <span>Mensualidad:</span>
                                                    <span id="precio-mensual">S/ 0.00</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Estado actual -->
                                        <div class="mt-3">
                                            <div class="d-flex align-items-center">
                                                <span class="me-2">Estado actual:</span>
                                                <?php
                                                $badgeClass = [
                                                    'vigente' => 'bg-success',
                                                    'aceptada' => 'bg-primary',
                                                    'rechazada' => 'bg-danger',
                                                    'vencida' => 'bg-secondary'
                                                ];
                                                ?>
                                                <span class="badge <?= $badgeClass[$cotizacion['estado']] ?? 'bg-secondary' ?>">
                                                    <?= ucfirst($cotizacion['estado']) ?>
                                                </span>
                                            </div>
                                            <small class="text-muted">
                                                Creada: <?= date('d/m/Y H:i', strtotime($cotizacion['created_at'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Observaciones -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="observaciones" class="form-label">Observaciones</label>
                                    <textarea class="form-control" id="observaciones" name="observaciones" rows="3" 
                                              placeholder="Notas adicionales sobre la cotización..."><?= old('observaciones', $cotizacion['observaciones']) ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="d-flex justify-content-between">
                            <div>
                                <a href="<?= base_url('cotizaciones/show/' . $cotizacion['idcotizacion']) ?>" class="btn btn-secondary me-2">
                                    <i class="ti-arrow-left me-1"></i>Cancelar
                                </a>
                                <a href="<?= base_url('cotizaciones') ?>" class="btn btn-outline-secondary">
                                    <i class="ti-list me-1"></i>Ver Todas
                                </a>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti-check me-1"></i>Actualizar Cotización
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/cotizaciones/cotizaciones-edit.js') ?>"></script>
<?= $this->endSection() ?>
